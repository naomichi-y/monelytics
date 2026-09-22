<script>
    $(function() {
        /*
         * セレクタはモーダルの中へ閉じる。モーダルは document.body へ差し込まれ、
         * 月指定のセレクトは本体画面のものと id が重なっていたため、
         * $("#date_month") が本体側に当たっていた。日付範囲を入れると、
         * モーダルではなく裏の画面のセレクトが「未指定」に書き換わっていた。
         *
         * 束ねる先も document ではなくモーダル自身にする。モーダルは閉じると
         * 取り除かれて開くたびに取り直すので、document に積むと開いた回数だけ
         * ハンドラが溜まり、リセットが何度も走る。
         */
        var $modal = $("#search_modal");
        var $month = $modal.find("#search_date_month");
        var $begin = $modal.find("#begin_date");
        var $end = $modal.find("#end_date");

        /*
         * 日付範囲と月指定は同時には効かない。両方送られると getDateRange は
         * 範囲を優先するため、月を選べるままにすると、選んだ月と出てくる期間が
         * 食い違う。範囲が入っている間は月を触らせない。
         */
        function syncMonthState() {
            var has_range = Boolean($begin.val() || $end.val());

            $month.prop("disabled", has_range);

            if (has_range) {
                $month.prop("selectedIndex", 0);
            }
        }

        $month.on("change", function() {
            $begin.val('');
            $end.val('');
            syncMonthState();
        });

        $begin.add($end).on("change input", syncMonthState);

        $modal.find("#clear_date_range").on("click", function() {
            $begin.val('');
            $end.val('');
            syncMonthState();
        });

        // 詳細検索モーダル (リセット押下)
        $modal.find("#reset").on("click", function() {
            $modal.find("input[type='text'], input[type='date'], select")
                .val("")
                .removeAttr("selected");

            $month.prop("selectedIndex", 0);
            syncMonthState();
        });

        $begin.dateFormat("#begin_date");
        $end.dateFormat("#end_date");
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
                            {{-- id は本体画面のセレクトと分ける。同じ id が 2 つ並ぶと
                                 ラベルの for も jQuery のセレクタも先に現れる本体側へ
                                 当たる。name は送信する項目名なので date_month のまま。 --}}
                            {!! Form::label('search_date_month', '月指定', ['class' => 'col-md-3 col-form-label']) !!}
                            <div class="col-md-4">
                                @php
                                    $month_attributes = ['class' => 'form-select', 'id' => 'search_date_month'];

                                    if ($condition->hasDateRange()) {
                                        $month_attributes['disabled'] = 'disabled';
                                    }
                                @endphp
                                {!! Form::select('date_month', $month_list, Html::requestValue('date_month'), $month_attributes) !!}
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
