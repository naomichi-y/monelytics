@extends('layouts.master')

@section('title')
日別集計
@stop

@section('include_header')
    {!! Html::style('assets/css/responsive_table.css') !!}
    {!! Html::script('assets/js/responsive_table.js') !!}
    <script>
        $(function() {
            $("#date_month").change(function() {
                $("#search_form").submit();
            });

            $("table").responsiveTable();

            // 詳細検索押下
            $("#open_condition").click(function() {
                $.get("/summary/daily/condition",
                    {
                        date_month: {!! Html::encodeJsJsonValue('date_month', date('Y-m')) !!},
                        begin_date: {!! Html::encodeJsJsonValue('begin_date') !!},
                        end_date: {!! Html::encodeJsJsonValue('end_date') !!},
                        activity_category_group_id: {!! Html::encodeJsJsonValue('activity_category_group_id', null, 'array') !!},
                        keyword: {!! Html::encodeJsJsonValue('keyword') !!},
                        credit_flag: {!! Html::encodeJsJsonValue('credit_flag') !!},
                        special_flag: {!! Html::encodeJsJsonValue('special_flag') !!}
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

                $.get("/cost/variable/" + activity_id + "/edit",
                    {},
                    function(data) {
                        $(data).modal();
                    }
                );
            });
        });
    </script>
@stop

@section('function')
    <div class="well">
        {!! Form::open(['url' => 'summary/daily', 'class' => 'form-horizontal', 'id' => 'search_form', 'method' => 'get']) !!}
            <div class="form-group form-group-sm form-group-adjust">
                <div class="col-md-8">
                    {!! Form::select('date_month', $month_list, Input::get('date_month', date('Y-m')), ['class' => 'form-control', 'id' => 'date_month']) !!}
                </div>
                <div class="col-md-4">
                    <a class="btn btn-info btn-sm" id="open_condition">詳細検索</a>
                </div>
            </div>
        {!! Form::close() !!}
    </div>
@stop

@section('content')
    @include('layouts/delete_modal', ['action' => 'cost/variable'])

    @if ($activities->total())
    {!! Form::open(['url' => 'summary/daily', 'method' => 'get', 'id' => 'list-form']) !!}
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
                        {!! Html::sortLabel('activity_date', '発生日', true) !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('activity_category_group_id', '科目名') !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('location', '場所') !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('content', '用途') !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('amount', '金額') !!}
                    </th>
                    <th class="text-center">
                        <span class="glyphicon glyphicon-credit-card"></span>
                        {!! Html::sortLabel('credit_flag', '') !!}
                    </th>
                    <th class="text-center">
                        <span class="glyphicon glyphicon-star"></span>
                        {!! Html::sortLabel('special_flag', '') !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('create_date', '登録日時') !!}
                    </th>
                    <th class="text-center">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($activities as $activity)
                <tr data-id="{{$activity->id}}">
                    <td class="text-center">
                        {{Html::date($activity->activity_date)}}
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
                    <td class="text-center">{{Html::datetime($activity->create_date)}}</td>
                    <td class="text-center">
                        {!! Form::button('編集', ['class' => 'btn btn-primary open_edit']) !!}
                        {!! Form::button('削除', ['class' => 'btn btn-default open_delete', 'data-toggle' => 'modal', 'data-target' => '#delete-modal']) !!}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="text-right">
            合計金額: {{number_format($activities->total_amount)}}
        </div>
        <div class="text-right">{!! $activities->render() !!}</div>
        {!! Form::close() !!}
    @else
        <p>データがありません。</p>
    @endif
@stop
