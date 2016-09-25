<div class="modal fade" tabindex="-1">
  <script>
    $(function() {
      $(document).on("click", "#update-{{$id}}", function() {
        doSubmit();
      });

      // @see https://github.com/naomichi-y/monelytics/issues/1
      $(this).on('hidden.bs.modal', function() {
        $(".modal").remove();
      });

      var doSubmit = function() {
        var creditFlag = $("#credit_flag").prop("checked") ? 1 : 0;
        var specialFlag = $("#special_flag").prop("checked") ? 1 : 0;

        $.put("/cost/variable/" + {{$id}},
          {
            activity_date: $("#activity_date").val(),
            activity_category_group_id: $("#activity_category_group_id").val(),
            amount: $("#amount", null, "int").val(),
            location: $("#location").val(),
            content: $("#content").val(),
            credit_flag: creditFlag,
            special_flag: specialFlag
          },
          function(data) {
            if (data["result"] == false) {
              $("#ajax-errors").removeClass("hide");
              $("#ajax-message-list > li").remove();

              $.each(data["errors"], function(key, value) {
                $("#ajax-message-list").append("<li>" + value + "</li>");
              });

            } else {
              location.reload();
            }
          },
          "json"
        );
      };

      $.enterCallback(doSubmit);

      $("[name=activity_date]").dateFormat();
    });
  </script>
  <div class="modal-dialog">
    <div class="modal-content">
      {{Form::open(array('class' => 'form-horizontal'))}}
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">編集</h4>
        </div>

        <div class="modal-body">
          <div class="alert alert-dismissable alert-warning hide" id="ajax-errors">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <ul id="ajax-message-list"></ul>
          </div>

          <div class="row">
            <div class="form-group">
              {{Form::label('activity_date', '発生日', array('class' => 'col-md-3 control-label'))}}
              <div class="col-md-3">
                @if (Agent::isDesktop())
                  {{Form::text('activity_date', HTML::date($activity->activity_date, false), array('class' => 'form-control date-picker', 'placeholder' => '月/日'))}}
                @else
                  {{Form::date('activity_date', str_replace('/', '-', HTML::date($activity->activity_date, false)), array('class' => 'form-control'))}}
                @endif
              </div>
            </div>

            <div class="form-group">
              {{Form::label('activity_category_group_id', '科目名', array('class' => 'col-md-3 control-label'))}}
              <div class="col-md-4">
                {{Form::select('activity_category_group_id', $activity_category_groups, $activity->activity_category_group_id, array('class' => 'form-control'))}}
              </div>
            </div>

            <div class="form-group">
              {{Form::label('amount', '金額', array('class' => 'col-md-3 control-label'))}}
              <div class="col-md-3">
                {{Form::text('amount', $activity->amount, array('class' => 'form-control', 'pattern' => '[\-0-9]*'))}}
              </div>
            </div>

            <div class="form-group">
              {{Form::label('location', '場所', array('class' => 'col-md-3 control-label'))}}
              <div class="col-md-6">
                {{Form::text('location', $activity->location, array('class' => 'form-control'))}}
              </div>
            </div>

            <div class="form-group">
              {{Form::label('content', '用途', array('class' => 'col-md-3 control-label'))}}
              <div class="col-md-6">
                {{Form::text('content', $activity->content, array('class' => 'form-control'))}}
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-3 control-label">
                <span class="glyphicon glyphicon-credit-card"></span>
              </label>
              <div class="col-md-6">
                <div class="checkbox-inline">
                  {{Form::checkbox('credit_flag', Monelytics\Models\Activity::CREDIT_FLAG_USE, $activity->credit_flag, array('id' => 'credit_flag'))}}
                  {{Form::label('credit_flag', 'クレジットカードを使用')}}
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-3 control-label">
                <span class="glyphicon glyphicon-star"></span>
              </label>
              <div class="col-md-6">
                <div class="checkbox-inline">
                  {{Form::checkbox('special_flag', Monelytics\Models\Activity::SPECIAL_FLAG_USE, $activity->special_flag, array('id' => 'special_flag'))}}
                  {{Form::label('special_flag', '特別収支')}}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          {{Form::button('更新', array('class' => 'btn btn-primary', 'id' => "update-$id"))}}
          {{Form::button('キャンセル', array('class' => 'btn btn-default', 'data-dismiss' => 'modal', 'aria-hidden' => 'true'))}}
        </div>
      {{Form::close()}}
    </div>
  </div>
</div>
