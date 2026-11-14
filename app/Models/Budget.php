<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'org_id',
        'activity_name',
        'limit_amount',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'limit_amount' => 'decimal:2',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedAmount(): float
    {
        return (float) $this->transactions()
            ->where('type', 'expense')
            ->where('status', 'active')
            ->sum('amount');
    }

    public function remainingAmount(): float
    {
        return (float) $this->limit_amount - $this->usedAmount();
    }

    public function usagePercentage(): float
    {
        if ((float) $this->limit_amount <= 0) {
            return 0;
        }

        return round(($this->usedAmount() / (float) $this->limit_amount) * 100, 1);
    }
}