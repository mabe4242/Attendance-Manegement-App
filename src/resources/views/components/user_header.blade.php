<header class="header">
    <div class="header__inner">
        <div class="header-utilities">
            <a class="header__logo" href="/attendance">
                <img src="{{ asset('icons/logo.png') }}" alt="COACHTECHのロゴ" class="logo-img">
            </a>
            <nav>
                <ul class="header-nav">
                    @if (Auth::check())
                        <li class="header-nav__item">
                            <a class="header-nav__link btn" href="{{ route('attendance.create') }}">勤怠</a>
                        </li>
                        <li class="header-nav__item">
                            <a class="header-nav__link btn" href="{{ route('attendance.index') }}">勤怠一覧</a>
                        </li>
                        <li class="header-nav__item">
                            <a class="header-nav__link btn" href="{{ route('attendance_requests.index') }}">申請</a>
                        </li>
                        <li class="header-nav__item">
                            <form class="form" action="/logout" method="post">
                                @csrf
                                <button class="header-nav__button btn">ログアウト</button>
                            </form>
                        </li>
                    @endif
                </ul>
            </nav>
        </div>
    </div>
</header>
