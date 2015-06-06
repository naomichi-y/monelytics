@extends('layouts.master')

@section('title')
月別集計
@stop

@section('include_header')
  {{HTML::script('assets/components/highcharts/4.1.1/js/highcharts.js')}}
  {{HTML::script('assets/js/gcalendar-holidays.js')}}
  {{HTML::script('assets/js/pie-chart.js')}}
  <script>
    $(function() {
      $("#date_month").change(function() {
        $("#search_form").submit();
      });

      // 詳細検索押下
      $("#open_condition").click(function() {
        $.get("/summary/monthly/condition",
          {
            date_month: {{HTML::encodeJsJsonValue('date_month', date('Y-m'))}},
            begin_date: {{HTML::encodeJsJsonValue('begin_date')}},
            end_date: {{HTML::encodeJsJsonValue('end_date')}}
          },
          function(data) {
            $(data).modal();
          }
        );
      });

      $("#tabs").startTabs("monthly_summary-tab");
    });
  </script>
  {{HTML::script('assets/components/jquery_plugins/jquery.tablefix_1.0.1.js')}}
@stop

@section('function')
  <div class="well">
    {{Form::open(array('url' => 'summary/monthly', 'class' => 'form-horizontal', 'id' => 'search_form', 'method' => 'get'))}}
      <div class="form-group form-group-sm form-group-adjust">
        <div class="col-md-8">
          {{Form::select('date_month', $month_list, Input::get('date_month', date('Y-m')), array('class' => 'form-control', 'id' => 'date_month'))}}
        </div>
        <div class="col-md-4">
          <a class="btn btn-info btn-sm" id="open_condition">詳細検索</a>
        </div>
      </div>
    {{Form::close()}}
  </div>
@stop

@section('content')
  <div id="tabs">
    <ul>
      <li><a href="/summary/monthly/list?date_month={{{Input::get('date_month', date('Y-m'))}}}&amp;begin_date={{{Input::get('begin_date')}}}&amp;end_date={{{Input::get('end_date')}}}">集計表</a></li>
      <li><a href="/summary/monthly/calendar?date_month={{{Input::get('date_month', date('Y-m'))}}}">カレンダー</a></li>
      <li><a href="/summary/monthly/pie-chart?balance_type={{ActivityCategory::BALANCE_TYPE_EXPENSE}}&amp;date_month={{{Input::get('date_month', date('Y-m'))}}}&amp;begin_date={{{Input::get('begin_date')}}}&amp;end_date={{{Input::get('end_date')}}}">支出構成グラフ</a></li>
      <li><a href="/summary/monthly/pie-chart?balance_type={{ActivityCategory::BALANCE_TYPE_INCOME}}&amp;date_month={{{Input::get('date_month', date('Y-m'))}}}&amp;begin_date={{{Input::get('begin_date')}}}&amp;end_date={{{Input::get('end_date')}}}">収入構成グラフ</a></li>
      <li><a href="/summary/monthly/ranking?date_month={{{Input::get('date_month', date('Y-m'))}}}&amp;begin_date={{{Input::get('begin_date')}}}&amp;end_date={{{Input::get('end_date')}}}">ランキング</a></li>
    </ul>
  </div>
@stop
