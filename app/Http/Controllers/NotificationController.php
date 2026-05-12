<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Http;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;
use App\Models\NotificationHistory;
use Exception;

class NotificationController extends Controller
{
    public function saveToken(Request $request)
    {
        try {
            $request->validate([
                'fcm_token' => 'required|string',
                'device' => 'nullable|string',
            ]);

            $user = auth()->user();

            // Guest user (not logged in)
            if (!$user) {
                FcmToken::updateOrCreate(
                    ['token' => $request->fcm_token],
                    [
                        'user_id' => null,
                        'device_type' => $request->device ?? 'android',
                        'last_used_at' => now(),
                    ]
                );

                Log::info('Guest FCM Token saved: ' . $request->fcm_token);

                return response()->json([
                    'status' => true,
                    'message' => 'FCM token saved as guest',
                ], 200);
            }

            // Logged in user
            FcmToken::updateOrCreate(
                ['token' => $request->fcm_token],
                [
                    'user_id' => $user->id,
                    'device_type' => $request->device ?? 'android',
                    'last_used_at' => now(),
                ]
            );

            Log::info('FCM Token saved for user: ' . $user->id);

            return response()->json([
                'status' => true,
                'message' => 'FCM token saved successfully',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('FCM Token Save Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // 🔴 NEW FUNCTION - Login ke baad token attach karne ke liye
    public function attachTokenToUser(Request $request)
    {
        try {
            $request->validate([
                'fcm_token' => 'required|string',
            ]);

            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not logged in',
                ], 401);
            }

            $updated = FcmToken::where('token', $request->fcm_token)
                ->update([
                    'user_id' => $user->id,
                    'last_used_at' => now(),
                ]);

            if ($updated) {
                Log::info('Token attached to user: ' . $user->id);
                return response()->json([
                    'status' => true,
                    'message' => 'Token attached to user successfully',
                ], 200);
            } else {
                // Token doesn't exist, create new
                FcmToken::create([
                    'token' => $request->fcm_token,
                    'user_id' => $user->id,
                    'device_type' => 'android',
                    'last_used_at' => now(),
                ]);
                
                return response()->json([
                    'status' => true,
                    'message' => 'New token created and attached to user',
                ], 200);
            }

        } catch (\Exception $e) {
            Log::error('Attach token error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function getFcmAccessToken(): string
    {
        $serviceAccountPath = env('FIREBASE_SERVICE_ACCOUNT');
        
        if (!$serviceAccountPath) {
            throw new \Exception('FIREBASE_SERVICE_ACCOUNT path not set in .env');
        }
        
        $fullPath = base_path($serviceAccountPath);

        if (!file_exists($fullPath)) {
            throw new \Exception('Firebase service account file not found at: ' . $fullPath);
        }

        $client = new GoogleClient();
        $client->setAuthConfig($fullPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $tokenArray = $client->fetchAccessTokenWithAssertion();

        if (!isset($tokenArray['access_token'])) {
            throw new \Exception('Unable to fetch access token for FCM: ' . json_encode($tokenArray));
        }

        return $tokenArray['access_token'];
    }

    public function notifyAll(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'image_url' => 'nullable|string',
            'send_to' => 'nullable|string',
            'user_ids' => 'nullable|array',
            'user_names' => 'nullable|array',
        ]);

        $projectId = env('FIREBASE_PROJECT_ID');
        if (!$projectId) {
            return response()->json(['status' => false, 'message' => 'FIREBASE_PROJECT_ID missing in .env'], 500);
        }

        // Get tokens based on selection
        if ($request->send_to == 'specific' && !empty($request->user_ids)) {
            $tokens = FcmToken::whereIn('user_id', $request->user_ids)->pluck('token')->unique()->toArray();
        } else {
            $tokens = FcmToken::whereNotNull('token')->pluck('token')->unique()->toArray();
        }

        if (empty($tokens)) {
            return response()->json(['status' => false, 'message' => 'No FCM tokens found.'], 404);
        }

        try {
            $accessToken = $this->getFcmAccessToken();
        } catch (\Exception $e) {
            Log::error('FCM Access Token Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Access token error: ' . $e->getMessage()], 500);
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $successCount = 0;
        $failCount = 0;
        $invalidTokens = [];

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $request->title,
                        'body' => $request->body,
                    ],
                ],
            ];

            if ($request->image_url) {
                $payload['message']['notification']['image'] = $request->image_url;
            }

            $response = Http::withToken($accessToken)->post($url, $payload);
            $body = $response->json();

            if ($response->successful()) {
                $successCount++;
            } else {
                $failCount++;
                $status = $body['error']['status'] ?? null;
                if ($status && in_array($status, ['NOT_FOUND', 'INVALID_ARGUMENT', 'UNREGISTERED'])) {
                    FcmToken::where('token', $token)->delete();
                    $invalidTokens[] = $token;
                }
            }
        }

        // Save to history
        try {
            NotificationHistory::create([
                'title' => $request->title,
                'body' => $request->body,
                'image_url' => $request->image_url,
                'send_to' => $request->send_to ?? 'all',
                'user_ids' => $request->user_ids ?? [],
                'user_names' => $request->user_names ?? [],
                'total_receivers' => count($tokens),
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'sent_by' => auth()->id(),
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save notification history: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Broadcast completed',
            'summary' => [
                'total_tokens' => count($tokens),
                'successfully_sent' => $successCount,
                'failed' => $failCount,
                'invalid_tokens_removed' => $invalidTokens,
            ],
        ]);
    }

    public function getHistory(Request $request)
    {
        $histories = NotificationHistory::orderBy('sent_at', 'desc')->limit(100)->get();
        
        return response()->json([
            'status' => true,
            'data' => $histories
        ]);
    }
}