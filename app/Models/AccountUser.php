<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountUser extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'account_user';

    protected $fillable = [
        'user_id',
        'account_id',
    ];
}
