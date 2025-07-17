<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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
            } else {
                throw new \Exception('Account ID is required/missing');
            }

            if (request('date') && !empty(request('date'))) {
                $date = \Carbon\Carbon::createFromFormat('Y-m', request('date'));
                $transactions->whereYear('date', $date->format('Y'))->whereMonth('date', $date->format('m'));
            }

            if (request('type') && !empty(request('type'))) {
                $transactions = $transactions->where('type', request('type'));
            }

            if (request('transactions_category_id') && !empty(request('transactions_category_id'))) {
                $transactions = $transactions->where('transactions_category_id', request('transactions_category_id'));
            }

            $transactions = $transactions->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $totalIncome = 0;
            $totalExpense = 0;
            $dailyExpense = [];

            // Get the start of the current week (Monday)
            $startOfWeek = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY);

            // Get the end of the current week (Sunday)
            $endOfWeek = \Carbon\Carbon::now()->endOfWeek(\Carbon\Carbon::SUNDAY);

            $dates = [];

            for ($i = 0; $i < 7; $i++) {
                $date = $startOfWeek->copy()->addDays($i)->format('Y-m-d');
                $dates[$date] = 0;
            }

            foreach ($transactions as $transaction) {
                if ($transaction->type === 'income') {
                    $totalIncome += $transaction->amount;
                } else if ($transaction->type === 'expense') {
                    $totalExpense += $transaction->amount;

                    $date = $transaction->date->format('Y-m-d');
                    // Only track expenses that occurred within the current week (Monday-Sunday)
                    if ($transaction->date >= $startOfWeek && $transaction->date <= $endOfWeek) {
                        $dates[$date] += $transaction->amount;
                    }
                }
            }

            $dailyExpense = $dates;
            $weeklyExpense = array_sum($dailyExpense);

            return response()->json([
                'success' => true,
                'message' => 'List of all transactions',
                'total_amount' => [
                    'income' => $totalIncome,
                    'expense' => $totalExpense,
                    'total' => $totalIncome - $totalExpense,
                    'daily_expense' => $dailyExpense,
                    'weekly_expense' => $weeklyExpense
                ],
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
            $validator = Validator::make($request->all(), [
                'date' => 'nullable|date',
                'description' => 'nullable|string|max:255',
                'amount' => 'required|numeric|gt:0',
                'type' => 'required|in:income,expense',
                'transactions_category_id' => 'required|exists:transactions_categories,id',
                'account_id' => 'required|exists:accounts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $validatedData = $validator->validated();
            $validatedData['date'] = empty($validatedData['date']) ? now()->format('Y-m-d') : $validatedData['date'];

            $transaction = null;
            $accountTransaction = null;
            $account = null;

            DB::transaction(function () use ($validatedData, &$transaction, &$accountTransaction, &$account) {
                // Pastikan akun milik user login
                $account = Auth::user()->accounts()->lockForUpdate()->find($validatedData['account_id']);

                if (!$account) {
                    throw new \Exception("The selected account does not belong to the logged-in user.");
                }

                // Simpan transaksi
                $transaction = Transaction::create([
                    'date' => $validatedData['date'],
                    'description' => $validatedData['description'],
                    'amount' => $validatedData['amount'],
                    'type' => $validatedData['type'],
                    'transactions_category_id' => $validatedData['transactions_category_id'],
                ]);

                // Hitung saldo akhir
                $lastBalance = $account->balance;
                $endingBalance = $validatedData['type'] === 'income'
                    ? $lastBalance + $validatedData['amount']
                    : $lastBalance - $validatedData['amount'];

                // Simpan relasi akun-transaksi
                $accountTransaction = AccountTransaction::create([
                    'date' => $validatedData['date'],
                    'amount' => $validatedData['amount'],
                    'ending_balance' => $endingBalance,
                    'account_id' => $validatedData['account_id'],
                    'transaction_id' => $transaction->id,
                ]);

                // Update saldo akun
                $account->balance = $endingBalance;
                $account->save();
            });

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
                    'ending_balance' => $accountTransaction->ending_balance,
                ]
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the transaction',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(String $transaction_id): JsonResponse
    {
        try {
            $transaction = null;
            $accountTransactions = null;

            DB::transaction(function () use ($transaction_id, &$transaction, &$accountTransactions) {
                $transaction = Transaction::findOrFail($transaction_id);
                $accountTransactions = AccountTransaction::where('transaction_id', $transaction->id)->get();

                if ($accountTransactions->isEmpty()) {
                    throw new \Exception('No account transactions found for this transaction');
                }

                foreach ($accountTransactions as $accountTransaction) {
                    $account = Auth::user()->accounts()->lockForUpdate()->find($accountTransaction->account_id);

                    if (!$account) {
                        throw new \Exception('Unauthorized access to account.');
                    }

                    // Update saldo akun
                    if ($transaction->type === 'income') {
                        $account->balance -= $accountTransaction->amount;
                    } elseif ($transaction->type === 'expense') {
                        $account->balance += $accountTransaction->amount;
                    }

                    $account->save();
                }

                $transaction->delete();
            });

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
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the transaction',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function generateEncryptedShareUrl(Request $request)
    {
        try {
            $user_id = $request->user_id;
            $account_id = $request->account_id;
            $date = $request->date;
            $data = json_encode(['user_id' => $user_id, 'account_id' => $account_id, 'date' => $date]);
            $encrypted = Crypt::encrypt($data);

            return response()->json([
                'success' => true,
                'message' => 'Encrypted URL generated successfully',
                'data' => [
                    'encryptedUrl' => $encrypted,
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function streamReportEncrypted(string $encryptedData): JsonResponse
    {
        try {
            $decrypted = Crypt::decrypt($encryptedData);
            $data = json_decode($decrypted, true);
            $user_id = $data['user_id'];
            $account_id = $data['account_id'];
            $date = $data['date'] ?? null;

            $user = User::findOrFail($user_id);
            $account = Account::findOrFail($account_id);
            $transactions = Transaction::with('category')
                ->whereHas('accountTransactions', function ($query) use ($account_id) {
                    $query->where('account_id', $account_id);
                });

            if ($date) {
                $date = \Carbon\Carbon::createFromFormat('Y-m', $date);
                $transactions->whereYear('date', $date->format('Y'))->whereMonth('date', $date->format('m'));
            } else {
                $transactions->whereYear('date', now()->format('Y'))->whereMonth('date', now()->format('m'));
            }

            $transactions = $transactions->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $totalIncome = 0;
            $totalExpense = 0;
            foreach ($transactions as $transaction) {
                if ($transaction->type === 'income') {
                    $totalIncome += $transaction->amount;
                } else if ($transaction->type === 'expense') {
                    $totalExpense += $transaction->amount;
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'Stream report',
                'data' => [
                    'date' => $date,
                    'user' => $user,
                    'account' => $account,
                    'total_amount' => [
                        'income' => $totalIncome,
                        'expense' => $totalExpense,
                        'total' => $totalIncome - $totalExpense,
                    ],
                    'transactions' => $transactions
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function streamReport(string $user_id, string $account_id, string $date): JsonResponse
    {
        try {
            $user = User::findOrFail($user_id);
            $account = Account::findOrFail($account_id);
            $transactions = Transaction::with('category')
                ->whereHas('accountTransactions', function ($query) use ($account_id) {
                    $query->where('account_id', $account_id);
                });

            if ($date) {
                $date = \Carbon\Carbon::createFromFormat('Y-m', $date);
                $transactions->whereYear('date', $date->format('Y'))->whereMonth('date', $date->format('m'));
            } else {
                $transactions->whereYear('date', now()->format('Y'))->whereMonth('date', now()->format('m'));
            }

            $transactions = $transactions->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $totalIncome = 0;
            $totalExpense = 0;
            foreach ($transactions as $transaction) {
                if ($transaction->type === 'income') {
                    $totalIncome += $transaction->amount;
                } else if ($transaction->type === 'expense') {
                    $totalExpense += $transaction->amount;
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'Stream report',
                'data' => [
                    'user' => $user,
                    'account' => $account,
                    'total_amount' => [
                        'income' => $totalIncome,
                        'expense' => $totalExpense,
                        'total' => $totalIncome - $totalExpense,
                    ],
                    'transactions' => $transactions
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
