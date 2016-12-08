<!doctype html>
<html>
    @section('extend_head_tags')
        {!! Html::style('assets/css/parallax.css') !!}
        {!! Html::script('assets/components/jquery_plugins/parallax.js/1.4.2/parallax.min.js') !!}
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
