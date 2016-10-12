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
            $("#begin_date").val('');
            $("#end_date").val('');
            $("#date_month").prop("disabled", false);
        });

        // 詳細検索モーダル (リセット押下)
        $(document).on("click", "#reset", function() {
            $("input[type='text'], input[type='radio'], input[type='checkbox'], select")
                .val("")
                .removeAttr("checked")
                .removeAttr("selected");

            $("#date_month").prop("selectedIndex", 0);
            $("#credit_flag_all").prop("checked", true);
            $("#special_flag_all").prop("checked", true);
        });

        $("#begin_date").dateFormat();
        $("#end_date").dateFormat();
    });
</script>

<div id="search_modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            {!! Form::open(['url' => 'summary/daily', 'method' => 'get', 'class' => 'form-horizontal']) !!}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">検索条件</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group">
                            {!! Form::label('date_month', '月指定', ['class' => 'col-md-3 control-label']) !!}
                            <div class="col-md-3">
                                {!! Form::select('date_month', $month_list, Request::input('date_month'), ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            {!! Form::label('begin_date', '日付範囲指定', ['class' => 'col-md-3 control-label']) !!}
                            @if (Agent::isDesktop())
                                <div class="col-md-3">
                                    {!! Form::text('begin_date', Request::input('begin_date'), ['class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off']) !!}
                                </div>
                                {!! Form::label('end_date', '〜', ['class' => 'col-md-1 control-label label-range-text']) !!}
                                <div class="col-md-3">
                                    {!! Form::text('end_date', Request::input('end_date'), ['class' => 'form-control date-picker', 'placeholder' => '月/日', 'autocomplete' => 'off']) !!}
                                </div>
                            @else
                                <div class="col-md-3">
                                    {!! Form::date('begin_date', Request::input('begin_date'), ['class' => 'form-control']) !!}
                                </div>
                                {!! Form::label('end_date', '〜', ['class' => 'col-md-1 control-label label-range-text']) !!}
                                <div class="col-md-3">
                                    {!! Form::date('end_date', Request::input('end_date'), ['class' => 'form-control']) !!}
                                </div>
                            @endif
                            {!! Form::button('クリア', ['class' => 'btn btn-default', 'id' => 'clear_date_range']) !!}
                        </div>

                        <div class="form-group">
                            {!! Form::label('activity_category_group_id', '科目名', ['class' => 'col-md-3 control-label']) !!}
                            <div class="col-md-5">
                                {!! Form::select('activity_category_group_id[]', $activity_category_groups, Request::input('activity_category_group_id'), ['class' => 'form-control', 'multiple' => 'multiple', 'id' => 'activity_category_group_id']) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            {!! Form::label('keyword', '場所・用途', ['class' => 'col-md-3 control-label']) !!}
                            <div class="col-md-8">
                                {!! Form::text('keyword', Request::input('keyword'), ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3 control-label">
                                <span class="glyphicon glyphicon-credit-card"></span>
                            </label>
                            <div class="col-md-6">
                                <div class="radio-inline">
                                    {!! Form::radio('credit_flag', '', $credit_flag_all, ['id' => 'credit_flag_all']) !!}
                                    {!! Form::label('credit_flag_all', '全て') !!}
                                </div>
                                <div class="radio-inline">
                                    {!! Form::radio('credit_flag', '1', $credit_flag_on, ['id' => 'credit_flag_on']) !!}
                                    {!! Form::label('credit_flag_on', '含む') !!}
                                </div>
                                <div class="radio-inline">
                                    {!! Form::radio('credit_flag', '0', $credit_flag_off, ['id' => 'credit_flag_off']) !!}
                                    {!! Form::label('credit_flag_off', '含まない') !!}
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3 control-label">
                                <span class="glyphicon glyphicon-star"></span>
                            </label>
                            <div class="col-md-6">
                                <div class="radio-inline">
                                    {!! Form::radio('special_flag', '', $special_flag_all, ['id' => 'special_flag_all']) !!}
                                    {!! Form::label('special_flag_all', '全て') !!}
                                </div>
                                <div class="radio-inline">
                                    {!! Form::radio('special_flag', '1', $special_flag_on, ['id' => 'special_flag_on']) !!}
                                    {!! Form::label('special_flag_on', '含む') !!}
                                </div>
                                <div class="radio-inline">
                                    {!! Form::radio('special_flag', '0', $special_flag_off, ['id' => 'special_flag_off']) !!}
                                    {!! Form::label('special_flag_off', '含まない') !!}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    {!! Form::submit('検索', ['class' => 'btn btn-primary', 'id' => 'search']) !!}
                    {!! Form::button('リセット', ['class' => 'btn btn-default', 'id' => 'reset']) !!}
                    {!! Form::button('閉じる', ['class' => 'btn btn-default', 'data-dismiss' => 'modal', 'aria-hidden' => 'true']) !!}
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
