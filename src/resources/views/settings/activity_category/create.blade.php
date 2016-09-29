<script>
    $(function() {
        var doSubmit = function doSubmit() {
            // 科目タイプの値取得
            var costType = "";

            if ($("#cost_type_variable").prop("checked")) {
                costType = $("#cost_type_variable").val();
            } else if ($("#cost_type_constant").prop("checked")) {
                costType = $("#cost_type_constant").val();
            }

            // 収支タイプの値取得
            var balanceType = "";

            if ($("#balance_type_expense").prop("checked")) {
                balanceType = $("#balance_type_expense").val();
            } else if ($("#balance_type_income").prop("checked")) {
                balanceType = $("#balance_type_income").val();
            }

            $.post("/settings/activityCategory",
                {
                    category_name: $("#category_name").val(),
                    content: $("#content").val(),
                    cost_type: costType,
                    balance_type: balanceType
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
            {!! Form::open(['class' => 'form-horizontal']) !!}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">科目カテゴリの登録</h4>
                </div>

                <div class="modal-body">
                    <div class="alert alert-dismissable alert-warning hide" id="ajax-errors">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <ul id="ajax-message-list"></ul>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            {!! Form::label('category_name', '科目カテゴリ名', ['class' => 'col-md-3 control-label']) !!}
                            <div class="col-md-6">
                                {!! Form::text('category_name', Input::get('category_name'), ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            {!! Form::label('content', '用途', ['class' => 'col-md-3 control-label']) !!}
                            <div class="col-md-6">
                                {!! Form::textarea('content', Input::get('content'), ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            {!! Form::label('', '科目タイプ', ['class' => 'col-md-3 control-label']) !!}
                            <div class="col-md-6">
                                <div class="radio-inline">
                                    {!! Form::radio('cost_type', App\Models\ActivityCategory::COST_TYPE_VARIABLE, false, ['id' => 'cost_type_variable']) !!}
                                    {!! Form::label('cost_type_variable', '変動収支') !!}
                                </div>
                                <div class="radio-inline">
                                    {!! Form::radio('cost_type', App\Models\ActivityCategory::COST_TYPE_CONSTANT, false, ['id' => 'cost_type_constant']) !!}
                                    {!! Form::label('cost_type_constant', '固定収支') !!}
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            {!! Form::label('', '収支タイプ', ['class' => 'col-md-3 control-label']) !!}
                            <div class="col-md-6">
                                <div class="radio-inline">
                                    {!! Form::radio('balance_type', App\Models\ActivityCategory::BALANCE_TYPE_INCOME, false, ['id' => 'balance_type_income']) !!}
                                    {!! Form::label('balance_type_income', '収入') !!}
                                </div>
                                <div class="radio-inline">
                                    {!! Form::radio('balance_type', App\Models\ActivityCategory::BALANCE_TYPE_EXPENSE, false, ['id' => 'balance_type_expense']) !!}
                                    {!! Form::label('balance_type_expense', '支出') !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    {!! Form::button('登録', ['class' => 'btn btn-primary', 'id' => 'create']) !!}
                    {!! Form::button('キャンセル', ['class' => 'btn btn-default', 'data-dismiss' => 'modal', 'aria-hidden' => 'true']) !!}
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
