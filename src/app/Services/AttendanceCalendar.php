<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceCalendar
{
    /**
     * 対象期間の稼働日（平日・祝日除外・年末年始休暇除外）を返す
     */
    public function workingDays(Carbon $start, Carbon $end): Collection
    {
        $companyVacationStart = Carbon::create(2025, 12, 28)->startOfDay();
        $companyVacationEnd   = Carbon::create(2026,  1,  4)->endOfDay();

        // 対象期間内の祝日（必要分だけ）
        $holidaySet = collect([
            '2025-11-03', // 文化の日
            '2025-11-24', // 勤労感謝の日 振替
            '2026-01-01', // 元日
            '2026-01-12', // 成人の日
        ])->map(fn($d) => Carbon::parse($d)->toDateString())->flip();

        $days = collect();
        $d = $start->copy()->startOfDay();

        while ($d->lte($end)) {
            $isWeekend  = $d->isSaturday() || $d->isSunday();
            $isHoliday  = $holidaySet->has($d->toDateString());
            $inVacation = $d->betweenIncluded($companyVacationStart, $companyVacationEnd);

            if (!$isWeekend && !$isHoliday && !$inVacation) {
                $days->push($d->copy());
            }

            $d->addDay();
        }

        return $days;
    }
}
