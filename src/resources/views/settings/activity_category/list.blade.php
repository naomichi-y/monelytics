@if (sizeof($activity_categories))
  <table class="table table-striped table-hover">
    <colgroup>
      <col style="width: 25%" />
      <col style="width: 30%" />
      <col style="width: 5%" />
      <col style="width: 15%" />
      <col style="width: 5%" />
      <col style="width: 20%" />
    </colgroup>
    <thead>
      <tr>
        <th class="text-center">科目カテゴリ名</th>
        <th class="text-center">用途</th>
        <th class="text-center">収支タイプ</th>
        <th class="text-center">登録日時</th>
        <th class="text-center hidden-xs">表示順序</th>
        <th class="text-center">操作</th>
      </tr>
    </thead>
    <tbody id="{{$id}}_sortable">
      @foreach ($activity_categories as $activity_category)
        <tr data-id="{{$activity_category->id}}">
          <td>
            {{{$activity_category->category_name}}}
            <p class="note">
            {{{collection_to_string($activity_category->activityCategoryGroups, 'group_name')}}}
            </p>
          </td>
          <td>{{nl2br(e($activity_category->content))}}</td>
          <td class="text-center">
            @if ($activity_category->balance_type == Monelytics\Models\ActivityCategory::BALANCE_TYPE_EXPENSE)
              支出
            @else
              収入
            @endif
          </td>
          <td class="text-center">{{Html::datetime($activity_category->create_date)}}</td>
          <td class="text-center sort-col hidden-xs"><span class="glyphicon glyphicon-sort"></span></td>
          <td class="text-center">
            {!! Form::hidden($id . '_sortable_ids[]', $activity_category->id) !!}
            {!! Form::button('編集', array('class' => 'btn btn-primary open_edit')) !!}
            {!! Form::button('削除', array('class' => 'btn btn-default open_delete', 'data-toggle' => 'modal', 'data-target' => '#delete-modal')) !!}
            {!! Form::button('科目の確認', array('class' => 'btn btn-default show-category-group')) !!}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
@else
  <p>データがありません。</p>
@endif
