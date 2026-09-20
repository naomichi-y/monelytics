<!doctype html>
<html>
    @section('extend_head_tags')
        {!! Html::versionedStyle('assets/css/parallax.css') !!}
        {!! Html::script('assets/components/jquery_plugins/parallax.js/1.4.2/parallax.min.js') !!}

        {{-- parallax.js 1.4.2 は $(document).on("ready", ...) で自動初期化する。
             jQuery 3 でこの呼び出し方は削除されたため初期化されず、背景画像が
             敷かれない。紹介文は白抜きなので、本文が真っ白な画面に消える。 --}}
        <script>
            $(function() {
                $('[data-parallax="scroll"]').parallax();
            });
        </script>
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
