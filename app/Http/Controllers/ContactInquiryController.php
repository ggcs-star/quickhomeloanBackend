<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactInquiry;

class ContactInquiryController extends Controller
{
    // 📌 Store (Contact Form Submit)
    public function store(Request $request)
    {
        try {
            $request->validate([
                'full_name' => 'required|string|max:255',
                'email'     => 'required|email',
                'subject'   => 'required|string|max:255',
                'message'   => 'required|string',
            ]);

            $data = ContactInquiry::create($request->only([
                'full_name',
                'email',
                'subject',
                'message'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data'    => $data
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

    // 📌 List All (Admin use)
    public function index()
    {
        $data = ContactInquiry::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // 📌 Show Single
    public function show($id)
    {
        $data = ContactInquiry::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // 📌 Delete
    public function destroy($id)
    {
        $data = ContactInquiry::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }
}