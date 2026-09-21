<script>
$(function () {
    var $chart = $("#yearly_trend_chart");

    function load() {
        $chart.loadYearlyTrend({
            begin_year: {!! Html::encodeJsJsonValue('begin_year', date('Y')) !!},
            end_year: {!! Html::encodeJsJsonValue('end_year', date('Y')) !!},
            output_type: {!! Html::encodeJsJsonValue('output_type', App\Libraries\Condition\YearlySummaryCondition::OUTPUT_TYPE_MONTHLY) !!},
            balance_type: $("#trend_balance_type").val()
        });
    }

    // タブと同じくクッキーへ保持し、リロードしても選択が残るようにする。
    $("#trend_balance_type").rememberSelect("yearly_summary-balance_type");

    $("#trend_balance_type").change(load);
    load();
});
</script>
{{-- .card に下余白はないため、指定しないと絞り込みがグラフに貼り付く。 --}}
<div class="card card-body mb-3">
    <div class="row g-2 align-items-center form-group-adjust">
        <div class="col-md-4">
                {!! Form::select(
                    'trend_balance_type',
                    [
                        App\Models\ActivityCategory::BALANCE_TYPE_EXPENSE => '支出',
                        App\Models\ActivityCategory::BALANCE_TYPE_INCOME => '収入',
                        '' => 'すべて',
                    ],
                    App\Models\ActivityCategory::BALANCE_TYPE_EXPENSE,
                    ['class' => 'form-select form-select-sm', 'id' => 'trend_balance_type']
                ) !!}
        </div>
    </div>
</div>
<div id="yearly_trend_chart"></div>
