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

        $("#begin_date").dateFormat("#begin_date");
        $("#end_date").dateFormat("#end_date");
    });
</script>

<div id="search_modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            {!! Form::open(['url' => 'summary/monthly', 'method' => 'get']) !!}
                <div class="modal-header">
                    <h4 class="modal-title">検索条件</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="row mb-3">
                            {!! Form::label('date_month', '月指定', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-4">
                                {!! Form::select('date_month', $month_list, Html::requestValue('date_month'), ['class' => 'form-select']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('begin_date', '日付範囲指定', ['class' => 'col-md-3 col-form-label']) !!}
                            @if (Agent::isDesktop())
                                <div class="col-md-3">
                                    {!! Form::text('begin_date', Html::requestValue('begin_date'), ['class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off']) !!}
                                </div>
                                {!! Form::label('end_date', '〜', ['class' => 'col-md-1 col-form-label label-range-text']) !!}
                                <div class="col-md-3">
                                    {!! Form::text('end_date', Html::requestValue('end_date'), ['class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off']) !!}
                                </div>
                            @else
                                <div class="col-md-3">
                                    {!! Form::date('begin_date', Html::requestValue('begin_date'), ['class' => 'form-control']) !!}
                                </div>
                                {!! Form::label('end_date', '〜', ['class' => 'col-md-1 col-form-label label-range-text']) !!}
                                <div class="col-md-3">
                                    {!! Form::date('end_date', Html::requestValue('end_date'), ['class' => 'form-control']) !!}
                                </div>
                            @endif
                            {!! Form::button('クリア', ['class' => 'btn btn-secondary col-auto', 'id' => 'clear_date_range']) !!}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    {!! Form::submit('検索', ['class' => 'btn btn-primary']) !!}
                    {!! Form::button('リセット', ['class' => 'btn btn-secondary', 'id' => 'reset']) !!}
                    {!! Form::button('閉じる', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal', 'aria-hidden' => 'true']) !!}
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
