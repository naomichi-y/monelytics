<head prefix="og: http://ogp.me/ns#">
  <meta charset="utf-8">
  <title>monelytics</title>
  <meta name="description" content="シンプルで使いやすい無料の家計簿アプリ。タブレットやスマホにも対応しています。">
  <meta name="keywords" content="家計簿, 無料, 簡単, 収支管理, スマホ">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <meta property="og:type" content="product">
  <meta property="og:title" content="monelytics - シンプルな家計簿アプリ">
  <meta property="og:description" content="シンプルで使いやすい無料の家計簿アプリ。タブレットやスマホにも対応しています。">
  <meta property="og:url" content="{{Config::get('app.url')}}">
  <meta property="og:image" content="{{Config::get('app.url')}}assets/images/logo_ogp.png">
  <meta property="og:site_name" content="monelytics">
  <meta property="og:locale" content="ja_JP">
  {{HTML::style('assets/components/bootstrap/3.3.2/sandstone/bootstrap.min.css')}}
  {{HTML::style('assets/components/jquery-ui/1.11.3/jquery-ui.min.css')}}
  {{HTML::style('assets/components/jquery_plugins/darktooltip/css/darktooltip.min.css')}}
  {{HTML::style('assets/css/style.css')}}
  {{HTML::script('assets/components/jquery/2.1.1/jquery.min.js')}}
  {{HTML::script('assets/components/jquery_plugins/jquery-cookie/src/jquery.cookie.js')}}
  {{HTML::script('assets/components/bootstrap/3.3.2/js/bootstrap.min.js')}}
  {{HTML::script('assets/components/jquery-ui/1.11.3/jquery-ui.min.js')}}
  {{HTML::script('assets/components/jquery_plugins/jquery.ui.datepicker-ja.js')}}
  {{HTML::script('assets/components/jquery_plugins/darktooltip/js/jquery.darktooltip.min.js')}}
  {{HTML::script('assets/components/gcalendar-holidays.js')}}
  {{HTML::script('assets/components/analytics.js')}}
  {{HTML::script('assets/js/common.js')}}
  @yield('extend_head_tags')
</head>
