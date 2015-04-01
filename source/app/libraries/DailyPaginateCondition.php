<?php
class DailyPaginateCondition extends BaseDateCondition {
  public $activity_category_group_id = array();
  public $keyword;
  public $location;
  public $credit_flag;
  public $special_flag;
  public $cost_type;
  public $sort_field;
  public $sort_type;
  public $limit;

  public function __construct(array $fields = array())
  {
    if (empty($fields['sort_field'])) {
      $fields['sort_field'] = 'activity_date';
    }

    if (empty($fields['limit'])) {
      $fields['limit'] = Agent::isMobile() ? 5 : 30;
    }

    parent::__construct($fields);
  }
}
