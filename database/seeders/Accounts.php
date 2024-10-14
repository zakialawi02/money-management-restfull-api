<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class Accounts extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Tabungan',
                'description' => 'Tabungan hidup',
                'balance' => 0
            ],
        ];

        foreach ($accounts as $account) {
            Account::create($account);
        }
    }
}
