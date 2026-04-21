<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Calculator;
use Illuminate\Http\Request;
class CalculatorController extends Controller
{
  public function index()
{
    $calculators = Calculator::where('is_active', true)
        ->get(['name', 'slug', 'category', 'description', 'access_type']);

    return response()->json([
        'status' => true,
        'data' => $calculators
    ]);
}
}