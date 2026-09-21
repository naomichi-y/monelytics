<style>
.activity-status {
    background-color: #f8f5f0;
    border-radius: 4px;
    margin: 0;
}

.operator {
    padding-top: 15px;
}

.comparison {
    color: #6c757d;
    display: block;
    font-size: 0.8rem;
}

.comparison-note {
    color: #6c757d;
    font-size: 0.8rem;
    margin: 0;
    padding-bottom: 10px;
}
</style>
<div class="row text-center activity-status">
    <div class="col-md-3">
        <h3>収入</h3>
        <p>
            {{number_format($status[App\Models\ActivityCategory::BALANCE_TYPE_INCOME])}}
            @if (isset($comparisons['totals']['income']))
                <span class="comparison">{{Html::comparisonRate($comparisons['totals']['income'])}}</span>
            @endif
        </p>
    </div>
    <div class="col-md-1 operator d-none d-md-block">
        <h3>+</h3>
    </div>
    <div class="col-md-3">
        <h3>支出</h3>
        <p>
            {{number_format($status[App\Models\ActivityCategory::BALANCE_TYPE_EXPENSE])}}
            @if (isset($comparisons['totals']['expense']))
                <span class="comparison">{{Html::comparisonRate($comparisons['totals']['expense'])}}</span>
            @endif
        </p>
    </div>
    <div class="col-md-1 operator d-none d-md-block">
        <h3>=</h3>
    </div>
    <div class="col-md-4">
        <h3>残高</h3>
        <p>
            {{number_format($status[0])}}
            @if (isset($comparisons['totals']['total']))
                <span class="comparison">{{Html::comparisonRate($comparisons['totals']['total'])}}</span>
            @endif
        </p>
    </div>
    @if (sizeof($comparisons['totals']))
        <div class="col-12">
            {{-- 金額は今月まるごと、増減率は今日までで切ってある。月の途中を
                 まるごとの先月と比べると必ず減って見えるため、比較だけ日で
                 区切る。基準が違うので、どこまでを比べたのか書いておく。 --}}
            <p class="comparison-note">増減は先月の {{Html::date($comparisons['period']['previous_end_date'], false)}} までとの比較です。</p>
        </div>
    @endif
</div>
