@extends('layouts.master')

@section('title')
科目
@stop

@section('include_header')
    {!! Html::style('assets/css/responsive_table.css') !!}
    {!! Html::script('assets/js/responsive_table.js') !!}
    <script>
        $(function() {
            $("table").responsiveTable();

            $("#search_activity_category_id").change(function() {
                $("#search-form").submit();
            });

            // 科目の登録押下
            $("#open_create").click(function() {
                $.get("/settings/activityCategoryGroup/create",
                    {
                        activity_category_id: $("#search_activity_category_id").val()
                    },
                    function(data) {
                        $(data).modal();
                    }
                );
            });

            // テーブル行のソートを有効化
            $("#sortable").sortable({
                stop: function() {
                    var ids = [];

                    $("[name='sortable_ids[]']").each(function() {
                        ids.push(this.value);
                    });

                    $.post("/settings/activityCategoryGroup/sort",
                        {
                            ids: ids
                        }
                    );
                }
            });

            $("#sortable").disableSelection();

            // 編集押下
            $(".open_edit").click(function() {
                var row = $(".open_edit").index(this);
                var id = $("#sortable tr:eq(" + row + ")").attr("data-id");

                $.get("/settings/activityCategoryGroup/" + id + "/edit",
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
        <div class="row">
            <div class="form-group form-group-sm form-group-adjust">
                <div class="col-md-8">
                    {!! Form::open(['url' => 'settings/activityCategoryGroup', 'method' => 'get', 'class' => 'form-horizontal', 'id' => 'search-form']) !!}
                        {!! Form::select('activity_category_id', $activity_category_list, Request::input('activity_category_id'), ['class' => 'form-control', 'id' => 'search_activity_category_id']) !!}
                 {!! Form::close() !!}
                </div>
                <div class="col-md-4">
                    <a class="btn btn-info btn-sm" id="open_create">科目の登録</a>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    @include('layouts/delete_modal', ['action' => 'settings/activityCategoryGroup'])

    @if (sizeof($activity_category_groups))
        <table class="table table-striped table-hover">
            <colgroup>
                <col style="width: 20%" />
                <col style="width: 40%" />
                <col style="width: 5%" />
                <col style="width: 15%" />
                <col style="width: 5%" />
                <col style="width: 15%" />
            </colgroup>
            <thead>
                <tr>
                    <th class="text-center">科目名</th>
                    <th class="text-center">用途</th>
                    <th class="text-center"><span class="glyphicon glyphicon-credit-card"></span></th>
                    <th class="text-center">登録日時</th>
                    <th class="text-center hidden-xs">表示順序</th>
                    <th class="text-center">操作</th>
                </tr>
            </thead>
            <tbody id="sortable">
                @foreach ($activity_category_groups as $activity_category_group)
                    <tr data-id="{{$activity_category_group->id}}">
                        <td>{{{$activity_category_group->group_name}}}</td>
                        <td>{{nl2br(e($activity_category_group->content))}}</td>
                        <td class="text-center">
                            @if ($activity_category_group->credit_flag)
                                <span class="glyphicon glyphicon-ok"></span>
                            @endif
                        </td>
                        <td class="text-center">{{Html::datetime($activity_category_group->create_date)}}</td>
                        <td class="text-center sort-col hidden-xs"><span class="glyphicon glyphicon-sort"></span></td>
                        <td class="text-center">
                            {!! Form::hidden('sortable_ids[]', $activity_category_group->id) !!}
                            {!! Form::button('編集', ['class' => 'btn btn-primary open_edit']) !!}
                            {!! Form::button('削除', ['class' => 'btn btn-default open_delete', 'data-toggle' => 'modal', 'data-target' => '#delete-modal']) !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif (!Request::has('activity_category_id'))
        <p>科目カテゴリを選択して下さい。</p>
    @else
        <p>データがありません。</p>
    @endif
@stop
