@extends('layouts.master')

@section('title')
日別集計
@stop

@section('include_header')
  {{HTML::style('assets/css/responsive_table.css')}}
  {{HTML::script('assets/js/responsive_table.js')}}
  <script>
    $(function() {
      $("table").responsiveTable();

      // 詳細検索押下
      $("#open_condition").click(function() {
        $.get("/summary/daily/condition",
          {
            date_month: {{HTML::encodeJsJsonValue('date_month', date('Y-m'))}},
            begin_date: {{HTML::encodeJsJsonValue('begin_date')}},
            end_date: {{HTML::encodeJsJsonValue('end_date')}},
            activity_category_group_id: {{HTML::encodeJsJsonValue('activity_category_group_id', null, 'array')}},
            keyword: {{HTML::encodeJsJsonValue('keyword')}},
            credit_flag: {{HTML::encodeJsJsonValue('credit_flag')}},
            special_flag: {{HTML::encodeJsJsonValue('special_flag')}}
          },
          function(data) {
            $(data).modal();
          }
        );
      });

      // 編集押下
      $(".open_edit").click(function() {
        var selected_id = $(".open_edit").index(this) + 1;
        var activity_id = $("tr:eq(" + selected_id + ")").attr("data-id");

        $.get("/cost/variable/edit",
          {
            id: activity_id
          },
          function(data) {
            $(data).modal();
          }
        );
      });
    });
  </script>
@stop

@section('function')
  <div class="well bs-component">
    <a class="btn btn-info btn-sm" id="open_condition">詳細検索</a>
  </div>
@stop

@section('content')
  @include('layouts/delete_modal', array('action' => 'cost/variable/delete'))

  @if ($activities->getTotal())
  {{Form::open(array('url' => 'summary/daily', 'method' => 'get', 'id' => 'list-form'))}}
    <table class="table table-striped table-hover">
      <colgroup>
        <col style="width: 11%" />
        <col style="width: 11%" />
        <col style="width: 19%" />
        <col style="width: 19%" />
        <col style="width: 4%" />
      </colgroup>
      <colgroup span="2" style="width: 4%">
      <colgroup span="2" style="width: 14%">
      <thead>
        <tr>
          <th class="text-center">
            {{HTML::sortLabel('activity_date', '発生日', true)}}
          </th>
          <th class="text-center">
            {{HTML::sortLabel('activity_category_group_id', '科目名')}}
          </th>
          <th class="text-center">
            {{HTML::sortLabel('location', '場所')}}
          </th>
          <th class="text-center">
            {{HTML::sortLabel('content', '用途')}}
          </th>
          <th class="text-center">
            {{HTML::sortLabel('amount', '金額')}}
          </th>
          <th class="text-center">
            <span class="glyphicon glyphicon-credit-card"></span>
            {{HTML::sortLabel('credit_flag', '')}}
          </th>
          <th class="text-center">
            <span class="glyphicon glyphicon-star"></span>
            {{HTML::sortLabel('special_flag', '')}}
          </th>
          <th class="text-center">
            {{HTML::sortLabel('create_date', '登録日時')}}
          </th>
          <th class="text-center">操作</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($activities as $activity)
        <tr data-id="{{$activity->id}}">
          <td class="text-center">
            {{HTML::date($activity->activity_date)}}
          </td>
          <td>{{{$activity->activityCategoryGroup->group_name}}}</td>
          <td>{{{$activity->location}}}</td>
          <td>{{{$activity->content}}}</td>
          <td class="text-right">{{number_format($activity->amount)}}</td>
          <td class="text-center">
            @if ($activity->credit_flag)
              <span class="glyphicon glyphicon-ok"></span>
            @endif
          </td>
          <td class="text-center">
            @if ($activity->special_flag)
              <span class="glyphicon glyphicon-ok"></span>
            @endif
          </td>
          <td class="text-center">{{HTML::datetime($activity->create_date)}}</td>
          <td class="text-center">
            {{Form::button('編集', array('class' => 'btn btn-primary open_edit'))}}
            {{Form::button('削除', array('class' => 'btn btn-default open_delete', 'data-toggle' => 'modal', 'data-target' => '#delete-modal'))}}
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="text-right">
      合計金額: {{number_format($activities->total_amount)}}
    </div>
    <div class="text-right">{{$activities->links()}}</div>
    {{Form::close()}}
  @else
    <p>データがありません。</p>
  @endif
@stop
