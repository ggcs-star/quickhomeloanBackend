<?php

namespace App\Http\Controllers;

use App\Models\Lender;
use Illuminate\Http\Request;

class LenderController extends Controller
{
   public function index(Request $request)
{
    try {
        $query = Lender::query();

        if ($request->has('type') && $request->type !== 'All') {
            $query->where('type', $request->type);
        }

        $lenders = $query->orderBy('name')->get()->map(function ($lender) {

            $product = $lender->products[0] ?? [];

            return [
                'id'     => (string) $lender->_id,
                'name'   => $lender->name,
                'type'   => $lender->type,
                'logo'   => $lender->logo_url,  
                'rate'   => $product['rate'] ?? null,
                'emi'    => $product['emi'] ?? null,
                'loan'   => $product['loan'] ?? null,
                'tenure' => $product['tenure'] ?? null,
                'slug'   => str_replace(' ', '-', strtolower($lender->name)),
            ];
        });

        return response()->json([
            'status' => true,
            'count'  => $lenders->count(),
            'data'   => $lenders,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong: ' . $e->getMessage(),
            'data'    => [],
        ], 500);
    }
}

}
