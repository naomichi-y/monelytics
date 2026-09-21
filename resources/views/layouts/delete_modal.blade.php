<script>
    $(function() {
        var id;

        // 開くのは data-bs-toggle に任せる。ここでは対象の行を覚えるだけ。
        $(".open_delete").click(function() {
            id = $(this).closest("[data-id]").attr("data-id");
        });

        $("#delete").click(function() {
            $("#delete_form").attr('action', "/{{$action}}/" + id);
            $("#delete_form").submit();
        });
    });
</script>

<div id="delete-modal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            {!! Form::open(['id' => 'delete_form', 'method' => 'delete']) !!}
                <div class="modal-header">
                    <h4 class="modal-title">削除の確認</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body">
                    <p>対象レコードを削除します。よろしいですか?</p>
                </div>
                <div class="modal-footer">
                    {!! Form::button('削除', ['class' => 'btn btn-primary', 'id' => 'delete']) !!}
                    {!! Form::button('キャンセル', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal', 'aria-hidden' => 'true']) !!}
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
