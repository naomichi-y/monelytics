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

            // 詳細検索押下
            $("#open_condition").click(function() {
                $.get("/summary/monthly/condition",
                    {
                        date_month: {!! Html::encodeJsJsonValue('date_month', date('Y-m')) !!},
                        begin_date: {!! Html::encodeJsJsonValue('begin_date') !!},
                        end_date: {!! Html::encodeJsJsonValue('end_date') !!}
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
                    {!! Form::select('date_month', $month_list, Html::requestValue('date_month', date('Y-m')), ['class' => 'form-select', 'id' => 'date_month']) !!}
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
                         (@see MonthlyController::index)。 --}}
                    {!! Form::button('<span class="bi bi-chevron-left"></span> 前月', [
                        'class' => 'btn btn-secondary btn-sm month_step',
                        'data-month' => $adjacent_months['previous'],
                        'disabled' => $adjacent_months['previous'] === null,
                    ]) !!}
                </div>
                <div class="col-6 d-grid">
                    {!! Form::button('翌月 <span class="bi bi-chevron-right"></span>', [
                        'class' => 'btn btn-secondary btn-sm month_step',
                        'data-month' => $adjacent_months['next'],
                        'disabled' => $adjacent_months['next'] === null,
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
