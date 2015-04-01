$(function() {
  /**
   * Datepickerの初期化。
   */
  $(document).on("focus", ".date-picker", function() {
    $(this).datepicker({
      dateFormat: "yy/mm/dd",
      constrainInput: false,
    });
  });

  // フォームの最初の要素にフォーカスを合わせる
  // $('input:visible').first().focus();

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
  $.fn.dateFormat = function() {
    $(document).on("change", $(this).selector, function() {
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
      active: $.cookie(cookie_name),
      activate: function(e, ui){
        $.cookie(cookie_name, ui.newTab.index(),{
          expires : 10
        });
      }
    });
  }

  /**
   * 初めのフォーム要素が日付フィールドの場合、Datepickerが開かないよう制御する。
   */
  $.fn.disableDatepickerFocus = function() {
    $(".ui-datepicker").css("display", "none");

    $(this).click(function() {
      $(".ui-datepicker").css("display", "block");
    });
  }
});

