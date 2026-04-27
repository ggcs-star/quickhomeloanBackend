<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Str;
  use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */

public function store(Request $request)
{
    DB::beginTransaction();

    try {
        Log::info('Registration attempt started', ['email' => $request->email, 'mobile' => $request->mobile_number]);

        $request->validate([
            'full_name'      => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile_number'  => ['required', 'digits_between:10,15'],
            'password'       => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        Log::info('Validation passed for registration', ['email' => $request->email]);

        $user = User::create([
            'full_name'     => $request->full_name,
            'email'         => $request->email,
            'mobile_number' => $request->mobile_number,
            'password'      => Hash::make($request->password),
            'status'        => 'active',
        ]);

        Log::info('User created successfully', ['user_id' => $user->_id]);

        $token = Str::random(60);

        PersonalAccessToken::create([
            'user_id' => (string) $user->_id,
            'name'    => 'api-token',
            'token'   => hash('sha256', $token),
        ]);

        Log::info('Personal access token created', ['user_id' => $user->_id]);

        DB::commit();
        Log::info('Registration transaction committed', ['user_id' => $user->_id]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user'    => $user,
            'token'   => $token,
        ], 201);

    } catch (ValidationException $e) {
        DB::rollBack();
        Log::warning('Validation failed for registration', ['errors' => $e->errors()]);
        return response()->json([
            'success' => false,
            'message' => 'Validation Failed',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Server error during registration', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Server Error',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


}
