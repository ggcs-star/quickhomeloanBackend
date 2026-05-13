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
                'subscription' => $subscription->toArray()
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
        Log::info('Webhook Hit RAW', ['payload' => $request->getContent()]);

        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        $expected = hash_hmac(
            'sha256',
            $payload,
            env('RAZORPAY_WEBHOOK_SECRET')
        );

      
        if (!hash_equals($expected, $signature)) {
            Log::error('Invalid Webhook Signature', [
                'expected' => $expected,
                'received' => $signature
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

      
        $data = json_decode($payload, true);
        $event = $data['event'] ?? null;

        Log::info('Webhook Event', ['event' => $event]);

        if ($event == 'subscription.activated') {

            $subId = $data['payload']['subscription']['entity']['id'] ?? null;

            $payment = UserPayment::where('subscription_id', $subId)->first();

            if ($payment) {
                $payment->update([
                    'status' => 'success',
                    'start_date' => now(),
                    'end_date' => now()->addYear()
                ]);
            }
        }

        if ($event == 'subscription.cancelled') {

            $subId = $data['payload']['subscription']['entity']['id'] ?? null;

            UserPayment::where('subscription_id', $subId)
                ->update(['status' => 'cancelled']);
        }

        return response()->json(['status' => 'ok']);

    } catch (\Exception $e) {

        Log::error('Webhook Error', [
            'message' => $e->getMessage()
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

        $subscription = UserPayment::where('user_id', $userId)
            ->where('status', 'success')
            ->where('end_date', '>=', now())
            ->latest()
            ->first();

        if ($subscription) {

            $daysRemaining = now()->diffInDays($subscription->end_date, false);

            return response()->json([
                'access' => true,

                'start_date' => $subscription->start_date,
                'end_date' => $subscription->end_date,
                'days_remaining' => $daysRemaining,

                'auto_renewal' => 'Enabled',
                'amount' => 999
            ]);
        }

        return response()->json([
            'access' => false,

            'start_date' => null,
            'end_date' => null,
            'days_remaining' => 0,

            'auto_renewal' => 'Enabled',
            'amount' => 999
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
// PAYMENT HISTORY
public function paymentHistory()
{
    try {

        $payments = UserPayment::where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Payment history fetched successfully',
            'data' => $payments
        ]);

    } catch (\Exception $e) {

        Log::error('Payment History Error', [
            'message' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Unable to fetch payment history'
        ], 500);
    }
}
}