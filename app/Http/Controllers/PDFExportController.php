<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFExportController extends Controller
{
    public function exportPDFReport(string $accountId)
    {

        try {
            $pocketAccount = Account::findOrFail($accountId);
            $transactions = Transaction::with('category');

            if (request('date') && !empty(request('date'))) {
                $date = \Carbon\Carbon::createFromFormat('Y-m', request('date'));
                $transactions->whereYear('date', $date->format('Y'))->whereMonth('date', $date->format('m'));
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

            $data = [
                'title' => 'Transaction Report on ' . $pocketAccount->name,
                'date' => request('date') ? \Carbon\Carbon::createFromFormat('Y-m', request('date'))->format('F Y') : 'All Time',
                'pocket' => $pocketAccount,
                'total_amount' => [
                    'balance' => $pocketAccount->balance,
                    'income' => $totalIncome,
                    'expense' => $totalExpense,
                    'total' => $totalIncome - $totalExpense,
                ],
                'data' => $transactions,
                'length' => $transactions->count(),
            ];
            // return $data;

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
