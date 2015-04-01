@extends('layouts.master')

@section('title')
年別集計
@stop

@section('include_header')
  <script>
    $(function() {
      // 詳細検索押下
      $("#open_condition").click(function() {
        $.get("/yearlySummary/condition",
          {
            begin_year: {{HTML::encodeJsJsonValue('begin_year', date('Y'))}},
            end_year: {{HTML::encodeJsJsonValue('end_year', date('Y'))}},
            output_type: {{HTML::encodeJsJsonValue('output_type', YearlySummaryCondition::OUTPUT_TYPE_MONTHLY)}}
          },
          function(data) {
            $(data).modal();
          }
        );
      });

      $("#tabs").startTabs("yearly_summary-tab");
    });
  </script>
  {{HTML::script('assets/js/jquery.tablefix_1.0.1.js')}}
@stop

@section('function')
  <div class="well bs-component">
    <a class="btn btn-info btn-sm" id="open_condition">詳細検索</a>
  </div>
@stop

@section('content')
  <div id="tabs">
    <ul>
      <li><a href="/yearlySummary/list?begin_year={{{Input::get('begin_year', date('Y'))}}}&amp;end_year={{{Input::get('end_year', date('Y'))}}}&amp;output_type={{Input::get('output_type', 1)}}">集計表</a></li>
    </ul>
  </div>
@stop
