<?php

namespace Tests\Unit;

use App\Services\ManualSubscriptionCalendar;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ManualSubscriptionCalendarTest extends TestCase
{
    public function test_anniversaries_follow_the_start_day(): void
    {
        $start = Carbon::parse('2026-01-17');

        $this->assertSame('2026-02-17', ManualSubscriptionCalendar::anniversary($start, 1)->toDateString());
        $this->assertSame('2026-03-17', ManualSubscriptionCalendar::anniversary($start, 2)->toDateString());
        $this->assertSame('2026-04-17', ManualSubscriptionCalendar::anniversary($start, 3)->toDateString());
    }

    public function test_first_of_month_stays_on_the_first(): void
    {
        $start = Carbon::parse('2026-01-01');

        $this->assertSame('2026-02-01', ManualSubscriptionCalendar::anniversary($start, 1)->toDateString());
        $this->assertSame('2026-03-01', ManualSubscriptionCalendar::anniversary($start, 2)->toDateString());
    }

    public function test_day_31_clamps_then_restores_the_original_day(): void
    {
        $start = Carbon::parse('2026-01-31');

        $this->assertSame('2026-02-28', ManualSubscriptionCalendar::anniversary($start, 1)->toDateString());
        $this->assertSame('2026-03-31', ManualSubscriptionCalendar::anniversary($start, 2)->toDateString());
        $this->assertSame('2026-04-30', ManualSubscriptionCalendar::anniversary($start, 3)->toDateString());
        $this->assertSame('2026-05-31', ManualSubscriptionCalendar::anniversary($start, 4)->toDateString());
    }

    public function test_leap_day_is_kept_only_on_leap_years(): void
    {
        $start = Carbon::parse('2024-01-31');

        $this->assertSame('2024-02-29', ManualSubscriptionCalendar::anniversary($start, 1)->toDateString());

        $leapStart = Carbon::parse('2024-02-29');
        $this->assertSame('2025-02-28', ManualSubscriptionCalendar::anniversary($leapStart, 1, 'year')->toDateString());
        $this->assertSame('2028-02-29', ManualSubscriptionCalendar::anniversary($leapStart, 4, 'year')->toDateString());
    }

    public function test_due_dates_stop_at_today_and_skip_the_start_date(): void
    {
        $start = Carbon::parse('2026-01-12');
        $dates = ManualSubscriptionCalendar::dueDatesUntil($start, Carbon::parse('2026-04-20'));

        $this->assertSame([
            '2026-02-12',
            '2026-03-12',
            '2026-04-12',
        ], array_map(fn (Carbon $date) => $date->toDateString(), $dates));
    }

    public function test_calendar_days_ignore_the_clock_time(): void
    {
        $from = Carbon::parse('2026-09-12 23:00:00', 'Europe/Paris');
        $to = Carbon::parse('2026-09-15 00:30:00', 'Europe/Paris');

        $this->assertSame(3, ManualSubscriptionCalendar::calendarDaysBetween($from, $to));
    }
}
