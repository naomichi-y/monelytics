<script>
    $(function() {
        // 詳細検索モーダル (リセット押下)
        $(document).on("click", "#reset", function() {
            $("input[type='text'], select")
                .val("")
                .removeAttr('selected');
            $("#begin_year").prop('selectedIndex', 0);
            $("#end_year").prop('selectedIndex', 0);
        });
    });
</script>

<div id="search_modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            {!! Form::open(['url' => 'summary/yearly', 'method' => 'get', 'class' => 'form-horizontal']) !!}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">検索条件</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group">
                            {!! Form::label('begin_year', '検索範囲', ['class' => 'col-md-2 control-label']) !!}
                            <div class="col-md-2">
                                {!! Form::select('begin_year', $date_list, Input::get('begin_year'), ['class' => 'form-control']) !!}
                            </div>
                            {!! Form::label('end_year', '〜', ['class' => 'col-md-2 control-label label-range-text']) !!}
                            <div class="col-md-2">
                                {!! Form::select('end_year', $date_list, Input::get('end_year'), ['class' => 'form-control']) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            {!! Form::label('', '出力形式', ['class' => 'col-md-2 control-label']) !!}
                            <div class="col-md-6">
                                <div class="radio-inline">
                                    {!! Form::radio('output_type', App\Libraries\Condition\YearlySummaryCondition::OUTPUT_TYPE_MONTHLY, $output_type_monthly, ['id' => 'output_type_monthly']) !!}
                                    {!! Form::label('output_type_monthly', '月単位') !!}
                                </div>
                                <div class="radio-inline">
                                    {!! Form::radio('output_type', App\Libraries\Condition\YearlySummaryCondition::OUTPUT_TYPE_YEARLY, $output_type_yearly, ['id' => 'output_type_yearly']) !!}
                                    {!! Form::label('output_type_yearly', '年単位') !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    {!! Form::submit('検索', ['class' => 'btn btn-primary']) !!}
                    {!! Form::button('リセット', ['class' => 'btn btn-default', 'id' => 'reset']) !!}
                    {!! Form::button('閉じる', ['class' => 'btn btn-default', 'data-dismiss' => 'modal', 'aria-hidden' => 'true']) !!}
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
