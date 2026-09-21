<div id="update-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <script>
        $(function() {
            var doSubmit = function doSubmit() {
                // 変動・固定の値取得
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

                $.put("/settings/activityCategory/{{$id}}",
                    {
                        category_name: $("#category_name").val(),
                        content: $("#content").val(),
                        cost_type: costType,
                        balance_type: balanceType
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

            $(document).on("click", "#update-{{$id}}", function() {
                doSubmit();
            });
        });
    </script>

    <div class="modal-dialog">
        <div class="modal-content">
            {!! Form::open() !!}
                <div class="modal-header">
                    <h4 class="modal-title">分類の編集</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-dismissible alert-warning d-none" id="ajax-errors">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
                        <ul class="mb-0" id="ajax-message-list"></ul>
                    </div>

                    <div class="row">
                        <div class="row mb-3">
                            {!! Form::label('category_name', '分類名', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-6">
                                {!! Form::text('category_name', Request::input('category_name', $activity_category->category_name), ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('content', '用途', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-6">
                                {!! Form::textarea('content', Request::input('content', $activity_category->content), ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('', '変動・固定', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-6">
                                <div class="form-check form-check-inline">
                                    {!! Form::radio('cost_type', App\Models\ActivityCategory::COST_TYPE_VARIABLE, $cost_type_variable, ['id' => 'cost_type_variable', 'class' => 'form-check-input']) !!}
                                    {!! Form::label('cost_type_variable', '変動収支', ['class' => 'form-check-label']) !!}
                                </div>
                                <div class="form-check form-check-inline">
                                    {!! Form::radio('cost_type', App\Models\ActivityCategory::COST_TYPE_CONSTANT, $cost_type_constant, ['id' => 'cost_type_constant', 'class' => 'form-check-input']) !!}
                                    {!! Form::label('cost_type_constant', '固定収支', ['class' => 'form-check-label']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('', '収支タイプ', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-6">
                                <div class="form-check form-check-inline">
                                    {!! Form::radio('balance_type', App\Models\ActivityCategory::BALANCE_TYPE_INCOME, $balance_type_income, ['id' => 'balance_type_income', 'class' => 'form-check-input']) !!}
                                    {!! Form::label('balance_type_income', '収入', ['class' => 'form-check-label']) !!}
                                </div>
                                <div class="form-check form-check-inline">
                                    {!! Form::radio('balance_type', App\Models\ActivityCategory::BALANCE_TYPE_EXPENSE, $balance_type_expense, ['id' => 'balance_type_expense', 'class' => 'form-check-input']) !!}
                                    {!! Form::label('balance_type_expense', '支出', ['class' => 'form-check-label']) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    {!! Form::button('更新', ['class' => 'btn btn-primary', 'id' => "update-$id"]) !!}
                    {!! Form::button('キャンセル', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal', 'aria-hidden' => 'true']) !!}
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
