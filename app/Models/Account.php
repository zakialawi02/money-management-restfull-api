<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'accounts';
    protected $fillable = [
        'name',
        'balance',
        'description',
    ];

    protected $casts = [
        'balance' => 'float',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'account_user', 'account_id', 'user_id')->withTimestamps();
    }
}
