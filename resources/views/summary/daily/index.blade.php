@extends('layouts.master')

@section('title')
日別集計
@stop

@section('include_header')
    {!! Html::versionedStyle('assets/css/responsive_table.css') !!}
    {!! Html::versionedScript('assets/js/responsive_table.js') !!}
    <script>
        $(function() {
            $("#date_month").change(function() {
                $("#search_form").submit();
            });

            $("table").responsiveTable();

            // 詳細検索押下
            $("#open_condition").click(function() {
                $.get("/summary/daily/condition",
                    {
                        date_month: {!! Html::encodeJsJsonValue('date_month', date('Y-m')) !!},
                        begin_date: {!! Html::encodeJsJsonValue('begin_date') !!},
                        end_date: {!! Html::encodeJsJsonValue('end_date') !!},
                        activity_category_item_id: {!! Html::encodeJsJsonValue('activity_category_item_id', null, 'array') !!},
                        keyword: {!! Html::encodeJsJsonValue('keyword') !!},
                        credit_flag: {!! Html::encodeJsJsonValue('credit_flag') !!},
                    },
                    function(data) {
                        showModal(data);
                    }
                );
            });

            // 編集押下
            $(".open_edit").click(function() {
                var selected_id = $(".open_edit").index(this) + 1;
                var activity_id = $("tr:eq(" + selected_id + ")").attr("data-id");

                $.get("/cost/variable/" + activity_id + "/edit",
                    {},
                    function(data) {
                        showModal(data);
                    }
                );
            });
        });
    </script>
@stop

@section('function')
    <div class="card card-body">
        {!! Form::open(['url' => 'summary/daily', 'id' => 'search_form', 'method' => 'get']) !!}
            <div class="row g-2 align-items-center form-group-adjust">
                <div class="col-md-8">
                    {{-- 選択は Condition が決めた値を出す。ここで Request から
                         組み直していたころは、指定が無いときだけセレクトが当月を
                         出し、一覧は全期間を並べていた。

                         詳細検索で日付範囲を入れている間は操作させない。範囲と月の
                         両方が送られると getDateRange は範囲を優先するため、ここで
                         月を選べると、選んだ月と出てくる期間が食い違う。

                         代わりに効いている期間を横に出す。セレクトが「未指定」で
                         止まっているだけだと、何で絞られているのかが画面から読めない。 --}}
                    @php
                        $month_attributes = ['class' => 'form-select', 'id' => 'date_month'];

                        if ($condition->hasDateRange()) {
                            // 幅は中身に合わせる。form-select は幅 100% なので、
                            // 横に期間を並べると場所を取り合い、セレクトの文字が
                            // 押し潰されて月の頭だけが見えていた。
                            $month_attributes['class'] .= ' w-auto';
                            $month_attributes['disabled'] = 'disabled';
                        }

                        $month_select = Form::select('date_month', $month_list, $condition->date_month, $month_attributes);

                        // 片側だけの指定も通る (「この日以降」)。空いている側は
                        // 日付を出さず、記号だけを残して向きを示す。
                        $date_range_label = sprintf(
                            '%s 〜 %s',
                            $date_range->begin_date ? Html::date($date_range->begin_date, false) : '',
                            $date_range->end_date ? Html::date($date_range->end_date, false) : ''
                        );
                    @endphp
                    @if ($condition->hasDateRange())
                        <div class="d-flex align-items-center gap-2">
                            {!! $month_select !!}
                            <span class="text-nowrap">{{trim($date_range_label)}}</span>
                        </div>
                    @else
                        {!! $month_select !!}
                    @endif
                </div>
                <div class="col-md-4">
                    <a class="btn btn-info btn-sm" id="open_condition">詳細検索</a>
                </div>
            </div>
        {!! Form::close() !!}
    </div>
@stop

@section('content')
    @include('layouts/delete_modal', ['action' => 'cost/variable'])

    @if ($activities->total())
    {!! Form::open(['url' => 'summary/daily', 'method' => 'get', 'id' => 'list-form']) !!}
        <table class="table table-striped table-hover">
            <colgroup>
                <col style="width: 11%" />
                <col style="width: 11%" />
                <col style="width: 19%" />
                <col style="width: 19%" />
                <col style="width: 8%" />
                <col style="width: 4%" />
                <col style="width: 14%" />
                <col style="width: 14%" />
            </colgroup>
            <thead>
                <tr>
                    <th class="text-center">
                        {!! Html::sortLabel('activity_date', '発生日', true) !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('activity_category_item_id', '小項目') !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('location', '場所') !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('content', '用途') !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('amount', '金額') !!}
                    </th>
                    <th class="text-center">
                        <span class="bi bi-credit-card"></span>
                        {!! Html::sortLabel('credit_flag', '') !!}
                    </th>
                    <th class="text-center">
                        {!! Html::sortLabel('create_date', '登録日時') !!}
                    </th>
                    <th class="text-center">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($activities as $activity)
                <tr data-id="{{$activity->id}}">
                    <td class="text-center">
                        {{Html::date($activity->activity_date)}}
                    </td>
                    <td>{{{$activity->activityCategoryItem->item_name}}}</td>
                    {{-- 場所は、今の絞り込みに location を足した日別集計へのリンクにする。
                         月別集計の利用頻度ランキングが同じ遷移をしており、そちらと揃える。

                         期間は Condition が持っている形のまま渡す。月を見ているなら
                         date_month、日付範囲で絞っているなら begin_date / end_date が
                         載る。ここで解決済みの実日付を足すと、月を見ているだけの人が
                         踏んだ先まで「日付範囲指定」の画面になり、月のセレクトが
                         操作不可になっていた。期間が必ず入っているのは、指定の無い
                         ときに Condition が当月を埋めるため。

                         空欄はリンクにしない。押しても絞り込みが効かず (Service 側が
                         strlen で捨てる)、同じ一覧が出るだけのため。 --}}
                    <td>
                        @if (strlen($activity->location ?? ''))
                            @php
                                $location_queries = array_merge($condition->toArray(), [
                                    'location' => $activity->location,
                                ]);
                            @endphp
                            {!! Html::linkWithQueryString('/summary/daily', $location_queries, $activity->location) !!}
                        @endif
                    </td>
                    <td>{{{$activity->content}}}</td>
                    <td class="text-end">{!! Html::amount($activity->amount) !!}</td>
                    <td class="text-center">
                        @if ($activity->credit_flag)
                            <span class="bi bi-check-lg"></span>
                        @endif
                    </td>
                    <td class="text-center">{{Html::datetime($activity->create_date)}}</td>
                    <td class="text-center">
                        {!! Form::button('編集', ['class' => 'btn btn-primary open_edit']) !!}
                        {!! Form::button('削除', ['class' => 'btn btn-secondary open_delete', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal']) !!}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="text-end">
            合計金額: {!! Html::amount($activities->total_amount) !!}
        </div>
        {{-- 番号は出さず「前へ / 次へ」だけ。ページ数が多く、番号を並べても
             行が埋まるだけで選べないため。 --}}
        <div class="text-end">{!! $activities->render('pagination::simple-bootstrap-5') !!}</div>
        {!! Form::close() !!}
    @else
        <p>データがありません。</p>
    @endif
@stop
