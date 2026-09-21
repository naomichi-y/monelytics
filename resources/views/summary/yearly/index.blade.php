@extends('layouts.master')

@section('title')
年別集計
@stop

@section('include_header')
    {!! Html::script('assets/components/highcharts/13.1.0/js/highcharts.js') !!}
    {!! Html::versionedScript('assets/js/line-chart.js') !!}
    <script>
        $(function() {
            // 詳細検索押下
            $("#open_condition").click(function() {
                $.get("/summary/yearly/condition",
                    {
                        begin_year: {!! Html::encodeJsJsonValue('begin_year', date('Y')) !!},
                        end_year: {!! Html::encodeJsJsonValue('end_year', date('Y')) !!},
                        output_type: {!! Html::encodeJsJsonValue('output_type', App\Libraries\Condition\YearlySummaryCondition::OUTPUT_TYPE_MONTHLY) !!},
                        keyword: {!! Html::encodeJsJsonValue('keyword') !!}
                    },
                    function(data) {
                        showModal(data);
                    }
                );
            });

            $("#tabs").startTabs();
        });
    </script>
    {!! Html::script('assets/components/jquery_plugins/jquery.tablefix_1.0.1.js') !!}
@stop

@section('function')
    {{-- .card は縦方向の flex コンテナ。そのままだと中のボタンが
         横いっぱいに伸びるため、右寄せで自然な幅に留める。 --}}
    <div class="card card-body align-items-end">
        <a class="btn btn-info btn-sm" id="open_condition">詳細検索</a>
    </div>
@stop

@section('content')
    {{-- タブの中身を読む URL。自前で href を組み立てるので、区切りの '&' は
         ここで '&amp;' へ逃がす ({{ }} が行う)。値そのものは
         buildQueryString が URL エンコード済みで、HTML エスケープでは
         代わりにならない。 --}}
    <div id="tabs">
        <ul>
            <li data-tab="report"><a href="/summary/yearly/report?{{ $condition->buildQueryString() }}">集計表</a></li>
            <li data-tab="line-chart"><a href="/summary/yearly/line-chart?{{ $condition->buildQueryString() }}">推移グラフ</a></li>
        </ul>
    </div>
@stop
