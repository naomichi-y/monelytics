@extends('layouts.master')

@section('title')
科目カテゴリ
@stop

@section('include_header')
  {{HTML::style('assets/css/responsive_table.css')}}
  {{HTML::script('assets/js/responsive_table.js')}}
  <script>
    $(function() {
      $("table:eq(0)").responsiveTable();
      $("table:eq(1)").responsiveTable();

      // 科目の登録押下
      $("#open_create").click(function() {
        $.get("/settings/activityCategory/create",
          {},
          function(data) {
            $(data).modal();
          }
        );
      });

      // テーブル行のソートを有効化
      $("#variable_sortable").sortable({
        stop: function() {
          sendSort('variable_sortable_ids[]');
        }
      });

      $("#constant_sortable").sortable({
        stop: function() {
          sendSort('constant_sortable_ids[]');
        }
      });

      // ソート結果を送信
      var sendSort = function(name) {
        var ids = [];

        $("[name='" + name + "']").each(function() {
          ids.push(this.value);
        });

        $.post("/settings/activityCategory/sort",
          {
            ids: ids
          }
        );
      };

      $("#variable_sortable").disableSelection();
      $("#constant_sortable").disableSelection();

      // 編集押下
      $(".open_edit").click(function() {
        var id = $(this).parent().parent().attr("data-id");

        $.get("/settings/activityCategory/edit",
          {
            id: id
          },
          function(data) {
            $(data).modal();
          }
        );
      });

      // 科目の確認を押下
      $(".show-category-group").click(function() {
        var id = $(this).parent().parent().attr("data-id");
        window.location.href = "/settings/activityCategoryGroup?activity_category_id=" + id;
      });
    });
  </script>
@stop

@section('function')
  <div class="well">
    <a class="btn btn-info btn-sm" id="open_create">科目カテゴリの登録</a>
  </div>
@stop

@section('content')
  @include('layouts/delete_modal', array('action' => 'settings/activityCategory/delete'))

  <h2>変動収支</h2>
  @include('settings/activity_category/list', array('id' => 'variable', 'activity_categories' => $variable_categories))

  <h2>固定収支</h2>
  @include('settings/activity_category/list', array('id' => 'constant', 'activity_categories' => $constant_categories))
@stop
