@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
<link rel="stylesheet" href="{{ asset('css/staff.css') }}">
@endsection

@section('content')
<div class="attendance-body">
    <div class="section-title">
        <span class="section-title__bar"></span>
        <span class="section-title__text">スタッフ一覧</span>
    </div>
    <table class="attendance-table">
        <thead>
            <tr>
                <th class="text-gray">名前</th>
                <th class="text-gray">メールアドレス</th>
                <th class="text-gray">月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach($staffs as $user)
            <tr>
                <td class="text-gray">{{ $user->name }}</td>
                <td class="text-gray">{{ $user->email }}</td>
                <td>
                    <a href="{{ route('admin.attendance.staff', $user->id) }}" class="text-black">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection