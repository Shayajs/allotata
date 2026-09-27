<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Dates anniversaire d'un abonnement manuel, calées sur le jour de début.
 * Le jour est ramené au dernier jour du mois quand le mois est plus court,
 * puis le jour d'origine est rétabli dès que le mois le permet.
 */
class ManualSubscriptionCalendar
{
    public static function anniversary(Carbon $start, int $step, string $cycle = 'month'): Carbon
    {
        $anchor = $start->copy()->startOfDay();
        $day = $anchor->day;

        if ($cycle === 'year') {
            $year = $anchor->year + $step;
            $month = $anchor->month;
        } else {
            $monthIndex = ($anchor->month - 1) + $step;
            $year = $anchor->year + intdiv($monthIndex, 12);
            $month = ($monthIndex % 12) + 1;
        }

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        return Carbon::create($year, $month, min($day, $daysInMonth))->startOfDay();
    }

    /**
     * @return list<Carbon>
     */
    public static function dueDatesUntil(Carbon $start, Carbon $today, string $cycle = 'month'): array
    {
        $today = $today->copy()->startOfDay();
        $dates = [];
        $limit = $cycle === 'year' ? 80 : 600;

        for ($step = 1; $step <= $limit; $step++) {
            $due = self::anniversary($start, $step, $cycle);
            if ($due->gt($today)) {
                break;
            }
            $dates[] = $due;
        }

        return $dates;
    }

    public static function calendarDaysBetween(Carbon $from, Carbon $to): int
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $days = (int) $start->diff($end)->days;

        return $end->gte($start) ? $days : -$days;
    }
}
