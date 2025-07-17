<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;

class RecalculateEndingBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recalculate:ending-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate ending_balance for all account_transactions based on chronological order';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting recalculation of ending balances...");

        $accounts = Account::all();

        foreach ($accounts as $account) {
            DB::transaction(function () use ($account) {
                $balance = 0;

                $transactions = AccountTransaction::where('account_id', $account->id)
                    ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
                    ->orderBy('account_transactions.date')
                    ->orderBy('account_transactions.created_at')
                    ->select('account_transactions.*', 'transactions.type')
                    ->get();

                foreach ($transactions as $txn) {
                    $amount = $txn->amount;

                    if ($txn->type === 'income') {
                        $balance += $amount;
                    } elseif ($txn->type === 'expense') {
                        $balance -= $amount;
                    }

                    $txn->ending_balance = $balance;
                    $txn->save();
                }

                // Update latest account balance cache
                $account->balance = $balance;
                $account->save();

                $this->info("✔ Updated: {$account->name} (balance: {$balance})");
            });
        }

        $this->info("✅ Done recalculating ending balances.");
        return Command::SUCCESS;
    }
}
