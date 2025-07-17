<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PDFExportController extends Controller
{
    public function exportPDFReport(string $accountId)
    {
        try {
            // Validasi akun milik user
            $account = Auth::user()->accounts()->findOrFail($accountId);

            $requestedDate = request('date');

            // Ambil transaksi milik akun tersebut
            $transactions = Transaction::with('category')
                ->whereHas('accountTransactions', function ($query) use ($accountId) {
                    $query->where('account_id', $accountId);
                });

            if ($requestedDate && !empty($requestedDate)) {
                $date = \Carbon\Carbon::createFromFormat('Y-m', $requestedDate);
                $transactions->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month);
            }

            $monthlyEndingBalance = null;

            if ($requestedDate) {
                $date = \Carbon\Carbon::createFromFormat('Y-m', $requestedDate);

                $lastTxn = \App\Models\AccountTransaction::where('account_id', $accountId)
                    ->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->orderBy('date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();

                $monthlyEndingBalance = $lastTxn?->ending_balance;
            }


            $transactions = $transactions->orderBy('date', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Hitung total pemasukan/pengeluaran
            $totalIncome = $transactions->where('type', 'income')->sum('amount');
            $totalExpense = $transactions->where('type', 'expense')->sum('amount');

            $data = [
                'title' => 'Transaction Report on ' . $account->name,
                'date' => $requestedDate
                    ? \Carbon\Carbon::createFromFormat('Y-m', $requestedDate)->format('F Y')
                    : 'All Time',
                'pocket' => $account,
                'total_amount' => [
                    'balance' => $account->balance,
                    'balance_end' => $monthlyEndingBalance ?? 'N/A',
                    'income' => $totalIncome,
                    'expense' => $totalExpense,
                    'total' => $totalIncome - $totalExpense,
                ],
                'data' => $transactions,
                'length' => $transactions->count(),
            ];

            // Generate PDF
            $pdf = Pdf::loadView('PDFView.reportTransaction', $data)->setPaper('a4', 'portrait');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'report.pdf', [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="report.pdf"',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
