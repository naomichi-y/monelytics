@if (sizeof($activity_categories))
    <table class="table table-striped table-hover">
        {{-- 操作の 3 つのボタンは「小項目の確認」が長く、20% では 3 つ目が
             次の行へ回る。用途から回して 1 行に収める。 --}}
        <colgroup>
            <col style="width: 23%" />
            <col style="width: 24%" />
            <col style="width: 5%" />
            <col style="width: 15%" />
            <col style="width: 5%" />
            <col style="width: 28%" />
        </colgroup>
        <thead>
            <tr>
                <th class="text-center">大項目名</th>
                <th class="text-center">用途</th>
                <th class="text-center">収支タイプ</th>
                <th class="text-center">登録日時</th>
                <th class="text-center d-none d-md-table-cell">表示順序</th>
                <th class="text-center">操作</th>
            </tr>
        </thead>
        <tbody id="{{$id}}_sortable">
            @foreach ($activity_categories as $activity_category)
                <tr data-id="{{$activity_category->id}}">
                    <td>
                        {{{$activity_category->category_name}}}
                        <p class="note">
                        {{{Html::collection_to_string($activity_category->activityCategoryItems, 'item_name')}}}
                        </p>
                    </td>
                    <td>{{nl2br(e($activity_category->content))}}</td>
                    <td class="text-center">
                        @if ($activity_category->balance_type == App\Models\ActivityCategory::BALANCE_TYPE_EXPENSE)
                            支出
                        @else
                            収入
                        @endif
                    </td>
                    <td class="text-center">{{Html::datetime($activity_category->create_date)}}</td>
                    <td class="text-center sort-col d-none d-md-table-cell"><i class="bi bi-arrow-down-up"></i></td>
                    <td class="text-center">
                        {!! Form::hidden($id . '_sortable_ids[]', $activity_category->id) !!}
                        {!! Form::button('編集', ['class' => 'btn btn-primary open_edit']) !!}
                        {!! Form::button('削除', ['class' => 'btn btn-secondary open_delete', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal']) !!}
                        {!! Form::button('小項目の確認', ['class' => 'btn btn-secondary show-category-group']) !!}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p>データがありません。</p>
@endif
