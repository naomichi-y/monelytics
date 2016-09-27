$(function() {
  $.fn.responsiveTable = function(options) {
    var $element = $(this);
    var options = $.extend({
      maxWidth: 768
    }, options);
    var smallLayout = false;
    var headerSize;

    // 引数に指定されたテーブル要素を取得
    this.each(function () {
      function makeResponsiveLayout() {
        var $headers = $element.find("tr").first().children("td,th");
        var $headerData = [];

        // ヘッダ(1行目)のタグを排列に格納
        $headers.each(function () {
          $headerData.push($(this));
        });

        headerSize = $headerData.length;

        // 2行目のデータ行はメディアクエリによって見出しとデータが横並びとなる
        // 以下の処理は3行目以降に見出し列を追加する処理である
        $element.find("tr").slice(2).each(function () {
          for (var i = 0; i < $headerData.length; i++) {
            var $lastHeader = $element.find("thead tr th").last();
            var html;

            if ($headerData[i].context.className.indexOf('hidden-xs') == -1) {
              if (i == 0) {
                // 行が変わるタイミングでセルにCSSを追加 (マージンを入れる)
                html = "<th class=\"responsive-div\">" + $headerData[i].html() +"</th>";
              } else {
                html = "<th>" + $headerData[i].html()  + "</th>";
              }

              $lastHeader.after(html);
            }
          };
        });

        $element.find("tbody tr").slice(1).each(function () {
          $(this).first().children("td").first().addClass("responsive-div");
        });
      };

      // 最小レイアウトからデフォルトレイアウトに戻す
      function restoreTableFormat() {
        // 3行目以降に追加した全ての見出しセルを削除する
         var $headers = $element.find("thead tr th").slice(headerSize);
         $headers.remove();
      }

      if ($(window).width() <= options.maxWidth) {
        makeResponsiveLayout();
        smallLayout = true;
      }

      $(window).resize(function() {
        var currentWidth = $(window).width();

        // デフォルトレイアウトから最小レイアウトへの変更を検知
        if (!smallLayout && currentWidth <= options.maxWidth) {
          makeResponsiveLayout();
          smallLayout = true;

        // 最小レイアウトからデフォルトレイアウトへの変更を検知
        } else if (smallLayout && currentWidth > options.maxWidth) {
          restoreTableFormat();
          smallLayout = false;
        }
      });
    });
  }
});
