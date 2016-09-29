<?php
namespace App\Libraries\Condition;

class YearlySummaryCondition extends BaseDateCondition {
  const OUTPUT_TYPE_MONTHLY = 1;
  const OUTPUT_TYPE_YEARLY = 2;

  public $begin_year;
  public $end_year;
  public $output_type;
}
