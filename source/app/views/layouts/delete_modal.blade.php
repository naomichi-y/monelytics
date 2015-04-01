<script>
  $(function() {
    // 削除モーダルの表示
    $(".open_delete").click(function() {
      var id = $(this).parent().parent().attr("data-id");

      $("#id").val(id);
      window.location.href = "#delete-modal";
    });

    $("#delete").click(function() {
      $("#delete_form").submit();
    });
  });
</script>
<div id="delete-modal" class="modal fade" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      {{Form::open(array('url' => $action, 'id' => 'delete_form'))}}
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">削除の確認</h4>
        </div>
        <div class="modal-body">
          <p>対象レコードを削除します。よろしいですか?</p>
        </div>
        <div class="modal-footer">
          {{Form::hidden('id', '', array('id' => 'id'))}}
          {{Form::button('削除', array('class' => 'btn btn-primary', 'id' => 'delete'))}}
          {{Form::button('キャンセル', array('class' => 'btn btn-default', 'data-dismiss' => 'modal', 'aria-hidden' => 'true'))}}
        </div>
      {{Form::close()}}
    </div>
  </div>
</div>
