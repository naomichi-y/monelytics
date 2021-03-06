function getQueryParams() {
  if (location.search.length > 1) {
    var pair = new Object();
    var param = location.search.substr(1).split("&");

    for (var i = 0; i < param.length; i++) {
      var val = param[i].split("=");
      pair[val[0]] = val[1];
    }

    return pair;

  } else {
    return false;
  }
}

$(function() {
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
    }
  });

  $.each(["put", "delete"], function(i, method) {
    $[method] = function(url, data, callback, type) {
      if ($.isFunction(data)) {
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
      var year = params["date_month"].substring(0, 4);
      var month = params["date_month"].substring(5, 7);
      var current = new Date();

      if (current.getYear() != year && current.getMonth() + 1 != month) {
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

