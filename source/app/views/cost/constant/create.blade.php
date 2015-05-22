@extends('layouts.master')

@section('title')
固定収支
@stop

@section('include_header')
  {{HTML::style('assets/css/responsive_table.css')}}
  {{HTML::script('assets/js/responsive_table.js')}}
  <script>
    $(function() {
      $("#date_month").change(function() {
        $("#search_form").submit();
      });

      $('table').responsiveTable();
      $("[name^=activity_date]").dateFormat();
      $("[name^=activity_date]").first().disableDatepickerFocus();
    });
  </script>
@stop

@section('function')
  <div class="well bs-component">
    {{Form::open(array('url' => 'cost/constant/create', 'class' => 'form-horizontal', 'id' => 'search_form', 'method' => 'get'))}}
      <div class="form-group form-group-sm form-group-adjust">
        <div class="col-md-6 col-md-offset-6">
          {{Form::select('date_month', $date_months, $selected_date_month, array('class' => 'form-control', 'id' =>  'date_month'))}}
        </div>
      </div>
    {{Form::close()}}
  </div>
@stop

@section('content')
  @include('layouts/delete_modal', array('action' => 'cost/constant/delete'))

  @if (sizeof($constant_costs))
    {{Form::open(array('url' => 'cost/constant/create'))}}
      <table class="table table-striped table-hover">
        <colgroup>
          <col style="width: 15%" />
          <col style="width: 20%" />
          <col style="width: 15%" />
          <col style="width: 10%" />
          <col style="width: 25%" />
          <col style="width: 5%" />
          <col style="width: 10%" />
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">科目カテゴリ名</th>
            <th class="text-center">科目名</th>
            <th class="text-center">発生日</th>
            <th class="text-center">金額</th>
            <th class="text-center">用途</th>
            <th class="text-center"><span class="glyphicon glyphicon-credit-card"></span></th>
            <th class="text-center">操作</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($constant_costs as $constant_category)
            {{-- */ $i = 0 /* --}}
            @foreach ($constant_category['activity_category_groups'] as $activity_category_group)
              <tr data-id="{{$activity_category_group->activity_id}}">
                @if ($i == 0)
                  <td>{{{$constant_category['category_name']}}}</td>
                @else
                  <td></td>
                @endif
                <td>{{{$activity_category_group->group_name}}}</td>
                <td>
                  @if (Agent::isDesktop())
                    {{Form::text("activity_date[$selected_date_month][$activity_category_group->id]", Input::old("activity_date[$selected_date_month][$activity_category_group->id'", $activity_category_group->activity_date), array('class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off'))}}
                  @else
                    {{Form::date("activity_date[$selected_date_month][$activity_category_group->id]", Input::old("activity_date[$selected_date_month][$activity_category_group->id'", str_replace('/', '-', $activity_category_group->activity_date)), array('class' => 'form-control'))}}
                  @endif
                </td>
                <td>{{Form::number("amount[$selected_date_month][$activity_category_group->id]", Input::old("constant[$selected_date_month][$activity_category_group->id]", $activity_category_group->amount), array('class' => 'form-control text-right', 'autocomplete' => 'off'))}}</td>
                <td>{{Form::text("content[$selected_date_month][$activity_category_group->id]", Input::old("content[$selected_date_month][$activity_category_group->id]", $activity_category_group->content), array('class' => 'form-control'))}}</td>
                <td>
                  <div class="text-center">
                    @if ($activity_category_group->credit_flag === null)
                      {{Form::checkbox("credit_flag[$selected_date_month][$activity_category_group->id]", '1', $activity_category_group->default_credit_flag)}}
                    @else
                      {{Form::checkbox("credit_flag[$selected_date_month][$activity_category_group->id]", '1', $activity_category_group->credit_flag)}}
                    @endif
                  </div>
                </td>
                <td class="text-center">
                  @if ($activity_category_group->activity_id !== null)
                    {{Form::button('削除', array('class' => 'btn btn-primary open_delete', 'data-toggle' => 'modal', 'data-target' => '#delete-modal'))}}
                  @endif
                </td>
              </tr>
              {{-- */ $i++ /* --}}
            @endforeach
          @endforeach
        </tbody>
      </table>
      <div class="text-center">
        {{Form::submit('更新', array('class' => 'btn btn-primary'))}}
      </div>
    {{Form::close()}}
  @else
    <p>科目が未登録です。{{link_to('/activityCategoryGroup', '科目ページ')}} からデータを登録して下さい。</p>
  @endif
@stop
