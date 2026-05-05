<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoanInquiry;

class LoanInquiryController extends Controller
{
    // 📌 Store (Create Inquiry)
    public function store(Request $request)
    {
        try {
            $request->validate([
                'full_name'     => 'required|string|max:255',
                'mobile_number' => 'required|digits:10',
                'email'         => 'nullable|email',
                'loan_purpose'  => 'required|string',
            ]);

            $inquiry = LoanInquiry::create($request->only([
                'full_name',
                'mobile_number',
                'email',
                'loan_purpose'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Inquiry submitted successfully',
                'data'    => $inquiry
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error'   => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    // 📌 List (All Inquiries)
    public function index()
    {
        $data = LoanInquiry::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // 📌 Show Single
    public function show($id)
    {
        $inquiry = LoanInquiry::find($id);

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $inquiry
        ]);
    }

    // 📌 Delete
    public function destroy($id)
    {
        $inquiry = LoanInquiry::find($id);

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found'
            ], 404);
        }

        $inquiry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inquiry deleted successfully'
        ]);
    }
}