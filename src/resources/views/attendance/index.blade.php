@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
<div class="attendance-body">
    <div class="section-title">
        <span class="section-title__bar"></span>
        <span class="section-title__text">勤怠一覧</span>
    </div>
    <div class="month-selector">
        <a href="?month={{ $prevMonth }}" class="month-btn prev-month text-gray">
            <img src="{{ asset('/img/arrow.png') }}" alt="前月" class="arrow-img">
            前月
        </a>
        <label class="calendar-label">
            <img src="{{ asset('/img/calendar.png') }}" alt="カレンダーアイコン" class="calendar-icon">
            <input type="month" class="month-input" value="{{ $month->format('Y-m') }}" onchange="location.href='?month='+this.value">
            <span class="month-display text-gray">{{ $month->format('Y/m') }}</span>
        </label>
        <a href="?month={{ $nextMonth }}" class="month-btn next-month text-gray">
            翌月
            <img src="{{ asset('/img/arrow.png') }}" alt="翌月" class="arrow-img right">
        </a>
    </div>
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
            @foreach($days as $attendance)
            <tr>
                <td class="text-gray">{{ \Carbon\Carbon::parse($attendance['date'])->format('m/d') }}({{ ['日','月','火','水','木','金','土'][\Carbon\Carbon::parse($attendance['date'])->dayOfWeek] }})</td>
                <td class="text-gray">{{ $attendance['attendance']?->clock_in_at ? \Carbon\Carbon::parse($attendance['attendance']->clock_in_at)->format('H:i') : '' }}</td>
                <td class="text-gray">{{ $attendance['attendance']?->clock_out_at ? \Carbon\Carbon::parse($attendance['attendance']->clock_out_at)->format('H:i') : '' }}</td>
                <td class="text-gray">{{ $attendance['break_minutes'] !== null ? sprintf('%02d:%02d', floor($attendance['break_minutes'] / 60), $attendance['break_minutes'] % 60) : '' }}</td>
                <td class="text-gray">{{ $attendance['work_minutes'] !== null ? sprintf('%02d:%02d', floor($attendance['work_minutes'] / 60), $attendance['work_minutes'] % 60) : '' }}</td>
                <td>
                    @if($attendance['attendance'])
                        <a href="{{ route('attendance.detail', $attendance['attendance']->id) }}" class="text-black">詳細</a>
                    @else
                        <span class="text-black">詳細</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection