<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Coachtech勤怠管理アプリ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }} ">
    <link rel="stylesheet" href="{{ asset('css/common.css') }} ">
    @yield('css')
</head>
<body>
<header class="header">
    <div class="header__container">
        <div class="header-utilities">
            <div class="header__logo">
                <a href="{{ url('/admin/attendance/list') }}">
                    <img src="{{ asset('/img/logo.svg') }}" alt="coachtechロゴ">
                </a>
            </div>
            <nav class="header__nav">
                <ul class="nav__list">
                    <li class="nav__item">
                        <a href="{{ route('admin.attendance.index') }}" class="nav__text">勤怠一覧</a>
                    </li>
                    <li class="nav__item">
                        <a href="{{ route('admin.staff.index') }}" class="nav__text">スタッフ一覧</a>
                    </li>
                    <li class="nav__item">
                        <a href="{{ route('stamp_correction_request.list') }}" class="nav__text">申請一覧</a>
                    </li>
                    <li class="nav__item">
                        @auth
                            <form action="{{ route('admin.logout') }}" method="POST" class="logout-form">
                                @csrf
                                <button type="submit" class="nav__text">ログアウト</button>
                            </form>
                        @else
                            <a href="{{ route('admin.login') }}" class="nav__text">ログイン</a>
                        @endauth
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<!-- フラッシュメッセージ -->
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif

@yield('content')
</body>
</html>