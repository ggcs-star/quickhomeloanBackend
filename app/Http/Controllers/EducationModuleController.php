<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EducationModule;

class EducationModuleController extends Controller
{
    public function index()
    {
        $modules = EducationModule::with([
            'contents' => function ($q) {
                $q->where('status', 1)->get()
                    ->sortBy(function ($item) {
                        return $item->order == 0 ? 9999 : $item->order;
                    });
            }
        ])
            ->where('status', 1)
            ->get()
            ->sortBy(function ($item) {
                return $item->order == 0 ? 9999 : $item->order;
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Modules fetched successfully',
            'data' => $modules
        ]);
    }
    public function audioModules()
    {
        $modules = EducationModule::with([
            'contents' => function ($q) {
                $q->where('status', 1)
                    ->where('type', 'audio')
                    ->get()
                    ->sortBy(function ($item) {
                        return $item->order == 0 ? 9999 : $item->order;
                    })
                    ->values();
            }
        ])
            ->where('status', 1)
            ->get()
            ->sortBy(function ($item) {
                return $item->order == 0 ? 9999 : $item->order;
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Audio modules fetched successfully',
            'data' => $modules
        ]);
    }

    public function videoModules()
    {
        $modules = EducationModule::with([
            'contents' => function ($q) {
                $q->where('status', 1)
                    ->where('type', 'video')
                    ->get()
                    ->sortBy(function ($item) {
                        return $item->order == 0 ? 9999 : $item->order;
                    })
                    ->values();
            }
        ])
            ->where('status', 1)
            ->get()
            ->sortBy(function ($item) {
                return $item->order == 0 ? 9999 : $item->order;
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Video modules fetched successfully',
            'data' => $modules
        ]);
    }
}