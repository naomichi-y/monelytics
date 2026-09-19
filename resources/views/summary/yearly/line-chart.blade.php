<script>
$(function () {
    var $chart = $("#yearly_trend_chart");

    function load() {
        $chart.loadYearlyTrend({
            begin_year: {!! Html::encodeJsJsonValue('begin_year', date('Y')) !!},
            end_year: {!! Html::encodeJsJsonValue('end_year', date('Y')) !!},
            balance_type: $("#trend_balance_type").val()
        });
    }

    $("#trend_balance_type").change(load);
    load();
});
</script>
<div class="well">
    <div class="form-group form-group-sm form-group-adjust">
        <div class="col-md-4">
            {!! Form::select(
                'trend_balance_type',
                [
                    App\Models\ActivityCategory::BALANCE_TYPE_EXPENSE => '支出',
                    App\Models\ActivityCategory::BALANCE_TYPE_INCOME => '収入',
                    '' => '収支',
                ],
                App\Models\ActivityCategory::BALANCE_TYPE_EXPENSE,
                ['class' => 'form-control', 'id' => 'trend_balance_type']
            ) !!}
        </div>
    </div>
</div>
<div id="yearly_trend_chart"></div>
