@extends('layouts.master')

@section('title')
月別集計
@stop

@section('include_header')
    {!! Html::script('assets/components/highcharts/13.1.0/js/highcharts.js') !!}
    {!! Html::versionedScript('assets/js/pie-chart.js') !!}
    <script>
        $(function() {
            $("#date_month").change(function() {
                $("#search_form").submit();
            });

            {{-- モーダルへ渡すのは Condition が決めた値。リクエストを読み直して
                 date('Y-m') を既定にしていたころは、日付範囲で絞っている画面
                 (URL に date_month が無い) から開いても月指定が当月になり、
                 範囲で見ているのに月を選んでいるように見えていた。 --}}
            // 詳細検索押下
            $("#open_condition").click(function() {
                $.get("/summary/monthly/condition",
                    {
                        date_month: {!! Html::encodeJsValue($condition->date_month) !!},
                        begin_date: {!! Html::encodeJsValue($condition->begin_date) !!},
                        end_date: {!! Html::encodeJsValue($condition->end_date) !!}
                    },
                    function(data) {
                        showModal(data);
                    }
                );
            });

            // 前月・翌月。セレクトを動かしてフォームを送る。値を代入しても
            // change は起きないため、送信は自分で呼ぶ。
            //
            // リンクではなくフォームにするのは、開いているタブを引き継ぐのが
            // 送信時の処理 (common.js) だからで、リンクで飛ぶと月を変えるたび
            // 集計表へ戻る。
            $(".month_step").click(function() {
                $("#date_month").val($(this).data("month"));
                $("#search_form").submit();
            });

            $("#tabs").startTabs();
        });
    </script>
    {!! Html::script('assets/components/jquery_plugins/jquery.tablefix_1.0.1.js') !!}
@stop

@section('function')
    <div class="card card-body">
        {!! Form::open(['url' => 'summary/monthly', 'id' => 'search_form', 'method' => 'get']) !!}
            <div class="row g-2 align-items-center form-group-adjust">
                <div class="col">
                    @if ($condition->hasDateRange())
                        {{-- 日付範囲で絞っている間は月のセレクトを出さず、効いて
                             いる期間を出す。日別集計と同じ扱い。範囲と月の両方が
                             送られると getDateRange は範囲を優先するので、月を
                             選べても結果は変わらず、選択と表示が食い違う。 --}}
                        <span class="text-nowrap">{{Html::dateRange($date_range)}}</span>
                    @else
                        {{-- 選択は Condition が決めた値を出す。ここで date('Y-m') を
                             もう一度書くと、詳細検索との既定がずれる。 --}}
                        {!! Form::select('date_month', $month_list, $condition->date_month, ['class' => 'form-select', 'id' => 'date_month']) !!}
                    @endif
                </div>
                <div class="col-auto">
                    <a class="btn btn-info btn-sm" id="open_condition">詳細検索</a>
                </div>
            </div>
            {{-- 前月・翌月はセレクトの左右ではなく下に置く。帯は col-md-4 の
                 中にあり、md では左右に挟むと月の表示が「2026」で切れる。 --}}
            <div class="row g-2 mt-1">
                <div class="col-6 d-grid">
                    {{-- 押せるかどうかは、その向きに記録があるかで決まる
                         (@see MonthlyController::index)。

                         日付範囲で絞っている間はどちらも押せない。押すと
                         セレクトへ月を入れて送る作りだが、そのセレクトが
                         出ていない。月へ戻るのは詳細検索のクリアから。 --}}
                    {!! Form::button('<span class="bi bi-chevron-left"></span> 前月', [
                        'class' => 'btn btn-secondary btn-sm month_step',
                        'data-month' => $adjacent_months['previous'],
                        'disabled' => $condition->hasDateRange() || $adjacent_months['previous'] === null,
                    ]) !!}
                </div>
                <div class="col-6 d-grid">
                    {!! Form::button('翌月 <span class="bi bi-chevron-right"></span>', [
                        'class' => 'btn btn-secondary btn-sm month_step',
                        'data-month' => $adjacent_months['next'],
                        'disabled' => $condition->hasDateRange() || $adjacent_months['next'] === null,
                    ]) !!}
                </div>
            </div>
        {!! Form::close() !!}
    </div>
@stop

@section('content')
    <div id="tabs">
        <ul>
            <li data-tab="report"><a href="/summary/monthly/report?date_month={{{Html::requestValue('date_month', date('Y-m'))}}}&amp;begin_date={{{Html::requestValue('begin_date')}}}&amp;end_date={{{Html::requestValue('end_date')}}}">集計表</a></li>
            <li data-tab="calendar"><a href="/summary/monthly/calendar?date_month={{{Html::requestValue('date_month', date('Y-m'))}}}">カレンダー</a></li>
            <li data-tab="expense-chart"><a href="/summary/monthly/pie-chart?balance_type={{App\Models\ActivityCategory::BALANCE_TYPE_EXPENSE}}&amp;date_month={{{Html::requestValue('date_month', date('Y-m'))}}}&amp;begin_date={{{Html::requestValue('begin_date')}}}&amp;end_date={{{Html::requestValue('end_date')}}}">支出構成グラフ</a></li>
            <li data-tab="income-chart"><a href="/summary/monthly/pie-chart?balance_type={{App\Models\ActivityCategory::BALANCE_TYPE_INCOME}}&amp;date_month={{{Html::requestValue('date_month', date('Y-m'))}}}&amp;begin_date={{{Html::requestValue('begin_date')}}}&amp;end_date={{{Html::requestValue('end_date')}}}">収入構成グラフ</a></li>
            <li data-tab="ranking"><a href="/summary/monthly/ranking?date_month={{{Html::requestValue('date_month', date('Y-m'))}}}&amp;begin_date={{{Html::requestValue('begin_date')}}}&amp;end_date={{{Html::requestValue('end_date')}}}">ランキング</a></li>
        </ul>
    </div>
@stop
