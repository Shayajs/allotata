<?php

namespace App\Services\BillingLab;

use Carbon\Carbon;

class LaravelClock
{
    public function travelTo(Carbon $when): void
    {
        Carbon::setTestNow($when->copy());
    }

    public function reset(): void
    {
        Carbon::setTestNow();
    }

    public function now(): Carbon
    {
        return now()->copy();
    }
}
