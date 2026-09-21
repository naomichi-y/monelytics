<div class="modal fade" tabindex="-1">
    <script>
        $(function() {
            var active = true;

            $(document).on("click", "#update-{{$id}}", function() {
                doSubmit();
            });

            // @see https://github.com/naomichi-y/monelytics/issues/1
            // 取り除きは showModal が行う。ここでは閉じたあとの送信を止める。
            $(document).on('hidden.bs.modal', function() {
                active = false;
            });

            var doSubmit = function() {
                if (!active) {
                  return;
                }

                var creditFlag = $("#credit_flag").prop("checked") ? 1 : 0;

                $.put("/cost/variable/" + {{$id}},
                    {
                        activity_date: $("#activity_date").val(),
                        activity_category_group_id: $("#activity_category_group_id").val(),
                        amount: $("#amount", null, "int").val(),
                        location: $("#location").val(),
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
            };

            $.enterCallback(doSubmit);

            $("[name=activity_date]").dateFormat("[name=activity_date]");
        });
    </script>

    <div class="modal-dialog">
        <div class="modal-content">
            {!! Form::open() !!}
                <div class="modal-header">
                    <h4 class="modal-title">編集</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-dismissible alert-warning d-none" id="ajax-errors">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
                        <ul class="mb-0" id="ajax-message-list"></ul>
                    </div>

                    <div class="row">
                        <div class="row mb-3">
                            {!! Form::label('activity_date', '発生日', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-3">
                                @if (Agent::isDesktop())
                                    {!! Form::text('activity_date', Html::date($activity->activity_date, false), ['class' => 'form-control date-picker', 'placeholder' => '月/日']) !!}
                                @else
                                    {!! Form::date('activity_date', str_replace('/', '-', Html::date($activity->activity_date, false)), ['class' => 'form-control']) !!}
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('activity_category_group_id', '科目', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-4">
                                {!! Form::select('activity_category_group_id', $activity_category_groups, $activity->activity_category_group_id, ['class' => 'form-select']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('amount', '金額', ['class' => 'col-md-3 col-form-label']) !!}
                            {{-- 単位とスピナーの分だけ入力欄が狭くなる。 --}}
                            <div class="col-md-4">
                                <div class="input-group">
                                    {!! Form::number('amount', $activity->amount, ['class' => 'form-control', 'pattern' => '[\-0-9]*']) !!}
                                    <span class="input-group-text">円</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('location', '場所', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-6">
                                {!! Form::text('location', $activity->location, ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('content', '用途', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-6">
                                {!! Form::text('content', $activity->content, ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label">
                                <span class="bi bi-credit-card"></span>
                            </label>
                            <div class="col-md-6">
                                <div class="form-check form-check-inline">
                                    {!! Form::checkbox('credit_flag', App\Models\Activity::CREDIT_FLAG_USE, $activity->credit_flag, ['id' => 'credit_flag', 'class' => 'form-check-input']) !!}
                                    {!! Form::label('credit_flag', 'クレジットカードを使用', ['class' => 'form-check-label']) !!}
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
