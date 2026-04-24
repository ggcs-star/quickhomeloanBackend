<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();


        $requestId = (string) Str::uuid();

        try {


            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);


            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                Log::warning('Login failed: user not found', [
                    'request_id' => $requestId,
                    'email' => $validated['email']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }


            if ($user->status !== 'active') {

                $message = match ($user->status) {
                    'inactive' => 'Your account is inactive. Please contact support.',
                    'ban' => 'Your account has been banned.',
                    default => 'Your account is not allowed to login.'
                };

                Log::warning('Login blocked: invalid status', [
                    'request_id' => $requestId,
                    'user_id' => $user->_id,
                    'status' => $user->status
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 403);
            }


            if (!Hash::check($validated['password'], $user->password)) {
                Log::warning('Login failed: wrong password', [
                    'request_id' => $requestId,
                    'user_id' => $user->_id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }


            Auth::login($user);


            PersonalAccessToken::where('user_id', (string) $user->_id)->delete();

            $plainToken = Str::random(60);

            PersonalAccessToken::create([
                'user_id' => (string) $user->_id,
                'name' => 'api-token',
                'token' => hash('sha256', $plainToken),
            ]);

            DB::commit();


            Log::info('Login successful', [
                'request_id' => $requestId,
                'user_id' => $user->_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully',
                'user' => $user->makeHidden(['password']),
                'token' => $plainToken,
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();

            Log::notice('Login validation failed', [
                'request_id' => $requestId,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Login server error', [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server Error'
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $bearer = $request->bearerToken();

            if (!$bearer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token Missing'
                ], 401);
            }

            PersonalAccessToken::where(
                'token',
                hash('sha256', $bearer)
            )->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
