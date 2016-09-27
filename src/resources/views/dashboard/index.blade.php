@extends('layouts.master')

@section('title')
ダッシュボード
@stop

@section('include_header')
  <script>
    $(function() {
      $("#activity_date").disableDatepickerFocus();

      // 今月の収支状況を表示
      $.get("/gadget/activity-status",
        {},
        function(data) {
          $("#activity_status").html(data);
        }
      );

      // アクティビティを表示
      $.get("/gadget/activity-graph",
        {},
        function(data) {
          $("#activity_graph").html(data);
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
      {!! Form::open(array('url' => 'cost/variable', 'class' => 'form-horizontal')) !!}
        <div class="well">
          <fieldset>
            <div class="form-group">
              {!! Form::label('activity_date', '発生日', array('class' => 'col-md-3 control-label')) !!}
              <div class="col-md-6">
                @if (Agent::isDesktop())
                  {!! Form::text("activity_date[0]", date('Y/m/d'), array('class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off', 'id' => 'activity_date')) !!}
                @else
                  {!! Form::date("activity_date[0]", date('Y-m-d'), array('class' => 'form-control', 'placeholder' => '月/日')) !!}
                @endif
              </div>
            </div>

            <div class="form-group">
              {!! Form::label('activity_category_group_id', '科目名', array('class' => 'col-md-3 control-label')) !!}
              <div class="col-md-8">
                {!! Form::select('activity_category_group_id[0]', $activity_category_groups, '', array('class' => 'form-control', 'id' => 'activity_category_group_id')) !!}
              </div>
            </div>

            <div class="form-group">
              {!! Form::label('amount', '金額', array('class' => 'col-md-3 control-label')) !!}
              <div class="col-md-5">
                {!! Form::text('amount[0]', '', array('class' => 'form-control text-right', 'id' => 'amount', 'autocomplete' => 'off', 'pattern' => '[\-0-9]*')) !!}
              </div>
            </div>

            <div class="form-group">
              {!! Form::label('location', '場所', array('class' => 'col-md-3 control-label')) !!}
              <div class="col-md-8">
                {!! Form::text('location[0]', '', array('class' => 'form-control', 'id' => 'location')) !!}
              </div>
            </div>

            <div class="form-group">
              {!! Form::label('content', '用途', array('class' => 'col-md-3 control-label')) !!}
              <div class="col-md-8">
                {!! Form::text('content[0]', '', array('class' => 'form-control', 'id' => 'content')) !!}
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-3 control-label">
                <span class="glyphicon glyphicon-credit-card"></span>
              </label>
              <div class="col-md-8">
                <div class="checkbox-inline">
                  {!! Form::checkbox('credit_flag[0]', App\Models\Activity::CREDIT_FLAG_USE, false, array('id' => 'credit_flag')) !!}
                  {!! Form::label('credit_flag', 'クレジットカード使用') !!}
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-3 control-label">
                <span class="glyphicon glyphicon-star"></span>
              </label>
              <div class="col-md-8">
                <div class="checkbox-inline">
                  {!! Form::checkbox('special_flag[0]', App\Models\Activity::SPECIAL_FLAG_USE, false, array('id' => 'special_flag')) !!}
                  {!! Form::label('special_flag', '特別収支') !!}
                </div>
              </div>
            </div>

            <div class="form-group text-center">
              {!! Form::submit('登録', array('class' => 'btn btn-primary')) !!}
            </div>
          </fieldset>
        </div>
      {!! Form::close() !!}
    </div>
    <div class="col-md-8">
      <h2>今月の収支状況</h2>
      <div id="activity_status"></div>
      <h2>アクティビティ</h2>
      <div id="activity_graph"></div>
      <h2>最近の収支履歴</h2>
      <div id="activity_history"></div>
    </div>
  </div>
@stop
