<script>
  $(function() {
    var doSubmit = function doSubmit() {
      // クレジットカードの値取得
      var creditFlag = $("#credit_flag").prop("checked") ? 1 : 0;

      $.post("/activityCategoryGroup/create",
        {
          activity_category_id: $("#activity_category_id").val(),
          group_name: $("#group_name").val(),
          content: $("#content").val(),
          credit_flag: creditFlag
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
    }

    $.enterCallback(doSubmit);

    $(document).on("click", "#create", function() {
      doSubmit();
    });
  });
</script>
<div id="create-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      {{Form::open(array('url' => 'activityCategoryGroup/create', 'class' => 'form-horizontal'))}}
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">科目の登録</h4>
        </div>

        <div class="modal-body">
          <div class="alert alert-dismissable alert-warning hide" id="ajax-errors">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <ul id="ajax-message-list"></ul>
          </div>

          <div class="row">
            <div class="form-group">
              {{Form::label('activity_category_id', '科目カテゴリ', array('class' => 'col-md-3 control-label'))}}
              <div class="col-md-4">
                {{Form::select('activity_category_id', $category_list, Input::get('activity_category_id'), array('class' => 'form-control'))}}
              </div>
            </div>

            <div class="form-group">
              {{Form::label('group_name', '科目名', array('class' => 'col-md-3 control-label'))}}
              <div class="col-md-6">
                {{Form::text('group_name', Input::get('group_name'), array('class' => 'form-control'))}}
              </div>
            </div>

            <div class="form-group">
              {{Form::label('content', '用途', array('class' => 'col-md-3 control-label'))}}
              <div class="col-md-6">
                {{Form::textarea('content', Input::get('content'), array('class' => 'form-control'))}}
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-3 control-label">
                <span class="glyphicon glyphicon-credit-card"></span>
              </label>
              <div class="col-md-6">
                <div class="checkbox-inline">
                  {{Form::checkbox('credit_flag', Activity::CREDIT_FLAG_USE, false, array('id' => 'credit_flag_use'))}}
                  {{Form::label('credit_flag_use', '使用')}}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          {{Form::button('登録', array('class' => 'btn btn-primary', 'id' => 'create'))}}
          {{Form::button('キャンセル', array('class' => 'btn btn-default', 'data-dismiss' => 'modal', 'aria-hidden' => 'true'))}}
        </div>
      {{Form::close()}}
    </div>
  </div>
</div>
