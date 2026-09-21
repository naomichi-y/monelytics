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
    // 区切りは URL そのものの文字である '&' にする。HTML への逃がしは
    // Html::link が行うため、ここで '&amp;' を入れると二重になり、
    // 2 つ目以降のパラメータ名が amp;xxx になって読み捨てられる。
    $append_query_string = http_build_query($queries, '', '&');

    if (strpos($url, '?') === false) {
        $url .= '?' . $append_query_string;
    } else {
        $url .= '&' . $append_query_string;
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
    $value = Request::input($field, $alternative);

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
    $sort_type = Request::input('sort_type');

    // Bootstrap 4 で glyphicon が外れたため、Bootstrap Icons の名前を使う。
    if ($sort_type === 'asc') {
        $style = 'bi-sort-down-alt';
    } else if ($sort_type === 'desc' || $default_sort) {
        $style = 'bi-sort-down';
    } else {
        $style = 'bi-arrow-down-up';
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

    $markup = sprintf('%s <a href="%s"><i class="bi %s"></i></a>',
        $label,
        $sort_uri,
        $style);

    return $markup;
});



/**
 * アプリ自身の JS を、ファイルの更新時刻を付けて読み込む。
 *
 * 配信元は Cache-Control を返さないため、ブラウザが古いファイルを
 * 使い続けることがある。応答の形を変えた JS が入れ替わらないと
 * 画面が壊れるので、内容が変わったら URL も変わるようにする。
 * バージョンがパスに入っている vendor 配下には使わない。
 */
Html::macro('versionedScript', function($path, $attributes = [], $secure = null) {
    return Html::script(Html::assetVersion($path), $attributes, $secure);
});

/**
 * アプリ自身の CSS を、ファイルの更新時刻を付けて読み込む。
 */
Html::macro('versionedStyle', function($path, $attributes = [], $secure = null) {
    return Html::style(Html::assetVersion($path), $attributes, $secure);
});

/**
 * パスにファイルの更新時刻を付与する。ファイルがなければそのまま返す。
 */
Html::macro('assetVersion', function($path) {
    $file = public_path($path);

    if (!is_file($file)) {
        return $path;
    }

    return $path . '?v=' . filemtime($file);
});

/**
 * 前月比の増減額を、符号と桁区切りを付けて返す。
 *
 * 科目ごとの比較は率ではなく額で出す。元が小さい科目は率が跳ね上がり
 * (100 円から 300 円で +200%)、額の大きい科目より目立ってしまうため。
 *
 * 記号は Html::comparisonRate と揃える。▲▼ を使わない理由もそちらと同じ。
 */
Html::macro('comparisonAmount', function($difference) {
    if ($difference == 0) {
        return '±0';
    }

    $mark = $difference > 0 ? '+' : '-';

    return $mark . number_format(abs($difference));
});

/**
 * 前月比の増減率を、符号を付けて返す。
 *
 * 支出は増えたときに正となるよう符号を揃えて渡されるため、金額そのものが
 * 負で表示される行でも正の値は「増えた」を意味する。
 *
 * 記号に ▲▼ は使わない。日本の会計表記では ▲ が負の数を指すのが通例で、
 * 「増えた」を ▲ で表すと支出欄で意味が逆に読まれる。+ / - なら取り違えない。
 */
Html::macro('comparisonRate', function($rate) {
    // ± は増減がちょうど 0 のときだけ。1% 未満の増減を整数に丸めると
    // 「増減なし」と区別が付かなくなるため、その範囲は小数第 1 位まで出す。
    if ($rate == 0) {
        return '±0%';
    }

    $mark = $rate > 0 ? '+' : '-';
    $absolute = abs($rate);
    $value = $absolute < 1 ? number_format($absolute, 1) : (string) round($absolute);

    return $mark . $value . '%';
});
