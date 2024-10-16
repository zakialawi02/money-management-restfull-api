<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\TransactionCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TransactionsCategoryResource;
use Illuminate\Http\JsonResponse;

class TransactionCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = TransactionCategory::all();

        try {
            return response()->json([
                'success' => true,
                'message' => 'List of all transaction categories',
                'data' => TransactionsCategoryResource::collection($categories),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $category = TransactionCategory::create($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Transaction category created successfully',
                'data' => TransactionsCategoryResource::make($category),
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(String $category_id): JsonResponse
    {
        try {
            $category = TransactionCategory::findOrFail($category_id);
            $category->delete();
            return response()->json([
                'success' => true,
                'message' => 'Transaction category deleted successfully',
                'data' => TransactionsCategoryResource::make($category),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction category not found',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
