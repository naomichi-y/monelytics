<!doctype html>
<html>
    @include('layouts.head_tags')
    @yield('include_header')

    {{-- Highcharts 13 は light-dark() で配色を選ぶ。OS が暗色設定だと
         グラフだけ黒くなるため、用意されている highcharts-light で明色に
         固定する (:root の color-scheme だけでは .highcharts-container の
         指定に負ける)。 --}}
    <body class="highcharts-light">
        @include('layouts.content_header')

        <div class="container">
            {{-- BS5 に .page-header はない。下線と余白は自前で持つ (style.css)。 --}}
            <div class="page-header">
                <div class="row">
                    <div class="col-md-8"><h1>@yield('title')</h1></div>
                    <div class="col-md-4 text-end">
                         @yield('function')
                    </div>
                </div>
            </div>

            @include('layouts.content_notice')

            @yield('content')
        </div>

        @include('layouts.content_footer')
    </body>
</html>
