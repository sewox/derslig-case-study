<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SuspiciousActivitySeverity;
use App\Enums\SuspiciousActivityStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspiciousActivity extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    // Ensure table name is mapped if convention is different (suspicious_activities is default for SuspiciousActivity model, so it's fine)

    protected $casts = [
        'details' => 'array',
        'severity' => SuspiciousActivitySeverity::class,
        'status' => SuspiciousActivityStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
