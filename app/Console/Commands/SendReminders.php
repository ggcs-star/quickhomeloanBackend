<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\UserNotification;
use App\Models\FcmToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Google\Client as GoogleClient;

class SendReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send event and task reminders to users';

    public function handle()
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        
        $reminders = Event::where('is_notified', false)
            ->whereNotNull('reminder_time')
            ->where('reminder_time', '<=', $now)
            ->get();
        
        foreach ($reminders as $event) {
            $this->sendReminder($event);
        }
        
        $this->info("Sent " . count($reminders) . " reminders");
    }
    
    private function sendReminder($event)
    {
        $title = $event->type === 'event' ? 'Event Reminder' : 'Task Reminder';
        
        if ($event->is_all_day) {
            $date = Carbon::parse($event->start_datetime)->format('d M Y');
            $body = $event->title . ' - All Day Event on ' . $date;
        } else {
            $body = $event->title . ' - ' . Carbon::parse($event->start_datetime)->format('d M Y h:i A');
        }
        
        UserNotification::create([
            'user_id' => $event->user_id,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'created_at' => now()
        ]);
        
        $this->sendFirebasePush($event->user_id, $title, $body);
        
        $event->is_notified = true;
        $event->save();
        
        if ($event->repeat_type !== 'none') {
            $this->scheduleNextReminder($event);
        }
    }
    
    private function sendFirebasePush($userId, $title, $body)
    {
        try {
            $tokens = FcmToken::where('user_id', $userId)
                ->whereNotNull('token')
                ->pluck('token')
                ->toArray();
            
            if (empty($tokens)) {
                \Log::info('No FCM token found for user: ' . $userId);
                return;
            }
            
            $serviceAccountPath = env('FIREBASE_SERVICE_ACCOUNT');
            if (!$serviceAccountPath) {
                \Log::error('FIREBASE_SERVICE_ACCOUNT not set in .env');
                return;
            }
            
            $fullPath = base_path($serviceAccountPath);
            if (!file_exists($fullPath)) {
                \Log::error('Firebase service account file not found: ' . $fullPath);
                return;
            }
            
            $client = new GoogleClient();
            $client->setAuthConfig($fullPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            
            $tokenArray = $client->fetchAccessTokenWithAssertion();
            
            if (!isset($tokenArray['access_token'])) {
                \Log::error('Failed to get access token from Firebase');
                return;
            }
            
            $accessToken = $tokenArray['access_token'];
            $projectId = env('FIREBASE_PROJECT_ID');
            
            if (!$projectId) {
                \Log::error('FIREBASE_PROJECT_ID not set in .env');
                return;
            }
            
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
            
            foreach ($tokens as $token) {
                $payload = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => [
                            'title' => $title,
                            'body' => $body,
                            'type' => 'calendar_reminder',
                        ],
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'channel_id' => 'calendar_reminders',
                                'priority' => 'high',
                                'sound' => 'default',
                            ],
                        ],
                        'apns' => [
                            'headers' => [
                                'apns-priority' => '10',
                            ],
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ],
                ];
                
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post($url, $payload);
                
                if ($response->successful()) {
                    \Log::info('FCM sent successfully for token: ' . substr($token, 0, 20) . '...');
                } else {
                    \Log::error('FCM failed: ' . $response->body());
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Firebase push error: ' . $e->getMessage());
        }
    }
    
    private function scheduleNextReminder($event)
    {
        $reminderTime = Carbon::parse($event->reminder_time);
        
        switch ($event->repeat_type) {
            case 'daily':
                $nextReminder = $reminderTime->addDay();
                break;
            case 'weekly':
                $nextReminder = $reminderTime->addWeek();
                break;
            case 'monthly':
                $nextReminder = $reminderTime->addMonth();
                break;
            case 'yearly':
                $nextReminder = $reminderTime->addYear();
                break;
            default:
                return;
        }
        
        if ($event->type === 'event' && !$event->is_all_day && $nextReminder > Carbon::parse($event->start_datetime)) {
            return;
        }
        
        if ($event->type === 'event' && $event->is_all_day && $nextReminder->startOfDay() > Carbon::parse($event->start_datetime)->startOfDay()) {
            return;
        }
        
        $event->reminder_time = $nextReminder;
        $event->is_notified = false;
        $event->save();
    }
}