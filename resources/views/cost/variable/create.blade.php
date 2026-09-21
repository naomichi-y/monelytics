@extends('layouts.master')

@section('title')
変動収支
@stop

@section('include_header')
    {!! Html::versionedStyle('assets/css/responsive_table.css') !!}
    <script>
        $(function() {
            $("[name^=activity_date]").dateFormat("[name^=activity_date]");
            $("[name^=activity_date]").first().disableDatepickerFocus();
        });
    </script>
@stop

@section('content')
    @if (sizeof($activity_category_items) > 1)
        {!! Form::open(['url' => 'cost/variable']) !!}
            <table class="table table-striped table-hover">
                {{-- 金額は単位の分だけ入力欄が狭くなる。元の 11% では 7 桁が
                     見切れる。広げすぎると常用する 4、5 桁に対して空きすぎる
                     ので、7 桁がちょうど収まるところで止める。 --}}
                <colgroup>
                    <col style="width: 15%" />
                    <col style="width: 15%" />
                    <col style="width: 13%" />
                    <col style="width: 26%" />
                    <col style="width: 26%" />
                    <col style="width: 5%" />
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">発生日</th>
                        <th class="text-center">小項目</th>
                        <th class="text-center">金額</th>
                        <th class="text-center">場所</th>
                        <th class="text-center">用途</th>
                        <th class="text-center"><span class="bi bi-credit-card"></span></th>
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 0; $i < $input_size; $i++)
                        @if ($i == 0)
                            <tr>
                        @else
                            <tr class="d-none d-md-table-row">
                        @endif
                        <td>
                            @if (Agent::isDesktop())
                                {!! Form::text("activity_date[$i]", Request::old("activity_date[$i]"), ['class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off']) !!}
                            @else
                                {!! Form::date("activity_date[$i]", Request::old("activity_date[$i]"), ['class' => 'form-control', 'placeholder' => '月/日']) !!}
                            @endif
                        </td>
                        <td>
                            {!! Form::select("activity_category_item_id[$i]", $activity_category_items, Request::old("activity_category_item_id[$i]"), ['class' => 'form-select']) !!}
                        </td>
                        <td>
                            <div class="input-group">
                                {!! Form::number("amount[$i]", Request::old("amount[$i]"), ['class' => 'form-control text-end', 'autocomplete' => 'off', 'pattern' => '[\-0-9]*']) !!}
                                <span class="input-group-text">円</span>
                            </div>
                        </td>
                        <td>
                            {!! Form::text("location[$i]", Request::old("location[$i]"), ['class' => 'form-control']) !!}
                        </td>
                        <td>
                            {!! Form::text("content[$i]", Request::old("content[$i]"), ['class' => 'form-control']) !!}
                        </td>
                        <td>
                            <div class="text-center">
                                {!! Form::checkbox("credit_flag[$i]", '1', Request::old("credit_flag[$i]")) !!}
                            </div>
                        </td>
                    </tr>
                    @endfor
                </tbody>
            </table>
            <div class="text-center">
                {!! Form::submit('登録', ['class' => 'btn btn-primary']) !!}
                {!! Form::reset('リセット', ['class' => 'btn btn-secondary']) !!}
            </div>
        {!! Form::close() !!}
    @else
        <p>小項目が未登録です。{!! link_to('/settings/activityCategoryItem', '小項目ページ') !!} からデータを登録して下さい。</p>
    @endif
@stop
