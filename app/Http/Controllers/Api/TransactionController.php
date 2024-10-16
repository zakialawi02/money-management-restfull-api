<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\AccountTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $transactions = Transaction::with('category');

            if (request('account_id') && !empty(request('account_id'))) {
                $transactions = $transactions->whereHas('accountTransactions', function ($query) {
                    $query->where('account_id', request('account_id'));
                });
            }

            $transactions = $transactions->orderBy('date', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'List of all transactions',
                'data' => $transactions,
                'length' => $transactions->count(),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'date' => 'nullable|date',
                'description' => 'nullable|string|max:255',
                'amount' => 'required|numeric|gt:0',
                'type' => 'required|in:income,expense',
                'transactions_category_id' => 'required|exists:transactions_categories,id',
                'account_id' => 'required|exists:accounts,id', // Id akun yang terlibat
            ]);

            // Jika validasi gagal
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $validatedData = $validator->validated();
            $validatedData['date'] = empty($validatedData['date']) ? now()->format('Y-m-d') : $validatedData['date'];

            // 1. Simpan transaksi baru di tabel 'transactions'
            $transaction = Transaction::create([
                'date' => $validatedData['date'],
                'description' => $validatedData['description'],
                'amount' => $validatedData['amount'],
                'type' => $validatedData['type'],
                'transactions_category_id' => $validatedData['transactions_category_id'],
            ]);

            // 2. Simpan catatan di tabel 'account_transactions' yang menghubungkan transaksi dengan akun
            $accountTransaction = AccountTransaction::create([
                'date' => $validatedData['date'],
                'amount' => $validatedData['amount'], // Jumlah transaksi yang mempengaruhi akun
                'account_id' => $validatedData['account_id'], // Akun terkait
                'transaction_id' => $transaction->id, // ID transaksi yang baru dibuat
            ]);

            // 3. Update saldo akun terkait
            $account = Account::findOrFail($validatedData['account_id']);
            if ($validatedData['type'] === 'income') {
                $account->balance += $validatedData['amount']; // Tambah saldo jika pemasukan
            } else {
                $account->balance -= $validatedData['amount']; // Kurangi saldo jika pengeluaran
            }
            $account->save();

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'transaction' => [
                    'id' => $transaction->id,
                    'date' => $transaction->date,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'category' => $transaction->category,
                    'account' => $accountTransaction->account,
                ]
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Error jika account atau transaksi kategori tidak ditemukan
            return response()->json([
                'success' => false,
                'message' => 'Account or transaction category not found',
            ], 404);
        } catch (\Exception $e) {
            // Error umum untuk masalah lainnya, misalnya gagal menyimpan transaksi
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy(String $transaction_id): JsonResponse
    {
        try {
            // 1. Temukan transaksi yang akan dihapus
            $transaction = Transaction::findOrFail($transaction_id);

            // 2. Temukan semua entri terkait di 'account_transactions'
            $accountTransactions = AccountTransaction::where('transaction_id', $transaction->id)->get();

            // Jika tidak ada entri di 'account_transactions'
            if ($accountTransactions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account transactions found for this transaction',
                ], 404);
            }

            // 3. Update saldo akun yang terlibat sebelum menghapus transaksi
            foreach ($accountTransactions as $accountTransaction) {
                $account = Account::findOrFail($accountTransaction->account_id);

                // Sesuaikan saldo akun berdasarkan jenis transaksi
                if ($transaction->type === 'income') {
                    // Kurangi saldo jika ini transaksi pemasukan (karena transaksi dihapus)
                    $account->balance -= $accountTransaction->amount;
                } else if ($transaction->type === 'expense') {
                    // Tambah saldo jika ini transaksi pengeluaran (karena transaksi dihapus)
                    $account->balance += $accountTransaction->amount;
                }

                // Simpan saldo yang sudah diperbarui
                $account->save();
            }

            // 4. Hapus transaksi dari tabel 'transactions'
            $transaction->delete();

            // 5. Response jika sukses
            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully',
                'data' => [
                    'id' => $transaction->id,
                    'date' => $transaction->date,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'category' => $transaction->category,
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Jika transaksi atau akun tidak ditemukan
            return response()->json([
                'success' => false,
                'message' => 'Transaction or Account not found',
            ], 404);
        } catch (\Exception $e) {
            // Jika terjadi kesalahan lain, misalnya masalah sistem atau database
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
