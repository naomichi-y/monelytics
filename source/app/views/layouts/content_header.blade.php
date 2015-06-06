<!doctype html>
<header class="navbar navbar-default navbar-fixed-top">
  <div class="container">
    <nav>
      <div class="navbar-header">
        <div class="logo"><a href="/dashboard">{{HTML::image('assets/images/logo.png')}}</a></div>
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
      </div>
      <div class="collapse navbar-collapse" id="navbar">
        <ul class="nav navbar-nav navbar-right">
          @if (Auth::check())
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">収支管理 <span class="caret"></span></a>
              <ul class="dropdown-menu">
                <li>{{link_to('cost/variable/create', '変動収支')}}</li>
                <li>{{link_to('cost/constant/create', '固定収支')}}</li>
              </ul>
            </li>

            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">レポート <span class="caret"></span></a>
              <ul class="dropdown-menu">
                <li>{{link_to('summary/daily', '日別集計')}}</li>
                <li>{{link_to('summary/monthly', '月別集計')}}</li>
                <li>{{link_to('summary/yearly', '年別集計')}}</li>
              </ul>
            </li>

            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">設定 <span class="caret"></span></a>
              <ul class="dropdown-menu">
                <li>{{link_to('settings/activityCategory', '科目カテゴリ')}}</li>
                <li>{{link_to('settings/activityCategoryGroup', '科目')}}</li>
              </ul>
            </li>

            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">{{{Auth::user()->nickname}}} <span class="caret"></span></a>
              <ul class="dropdown-menu">
                <li>{{link_to('/user', 'アカウント')}}</li>
                <li>{{link_to('/user/logout', 'ログアウト')}}</li>
              </ul>
            </li>
          @else
            <li><a href="/user/create" class="dropdown-toggle">会員登録</a></li>
            <li><a href="/user/login" class="dropdown-toggle">ログイン</a></li>
          @endif
        </ul>
      </div>
    </nav>
  </div>
</header>
