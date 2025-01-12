<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            // Ambil akun milik user yang sedang login
            $accounts = User::find(Auth::id())->accounts;

            return response()->json([
                'success' => true,
                'message' => 'List of user accounts',
                'data' => $accounts,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500); // Gunakan kode 500 untuk server error
        }
    }

    public function show(String $account_id): JsonResponse
    {
        try {
            $account = Account::findOrFail($account_id);
            return response()->json([
                'success' => true,
                'message' => 'Account details',
                'data' => $account,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'balance' => 'numeric',
            'description' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'error' => $validator->errors(),
            ], 400);
        }

        try {
            $request->merge(['balance' => $request->balance ?? 0]);
            $account = Account::create($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Account created successfully',
                'data' => $account,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, String $account_id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'balance' => 'numeric',
            'description' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'error' => $validator->errors(),
            ], 400);
        }

        try {
            $account = Account::findOrFail($account_id);
            $account->update($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Account updated successfully',
                'data' => $account,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(String $account_id): JsonResponse
    {
        try {
            $account = Account::findOrFail($account_id);
            $account->delete();
            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully',
                'data' => $account,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
