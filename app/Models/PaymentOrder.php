<?php

namespace App\Models;

use App\Support\Pricing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrder extends Model
{
    protected $fillable = [
        'user_id', 'order_no', 'amount', 'currency', 'plan', 'status', 'trade_no', 'consented_at',
    ];

    protected $casts = [
        // amount 是「最小單位」(USD 用分、TWD/JPY 用元),不是元。
        // 要顯示給人看請走 Pricing::formatMinor($order->amount, $order->currency)。
        'amount' => 'integer',
        'consented_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** 這張訂單付款成功要延展幾天。看下單當時記下的方案,不看 config 當下的值。 */
    public function durationDays(): int
    {
        return Pricing::exists($this->plan)
            ? Pricing::days($this->plan)
            : Pricing::days(Pricing::defaultPlan());
    }

    public function formattedAmount(): string
    {
        return Pricing::formatMinor($this->amount, $this->currency ?: Pricing::defaultCurrency());
    }
}
