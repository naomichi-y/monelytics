<style>
.activity-status {
    background-color: #f8f5f0;
    border-radius: 4px;
    margin: 0;
}

.operator {
    padding-top: 15px;
}
</style>
<div class="row text-center activity-status">
    <div class="col-md-3">
        <h3>収入</h3>
        <p>{{number_format($status[App\Models\ActivityCategory::BALANCE_TYPE_INCOME])}}</p>
    </div>
    <div class="col-md-1 operator hidden-xs">
        <h3>+</h3>
    </div>
    <div class="col-md-3">
        <h3>支出</h3>
        <p>{{number_format($status[App\Models\ActivityCategory::BALANCE_TYPE_EXPENSE])}}</p>
    </div>
    <div class="col-md-1 operator hidden-xs">
        <h3>=</h3>
    </div>
    <div class="col-md-4">
        <h3>残高</h3>
        <p>{{number_format($status[0])}}</p>
    </div>
</div>
