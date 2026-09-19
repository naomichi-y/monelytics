<?php
namespace App\Support;

use Detection\MobileDetect;
use Illuminate\Support\Facades\Request;

/**
 * 端末判定。
 *
 * jenssegers/agent は 2019 年以降更新されておらず、PHP 8.5 に対応しない
 * mobiledetect 2.x に依存するため、mobiledetect 4.x を直接使う薄い置き換え。
 * ビューとコントローラが Agent:: で参照しているため、同じ静的 API を保つ。
 */
class Agent
{
    private static ?MobileDetect $detector = null;

    private static function detector(): MobileDetect
    {
        if (self::$detector === null) {
            self::$detector = new MobileDetect();
            self::$detector->setUserAgent((string) Request::userAgent());
        }

        return self::$detector;
    }

    public static function isMobile(): bool
    {
        return self::detector()->isMobile();
    }

    public static function isTablet(): bool
    {
        return self::detector()->isTablet();
    }

    public static function isDesktop(): bool
    {
        return ! self::isMobile() && ! self::isTablet();
    }
}
