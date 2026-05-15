<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;

class AppSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = AppSetting::first();

        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'App settings not found',
                'data' => null
            ], 404);
        }

        $imageBaseUrl = 'https://admin.quickhomeloan.in/public/storage/';

        return response()->json([
            'success' => true,
            'message' => 'App settings fetched successfully',
            'data' => [
                'app_name' => $settings->app_name,
                'app_logo' => $settings->app_logo ? $imageBaseUrl . $settings->app_logo : null,
                'splash_logo' => $settings->splash_logo ? $imageBaseUrl . $settings->splash_logo : null,
                'header_logo' => $settings->header_logo ? $imageBaseUrl . $settings->header_logo : null,
                'is_active' => $settings->is_active,
            ]
        ]);
    }
}