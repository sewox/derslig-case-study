<?php

namespace App\Listeners;

use App\Events\SuspiciousActivityDetected;
use App\Models\SuspiciousActivity;
use App\Enums\SuspiciousActivityStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogSuspiciousActivity
{
    // use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(SuspiciousActivityDetected $event): void
    {
        SuspiciousActivity::create([
            'user_id' => $event->user->id,
            'rule_type' => $event->ruleType,
            'severity' => $event->severity,
            'status' => SuspiciousActivityStatus::PENDING,
            'details' => ['message' => $event->details],
            'risk_score' => $this->calculateRiskScore($event->severity),
        ]);
    }

    protected function calculateRiskScore(string $severity): int
    {
        return match($severity) {
            'critical' => 100,
            'high' => 80,
            'medium' => 50,
            'low' => 20,
            default => 10,
        };
    }
}
