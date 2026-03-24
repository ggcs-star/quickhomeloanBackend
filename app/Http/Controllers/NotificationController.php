<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Http;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;
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
        $serviceAccountPath = base_path(env('FIREBASE_SERVICE_ACCOUNT'));

        if (!file_exists($serviceAccountPath)) {
            throw new \Exception('Firebase service account file not found at: ' . $serviceAccountPath);
        }

        $client = new GoogleClient();
        $client->setAuthConfig($serviceAccountPath);
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
        ]);

        $projectId = env('FIREBASE_PROJECT_ID');
        if (!$projectId) {
            return response()->json(['status' => false, 'message' => 'FIREBASE_PROJECT_ID missing in .env'], 500);
        }

        $tokens = FcmToken::pluck('token')->unique();
        if ($tokens->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No FCM tokens found.']);
        }

        try {
            $accessToken = $this->getFcmAccessToken();
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Access token error: ' . $e->getMessage()], 500);
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $successCount = 0;
        $failCount = 0;
        $invalidTokens = [];
        $errors = [];

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

            $response = Http::withToken($accessToken)
                ->post($url, $payload);

            $body = $response->json();

            if ($response->successful()) {
                $successCount++;
            } else {
                $failCount++;
                $errors[] = ['token' => $token, 'response' => $body];

                $status = $body['error']['status'] ?? null;

                if ($status && in_array($status, ['NOT_FOUND', 'INVALID_ARGUMENT', 'UNREGISTERED'])) {
                    FcmToken::where('token', $token)->delete();
                    $invalidTokens[] = $token;
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Broadcast completed (HTTP v1)',
            'summary' => [
                'total_tokens' => $tokens->count(),
                'successfully_sent' => $successCount,
                'failed' => $failCount,
                'invalid_tokens_removed' => $invalidTokens,
                'errors' => $errors,
            ],
        ]);
    }


}
