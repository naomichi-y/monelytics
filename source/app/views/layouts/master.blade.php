<!doctype html>
<html>
  <head prefix="og: http://ogp.me/ns#">
    <meta charset="utf-8">
    <title>
      monelytics
      @if ($__env->yieldContent('title'))
        / @yield('title')
      @endif
    </title>
    <meta name="description" content="シンプルで使いやすい無料の家計簿アプリ。タブレットやスマホにも対応しています。">
    <meta name="keywords" content="家計簿, 無料, 簡単, 収支管理, スマホ">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta property="og:type" content="product">
    <meta property="og:title" content="monelytics - シンプルな家計簿アプリ">
    <meta property="og:description" content="シンプルで使いやすい無料の家計簿アプリ。タブレットやスマホにも対応しています。">
    <meta property="og:url" content="{{Config::get('app.url')}}">
    <meta property="og:image" content="/assets/images/logo_ogp.png">
    <meta property="og:site_name" content="monelytics">
    <meta property="og:locale" content="ja_JP">
    {{HTML::style('assets/bootstrap/3.3.2/sandstone/bootstrap.min.css')}}
    {{HTML::style('assets/js/jquery-ui/1.11.3/jquery-ui.min.css')}}
    {{HTML::style('assets/js/darktooltip/css/darktooltip.min.css')}}
    {{HTML::style('assets/css/style.css')}}
    {{HTML::script('assets/js/jquery/2.1.1/jquery.min.js')}}
    {{HTML::script('assets/js/jquery-cookie/src/jquery.cookie.js')}}
    {{HTML::script('assets/bootstrap/3.3.2/js/bootstrap.min.js')}}
    {{HTML::script('assets/js/jquery-ui/1.11.3/jquery-ui.min.js')}}
    {{HTML::script('assets/js/jquery.ui.datepicker-ja.js')}}
    {{HTML::script('assets/js/gcalendar-holidays.js')}}
    {{HTML::script('assets/js/darktooltip/js/jquery.darktooltip.min.js')}}
    {{HTML::script('assets/js/analytics.js')}}
    {{HTML::script('assets/js/common.js')}}
    @yield('include_header')
  </head>
  <body>
    <header class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <nav>
          <div class="navbar-header">
            <div class="logo"><a href="/home">{{HTML::image('assets/images/logo.png')}}</a></div>
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
          </div>
          @if (Auth::check())
            <div class="collapse navbar-collapse" id="navbar">
              <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown">収支管理 <span class="caret"></span></a>
                  <ul class="dropdown-menu">
                    <li>{{link_to('variableCost/create', '変動収支')}}</li>
                    <li>{{link_to('constantCost/create', '固定収支')}}</li>
                  </ul>
                </li>

                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown">レポート <span class="caret"></span></a>
                  <ul class="dropdown-menu">
                    <li>{{link_to('dailySummary', '日別集計')}}</li>
                    <li>{{link_to('monthlySummary', '月別集計')}}</li>
                    <li>{{link_to('yearlySummary', '年別集計')}}</li>
                  </ul>
                </li>

                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown">設定 <span class="caret"></span></a>
                  <ul class="dropdown-menu">
                    <li>{{link_to('activityCategory', '科目カテゴリ')}}</li>
                    <li>{{link_to('activityCategoryGroup', '科目')}}</li>
                  </ul>
                </li>

                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown">{{{Auth::user()->nickname}}} <span class="caret"></span></a>
                  <ul class="dropdown-menu">
                    <li>{{link_to('/user', 'アカウント')}}</li>
                    <li>{{link_to('/user/logout', 'ログアウト')}}</li>
                  </ul>
                </li>
              </ul>
            </div>
          @endif
        </nav>
      </div>
    </header>

    <div class="container">
      @if ($__env->yieldContent('title'))
        <div class="page-header">
          <div class="row">
            <div class="col-md-8">
              <h1>@yield('title')</h1>
            </div>
            <div class="col-md-4 text-right">
               @yield('function')
            </div>
          </div>
        </div>
      @else
        <div class="title_none"></div>
      @endif

      @yield('content_header')

      @if (Session::has('success'))
      <div class="alert alert-dismissable alert-success">
        {{{Session::get('success')}}}
      </div>
      @endif

      @yield('announce')

      @if ($errors->count())
      <div class="alert alert-dismissable alert-warning">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <ul>
          @foreach ($errors->all() as $error)
          <li>{{{$error}}}</li>
          @endforeach
        </ul>
      </div>
      @endif

      @yield('content')
    </div>

    <footer>
      @include('layouts/social_links')

      <ul class="list-unstyled">
        <li>{{link_to('contact', 'お問い合わせ')}}</li>
        <li>{{link_to('http://about.me/naomichi.yamakita', '運営')}}</li>
        <li>{{link_to('https://github.com/naomichi-y/monelytics', 'GitHub')}}</li>
      </ul>
    </footer>
  </body>
</html>
