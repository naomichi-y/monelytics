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
            'activity_category_item_id' => [1, 2],
        ], 'link');

        $this->assertSame([
            'activity_category_item_id' => ['1', '2'],
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
     * 数字と単位の間は半角空白 1 つ。詰めると桁の並びに単位が食い込む。
     * 0 のときはリンクにしない。押しても絞り込みの結果は空で、開く意味がない。
     */
    public function testAmountLinkSeparatesUnitAndDropsZeroLink()
    {
        $link = fn($text) => '<a href="/summary/daily">' . $text . '</a>';

        $this->assertSame(
            '<span class="money"><a href="/summary/daily">1,200</a> <span class="unit">円</span></span>',
            Html::amountLink(1200, $link)
        );

        $this->assertSame(
            '<span class="money">0 <span class="unit">円</span></span>',
            Html::amountLink(0, $link)
        );
    }

    /**
     * 小項目ごとの比較は率ではなく額で出す。元が小さい小項目は率が跳ね上がり、
     * 額の大きい小項目より目立ってしまうため。記号は増減率と揃える。
     */
    public function testComparisonAmountShowsDirection()
    {
        $this->assertSame('+1,600', Html::comparisonAmount(1600));
        $this->assertSame('-900', Html::comparisonAmount(-900));
        $this->assertSame('±0', Html::comparisonAmount(0));
    }

    /**
     * 支出は増えたときに正となるよう符号を揃えて渡されるため、+ は
     * 金額が負の行でも「増えた」を意味する。
     *
     * ▲▼ を使わないのは、日本の会計表記で ▲ が負の数を指すためで、
     * 支出欄で意味が逆に読まれるのを避ける。
     */
    public function testComparisonRateShowsDirection()
    {
        $this->assertSame('+15%', Html::comparisonRate(15));
        $this->assertSame('-21%', Html::comparisonRate(-21));
        $this->assertSame('±0%', Html::comparisonRate(0));
    }

    /**
     * ± は増減がちょうど 0 のときだけに使う。合計のように元の額が大きいと
     * 1% 未満の増減が起こりやすく、整数に丸めると「増減なし」と区別が
     * 付かなくなる。
     */
    public function testComparisonRateKeepsChangeUnderOnePercent()
    {
        $this->assertSame('-0.2%', Html::comparisonRate(-0.2));
        $this->assertSame('+0.1%', Html::comparisonRate(0.1));

        // 1% 以上は従来どおり整数で出す。
        $this->assertSame('-7%', Html::comparisonRate(-6.8));
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
