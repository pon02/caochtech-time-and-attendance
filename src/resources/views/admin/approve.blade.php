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
        $payload = $changeRequest->payload;
    @endphp
    <form method="POST" action="{{ route('stamp_correction_request.approve.exec', ['attendance_change_request_id' => $changeRequest->id]) }}">
        @csrf
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
                        <div class="input-pair">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="text" name="after_attendance[clock_in_at]" value="{{ isset($payload->after_attendance['clock_in_at']) ? \Carbon\Carbon::parse($payload->after_attendance['clock_in_at'])->format('H:i') : '' }}" class="detail-input text-black align-input input-readonly" readonly tabindex="-1">
                                <span class="input-sep">〜</span>
                                <input type="text" name="after_attendance[clock_out_at]" value="{{ isset($payload->after_attendance['clock_out_at']) ? \Carbon\Carbon::parse($payload->after_attendance['clock_out_at'])->format('H:i') : '' }}" class="detail-input text-black align-input input-readonly" readonly tabindex="-1">
                            </div>
                        </div>
                    </td>
                </tr>
                @foreach($attendance->breaks as $i => $break)
                <tr>
                    <th class="text-gray">休憩{{ $i+1 }}</th>
                    <td class="text-black align-left">
                        <div class="input-pair">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="text" name="after_breaks[{{ $i }}][start_at]" value="{{ isset($payload->after_breaks[$i]['start_at']) ? \Carbon\Carbon::parse($payload->after_breaks[$i]['start_at'])->format('H:i') : '' }}" class="detail-input text-black align-input input-readonly" readonly tabindex="-1">
                                <span class="input-sep">〜</span>
                                <input type="text" name="after_breaks[{{ $i }}][end_at]" value="{{ isset($payload->after_breaks[$i]['end_at']) ? \Carbon\Carbon::parse($payload->after_breaks[$i]['end_at'])->format('H:i') : '' }}" class="detail-input text-black align-input input-readonly" readonly tabindex="-1">
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
                @php $i = count($attendance->breaks); @endphp
                <tr>
                    <th class="text-gray">休憩{{ $i+1 }}</th>
                    <td class="text-black align-left">
                        <div class="input-pair">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="text" name="after_breaks[{{ $i }}][start_at]" value="" class="detail-input text-black align-input input-readonly" readonly tabindex="-1">
                                <span class="input-sep">〜</span>
                                <input type="text" name="after_breaks[{{ $i }}][end_at]" value="" class="detail-input text-black align-input input-readonly" readonly tabindex="-1">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th class="text-gray">備考</th>
                    <td class="text-black align-left">
                        <div>
                        <textarea name="after_attendance[note]" class="detail-note-input text-black align-input input-readonly" rows="5" readonly tabindex="-1">{{ $payload->after_attendance['note'] ?? '' }}</textarea>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="detail-btn-area">
            @if($changeRequest->status === 'approved')
                <button type="button" class="detail-update-btn approved-bg" disabled>承認済み</button>
            @else
                <button type="submit" class="detail-update-btn">承認</button>
            @endif
        </div>
    </form>
</div>
@endsection
