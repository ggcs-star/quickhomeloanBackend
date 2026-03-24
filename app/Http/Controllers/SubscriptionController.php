<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPayment;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class SubscriptionController extends Controller
{
    
    public function createSubscription()
    {
        try {
            Log::info('Create Subscription Start', [
                'user_id' => auth()->id()
            ]);

            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

            UserPayment::where('user_id', auth()->id())
                ->where('status', 'pending')
                ->delete();

            $subscription = $api->subscription->create([
                'plan_id' => env('RAZORPAY_PLAN_ID'),
                'customer_notify' => 1,
                'total_count' => 12
            ]);

            Log::info('Razorpay Subscription Created', [
                'subscription' => $subscription
            ]);

            $payment = UserPayment::create([
                'user_id' => auth()->id(),
                'subscription_id' => $subscription['id'], 
                'status' => 'pending'
            ]);

            return response()->json([
                'subscription_id' => $subscription['id'],
                'key' => env('RAZORPAY_KEY')
            ]);

        } catch (\Exception $e) {

            Log::error('Create Subscription Error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'error' => 'Subscription creation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

   
    public function webhook(Request $request)
    {
        try {
            Log::info('Webhook Hit', $request->all());

            $signature = $request->header('X-Razorpay-Signature');
            $expected = hash_hmac(
                'sha256',
                $request->getContent(),
                env('RAZORPAY_WEBHOOK_SECRET')
            );

            if ($signature !== $expected) {
                Log::error('Invalid Webhook Signature');
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $event = $request['event'] ?? null;

            Log::info('Webhook Event', ['event' => $event]);

            if ($event == 'subscription.activated') {

                $subId = $request['payload']['subscription']['entity']['id'] ?? null;

                Log::info('Subscription Activated', ['sub_id' => $subId]);

                $payment = UserPayment::where('subscription_id', $subId)->first();

                if ($payment) {

                    $payment->update([
                        'status' => 'success',
                        'start_date' => now(),
                        'end_date' => now()->addMonth()
                    ]);

                    Log::info('Subscription Updated Successfully', [
                        'user_id' => $payment->user_id
                    ]);

                } else {
                    Log::error('Subscription Not Found in DB', [
                        'sub_id' => $subId
                    ]);
                }
            }

            if ($event == 'subscription.cancelled') {

                $subId = $request['payload']['subscription']['entity']['id'] ?? null;

                UserPayment::where('subscription_id', $subId)
                    ->update(['status' => 'cancelled']);

                Log::info('Subscription Cancelled', ['sub_id' => $subId]);
            }

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {

            Log::error('Webhook Error', [
                'message' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'error' => 'Webhook failed'
            ], 500);
        }
    }

    public function checkAccess()
    {
        try {
            $userId = auth()->id();

            $hasAccess = UserPayment::where('user_id', $userId)
                ->where('status', 'success')
                ->where('end_date', '>=', now()) 
                ->exists();

            return response()->json([
                'access' => $hasAccess
            ]);

        } catch (\Exception $e) {

            Log::error('Check Access Error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'error' => 'Unable to check access'
            ], 500);
        }
    }
}