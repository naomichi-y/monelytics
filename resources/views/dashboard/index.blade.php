@extends('layouts.master')

@section('title')
ダッシュボード
@stop

@section('include_header')
    <script>
        $(function() {
            $("#activity_date").disableDatepickerFocus();

            // 今月の変動支出を表示
            $.get("/gadget/variable-expense",
                {},
                function(data) {
                    $("#variable_expense").html(data);
                }
            );

            // 最近の収支履歴を表示
            $.get("/gadget/activity-history",
                {},
                function(data) {
                    $("#activity_history").html(data);
                }
            );
        });
    </script>
@stop

@section('content')
    <div class="row">
        <div class="col-md-4">
            <h2>かんたん入力</h2>
            {!! Form::open(['url' => 'cost/variable']) !!}
                <div class="card card-body">
                    <fieldset>
                        <div class="row mb-3">
                            {!! Form::label('activity_date', '発生日', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-6">
                                @if (Agent::isDesktop())
                                    {!! Form::text("activity_date[0]", date('Y/m/d'), ['class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off', 'id' => 'activity_date']) !!}
                                @else
                                    {!! Form::date("activity_date[0]", date('Y-m-d'), ['class' => 'form-control', 'placeholder' => '月/日']) !!}
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('activity_category_item_id', '科目', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-8">
                                {!! Form::select('activity_category_item_id[0]', $activity_category_items, '', ['class' => 'form-select', 'id' => 'activity_category_item_id']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('amount', '金額', ['class' => 'col-md-3 col-form-label']) !!}
                            {{-- 単位とスピナーの分だけ入力欄が狭くなる。col-md-5 では
                                 7 桁の頭が切れる。 --}}
                            <div class="col-md-6">
                                <div class="input-group">
                                    {!! Form::number('amount[0]', '', ['class' => 'form-control text-end', 'id' => 'amount', 'autocomplete' => 'off', 'pattern' => '[\-0-9]*']) !!}
                                    <span class="input-group-text">円</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('location', '場所', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-8">
                                {!! Form::text('location[0]', '', ['class' => 'form-control', 'id' => 'location']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('content', '用途', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-8">
                                {!! Form::text('content[0]', '', ['class' => 'form-control', 'id' => 'content']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label">
                                <span class="bi bi-credit-card"></span>
                            </label>
                            <div class="col-md-8">
                                <div class="form-check form-check-inline">
                                    {!! Form::checkbox('credit_flag[0]', App\Models\Activity::CREDIT_FLAG_USE, false, ['id' => 'credit_flag', 'class' => 'form-check-input']) !!}
                                    {!! Form::label('credit_flag', 'クレジットカード使用', ['class' => 'form-check-label']) !!}
                                </div>
                            </div>
                        </div>


                        <div class="text-center">
                            {!! Form::submit('登録', ['class' => 'btn btn-primary']) !!}
                        </div>
                    </fieldset>
                </div>
            {!! Form::close() !!}
        </div>
        <div class="col-md-8">
            <h2>今月の変動支出</h2>
            <div id="variable_expense"></div>
            <h2>最近の収支履歴</h2>
            <div id="activity_history"></div>
        </div>
    </div>
@stop
