{{-- BS3 の .navbar-default は sandstone では暗い帯だった。BS5 では配色を
     明示する必要がある。5.3 で .navbar-dark は非推奨になり、配色の切替は
     data-bs-theme で行う。 --}}
<header class="navbar navbar-expand-lg fixed-top bg-dark" data-bs-theme="dark">
    <div class="container">
        {{-- BS5 の navbar は <nav> 自身が flex コンテナになる。BS3 の
             .navbar-header で包む形は崩れるため、直下に並べる。 --}}
        <div class="logo"><a href="/">{!! Html::image('assets/images/logo.png') !!}</a></div>
        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="メニュー">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbar">
            <ul class="navbar-nav me-auto">
                @if (Auth::check())
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">収支管理</a>
                        <ul class="dropdown-menu" data-bs-theme="light">
                            <li>{!! link_to('cost/variable/create', '変動収支', ['class' => 'dropdown-item']) !!}</li>
                            <li>{!! link_to('cost/constant/create', '固定収支', ['class' => 'dropdown-item']) !!}</li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">レポート</a>
                        <ul class="dropdown-menu" data-bs-theme="light">
                            <li>{!! link_to('summary/daily', '日別集計', ['class' => 'dropdown-item']) !!}</li>
                            <li>{!! link_to('summary/monthly', '月別集計', ['class' => 'dropdown-item']) !!}</li>
                            <li>{!! link_to('summary/yearly', '年別集計', ['class' => 'dropdown-item']) !!}</li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">設定</a>
                        <ul class="dropdown-menu" data-bs-theme="light">
                            {{-- 小項目を先に置く。日々足すのは小項目で、大項目は
                                 最初に作ったあとはほとんど触らない。 --}}
                            <li>{!! link_to('settings/activityCategoryItem', '小項目', ['class' => 'dropdown-item']) !!}</li>
                            <li>{!! link_to('settings/activityCategory', '大項目', ['class' => 'dropdown-item']) !!}</li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a href="/user/create" class="nav-link">会員登録</a></li>
                    <li class="nav-item"><a href="/user/login" class="nav-link">ログイン</a></li>
                @endif
            </ul>

            @if (Auth::check())
                {!! Form::open(['url' => 'summary/daily', 'method' => 'get', 'class' => 'd-flex']) !!}
                    <div class="navbar-search">
                        {!! Form::text('keyword', Request::input('keyword'), ['class' => 'form-control', 'placeholder' => 'キーワード']) !!}
                        <button type="submit" class="navbar-search-button" aria-label="検索">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                {!! Form::close() !!}

                <ul class="navbar-nav ms-lg-3">
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">アカウント</a>
                        {{-- 右端に出るため、既定の左揃えではメニューが画面外へはみ出す。 --}}
                        <ul class="dropdown-menu dropdown-menu-end" data-bs-theme="light">
                            <li>{!! link_to('/user', 'プロフィール', ['class' => 'dropdown-item']) !!}</li>
                            <li>{!! link_to('/user/logout', 'ログアウト', ['class' => 'dropdown-item']) !!}</li>
                        </ul>
                    </li>
                </ul>
            @endif
        </div>
    </div>
</header>
