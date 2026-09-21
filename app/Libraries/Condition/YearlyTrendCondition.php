<?php
namespace App\Libraries\Condition;

class YearlyTrendCondition extends BaseCondition {
    public $begin_year;
    public $end_year;

    /**
     * YearlySummaryCondition::OUTPUT_TYPE_* のいずれか。
     * 集計表と同じ粒度で横軸を刻むため、詳細検索の指定をそのまま受ける。
     */
    public $output_type;

    /**
     * ActivityCategory::BALANCE_TYPE_* のいずれか。
     * 未指定なら支出と収入の小項目を両方対象にする。
     */
    public $balance_type;

    /**
     * 場所と用途に対する絞り込み。集計表と同じ行を対象にするため、詳細検索の
     * 指定をそのまま受ける。
     */
    public $keyword;
}
