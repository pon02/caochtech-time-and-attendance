@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
<div class="attendance-body">
    <div class="section-title">
        <span class="section-title__bar"></span>
        <span class="section-title__text">{{ $user->name }}さんの勤怠</span>
    </div>
    <div class="month-selector">
        <a href="?month={{ $prevMonth }}" class="month-btn prev-month text-gray">
            <img src="{{ asset('/img/arrow.png') }}" alt="前月" class="arrow-img">
            前月
        </a>
        <label class="calendar-label">
            <img src="{{ asset('/img/calendar.png') }}" alt="カレンダーアイコン" class="calendar-icon">
            <input type="month" class="month-input" value="{{ $month->format('Y-m') }}" onchange="location.href='?month='+this.value">
            <span class="month-display text-gray">{{ $month->format('Y年m月') }}</span>
        </label>
        <a href="?month={{ $nextMonth }}" class="month-btn next-month text-gray">
            翌月
            <img src="{{ asset('/img/arrow.png') }}" alt="翌月" class="arrow-img right">
        </a>
    </div>
    <div class="attendance-table-wrap">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="text-gray">日付</th>
                    <th class="text-gray">出勤</th>
                    <th class="text-gray">退勤</th>
                    <th class="text-gray">休憩</th>
                    <th class="text-gray">合計</th>
                    <th class="text-gray">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($days as $day)
                <tr>
                    <td class="text-gray">{{ $day['date']->format('m/d') }}({{ ['日','月','火','水','木','金','土'][$day['date']->dayOfWeek] }})</td>
                    <td class="text-gray">{{ $day['attendance'] && $day['attendance']->clock_in_at ? \Carbon\Carbon::parse($day['attendance']->clock_in_at)->format('H:i') : '' }}</td>
                    <td class="text-gray">{{ $day['attendance'] && $day['attendance']->clock_out_at ? \Carbon\Carbon::parse($day['attendance']->clock_out_at)->format('H:i') : '' }}</td>
                    <td class="text-gray">{{ $day['break_minutes'] !== null ? sprintf('%02d:%02d', floor($day['break_minutes'] / 60), $day['break_minutes'] % 60) : '' }}</td>
                    <td class="text-gray">{{ $day['work_minutes'] !== null ? sprintf('%02d:%02d', floor($day['work_minutes'] / 60), $day['work_minutes'] % 60) : '' }}</td>
                    <td>
                        @if($day['attendance'])
                            <a href="{{ route('admin.attendance.show', $day['attendance']->id) }}" class="text-black">詳細</a>
                        @else
                            <span class="text-black">詳細</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="csv-btn-area">
            <form method="GET" action="{{ route('admin.attendance.staff.csv', ['user' => $user->id]) }}">
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <button type="submit" class="csv-export-btn">CSV出力</button>
            </form>
        </div>
    </div>
</div>
@endsection
