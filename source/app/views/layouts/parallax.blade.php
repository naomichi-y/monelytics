<!doctype html>
<html>
  @section('extend_head_tags')
    {{HTML::style('assets/css/parallax.css')}}
    {{HTML::script('assets/components/jquery_plugins/parallax.js-1.3.1/parallax.min.js')}}
  @stop
  @include('layouts.head_tags')
  @yield('include_header')
  <body>
    @include('layouts.content_header')
    @include('layouts.content_notice')

    @yield('content')

    @include('layouts.content_footer')
  </body>
</html>
