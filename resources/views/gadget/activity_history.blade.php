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
            <col style="width: 20%" />
            <col style="width: 5%" />
            <col style="width: 25%" />
            <col style="width: 30%" />
        </colgroup>
        <thead>
            <th class="text-center">発生日</th>
            <th class="text-center">科目名</th>
            <th class="text-center">金額</th>
            <th class="text-center hidden-xs">場所</th>
            <th class="text-center hidden-xs">用途</th>
        </thead>
        <tbody>
            @foreach ($histories as $history)
                <tr data-date="{{$history->activity_date}}" class="activity-history">
                    <td class="text-center">{{Html::date($history->activity_date)}}</td>
                    <td>{{{$history->activityCategoryGroup->group_name}}}</td>
                    <td class="text-right">{{number_format($history->amount)}}</td>
                    <td class="hidden-xs">{{{$history->location}}}</td>
                    <td class="hidden-xs">{{{$history->content}}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p>データがありません。</p>
@endif
