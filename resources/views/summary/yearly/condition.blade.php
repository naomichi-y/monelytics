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
            {!! Form::open(['url' => 'summary/yearly', 'method' => 'get']) !!}
                <div class="modal-header">
                    <h4 class="modal-title">検索条件</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="row mb-3">
                            {!! Form::label('begin_year', '検索範囲', ['class' => 'col-md-2 col-form-label']) !!}
                            <div class="col-md-3">
                                {!! Form::select('begin_year', $date_list, Request::input('begin_year'), ['class' => 'form-select']) !!}
                            </div>
                            {!! Form::label('end_year', '〜', ['class' => 'col-md-2 col-form-label label-range-text']) !!}
                            <div class="col-md-3">
                                {!! Form::select('end_year', $date_list, Request::input('end_year'), ['class' => 'form-select']) !!}
                            </div>
                        </div>

                        {{-- 項目名は日別集計の詳細検索と揃える。同じ絞り込みを
                             画面ごとに違う名前で呼ぶと、同じものだと分からない。 --}}
                        <div class="row mb-3">
                            {!! Form::label('keyword', '場所・用途', ['class' => 'col-md-2 col-form-label']) !!}
                            <div class="col-md-8">
                                {!! Form::text('keyword', Request::input('keyword'), ['class' => 'form-control', 'id' => 'keyword']) !!}
                            </div>
                        </div>

                        <div class="row mb-3">
                            {!! Form::label('', '出力形式', ['class' => 'col-md-2 col-form-label']) !!}
                            <div class="col-md-6">
                                <div class="form-check form-check-inline">
                                    {!! Form::radio('output_type', App\Libraries\Condition\YearlySummaryCondition::OUTPUT_TYPE_MONTHLY, $output_type_monthly, ['id' => 'output_type_monthly', 'class' => 'form-check-input']) !!}
                                    {!! Form::label('output_type_monthly', '月単位', ['class' => 'form-check-label']) !!}
                                </div>
                                <div class="form-check form-check-inline">
                                    {!! Form::radio('output_type', App\Libraries\Condition\YearlySummaryCondition::OUTPUT_TYPE_YEARLY, $output_type_yearly, ['id' => 'output_type_yearly', 'class' => 'form-check-input']) !!}
                                    {!! Form::label('output_type_yearly', '年単位', ['class' => 'form-check-label']) !!}
                                </div>
                            </div>
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
