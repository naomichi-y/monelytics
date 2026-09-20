function getQueryParams() {
  if (location.search.length > 1) {
    // 自前で split すると符号化が戻らず、値に "=" を含む場合も切れる。
    var pair = {};

    new URLSearchParams(location.search).forEach(function(value, key) {
      pair[key] = value;
    });

    return pair;

  } else {
    return false;
  }
}

/**
 * サーバから受け取ったモーダルの HTML を差し込んで開く。
 *
 * Bootstrap 5 で jQuery プラグイン形式 ($(html).modal()) が廃止されたため、
 * 自分で組み立てる。閉じたときに取り除くのは、同じモーダルを開き直した
 * ときに id が重複しないようにするため。
 *
 * 取り除くのは差し込んだ要素だけにする。以前は $(".modal").remove() で
 * 全部消しており、編集モーダルを閉じると画面に元からある削除モーダルまで
 * 消えていた。
 *
 * @param {string} html
 * @returns {Element|null}
 */
function showModal(html) {
  // append 経由で差し込むと、中の <script> が実行される。
  var $injected = $(html).appendTo(document.body);
  var element = $injected.filter(".modal").get(0) || $injected.find(".modal").get(0);

  if (!element) {
    return null;
  }

  element.addEventListener("hidden.bs.modal", function() {
    $injected.remove();
  });

  bootstrap.Modal.getOrCreateInstance(element).show();

  return element;
}

$(function() {
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
    }
  });

  $.each(["put", "delete"], function(i, method) {
    $[method] = function(url, data, callback, type) {
      if (typeof data === "function") {
        type = type || callback;
        callback = data;
        data = undefined;
      }

      return $.ajax({
        type: method,
        url: url,
        data: data,
        success: callback,
        dataType: type
      });
    };
  });

  /**
   * Datepickerの初期化。
   */
  $(document).on("focus", ".date-picker", function() {
    var params = getQueryParams();
    defaultDate = "";

    if (params["date_month"]) {
      var year = Number(params["date_month"].substring(0, 4));
      var month = Number(params["date_month"].substring(5, 7));
      var current = new Date();

      // 以前は getYear() を使っていた。これは「年 - 1900」を返すため年の
      // 比較が常に成立し、条件も && だったので、別の年の同じ月を開いたとき
      // に初期表示がその月にならなかった。
      if (current.getFullYear() !== year || current.getMonth() + 1 !== month) {
        defaultDate = new Date(year, month - 1, 1);
      }
    }

    $(this).datepicker({
      dateFormat: "yy/mm/dd",
      constrainInput: false,
      defaultDate: defaultDate
    });
  });

  // フォームの最初の要素にフォーカスを合わせる
  // $("input:visible").first().focus();

  /**
   * 対象フォーム内でEnterキーが押された場合、callback関数をコールする。
   */
  $.enterCallback = function(callback) {
    $(document).on("keypress", "input", function(e) {
      if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
        callback();

        return false;
      }
    });
  }

  /**
   * "mm/dd"形式で入力された日付フォーマットを"yyyy/mm/dd"形式に変換する。
   */
  $.fn.dateFormat = function(selector) {
    // jQuery 3 で jQuery オブジェクトの selector プロパティが削除されたため、
    // 対象をセレクタ文字列で受け取る。動的に差し込まれる編集モーダル内の
    // 入力も拾えるよう、委譲は残す。
    $(document).on("change", selector, function() {
      var pattern = new RegExp("^([0-9]{1,2})/([0-9]{1,2})$");
      var matches = $(this).val().match(pattern);

      if (matches) {
        var date = new Date(new Date().getFullYear(), matches[1] - 1, matches[2]);

        if (!isNaN(date)) {
          var fullDate = date.getFullYear() + "/" + (date.getMonth() + 1) + "/" + date.getDate();
          $(this).val(fullDate);
        }
      }
    });
  }

  /**
   * タブを表示する。
   */
  $.fn.startTabs = function(cookie_name) {
    $(this).tabs({
      active: Cookies.get(cookie_name),
      activate: function(e, ui){
        Cookies.set(cookie_name, ui.newTab.index(), { expires: 10 });
      }
    });
  }

  /**
   * セレクトの選択状態をクッキーへ保持する。
   * タブと同じ持ち方 (startTabs と同一の保持期間) にして、リロードしても
   * 直前の選択が残るようにする。
   */
  $.fn.rememberSelect = function(cookie_name) {
    var $element = $(this);
    var saved = Cookies.get(cookie_name);

    // 値が空文字の選択肢もあるため、未保存かどうかは型で判定する。
    if (typeof saved === "string" && $element.find("option").filter(function() {
      return this.value === saved;
    }).length) {
      $element.val(saved);
    }

    $element.change(function() {
      Cookies.set(cookie_name, $element.val(), { expires: 10 });
    });

    return $element;
  }

  /**
   * 最初のフォーム要素が日付フィールドの場合、Datepickerが開かないよう制御する。
   */
  $.fn.disableDatepickerFocus = function() {
    $(".ui-datepicker").css("display", "none");

    $(this).click(function() {
      $(".ui-datepicker").css("display", "block");
    });
  }

  /**
   * テーブルの行が選択された際に背景色を変更する。
   */
  $(document).on("click", ".table-highlight tr", function() {
    if ($(this).attr("data-active") == "1") {
      $(this).css("background-color", "#fff");
      $(this).attr("data-active", "0");
    } else {
      $(this).css("background-color", "#fff8e7");
      $(this).attr("data-active", "1");
    }
  });
});

