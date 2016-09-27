<?php
Form::macro('date', function($name, $default = NULL, $attributes = [])
{
  $tag = '<input type="date" name="'. $name .'" ';

  if ($default) {
    $tag .= 'value="'. $default .'" ';
  }

  if (empty($attributes['id'])) {
    $attributes['id'] = $name;
  }

  foreach ($attributes as $key => $value) {
    $tag .= $key .'="'. $value .'" ';
  }

  $tag .= '>';

  return $tag;
});

/**
 * @param collection $collection
 * @param string $target
 * @param string $delimiter
 * @return string
 */
Html::macro('collection_to_string', function($collection, $target, $delimiter = ' / ') {
  $string = '';

  foreach ($collection as $value) {
    $string .= $value->$target . $delimiter;
  }

  $string = rtrim($string, $delimiter);

  return $string;
});

Html::macro('linkWithQueryString', function($url, array $queries = [], $title, array $attributes = [], $secure = null) {
  $append_query_string = http_build_query($queries, '', '&amp;');

  if (strpos($url, '?') === false) {
    $url .= '?' . $append_query_string;
  } else {
    $url .= '&amp;' . $append_query_string;
  }

  return Html::link($url, $title, $attributes, $secure);
});

/**
 * @param string $date
 * @param bool $append_week
 */
Html::macro('date', function($date, $append_week = true) {
  return Html::formatDate($date, 'Y/m/d', $append_week);
});


/**
 * @param string $date
 * @param bool $append_week
 */
Html::macro('datetime', function($date, $append_week = true) {
  return Html::formatDate($date, 'Y/m/d H:i', $append_week);
});

/**
 * @param string $date
 * @param string $date
 * @param bool $append_week
 */
Html::macro('formatDate', function($date, $format, $append_week) {
  $date = new DateTime($date);
  $format_date = $date->format($format);
  $new_date = null;

  if ($append_week) {
    $week = $date->format('w');
    $week_alias = [
      0 => '日',
      1 => '月',
      2 => '火',
      3 => '水',
      4 => '木',
      5 => '金',
      6 => '土'
    ];

    $new_date = sprintf('%s (%s)', $format_date, $week_alias[$week]);

  } else{
    $new_date = $format_date;
  }

  return $new_date;
});

/**
 * @param string $field
 * @param string $type
 * @return string
 */
Html::macro('encodeJsJsonValue', function($field, $alternative = null, $type = 'string') {
  $value = Input::get($field, $alternative);

  if ($value === null) {
    $markup = 'null';

  } else {
    switch ($type) {
      case 'string':
        $markup = '"' . addslashes($value) . '"';
        break;

      case 'numeric':
        $markup = addslashes($value);
        break;

      case 'bool':
        if ($value) {
          $markup = 'true';
        } else {
          $markup = 'false';
        }

        break;

      case 'array':
        $markup = '[';

        if (is_array($value)) {
          foreach ($value as $array_value) {
            if (is_string($value)) {
              $markup .= '"' . addslashes($array_value) . '", ';

            } else if (is_numeric($array_value)) {
              $markup .= $array_value . ', ';

            } else if (is_bool($array_value)) {
              if ($array_value) {
                $markup = 'true, ';
              } else {
                $markup = 'false, ';
              }
            }
          }

          $markup = rtrim($markup, ', ');
        }

        $markup .= ']';

        break;
    }
  }

  return $markup;
});

/**
 * @param string $field
 * @param string $label
 * @param bool $default_sort
 * @return string
 */
Html::macro('sortLabel', function($field, $label, $default_sort = false) {
  $sort_type = Input::get('sort_type');

  if ($sort_type === 'asc') {
    $style = 'glyphicon-sort-by-attributes';
  } else if ($sort_type === 'desc' || $default_sort) {
    $style = 'glyphicon-sort-by-attributes-alt';
  } else {
    $style = 'glyphicon glyphicon-sort';
  }

  $uri = URL::full();
  $parser = parse_url($uri);

  if (isset($parser['query'])) {
    parse_str($parser['query'], $parse_query);

    if (isset($parse_query['sort_type'])) {
      unset($parse_query['sort_type']);
    }

  } else {
    $parse_query = [];
  }

  $new_query = null;

  foreach ($parse_query as $name => $value) {
    if (is_array($value)) {
      foreach ($value as $assoc_value) {
        $new_query .= e($name). '[]=' . e($assoc_value) . '&amp;';
      }

    } else {
      $new_query .= e($name) . '=' . e($value) . '&amp;';
    }
  }

  if (strlen($sort_type)) {
    if ($sort_type === 'asc') {
      $order = 'desc';
    } else {
      $order = 'asc';
    }

  } else {
    $order = 'asc';
  }

  $sort_uri = sprintf('%s://%s%s?%ssort_field=%s&amp;sort_type=%s',
    $parser['scheme'],
    $parser['host'],
    $parser['path'],
    $new_query,
    $field,
    $order);

  $markup = sprintf('%s <a href="%s"><i class="glyphicon %s"></i></a>',
    $label,
    $sort_uri,
    $style);

  return $markup;
});


