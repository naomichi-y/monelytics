<?php
namespace App\Services;

use App\Models;

class ActivityCategoryItemService
{
    private $activity;
    private $activity_category_item;

    /**
     * コンストラクタ。
     *
     * @param Models\ActivityService $activity
     * @param Models\ActivityCategoryItem $activity_category_item
     */
    public function __construct(Models\Activity $activity, Models\ActivityCategoryItem $activity_category_item)
    {
        $this->activity = $activity;
        $this->activity_category_item = $activity_category_item;
    }

    /**
     * 小項目の表示順序を更新する。
     *
     * @param int $user_id
     * @param int $id
     * @param int $sort_order
     */
    public function updateSortOrder($user_id, $id, $sort_order)
    {
        $this->activity_category_item->where('id', '=', $id)
            ->where('user_id', '=', $user_id)
            ->update(['sort_order' => $sort_order]);
    }

    /**
     * 小項目の最終表示順序を取得する。
     *
     * @param int $user_id
     * @param int $activity_category_id
     * @return int
     */
    public function getLastSortOrder($user_id, $activity_category_id)
    {
        $builder = $this->activity_category_item->where('user_id', '=', $user_id)
            ->where('activity_category_id', '=', $activity_category_id)
            ->orderBy('sort_order', 'desc');

        $result = $builder->first();

        if ($result) {
            return $result->sort_order;
        }

        return 0;
    }

    /**
     * 小項目を登録する。
     *
     * @param int $user_id
     * @param array $fields
     * @param array &$errors
     * @return bool
     */
    public function create($user_id, array $fields, array &$errors = [])
    {
        $result = false;

        // 付け替え先は必ず所有者で絞る。id は利用者が自由に送れるため
        // (@see ActivityCategoryItem::rulesForUser)。
        if ($this->activity_category_item->validate($fields, $this->activity_category_item->rulesForUser($user_id))) {
            $fields['user_id'] = $user_id;
            $fields['sort_order'] = $this->getLastSortOrder($user_id, $fields['activity_category_id']) + 1;

            $this->activity_category_item->create($fields);

            $result = true;

        } else {
            $errors = $this->activity_category_item->getErrors();
        }

        return $result;
    }

    /**
     * 小項目のデータを取得する。
     *
     * @param int $user_id
     * @param int $activity_category_item_id
     * @return ActivityCategoryItem
     */
    public function find($user_id, $activity_category_item_id)
    {
        return $this->activity_category_item->where('user_id', '=', $user_id)
            ->findOrFail($activity_category_item_id);
    }

    /**
     * ユーザに紐づく全ての小項目データを取得する。
     *
     * @param int $user_id
     * @param int $activity_cztegory_id
     * @return Collection
     */
    public function findAll($user_id,  $activity_category_id)
    {
        $builder = $this->activity_category_item->where('user_id', '=', $user_id)
            ->where('activity_category_id', '=', $activity_category_id)
            ->orderBy('sort_order', 'asc');

        return $builder->get();
    }

    /**
     * 小項目データを更新する。
     *
     * 対象の小項目は where で所有者に絞ってあったが、付け替え先の大項目は
     * 絞っていなかった。本体を絞っても移す先を絞らなければ、他人の大項目へ
     * 移せる (@see ActivityCategoryItem::rulesForUser)。
     *
     * @param int $id
     * @param array $fields user_id を含む
     * @param array &$errors
     * @return bool
     */
    public function update($id, $fields, array &$errors = [])
    {
        $result = false;
        $rules = $this->activity_category_item->rulesForUser($fields['user_id'] ?? null);

        if ($this->activity_category_item->validate($fields, $rules)) {
            $this->activity_category_item->where('id', '=', $id)
            ->where('user_id', '=', $fields['user_id'])
            ->update($fields);

            $result = true;

        } else {
            $errors = $this->activity_category_item->getErrors();
        }

        return $result;
    }

    /**
     * 小項目データを削除する。
     *
     * @see ActivityCategoryService::delete と同じ理由で findOrFail を使い、
     * モデル経由で消す。
     *
     * @param int $user_id
     * @param int $activity_category_item_id
     */
    public function delete($user_id, $activity_category_item_id)
    {
        $this->activity_category_item->where('user_id', '=', $user_id)
            ->findOrFail($activity_category_item_id)
            ->delete();
    }
}
