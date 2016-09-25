@extends('layouts.master')

@section('title')
変動収支
@stop

@section('include_header')
  {{HTML::style('assets/css/responsive_table.css')}}
  <script>
    $(function() {
      $("[name^=activity_date]").dateFormat();
      $("[name^=activity_date]").first().disableDatepickerFocus();
    });
  </script>
@stop

@section('content')
  @if (sizeof($activity_category_groups) > 1)
    {{Form::open(array('url' => 'cost/variable'))}}
      <table class="table table-striped table-hover">
        <colgroup>
          <col style="width: 15%" />
          <col style="width: 15%" />
          <col style="width: 10%" />
          <col style="width: 25%" />
          <col style="width: 25%" />
        </colgroup>
        <colgroup span="2" style="width: 5%">
        <thead>
          <tr>
            <th class="text-center">発生日</th>
            <th class="text-center">科目名</th>
            <th class="text-center">金額</th>
            <th class="text-center">場所</th>
            <th class="text-center">用途</th>
            <th class="text-center"><span class="glyphicon glyphicon-credit-card"></span></th>
            <th class="text-center"><span class="glyphicon glyphicon-star"></span></th>
          </tr>
        </thead>
        <tbody>
          @for ($i = 0; $i < $input_size; $i++)
            @if ($i == 0)
              <tr>
            @else
              <tr class="hidden-xs">
            @endif
            <td>
              @if (Agent::isDesktop())
                {{Form::text("activity_date[$i]", Input::old("activity_date[$i]"), array('class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off'))}}
              @else
                {{Form::date("activity_date[$i]", Input::old("activity_date[$i]"), array('class' => 'form-control', 'placeholder' => '月/日'))}}
              @endif
            </td>
            <td>
              {{Form::select("activity_category_group_id[$i]", $activity_category_groups, Input::old("activity_category_group_id[$i]"), array('class' => 'form-control'))}}
            </td>
            <td>
              {{Form::text("amount[$i]", Input::old("amount[$i]"), array('class' => 'form-control text-right', 'autocomplete' => 'off', 'pattern' => '[\-0-9]*'))}}
            </td>
            <td>
              {{Form::text("location[$i]", Input::old("location[$i]"), array('class' => 'form-control'))}}
            </td>
            <td>
              {{Form::text("content[$i]", Input::old("content[$i]"), array('class' => 'form-control'))}}
            </td>
            <td>
              <div class="text-center">
                {{Form::checkbox("credit_flag[$i]", '1', Input::old("credit_flag[$i]"))}}
              </div>
            </td>
            <td>
              <div class="text-center">
                {{Form::checkbox("special_flag[$i]", '1', Input::old("special_flag[$i]"))}}
              </div>
            </td>
          </tr>
          @endfor
        </tbody>
      </table>
      <div class="text-center">
        {{Form::submit('登録', array('class' => 'btn btn-primary'))}}
        {{Form::reset('リセット', array('class' => 'btn btn-default'))}}
      </div>
    {{Form::close()}}
  @else
    <p>科目が未登録です。{{link_to('/activityCategoryGroup', '科目ページ')}} からデータを登録して下さい。</p>
  @endif
@stop
