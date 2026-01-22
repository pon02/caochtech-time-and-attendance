@php
    if (session('is_admin_login') !== true) {
        abort(403, '管理者ログインが必要です');
    }
@endphp
@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="attendance-body">
    <div class="section-title">
        <span class="section-title__bar"></span>
        <span class="section-title__text">勤怠詳細</span>
    </div>
    @php
        $pendingPayload = null;
        if ($attendance->status === 'pending') {
            $changeRequest = \App\Models\AttendanceChangeRequest::where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->with('payload')
                ->first();
            $pendingPayload = $changeRequest?->payload;
        }
        $isPending = $attendance->status === 'pending';
        $readonly = $isPending ? 'readonly tabindex="-1"' : '';
        $inputClass = 'detail-input text-black align-input' . ($isPending ? ' input-readonly' : '');
    @endphp
    <form method="POST" action="{{ route('stamp_correction_request.store') }}">
        @csrf
        <input type="hidden" name="is_admin" value="1">
        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
        <table class="detail-table">
            <tbody>
                <tr>
                    <th class="text-gray">名前</th>
                    <td class="text-black align-left">
                        <div class="date-flex-align">
                            <span class="date-year text-black">{{ $attendance->user->name }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th class="text-gray">日付</th>
                    <td class="text-black align-left">
                        <div class="date-flex-align">
                            <span class="date-year text-black">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                            <span class="date-md text-black">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th class="text-gray">出勤・退勤</th>
                    <td class="text-black align-left">
                        @php
                            $clockInValue = old('after_attendance.clock_in_at');
                            if ($clockInValue === null) {
                                $clockInValue = $isPending && $pendingPayload
                                    ? (isset($pendingPayload->after_attendance['clock_in_at'])
                                        ? \Carbon\Carbon::parse($pendingPayload->after_attendance['clock_in_at'])->format('H:i')
                                        : '')
                                    : ($attendance->clock_in_at
                                        ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i')
                                        : '');
                            }
                            $clockOutValue = old('after_attendance.clock_out_at');
                            if ($clockOutValue === null) {
                                $clockOutValue = $isPending && $pendingPayload
                                    ? (isset($pendingPayload->after_attendance['clock_out_at'])
                                        ? \Carbon\Carbon::parse($pendingPayload->after_attendance['clock_out_at'])->format('H:i')
                                        : '')
                                    : ($attendance->clock_out_at
                                        ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i')
                                        : '');
                            }
                        @endphp
                        <div class="input-pair">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="text" name="after_attendance[clock_in_at]"
                                    value="{{ $clockInValue }}"
                                    class="{{ $inputClass }}"
                                    pattern="^([01]\d|2[0-3]):([0-5]\d)$"
                                    oninput="this.value = this.value.replace(/[０-９：]/g, function(s){return String.fromCharCode(s.charCodeAt(0)-0xFEE0);}); this.value = this.value.replace(/[^0-9:]/g, '');"
                                    {!! $readonly !!}>
                                <span class="input-sep">〜</span>
                                <input type="text" name="after_attendance[clock_out_at]"
                                    value="{{ $clockOutValue }}"
                                    class="{{ $inputClass }}"
                                    pattern="^([01]\d|2[0-3]):([0-5]\d)$"
                                    oninput="this.value = this.value.replace(/[０-９：]/g, function(s){return String.fromCharCode(s.charCodeAt(0)-0xFEE0);}); this.value = this.value.replace(/[^0-9:]/g, '');"
                                    {!! $readonly !!}>
                            </div>
                        </div>
                        @error('after_attendance.clock_in_at')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                        @error('after_attendance.clock_out_at')
                            <span class="error-message">{{ $message }}</span><br>
                        @enderror
                    </td>
                </tr>
                @foreach($attendance->breaks as $i => $break)
                @php
                    $breakStartValue = old('after_breaks.'.$i.'.start_at');
                    if ($breakStartValue === null) {
                        $breakStartValue = $isPending && $pendingPayload
                            ? (isset($pendingPayload->after_breaks[$i]['start_at'])
                                ? \Carbon\Carbon::parse($pendingPayload->after_breaks[$i]['start_at'])->format('H:i')
                                : '')
                            : ($break->start_at
                                ? \Carbon\Carbon::parse($break->start_at)->format('H:i')
                                : '');
                    }
                    $breakEndValue = old('after_breaks.'.$i.'.end_at');
                    if ($breakEndValue === null) {
                        $breakEndValue = $isPending && $pendingPayload
                            ? (isset($pendingPayload->after_breaks[$i]['end_at'])
                                ? \Carbon\Carbon::parse($pendingPayload->after_breaks[$i]['end_at'])->format('H:i')
                                : '')
                            : ($break->end_at
                                ? \Carbon\Carbon::parse($break->end_at)->format('H:i')
                                : '');
                    }
                @endphp
                <tr>
                    <th class="text-gray">休憩{{ $i+1 }}</th>
                    <td class="text-black align-left">
                        <div class="input-pair">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="hidden" name="after_breaks[{{ $i }}][id]" value="{{ $break->id }}">
                                <input type="text" name="after_breaks[{{ $i }}][start_at]"
                                    value="{{ $breakStartValue }}"
                                    class="{{ $inputClass }}"
                                    pattern="^([01]\d|2[0-3]):([0-5]\d)$"
                                    oninput="this.value = this.value.replace(/[０-９：]/g, function(s){return String.fromCharCode(s.charCodeAt(0)-0xFEE0);}); this.value = this.value.replace(/[^0-9:]/g, '');"
                                    {!! $readonly !!}>
                                <span class="input-sep">〜</span>
                                <input type="text" name="after_breaks[{{ $i }}][end_at]"
                                    value="{{ $breakEndValue }}"
                                    class="{{ $inputClass }}"
                                    pattern="^([01]\d|2[0-3]):([0-5]\d)$"
                                    oninput="this.value = this.value.replace(/[０-９：]/g, function(s){return String.fromCharCode(s.charCodeAt(0)-0xFEE0);}); this.value = this.value.replace(/[^0-9:]/g, '');"
                                    {!! $readonly !!}>
                            </div>
                        </div>
                        @error('after_breaks.'.$i.'.start_at')
                            <span class="error-message">{{ $message }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                        @enderror
                        @error('after_breaks.'.$i.'.end_at')
                            <span class="error-message">{{ $message }}</span><br>
                        @enderror
                    </td>
                </tr>
                @endforeach
                @php $i = count($attendance->breaks); @endphp
                <tr>
                    <th class="text-gray">休憩{{ $i+1 }}</th>
                    <td class="text-black align-left">
                        <div class="input-pair">
                            <div style="display:flex;align-items:center;gap:8px;">
                                @php
                                    $emptyBreakStart = old('after_breaks.'.$i.'.start_at', '');
                                    $emptyBreakEnd = old('after_breaks.'.$i.'.end_at', '');
                                @endphp
                                <input type="text" name="after_breaks[{{ $i }}][start_at]"
                                    value="{{ $emptyBreakStart }}"
                                    class="{{ $inputClass }}"
                                    pattern="^([01]\d|2[0-3]):([0-5]\d)$"
                                    oninput="this.value = this.value.replace(/[０-９：]/g, function(s){return String.fromCharCode(s.charCodeAt(0)-0xFEE0);}); this.value = this.value.replace(/[^0-9:]/g, '');"
                                    {!! $readonly !!}>
                                <span class="input-sep">〜</span>
                                <input type="text" name="after_breaks[{{ $i }}][end_at]"
                                    value="{{ $emptyBreakEnd }}"
                                    class="{{ $inputClass }}"
                                    pattern="^([01]\d|2[0-3]):([0-5]\d)$"
                                    oninput="this.value = this.value.replace(/[０-９：]/g, function(s){return String.fromCharCode(s.charCodeAt(0)-0xFEE0);}); this.value = this.value.replace(/[^0-9:]/g, '');"
                                    {!! $readonly !!}>
                            </div>
                        </div>
                        @error('after_breaks.'.$i.'.start_at')
                            <span class="error-message">{{ $message }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                        @enderror
                        @error('after_breaks.'.$i.'.end_at')
                            <span class="error-message">{{ $message }}</span><br>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <th class="text-gray">備考</th>
                    <td class="text-black align-left">
                        <div>
                        @php
                            $noteValue = old('after_attendance.note');
                            if ($noteValue === null) {
                                $noteValue = $isPending && $pendingPayload ? ($pendingPayload->after_attendance['note'] ?? '') : $attendance->note;
                            }
                        @endphp
                        <textarea name="after_attendance[note]" class="detail-note-input text-black align-input @if($isPending) input-readonly @endif" rows="5" @if($isPending) readonly tabindex="-1" @endif>{{ $noteValue }}</textarea>
                        </div>
                        @error('after_attendance.note')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="detail-btn-area">
            @if($isPending)
                <span class="pending-msg">*承認待ちのため修正はできません。</span>
            @else
                <input type="hidden" name="reason" value="修正申請">
                <input type="hidden" name="before_attendance[clock_in_at]" value="{{ $attendance->clock_in_at }}">
                <input type="hidden" name="before_attendance[clock_out_at]" value="{{ $attendance->clock_out_at }}">
                <input type="hidden" name="before_attendance[note]" value="{{ $attendance->note }}">
                @foreach($attendance->breaks as $i => $break)
                    <input type="hidden" name="before_breaks[{{ $i }}][start_at]" value="{{ $break->start_at }}">
                    <input type="hidden" name="before_breaks[{{ $i }}][end_at]" value="{{ $break->end_at }}">
                @endforeach
                <button type="submit" class="detail-update-btn">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection
