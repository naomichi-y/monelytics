<?php
namespace App\Libraries\Condition;

class BaseDateCondition extends BaseCondition {
    public $date_year;
    public $date_month;
    public $begin_date;
    public $end_date;

    /**
     * @return stdClass
     */
    public function getDateRange()
    {
        $begin_date = null;
        $end_date = null;

        if (strlen($this->begin_date) == 0 && strlen($this->end_date) == 0) {
            if ($this->date_year) {
                $begin_date = $this->date_year . '-01-01';
                $end_date = $this->date_year . '-12-31';

            } else if ($this->date_month !== 'all') {
                $begin_date = $this->date_month . '-01';
                $last_day = date('d', strtotime('last day of ' . $this->date_month));
                $end_date = $this->date_month . '-' . $last_day;
            }

        } else {
            $begin_date = str_replace('/', '-', $this->begin_date);
            $end_date = str_replace('/', '-', $this->end_date);
        }

        // 日付の妥当性チェック
        if (!$this->isValidDate($begin_date)) {
            $begin_date = null;
        }

        if (!$this->isValidDate($end_date)) {
            $end_date = null;
        }

        $date_range = new \stdClass();
        $date_range->begin_date = $begin_date;
        $date_range->end_date = $end_date;

        return $date_range;
    }

    /**
     * @param string $date
     * @return bool
     */
    private function isValidDate($date)
    {
        $pattern = '/([0-9]{4})[-\/]([0-9]{1,2})[-\/]([0-9]{1,2})/';
        $result = false;

        if (preg_match($pattern, $date, $matches)) {
            $result = checkdate($matches[2], $matches[3], $matches[1]);
        }

        return $result;
    }
}
