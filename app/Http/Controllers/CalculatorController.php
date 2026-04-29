<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Calculator;
use Illuminate\Http\Request;
use App\Models\CalculatorMedia;

class CalculatorController extends Controller
{
    public function index()
    {
        $calculators = Calculator::orderBy('name')
            ->get(['name', 'slug', 'category', 'description', 'access_type', 'is_active']);

        return response()->json([
            'status' => true,
            'data' => $calculators
        ]);
    }

    public function getMedia(Request $request, $slug)
    {
        $calculator = Calculator::where('slug', $slug)->first();

        if (!$calculator) {
            return response()->json([
                'status' => false,
                'message' => 'Calculator not found'
            ], 404);
        }

        $query = CalculatorMedia::where('calculator_id', (string) $calculator->_id)
            ->where('status', true);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $media = $query
            ->orderBy('order')
            ->get();

        return response()->json([
            'status' => true,
            'calculator' => $calculator->name,
            'data' => $media
        ]);
    }
}