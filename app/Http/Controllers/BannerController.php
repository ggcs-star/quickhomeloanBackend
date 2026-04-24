<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::where('status', true)
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ], 200);
    }
}