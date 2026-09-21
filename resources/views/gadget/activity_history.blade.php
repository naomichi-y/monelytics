<style>
.activity-history {
    cursor: pointer;
}

</style>
<script>
    $(function() {
        $(document).on("click", ".activity-history", function() {
            var activityDate = $(this).attr("data-date");
            var url = "/summary/daily?begin_date=" + activityDate + "&end_date=" + activityDate;

            location.href = url;
        });
    });
</script>
@if (sizeof($histories))
    <table class="table table-striped table-hover">
        <colgroup>
            <col style="width: 20%" />
            <col style="width: 18%" />
            <col style="width: 12%" />
            <col style="width: 22%" />
            <col style="width: 28%" />
        </colgroup>
        <thead>
            <th class="text-center">発生日</th>
            <th class="text-center">科目</th>
            <th class="text-center">金額</th>
            <th class="text-center d-none d-md-table-cell">場所</th>
            <th class="text-center d-none d-md-table-cell">用途</th>
        </thead>
        <tbody>
            @foreach ($histories as $history)
                <tr data-date="{{$history->activity_date}}" class="activity-history">
                    <td class="text-center">{{Html::date($history->activity_date)}}</td>
                    <td>{{{$history->activityCategoryItem->item_name}}}</td>
                    <td class="text-end">{!! Html::amount($history->amount) !!}</td>
                    <td class="d-none d-md-table-cell">{{{$history->location}}}</td>
                    <td class="d-none d-md-table-cell">{{{$history->content}}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p>データがありません。</p>
@endif
