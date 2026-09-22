<?php
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
 * 検索条件を <script> の中へ値として埋め込む。
 *
 * 逃がしは json_encode に任せ、'<' と '>' も \u に倒す (JSON_HEX_TAG)。
 * 以前は addslashes で引用符だけを潰していたため、引用符を含まない
 * '</script><img src=x onerror=...>' のような値がそのまま出て、script 要素を
 * 閉じてしまっていた。値はクエリ文字列から来るので、URL を踏ませるだけで
 * 任意のスクリプトが動く状態だった。
 *
 * 型を指定するのは、受け取る側が文字列か配列かを決めたいため。指定と違う値が
 * 来たときは、その型の空の値へ倒す。JS の構文として壊れたものを書き出すと、
 * その画面の JS が丸ごと動かなくなる (数値として 'abc' を書けば構文誤り)。
 *
 * @param string $field
 * @param mixed $alternative 未指定のときの値
 * @param string $type string|numeric|bool|array
 * @return string
 */
Html::macro('encodeJsJsonValue', function($field, $alternative = null, $type = 'string') {
    $value = Request::input($field, $alternative);

    if ($value === null) {
        return 'null';
    }

    switch ($type) {
        case 'numeric':
            $value = is_numeric($value) ? $value + 0 : 0;
            break;

        case 'bool':
            $value = (bool) $value;
            break;

        case 'array':
            $value = is_array($value) ? array_values($value) : [];
            break;

        default:
            // 配列を文字列へ倒すと PHP 8 は警告を出し、Laravel はそれを例外に
            // 変えるため、?keyword[]=x のような指定で画面ごと落ちる。
            $value = is_array($value) ? '' : (string) $value;
            break;
    }

    return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
});

/**
 * フォームの初期値としてリクエストから読む。
 *
 * クエリ文字列の形は送る側が決められるので、?keyword[]=x のように配列も来る。
 * Form::text はその値を e() に通すため、配列だと「Array to string conversion」が
 * 例外になり、その欄を持つ画面が丸ごと 500 になる。帯の検索はどの画面にもある
 * ので、URL 1 つで全画面を落とせた。
 *
 * 配列は指定なしとして扱う。絞り込みが 1 つ落ちるだけで、画面は開く。
 * 複数選択のように配列を待つ欄では使わない (Request::input のまま)。
 *
 * @param string $field
 * @param mixed $default
 * @return mixed
 */
Html::macro('requestValue', function($field, $default = null) {
    $value = Request::input($field, $default);

    return is_array($value) ? $default : $value;
});

/**
 * @param string $field
 * @param string $label
 * @param bool $default_sort
 * @return string
 */
Html::macro('sortLabel', function($field, $label, $default_sort = false) {
    // 配列で来ると下の strlen が TypeError になり、見出しを持つ一覧が落ちる。
    $sort_type = Html::requestValue('sort_type');

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
 * 金額に単位を添える。
 *
 * 単位はどの欄にも付ける。見出しが「金額」と言っていても、数字だけを抜き出して
 * 読む人には何の数か分からないため。
 *
 * 戻り値はマークアップなので {!! !!} で出す。数字は number_format を通した
 * あとなので、逃がすものは含まれない。
 *
 * @param int $amount
 * @return string
 */
Html::macro('amount', function($amount) {
    return Html::withUnit(number_format($amount));
});

/**
 * 金額をリンクにして単位を添える。
 *
 * 0 のときはリンクにしない。押しても絞り込みの結果は空で、開く意味がない。
 *
 * @param int $amount
 * @param callable $link 桁区切り済みの文字列を受け取り、リンクを返す
 * @return string
 */
Html::macro('amountLink', function($amount, callable $link) {
    if (!$amount) {
        return Html::amount(0);
    }

    return Html::withUnit($link(number_format($amount)));
});

/**
 * 組み立て済みの金額表記 (リンクなど) に単位を添える。
 *
 * リンクの外側に置く。単位はリンク先と関係がなく、含めると押せる範囲が
 * 数字より広がる。
 *
 * 数字と単位の間は半角空白 1 つ。詰めると桁の並びに単位が食い込んで読みにくく、
 * リンクでは下線が数字で止まるぶん特に接して見える。
 *
 * @param string $markup
 * @return string
 */
Html::macro('withUnit', function($markup) {
    return '<span class="money">' . $markup . ' <span class="unit">円</span></span>';
});

/**
 * 前月比の増減額を、符号と桁区切りを付けて返す。
 *
 * 小項目ごとの比較は率ではなく額で出す。元が小さい小項目は率が跳ね上がり
 * (100 円から 300 円で +200%)、額の大きい小項目より目立ってしまうため。
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
