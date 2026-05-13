<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Http;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;
use App\Models\NotificationHistory;
use Exception;
use App\Models\UserNotification;
use App\Models\User;

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

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized user',
                ], 401);
            }

            FcmToken::updateOrCreate(
                [
                    'token' => $request->fcm_token,
                ],
                [
                    'user_id' => $user->id,
                    'device_type' => $request->device ?? 'web',
                    'last_used_at' => now(),
                ]
            );

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

            Log::error('FCM Token Save Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
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
        'title' => 'required|string|max:255',
        'body' => 'required|string|max:1000',
        'image_url' => 'nullable|string',
        'send_to' => 'nullable|string',
        'user_ids' => 'nullable|array',
        'user_names' => 'nullable|array',
    ]);

    $projectId = env('FIREBASE_PROJECT_ID');

    if (!$projectId) {
        return response()->json([
            'status' => false,
            'message' => 'FIREBASE_PROJECT_ID missing in .env'
        ], 500);
    }

    // Get tokens
    if ($request->send_to === 'specific' && !empty($request->user_ids)) {

        $tokens = FcmToken::whereIn('user_id', $request->user_ids)
            ->whereNotNull('token')
            ->pluck('token')
            ->unique()
            ->values()
            ->toArray();

    } else {

        $tokens = FcmToken::whereNotNull('token')
            ->pluck('token')
            ->unique()
            ->values()
            ->toArray();
    }

    if (empty($tokens)) {
        return response()->json([
            'status' => false,
            'message' => 'No FCM tokens found.'
        ], 404);
    }

    // Get Firebase Access Token
    try {

        $accessToken = $this->getFcmAccessToken();

    } catch (\Exception $e) {

        Log::error('FCM Access Token Error', [
            'message' => $e->getMessage()
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Unable to generate Firebase access token',
            'error' => $e->getMessage()
        ], 500);
    }

    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    $successCount = 0;
    $failCount = 0;
    $invalidTokens = [];
    $failedResponses = [];

    foreach ($tokens as $token) {

        try {

            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $request->title,
                        'body'  => $request->body,
                    ],
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                    ],
                ],
            ];

            // Add image if exists
            if (!empty($request->image_url)) {
                $payload['message']['notification']['image'] = $request->image_url;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post($url, $payload);

            $body = $response->json();

            Log::info('FCM Notification Response', [
                'token' => $token,
                'status_code' => $response->status(),
                'response' => $body,
            ]);

            if ($response->successful()) {

                $successCount++;

                FcmToken::where('token', $token)->update([
                    'last_used_at' => now(),
                ]);

            } else {

                $failCount++;

                $status = $body['error']['status'] ?? null;
                $message = $body['error']['message'] ?? 'Unknown Firebase error';

                $failedResponses[] = [
                    'token' => $token,
                    'status' => $status,
                    'message' => $message,
                ];

                if (in_array($status, [
                    'NOT_FOUND',
                    'INVALID_ARGUMENT',
                    'UNREGISTERED'
                ])) {

                    FcmToken::where('token', $token)->delete();

                    $invalidTokens[] = $token;
                }
            }

        } catch (\Exception $e) {

            $failCount++;

            Log::error('FCM Send Error', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            $failedResponses[] = [
                'token' => $token,
                'status' => 'EXCEPTION',
                'message' => $e->getMessage(),
            ];
        }
    }

    // Save notification history
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

        Log::error('Notification History Save Error', [
            'error' => $e->getMessage()
        ]);
    }

    // SAVE USER NOTIFICATIONS
    try {

        if ($request->send_to === 'specific' && !empty($request->user_ids)) {

            foreach ($request->user_ids as $userId) {

                UserNotification::create([
                    'user_id' => $userId,
                    'title' => $request->title,
                    'body' => $request->body,
                    'image_url' => $request->image_url,
                    'is_read' => false,
                ]);
            }

        } else {

            $users = User::all();

            foreach ($users as $user) {

                UserNotification::create([
                    'user_id' => (string) $user->_id,
                    'title' => $request->title,
                    'body' => $request->body,
                    'image_url' => $request->image_url,
                    'is_read' => false,
                ]);
            }
        }

    } catch (\Exception $e) {

        Log::error('User Notification Save Error', [
            'error' => $e->getMessage()
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'Broadcast completed',
        'summary' => [
            'total_tokens' => count($tokens),
            'successfully_sent' => $successCount,
            'failed' => $failCount,
            'invalid_tokens_removed' => $invalidTokens,
            'failed_responses' => $failedResponses,
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
    public function userNotifications()
{
    $user = auth()->user();

    $notifications = UserNotification::where(
        'user_id',
        (string) $user->_id
    )
    ->latest()
    ->get();

    return response()->json([
        'status' => true,
        'data' => $notifications
    ]);
}

public function unreadCount()
{
    $user = auth()->user();

    $count = UserNotification::where(
        'user_id',
        (string) $user->_id
    )
    ->where('is_read', false)
    ->count();

    return response()->json([
        'status' => true,
        'count' => $count
    ]);
}

public function markRead($id)
{
    $notification = UserNotification::find($id);

    if ($notification) {
        $notification->is_read = true;
        $notification->save();
    }

    return response()->json([
        'status' => true
    ]);
}
}