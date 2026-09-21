<?php
namespace App\Libraries\Condition;

class RankingCondition extends BaseDateCondition {
    /**
     * 順位を付けて並べる件数。上位だけを見るための画面なので、30 件まで
     * 並べると下へスクロールするばかりで順位が読めない。
     */
    public $limit = 10;
}
