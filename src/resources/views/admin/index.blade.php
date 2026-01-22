@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
<div class="attendance-body">
    <div class="section-title">
        <span class="section-title__bar"></span>
        <span class="section-title__text">{{ $date->format('Y年m月d日') }}の勤怠</span>
    </div>
    <div class="month-selector">
        <a href="?date={{ $prevDate }}" class="month-btn prev-month text-gray">
            <img src="{{ asset('/img/arrow.png') }}" alt="前日" class="arrow-img">
            前日
        </a>
        <label class="calendar-label">
            <img src="{{ asset('/img/calendar.png') }}" alt="カレンダーアイコン" class="calendar-icon">
            <input type="date" class="month-input" value="{{ $date->format('Y-m-d') }}" onchange="location.href='?date='+this.value">
            <span class="month-display text-gray">{{ $date->format('Y/m/d') }}</span>
        </label>
        <a href="?date={{ $nextDate }}" class="month-btn next-month text-gray">
            翌日
            <img src="{{ asset('/img/arrow.png') }}" alt="翌日" class="arrow-img right">
        </a>
    </div>
    <table class="attendance-table">
        <thead>
            <tr>
                <th class="text-gray">名前</th>
                <th class="text-gray">出勤</th>
                <th class="text-gray">退勤</th>
                <th class="text-gray">休憩</th>
                <th class="text-gray">合計</th>
                <th class="text-gray">詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $attendance)
            <tr>
                <td class="text-gray">{{ $attendance['staff_name'] }}</td>
                <td class="text-gray">{{ $attendance['clock_in_at'] ? \Carbon\Carbon::parse($attendance['clock_in_at'])->format('H:i') : '' }}</td>
                <td class="text-gray">{{ $attendance['clock_out_at'] ? \Carbon\Carbon::parse($attendance['clock_out_at'])->format('H:i') : '' }}</td>
                <td class="text-gray">{{ $attendance['break_minutes'] !== null ? sprintf('%02d:%02d', floor($attendance['break_minutes'] / 60), $attendance['break_minutes'] % 60) : '' }}</td>
                <td class="text-gray">{{ $attendance['work_minutes'] !== null ? sprintf('%02d:%02d', floor($attendance['work_minutes'] / 60), $attendance['work_minutes'] % 60) : '' }}</td>
                <td>
                    <a href="{{ route('admin.attendance.show', $attendance['id']) }}" class="text-black">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection