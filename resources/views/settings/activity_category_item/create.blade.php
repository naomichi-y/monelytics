<script>
    $(function() {
        var doSubmit = function doSubmit() {
            // クレジットカードの値取得
            var creditFlag = $("#credit_flag").prop("checked") ? 1 : 0;

            $.post("/settings/activityCategoryItem",
                {
                    activity_category_id: $("#activity_category_id").val(),
                    item_name: $("#item_name").val(),
                    content: $("#content").val(),
                    credit_flag: creditFlag
                },
                function(data) {
                    if (data["result"] == false) {
                        $("#ajax-errors").removeClass("d-none");
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
            {!! Form::open() !!}
                <div class="modal-header">
                    <h4 class="modal-title">小項目の登録</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-dismissible alert-warning d-none" id="ajax-errors">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
                        <ul class="mb-0" id="ajax-message-list"></ul>
                    </div>

                    <div class="row">
                        <div class="row mb-3">
                            {!! Form::label('activity_category_id', '大項目', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-4">
                                {!! Form::select('activity_category_id', $category_list, Html::requestValue('activity_category_id'), ['class' => 'form-select']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('item_name', '小項目名', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-6">
                                {!! Form::text('item_name', Html::requestValue('item_name'), ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('content', '用途', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-6">
                                {!! Form::textarea('content', Html::requestValue('content'), ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label">
                                <span class="bi bi-credit-card"></span>
                            </label>
                            <div class="col-md-6">
                                <div class="form-check form-check-inline">
                                    {!! Form::checkbox('credit_flag', App\Models\Activity::CREDIT_FLAG_USE, false, ['id' => 'credit_flag_use', 'class' => 'form-check-input']) !!}
                                    {!! Form::label('credit_flag_use', '使用', ['class' => 'form-check-label']) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    {!! Form::button('登録', ['class' => 'btn btn-primary', 'id' => 'create']) !!}
                    {!! Form::button('キャンセル', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal', 'aria-hidden' => 'true']) !!}
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
