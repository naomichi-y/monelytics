<?php
namespace Tests\Http;

use Html;

use Tests\TestCase;

class MacrosTest extends TestCase {
    /**
     * 区切りの '&' は Html::link が一度だけ逃がす。'&amp;' を渡して二重に
     * なると、2 つ目以降のパラメータ名が amp;xxx になって読み捨てられる。
     */
    public function testLinkWithQueryStringEscapesAmpersandOnce()
    {
        $markup = Html::linkWithQueryString('/summary/daily', [
            'begin_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ], 'link');

        $this->assertStringNotContainsString('&amp;amp;', $markup);

        $this->assertSame([
            'begin_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ], $this->queryOf($markup));
    }

    /**
     * すでにクエリを持つ URL には '&' で継ぎ足す。
     */
    public function testLinkWithQueryStringAppendsToExistingQuery()
    {
        $markup = Html::linkWithQueryString('/summary/daily?date_month=2026-09', [
            'credit_flag' => 0,
        ], 'link');

        $this->assertSame([
            'date_month' => '2026-09',
            'credit_flag' => '0',
        ], $this->queryOf($markup));
    }

    /**
     * 配列のパラメータも、受け取り側で配列として読める形にする。
     */
    public function testLinkWithQueryStringKeepsArrayParameter()
    {
        $markup = Html::linkWithQueryString('/summary/daily', [
            'activity_category_group_id' => [1, 2],
        ], 'link');

        $this->assertSame([
            'activity_category_group_id' => ['1', '2'],
        ], $this->queryOf($markup));
    }

    public function testAssetVersionAppendsModifiedTime()
    {
        $path = 'assets/css/style.css';

        $this->assertSame($path . '?v=' . filemtime(public_path($path)), Html::assetVersion($path));
    }

    public function testAssetVersionKeepsMissingFileUntouched()
    {
        $path = 'assets/css/does-not-exist.css';

        $this->assertSame($path, Html::assetVersion($path));
    }

    /**
     * 支出は増えたときに正となるよう符号を揃えて渡されるため、▲ は
     * 金額が負の行でも「増えた」を意味する。
     */
    public function testComparisonRateShowsDirection()
    {
        $this->assertSame('▲15%', Html::comparisonRate(15));
        $this->assertSame('▼21%', Html::comparisonRate(-21));
        $this->assertSame('±0%', Html::comparisonRate(0));
    }

    /**
     * リンクの href を、ブラウザが送るクエリとして読み直す。
     *
     * @param string $markup
     * @return array
     */
    private function queryOf($markup)
    {
        preg_match('/href="([^"]*)"/', $markup, $matches);
        parse_str((string) parse_url(html_entity_decode($matches[1]), PHP_URL_QUERY), $query);

        return $query;
    }
}
