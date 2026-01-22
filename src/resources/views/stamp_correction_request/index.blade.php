@extends('layouts.app')
@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
<link rel="stylesheet" href="{{ asset('css/tab.css') }}">
@endsection
@section('content')
<div class="attendance-body">
    <div class="section-title">
        <span class="section-title__bar"></span>
        <span class="section-title__text">申請一覧</span>
    </div>
    <div class="tab-area">
        <button class="tab-btn active" id="pending-tab" onclick="showTab('pending')">承認待ち</button>
        <button class="tab-btn" id="approved-tab" onclick="showTab('approved')">承認済み</button>
    </div>
    <div id="pending-table" class="tab-content">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="text-gray">状態</th>
                    <th class="text-gray">名前</th>
                    <th class="text-gray">対象日時</th>
                    <th class="text-gray">申請理由</th>
                    <th class="text-gray">申請日時</th>
                    <th class="text-gray">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pending as $row)
                <tr>
                    <td class="text-gray">承認待ち</td>
                    <td class="text-gray">{{ $row['staff_name'] }}</td>
                    <td class="text-gray no-letter-spacing">{{ \Carbon\Carbon::parse($row['target_date'])->format('Y/m/d') }}</td>
                    <td class="text-gray reason-left-align reason-padding">
                        @php $reason = $row['reason']; @endphp
                        {{ mb_strlen($reason) > 5 ? mb_substr($reason,0,4).'…' : $reason }}
                    </td>
                    <td class="text-gray no-letter-spacing">{{ \Carbon\Carbon::parse($row['requested_at'])->format('Y/m/d') }}</td>
                    <td>
                        @php
                            $isAdmin = auth()->check() && auth()->user()->role_id == 1 && session('is_admin_login') === true;
                        @endphp
                        @if($isAdmin)
                            <a href="{{ route('stamp_correction_request.approve', $row['id']) }}" class="text-black">詳細</a>
                        @else
                            <a href="{{ route('attendance.detail', $row['attendance_id']) }}" class="text-black">詳細</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-gray">承認待ちの申請はありません</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div id="approved-table" class="tab-content" style="display:none;">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="text-gray">状態</th>
                    <th class="text-gray">名前</th>
                    <th class="text-gray">対象日時</th>
                    <th class="text-gray">申請理由</th>
                    <th class="text-gray">申請日時</th>
                    <th class="text-gray">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approved as $row)
                <tr>
                    <td class="text-gray">承認済み</td>
                    <td class="text-gray">{{ $row['staff_name'] }}</td>
                    <td class="text-gray no-letter-spacing">{{ \Carbon\Carbon::parse($row['target_date'])->format('Y/m/d') }}</td>
                    <td class="text-gray reason-left-align">
                        @php $reason = $row['reason']; @endphp
                        {{ mb_strlen($reason) > 5 ? mb_substr($reason,0,4).'…' : $reason }}
                    </td>
                    <td class="text-gray no-letter-spacing">{{ \Carbon\Carbon::parse($row['requested_at'])->format('Y/m/d') }}</td>
                    <td>
                        @php
                            $isAdmin = auth()->check() && auth()->user()->role_id == 1 && session('is_admin_login') === true;
                        @endphp
                        @if($isAdmin)
                            <a href="{{ route('stamp_correction_request.approve', $row['id']) }}" class="text-black">詳細</a>
                        @else
                            <a href="{{ route('attendance.detail', $row['attendance_id']) }}" class="text-black">詳細</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-gray">承認済みの申請はありません</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script src="{{ asset('js/tab.js') }}"></script>
@endsection
