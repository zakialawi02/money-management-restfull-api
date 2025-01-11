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
                'name' => 'Bensin',
                'description' => 'Bensin',
            ],
            [
                'name' => 'Servis',
                'description' => 'Servis',
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
                'name' => 'Pajak',
                'description' => 'Pajak',
            ],
            [
                'name' => 'Amal & Donasi',
                'description' => 'Amal & Donasi',
            ],
            [
                'name' => 'Pembayaran',
                'description' => 'Pembayaran',
            ],
            [
                'name' => 'Tagihan',
                'description' => 'Tagihan',
            ],
            [
                'name' => 'Internet',
                'description' => 'Internet',
            ],
            [
                'name' => 'Pendidikan',
                'description' => 'Pendidikan',
            ],
            [
                'name' => 'Hiburan',
                'description' => 'Hiburan',
            ],
            [
                'name' => 'Belanja',
                'description' => 'Belanja',
            ],
            [
                'name' => 'Kos',
                'description' => 'Kos',
            ],
            [
                'name' => 'Laundry',
                'description' => 'Laundry',
            ],
            [
                'name' => 'Sewa',
                'description' => 'Sewa',
            ],
            [
                'name' => 'Pinjaman',
                'description' => 'Pinjaman',
            ],
            [
                'name' => 'Kesehatan',
                'description' => 'Kesehatan',
            ],
            [
                'name' => 'Top Up',
                'description' => 'Top Up',
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
