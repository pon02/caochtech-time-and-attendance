@extends('layouts.app')
@section('css')
<link rel="stylesheet" href="{{ asset('css/time_record.css') }}">
@endsection
@section('content')
<div class="attendance-body">
    <div class="attendance-status">{{ $status }}</div>
    <div class="attendance-date">{{ now()->format('Y年n月j日') }}({{ ['日','月','火','水','木','金','土'][now()->dayOfWeek] }})</div>
    <div class="attendance-time">{{ now()->format('H:i') }}</div>
    <div class="attendance-actions">
        @if($status === '勤務外')
            <form method="POST" action="{{ route('attendance.stamp') }}">
                @csrf
                <input type="hidden" name="type" value="clock_in">
                <button class="attendance-btn attendance-btn-main" type="submit">出勤</button>
            </form>
        @elseif($status === '出勤中')
            <form method="POST" action="{{ route('attendance.stamp') }}" style="display:inline-block;">
                @csrf
                <input type="hidden" name="type" value="clock_out">
                <button class="attendance-btn attendance-btn-main" type="submit">退勤</button>
            </form>
            <form method="POST" action="{{ route('attendance.stamp') }}" style="display:inline-block;">
                @csrf
                <input type="hidden" name="type" value="break_start">
                <button class="attendance-btn attendance-btn-sub" type="submit">休憩入</button>
            </form>
        @elseif($status === '休憩中')
            <form method="POST" action="{{ route('attendance.stamp') }}">
                @csrf
                <input type="hidden" name="type" value="break_end">
                <button class="attendance-btn attendance-btn-sub" type="submit">休憩戻</button>
            </form>
        @elseif($status === '退勤済')
            <div class="attendance-finish">お疲れ様でした。</div>
        @endif
    </div>
</div>
@endsection
