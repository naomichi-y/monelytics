@extends('layouts.master')

@section('title')
日別集計
@stop

@section('include_header')
    {!! Html::versionedStyle('assets/css/responsive_table.css') !!}
    {!! Html::versionedScript('assets/js/responsive_table.js') !!}
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
                    },
                    function(data) {
                        showModal(data);
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
                        showModal(data);
                    }
                );
            });
        });
    </script>
@stop

@section('function')
    <div class="card card-body">
        {!! Form::open(['url' => 'summary/daily', 'id' => 'search_form', 'method' => 'get']) !!}
            <div class="row g-2 align-items-center form-group-adjust">
                <div class="col-md-8">
                    {!! Form::select('date_month', $month_list, Request::get('date_month', date('Y-m')), ['class' => 'form-select', 'id' => 'date_month']) !!}
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
                <col style="width: 8%" />
                <col style="width: 4%" />
                <col style="width: 14%" />
                <col style="width: 14%" />
            </colgroup>
            <thead>
                <tr>
                    <th class="text-center">
                        {!! Html::sortLabel('activity_date', '発生日', true) !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('activity_category_group_id', '科目') !!}
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
                        <span class="bi bi-credit-card"></span>
                        {!! Html::sortLabel('credit_flag', '') !!}
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
                    <td class="text-end">{!! Html::amount($activity->amount) !!}</td>
                    <td class="text-center">
                        @if ($activity->credit_flag)
                            <span class="bi bi-check-lg"></span>
                        @endif
                    </td>
                    <td class="text-center">{{Html::datetime($activity->create_date)}}</td>
                    <td class="text-center">
                        {!! Form::button('編集', ['class' => 'btn btn-primary open_edit']) !!}
                        {!! Form::button('削除', ['class' => 'btn btn-secondary open_delete', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal']) !!}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="text-end">
            合計金額: {!! Html::amount($activities->total_amount) !!}
        </div>
        {{-- 番号は出さず「前へ / 次へ」だけ。ページ数が多く、番号を並べても
             行が埋まるだけで選べないため。 --}}
        <div class="text-end">{!! $activities->render('pagination::simple-bootstrap-5') !!}</div>
        {!! Form::close() !!}
    @else
        <p>データがありません。</p>
    @endif
@stop
