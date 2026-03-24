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

        try {
            Log::info('Login attempt started', ['email' => $request->email]);

            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            Log::info('Validation passed', ['email' => $request->email]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::warning('Login failed - user not found', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Login failed - password mismatch', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            Auth::login($user);
            Log::info('User authenticated', ['user_id' => $user->_id]);

            PersonalAccessToken::where('user_id', (string) $user->_id)->delete();
            Log::info('Old personal access tokens deleted', ['user_id' => $user->_id]);

            $token = Str::random(60);

            PersonalAccessToken::create([
                'user_id' => (string) $user->_id,
                'name' => 'api-token',
                'token' => hash('sha256', $token),
            ]);

            Log::info('New personal access token created', ['user_id' => $user->_id]);

            DB::commit();

            Log::info('Login transaction committed successfully', ['user_id' => $user->_id]);

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully',
                'user' => $user,
                'token' => $token,
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Server error during login', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage()
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
