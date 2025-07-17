<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'account_transactions';
    protected $fillable = [
        'account_id',
        'transaction_id',
        'amount',
        'ending_balance',
        'date',
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
