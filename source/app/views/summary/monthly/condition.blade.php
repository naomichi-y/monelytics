<script>
  $(function() {
    $(document).on("change", "#date_month", function() {
      $("#begin_date").val('');
      $("#end_date").val('');
    });

    $(document).on("change", "#begin_date, #end_date", function() {
      if ($(this).val()) {
        $("#date_month").prop("selectedIndex", 0);
      }
    });

    $(document).on("click", "#clear_date_range", function() {
      $("#begin_date").val("");
      $("#end_date").val("");
      $("#date_month").prop("disabled", false);
    });

    // 詳細検索モーダル (リセット押下)
    $(document).on("click", "#reset", function() {
      $("input[type='text'], select")
        .val("")
        .removeAttr("selected");
      $("#date_month").prop("selectedIndex", 0);
    });

    $("#begin_date").dateFormat();
    $("#end_date").dateFormat();
  });
</script>
<div id="search_modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      {{Form::open(array('url' => 'summary/monthly', 'method' => 'get', 'class' => 'form-horizontal'))}}
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">検索条件</h4>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="form-group">
              {{Form::label('date_month', '月指定', array('class' => 'col-md-3 control-label'))}}
              <div class="col-md-3">
                {{Form::select('date_month', $month_list, Input::get('date_month'), array('class' => 'form-control'))}}
              </div>
            </div>

            <div class="form-group">
              {{Form::label('begin_date', '日付範囲指定', array('class' => 'col-md-3 control-label'))}}
              @if (Agent::isDesktop())
                <div class="col-md-3">
                  {{Form::text('begin_date', Input::get('begin_date'), array('class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off'))}}
                </div>
                {{Form::label('end_date', '〜', array('class' => 'col-md-1 control-label label-range-text'))}}
                <div class="col-md-3">
                  {{Form::text('end_date', Input::get('end_date'), array('class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off'))}}
                </div>
              @else
                <div class="col-md-3">
                  {{Form::date('begin_date', Input::get('begin_date'), array('class' => 'form-control'))}}
                </div>
                {{Form::label('end_date', '〜', array('class' => 'col-md-1 control-label label-range-text'))}}
                <div class="col-md-3">
                  {{Form::date('end_date', Input::get('end_date'), array('class' => 'form-control'))}}
                </div>
              @endif
              {{Form::button('クリア', array('class' => 'btn btn-default', 'id' => 'clear_date_range'))}}
            </div>
          </div>
        </div>
        <div class="modal-footer">
          {{Form::submit('検索', array('class' => 'btn btn-primary'))}}
          {{Form::button('リセット', array('class' => 'btn btn-default', 'id' => 'reset'))}}
          {{Form::button('閉じる', array('class' => 'btn btn-default', 'data-dismiss' => 'modal', 'aria-hidden' => 'true'))}}
        </div>
      {{Form::close()}}
    </div>
  </div>
</div>
