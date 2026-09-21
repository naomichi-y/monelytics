@extends('layouts.master')

@section('title')
固定収支
@stop

@section('include_header')
    {!! Html::versionedStyle('assets/css/responsive_table.css') !!}
    {!! Html::versionedScript('assets/js/responsive_table.js') !!}
    <script>
        $(function() {
            $("#date_month").change(function() {
                $("#search_form").submit();
            });

            $('table').responsiveTable();
            $("[name^=activity_date]").dateFormat("[name^=activity_date]");
            $("[name^=activity_date]").first().disableDatepickerFocus();
        });
    </script>
@stop

@section('function')
    {!! Form::open(['url' => 'cost/constant/create', 'id' => 'search_form', 'method' => 'get']) !!}
        <div class="row g-2 align-items-center form-group-adjust">
            <div class="col-md-6 offset-md-6">
                {!! Form::select('date_month', $date_months, $selected_date_month, ['class' => 'form-select', 'id' =>  'date_month']) !!}
            </div>
        </div>
    {!! Form::close() !!}
@stop

@section('content')
    @include('layouts/delete_modal', ['action' => 'cost/constant'])

    @if (sizeof($constant_costs))
        {!! Form::open(['url' => 'cost/constant']) !!}
            <table class="table table-striped table-hover">
                {{-- 金額は単位の分だけ入力欄が狭くなる。元の 10% では 250,000 が
                     見切れる。広げすぎると空きすぎるので、7 桁がちょうど
                     収まるところで止める。 --}}
                <colgroup>
                    <col style="width: 15%" />
                    <col style="width: 18%" />
                    <col style="width: 15%" />
                    <col style="width: 13%" />
                    <col style="width: 24%" />
                    <col style="width: 5%" />
                    <col style="width: 10%" />
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">分類</th>
                        <th class="text-center">科目</th>
                        <th class="text-center">発生日</th>
                        <th class="text-center">金額</th>
                        <th class="text-center">用途</th>
                        <th class="text-center"><span class="bi bi-credit-card"></span></th>
                        <th class="text-center">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($constant_costs as $constant_category)
                        <?php $i = 0 ?>
                        @foreach ($constant_category['activity_category_items'] as $activity_category_item)
                            <tr data-id="{{$activity_category_item->activity_id}}">
                                @if ($i == 0)
                                    <td>{{{$constant_category['category_name']}}}</td>
                                @else
                                    <td></td>
                                @endif
                                <td>{{{$activity_category_item->item_name}}}</td>
                                <td>
                                    @if (Agent::isDesktop())
                                        {!! Form::text("activity_date[$selected_date_month][$activity_category_item->id]", Request::old("activity_date[$selected_date_month][$activity_category_item->id'", $activity_category_item->activity_date), ['class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off']) !!}
                                    @else
                                        {!! Form::date("activity_date[$selected_date_month][$activity_category_item->id]", Request::old("activity_date[$selected_date_month][$activity_category_item->id'", str_replace('/', '-', $activity_category_item->activity_date)), ['class' => 'form-control']) !!}
                                    @endif
                                </td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::number("amount[$selected_date_month][$activity_category_item->id]", Request::old("constant[$selected_date_month][$activity_category_item->id]", $activity_category_item->amount), ['class' => 'form-control text-end', 'autocomplete' => 'off', 'pattern' => '[\-0-9]*']) !!}
                                        <span class="input-group-text">円</span>
                                    </div>
                                </td>
                                <td>{!! Form::text("content[$selected_date_month][$activity_category_item->id]", Request::old("content[$selected_date_month][$activity_category_item->id]", $activity_category_item->content), ['class' => 'form-control']) !!}</td>
                                <td>
                                    <div class="text-center">
                                        @if ($activity_category_item->credit_flag === null)
                                            {!! Form::checkbox("credit_flag[$selected_date_month][$activity_category_item->id]", '1', $activity_category_item->default_credit_flag) !!}
                                        @else
                                            {!! Form::checkbox("credit_flag[$selected_date_month][$activity_category_item->id]", '1', $activity_category_item->credit_flag) !!}
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($activity_category_item->activity_id !== null)
                                        {!! Form::button('削除', ['class' => 'btn btn-primary open_delete', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal']) !!}
                                    @endif
                                </td>
                            </tr>
                            <?php $i++; ?>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            <div class="text-center">
                {!! Form::submit('更新', ['class' => 'btn btn-primary']) !!}
            </div>
        {!! Form::close() !!}
    @else
        <p>科目が未登録です。{!! link_to('/settings/activityCategoryItem', '科目ページ') !!} からデータを登録して下さい。</p>
    @endif
@stop
