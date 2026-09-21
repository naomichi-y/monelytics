/**
 * 日付入力のカレンダーに土日と祝日の色を付ける。
 *
 * 以前は gcalendar-holidays.js (2014 年の配布物) が Google カレンダーの
 * 代理サーバへ問い合わせていたが、その宛先は今は応答しない。祝日は
 * app/Libraries/Calendar が読んでいる内閣府の一覧から取り、月別集計の
 * カレンダーと同じ出どころに揃える。
 *
 * 一覧はカレンダーを開いたときに、表示している年のぶんだけ取りに行く。
 * 間に合わなかったぶんは届いた時点で描き直す。取れなくても土日の色は付く。
 */
(function($) {
    if (!$ || !$.datepicker || !$.datepicker.setDefaults) {
        return;
    }

    /** 取得済みの祝日。Y-m-d => 名称 */
    var holidays = {};

    /** 問い合わせ済みの年。応答の有無にかかわらず二度は行かない。 */
    var requested = {};

    /**
     * @param {Date} date
     * @returns {string} Y-m-d
     */
    var format = function(date) {
        var month = ("0" + (date.getMonth() + 1)).slice(-2);
        var day = ("0" + date.getDate()).slice(-2);

        return date.getFullYear() + "-" + month + "-" + day;
    };

    /**
     * 対象年の祝日を取り込む。
     *
     * @param {number} year
     * @param {Function} done 取り込めたときに呼ぶ
     */
    var load = function(year, done) {
        if (requested[year]) {
            return;
        }

        requested[year] = true;

        $.getJSON("/holidays", { year: year }, function(data) {
            $.extend(holidays, data);

            if (done) {
                done();
            }
        });
    };

    /**
     * 開いているカレンダーを描き直す。
     *
     * @param {HTMLElement} input
     */
    var refresh = function(input) {
        if (input) {
            $(input).datepicker("refresh");
        }
    };

    $.datepicker.setDefaults({
        beforeShow: function(input) {
            var date = $(input).datepicker("getDate") || new Date();

            load(date.getFullYear(), function() {
                refresh(input);
            });
        },

        onChangeMonthYear: function(year, month, inst) {
            var input = inst && inst.input ? inst.input[0] : null;

            load(year, function() {
                refresh(input);
            });
        },

        // 祝日が土日と重なる日は祝日として扱う。
        beforeShowDay: function(date) {
            var name = holidays[format(date)];

            if (name) {
                return [true, "calendar-holiday", name];
            }

            if (date.getDay() === 0) {
                return [true, "calendar-sunday", ""];
            }

            if (date.getDay() === 6) {
                return [true, "calendar-saturday", ""];
            }

            return [true, "", ""];
        }
    });
})(window.jQuery);
