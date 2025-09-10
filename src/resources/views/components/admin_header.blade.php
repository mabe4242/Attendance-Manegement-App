<header class="header">
    <div class="header__inner">
        <div class="header-utilities">
            <a class="header__logo" href="/admin/attendance/list">
                <img src="{{ asset('icons/logo.png') }}" alt="COACHTECHのロゴ" class="logo-img">
            </a>
            <nav>
                <ul class="header-nav">
                    @if (Auth::check())
                        <li class="header-nav__item">
                            <form class="form" action="/admin/logout" method="post">
                                @csrf
                                <button class="header-nav__button btn">ログアウト</button>
                            </form>
                        </li>
                    @else 
                        <li class="header-nav__item">
                            <form class="form" action="/admin/login">
                                <button class="header-nav__button btn">ログイン</button>
                            </form>
                        </li>
                    @endif
                    <li class="header-nav__item">
                        <a class="header-nav__link btn" href="">マイページ</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>
