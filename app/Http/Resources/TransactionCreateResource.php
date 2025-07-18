<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionCreateResource extends JsonResource
{
    protected $accountTransaction;

    public function __construct($resource, $accountTransaction = null)
    {
        parent::__construct($resource);
        $this->accountTransaction = $accountTransaction;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'category' => TransactionsCategoryResource::make($this->category),
            'account' => AccountResource::make($this->accountTransaction->account),
            'ending_balance' => $this->accountTransaction->ending_balance,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
