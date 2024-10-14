<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'transactions';
    protected $fillable = [
        'date',
        'amount',
        'description',
        'type',
        'transactions_category_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'transactions_category_id');
    }

    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class);
    }
}
