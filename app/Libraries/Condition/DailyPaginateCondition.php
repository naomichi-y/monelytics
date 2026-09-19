<?php
namespace App\Libraries\Condition;

class DailyPaginateCondition extends BaseDateCondition {
    /**
     * 並び替えを許す列。Html::sortLabel が一覧の見出しに出しているものと揃える。
     *
     * 利用者入力をそのまま orderBy へ渡すと、存在しない列名で QueryException が
     * 出て 500 になる。列名はクエリビルダが逃がすため注入は成立しないが、
     * 受け付ける値をここで閉じておく。
     */
    const SORT_FIELDS = [
        'activity_date',
        'activity_category_group_id',
        'location',
        'content',
        'amount',
        'credit_flag',
        'create_date',
    ];

    public $activity_category_group_id = [];
    public $keyword;
    public $location;
    public $credit_flag;
    public $cost_type;
    public $sort_field;
    public $sort_type;
    public $limit;

    public function __construct(array $fields = [])
    {
        if (!in_array($fields['sort_field'] ?? null, self::SORT_FIELDS, true)) {
            $fields['sort_field'] = 'activity_date';
        }

        // 並び順は利用者入力から来る。Laravel 13 は asc/desc 以外を拒否するため、
        // 旧来の「asc 以外は降順」という挙動を保ったまま値を確定させる。
        $fields['sort_type'] = strtolower($fields['sort_type'] ?? '') === 'asc' ? 'asc' : 'desc';

        if (empty($fields['limit'])) {
            $fields['limit'] = \Agent::isMobile() ? 5 : 30;
        }

        parent::__construct($fields);
    }
}
