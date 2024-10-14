<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransactionCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TransactionsCategories extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactions_categories = [
            [
                'name' => 'Uang Masuk',
                'description' => 'Uang masuk ke rekening',
            ],
            [
                'name' => 'Uang Keluar',
                'description' => 'Uang keluar dari rekening',
            ],
            [
                'name' => 'Makanan',
                'description' => 'Makanan',
            ],
            [
                'name' => 'Transportasi',
                'description' => 'Transportasi',
            ],
            [
                'name' => 'Listrik',
                'description' => 'Tagihan listrik',
            ],
            [
                'name' => 'Pulsa',
                'description' => 'Pulsa',
            ],
            [
                'name' => 'Pembayaran',
                'description' => 'Pembayaran',
            ],
            [
                'name' => 'Hiburan',
                'description' => 'Hiburan',
            ],
            [
                'name' => 'Lainnya',
                'description' => 'Pengeluaran lain-lain',
            ]
        ];

        foreach ($transactions_categories as $transactions_category) {
            TransactionCategory::create($transactions_category);
        }
    }
}
