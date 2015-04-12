<?php
class Activity extends BaseModel {
  const CREDIT_FLAG_USE = 1;
  const CREDIT_FLAG_UNUSE = 0;
  const SPECIAL_FLAG_USE = 1;
  const SPECIAL_FLAG_UNUSE = 0;

  protected $guarded = array('id');
  protected $validate_rules = array(
    'activity_date' => 'required|date',
    'activity_category_group_id' => 'required',
    'location' => 'max:64',
    'content' => 'max:255',
    'amount' => 'required|numeric'
  );

  public function user()
  {
    return $this->belongTo('User');
  }

  public function activityCategoryGroup()
  {
    return $this->belongsTo('ActivityCategoryGroup');
  }

  /**
   * @param Illuminate\Database\Eloquent\Builder $builder
   * @param stdClass $date_range
   */
  public function scopeActivityDate($builder, $date_range)
  {
    if (strlen($date_range->begin_date)) {
      if (strlen($date_range->end_date)) {
        $builder->whereBetween('activity_date', array($date_range->begin_date, $date_range->end_date));
      } else {
        $builder->where('activity_date', '>=', $date_range->begin_date);
      }

    } else if (strlen($date_range->end_date)) {
      $builder->where('activity_date', '<=', $date_range->end_date);
    }
  }

  /**
   * @param array $fields
   * @param array &$valid_fields
   * @return bool
   */
  public function validateVariableFields(array $fields, array &$valid_fields = array())
  {
    $has_error = false;
    $result = false;
    $j = sizeof($fields['activity_date']);
    $k = 0;

    for ($i = 0; $i < $j; $i++) {
      // 日付が入力されている場合は検証対象
      if (strlen($fields['activity_date'][$i])) {
        $rules = array(
          sprintf('activity_date.%s', $i) => $this->validate_rules['activity_date'],
          sprintf('activity_category_group_id.%s', $i) => $this->validate_rules['activity_category_group_id'],
          sprintf('location.%s', $i) => $this->validate_rules['location'],
          sprintf('content.%s', $i) => $this->validate_rules['content'],
          sprintf('amount.%s', $i) => $this->validate_rules['amount']
        );

        $attribute_names = array(
          sprintf('activity_date.%s', $i) => Lang::get('validation.attributes.activity_date'),
          sprintf('activity_category_group_id.%s', $i) => Lang::get('validation.attributes.group_name'),
          sprintf('location.%s', $i) => Lang::get('validation.attributes.location'),
          sprintf('content.%s', $i) => Lang::get('validation.attributes.content'),
          sprintf('amount.%s', $i) => Lang::get('validation.attributes.amount')
        );

        $validator = Validator::make($fields, $rules);
        $validator->setAttributeNames($attribute_names);

        if ($validator->fails()) {
          $has_error = true;
          $this->errors = $validator->messages();

          break;
        }

        $valid_fields[$k]['activity_date'] = $fields['activity_date'][$i];
        $valid_fields[$k]['activity_category_group_id'] = $fields['activity_category_group_id'][$i];
        $valid_fields[$k]['amount'] = $fields['amount'][$i];
        $valid_fields[$k]['location'] = $fields['location'][$i];
        $valid_fields[$k]['content'] = $fields['content'][$i];

        if (isset($fields['credit_flag'][$i])) {
          $valid_fields[$k]['credit_flag'] = $fields['credit_flag'][$i];
        } else {
          $valid_fields[$k]['credit_flag'] = Activity::CREDIT_FLAG_UNUSE;
        }

        if (isset($fields['special_flag'][$i])) {
          $valid_fields[$k]['special_flag'] = $fields['special_flag'][$i];
        } else {
          $valid_fields[$k]['special_flag'] = Activity::SPECIAL_FLAG_UNUSE;
        }

        $k++;
      }
    }

    if (sizeof($valid_fields) == 0 && !$has_error) {
      $validator = Validator::make(array(), array());
      $validator->messages()->add('none', Lang::get('validation.custom.create_record_none'));
      $this->errors = $validator->messages();

    } else if (!$has_error) {
      $result = true;
    }

    return $result;
  }

  /**
   * @param array $fields
   * @param array &$valid_fields
   */
  public function validateConstantFields(array $fields, array &$valid_fields = array())
  {
    $result = false;
    $has_error = false;
    $j = 0;

    $target_month = key($fields['activity_date']);

    foreach ($fields['activity_date'][$target_month] as $activity_category_group_id => $activity_date) {
      // 日付が入力されている場合は検証対象
      if (strlen($activity_date)) {
        $rules = array(
          sprintf('activity_date.%s.%s', $target_month, $activity_category_group_id) => 'date',
          sprintf('amount.%s.%s', $target_month, $activity_category_group_id) => 'required|numeric'
        );

        $attribute_names = array(
          sprintf('activity_date.%s.%s', $target_month, $activity_category_group_id) => Lang::get('validation.attributes.activity_date'),
          sprintf('amount.%s.%s', $target_month, $activity_category_group_id) => Lang::get('validation.attributes.amount')
        );

        $validator = Validator::make($fields, $rules);
        $validator->setAttributeNames($attribute_names);

        if ($validator->fails()) {
          $has_error = true;
          $this->errors = $validator->messages();

          break;
        }

        $valid_fields[$j]['activity_category_group_id'] = $activity_category_group_id;
        $valid_fields[$j]['activity_date'] = $fields['activity_date'][$target_month][$activity_category_group_id];
        $valid_fields[$j]['amount'] = $fields['amount'][$target_month][$activity_category_group_id];
        $valid_fields[$j]['content'] = $fields['content'][$target_month][$activity_category_group_id];

        if (isset($fields['credit_flag'][$target_month][$activity_category_group_id])) {
          $valid_fields[$j]['credit_flag'] = $fields['credit_flag'][$target_month][$activity_category_group_id];
        } else {
          $valid_fields[$j]['credit_flag'] = Activity::CREDIT_FLAG_UNUSE;
        }

        $j++;
      }
    }

    if (sizeof($valid_fields) == 0 && !$has_error) {
      $validator = Validator::make(array(), array());
      $validator->messages()->add('none', Lang::get('validation.custom.create_record_none'));
      $this->errors = $validator->messages();

    } else if (!$has_error) {
      $result = true;
    }

    return $result;
  }
}
