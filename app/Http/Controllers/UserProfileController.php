<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;
class UserProfileController extends Controller
{

    public function show(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            Log::info('Get User Profile API called', [
                'user_id' => $user->_id ?? $user->id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'User profile fetched successfully',
                'data' => [
                    'id' => $user->_id ?? $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'mobile_number' => $user->mobile_number,
                    'city' => $user->city,
                    'employment_type' => $user->employment_type,
                    'annual_income' => $user->annual_income,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);

        } catch (Exception $e) {

            Log::error('Get User Profile API error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }


    public function update(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $rules = [
                'full_name' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:50',
                'employment_type' => 'nullable|string|in:student,salaried,self_employed,business,unemployed',
                'annual_income' => 'nullable|numeric|min:0',
            ];

            if ($request->filled('email') && $request->email !== $user->email) {
                $rules['email'] = 'email|unique:users,email';
            }

            if ($request->filled('mobile_number') && $request->mobile_number !== $user->mobile_number) {
                $rules['mobile_number'] = 'regex:/^[6-9][0-9]{9}$/|unique:users,mobile_number';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $input = $request->only([
                'full_name',
                'email',
                'mobile_number',
                'city',
                'employment_type',
                'annual_income',
            ]);

            $dataToUpdate = [];

            foreach ($input as $key => $value) {
                if (!is_null($value) && $user->{$key} != $value) {
                    $dataToUpdate[$key] = $value;
                }
            }

            if (empty($dataToUpdate)) {
                return response()->json([
                    'status' => true,
                    'message' => 'No changes detected',
                ]);
            }

            $user->update($dataToUpdate);

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
                'data' => $dataToUpdate,
            ]);

        } catch (Exception $e) {

            Log::error('Edit Profile API error', [
                'user_id' => $user->_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }


}
