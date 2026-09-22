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
   * 縦スクロールバーの幅として見込む値。これを超えるはみ出しは、列が
   * 入りきっていないものとして横スクロールを残す。
   */
  var SCROLLBAR_SLACK = 20;

  /**
   * 集計表を入れる箱。月別集計と年別集計の report.blade.php が持つ。
   * 隠す側と戻す側で同じものを指すため 1 箇所に置く。
   */
  var TABLE_CONTAINER = "#tab-container";

  /**
   * ヘッダを固定した表を組み立て、ウィンドウ幅が変わったら組み直す。
   *
   * jquery.tablefix は呼ばれた時点の幅をピクセルで書き込み、その後は
   * 追従しない。読み込んだあとにウィンドウの幅を変えると、表だけが元の幅の
   * まま残り、右側が空く。組み直せるよう、加工前の HTML を控えておく。
   *
   * @param {Object} options table (対象のセレクタ)、widthAdjust、fixRows、fixCols
   */
  $.fn.fixTableHeader = function(options) {
    var $container = $(this);
    var original = $container.html();

    var build = function() {
      // 組み直している間は描かせない。下で素の HTML に戻すため、そのまま
      // だと表が本来の幅で一度置かれ、タブの枠を突き抜ける。ここは全て
      // 同期なので、隠してから戻すまでに描画は挟まらない。
      //
      // display ではなく visibility にする。display: none では箱の幅が 0 に
      // なり、tablefix へ渡す幅が取れない。
      $container.css("visibility", "hidden");

      // tablefix は表を複製して重ねる。二重に掛けないよう毎回もとに戻す。
      $container.html(original);

      var minHeight = 400;
      var height = window.innerHeight - minHeight;

      $container.find(options.table).tablefix({
        width: $container.width() - (options.widthAdjust || 0),
        height: (height < minHeight) ? minHeight : height,
        fixRows: options.fixRows,
        fixCols: options.fixCols
      });

      // tablefix が作る本体側の入れ物は overflow: auto。縦スクロールバーが
      // 出るとその幅ぶんだけ表がはみ出し、中身は収まっているのに横スクロール
      // バーまで現れる。はみ出しがスクロールバーの幅に収まるときだけ横を
      // 止める。列が多くて本当に入りきらない表 (年別集計) は動かせるまま。
      $container.find("div").filter(function() {
        return this.style.overflow === "auto";
      }).each(function() {
        var overflow = this.scrollWidth - this.clientWidth;

        this.style.overflowX = (overflow > 0 && overflow <= SCROLLBAR_SLACK) ? "hidden" : "auto";
      });

      $container.css("visibility", "");
    };

    build();

    // タブを切り替えるたびに読み込まれるため、前回の分を外してから繋ぐ。
    var timer = null;

    $(window).off("resize.fixTableHeader").on("resize.fixTableHeader", function() {
      clearTimeout(timer);
      timer = setTimeout(build, 200);
    });

    return $container;
  };

  /**
   * 開いているタブを持たせるクエリ。
   *
   * 以前はクッキーに入れていた。同じ画面を 2 つ開くと後から切り替えたほうに
   * 引きずられ、URL を人に渡しても相手には別のタブが出た。URL に持たせれば
   * 見ているものと渡すものが一致する。
   *
   * 番号ではなく名前を持つ。番号はタブを並べ替えたときに黙って別のタブを
   * 指すうえ、URL を見ても何のタブか読めない。名前は各タブの data-tab。
   */
  var TAB_PARAM = "tab";

  /**
   * @returns {?string} 今開いているタブの名前。指定がなければ null
   */
  function currentTab() {
    return new URLSearchParams(window.location.search).get(TAB_PARAM);
  }

  /**
   * タブの名前を今の URL に書き戻す。
   *
   * 履歴は積まない。タブの切り替えは「戻る」で辿りたい操作ではないうえ、
   * 何度か切り替えたあとに戻ろうとすると、その回数ぶん空打ちすることになる。
   *
   * @param {string} name
   */
  function rememberTab(name) {
    var url = new URL(window.location.href);

    url.searchParams.set(TAB_PARAM, name);
    window.history.replaceState(null, "", url.toString());
  }

  /**
   * タブを表示する。開いているタブは URL のクエリに持つ。
   *
   * 名前は各 li の data-tab から取る。月別集計の構成グラフのように、同じ
   * エンドポイントを引数違いで 2 つ並べるタブがあるため、リンク先からは
   * 一意に決められない。
   */
  /**
   * 最初の読み込みで確保する高さ。切り替えなら直前のパネルに合わせられるが、
   * 画面を開いた 1 回目には比べる相手がいない。集計表が収まる高さより低く、
   * 見出しだけの高さ (67px) よりは十分高いところ。
   */
  var TAB_LOADING_MIN_HEIGHT = 240;

  /**
   * 待っているあいだ、パネルに被せていることを表す印。
   * 高さの切り方と重ね方は style.css が持つ。
   */
  var TAB_WAITING_CLASS = "tab-waiting";

  /**
   * 読み込み中であることを示す中身。
   *
   * 中身ごと入れ替える使い方と、上へ重ねる使い方の両方があるため、
   * 組み立てだけを分けておく。
   *
   * @returns {string}
   */
  function loadingMarkup() {
    return '<div class="tab-loading" role="status">'
      + '<span class="spinner-border" aria-hidden="true"></span>'
      + '<span>読み込んでいます…</span>'
      + '</div>';
  }

  /**
   * 読み込み中であることを、その要素の中に出す。
   *
   * タブの中身と、届いたあとに自分でもう一度取りに行くグラフとで、同じ
   * 見た目にしたいので 1 箇所に置く。
   *
   * @returns {jQuery}
   */
  $.fn.showLoading = function() {
    return $(this).html(loadingMarkup());
  };

  $.fn.startTabs = function() {
    var $tabs = $(this);
    var names = $tabs.find("> ul > li").map(function() {
      return $(this).data(TAB_PARAM);
    }).get();

    // 切り替える直前のパネルの高さ。読み込み中に確保する分として使う。
    var reserved = null;

    $tabs.tabs({
      // 知らない名前や指定なしは先頭のタブ。
      active: Math.max(0, names.indexOf(currentTab())),
      activate: function(e, ui) {
        rememberTab(names[ui.newTab.index()]);
      },
      beforeActivate: function(e, ui) {
        reserved = ui.oldPanel.length ? ui.oldPanel.outerHeight() : null;
      },

      /**
       * 中身はどのタブも ajax で取りに行く。jQuery UI は切り替えた瞬間に
       * 空のパネルを見せて応答を待つため、集計表のように重いものだと、
       * その間だけタブの枠が見出しの高さまで縮む。届くと元の高さへ戻るので、
       * 下にあるものが上下に飛ぶうえ、待っているのか壊れたのか分からない。
       *
       * 直前と同じ高さを確保したうえで、待っていることを出す。中身は
       * jQuery UI が応答で上書きするので、消す手当ては要らない。
       */
      beforeLoad: function(e, ui) {
        ui.panel
          .css("min-height", (reserved || TAB_LOADING_MIN_HEIGHT) + "px")
          .showLoading();

        // 失敗したときは load が起きない。確保した高さが残り続ける。
        ui.jqXHR.fail(function() {
          ui.panel.css("min-height", "");
        });
      },
      /**
       * 届いた中身は、断片の script が組み直すまで素のままで置かれる。
       * 年別集計の集計表は本来の幅が 2900px を超えるため、そのあいだ
       * タブの枠を突き抜けて画面の外まで伸びていた。
       *
       * この load は jQuery UI が中身を入れた直後に、まだ同じ task の中で
       * 起きる。ここで隠せば素のままの表は一度も描かれない。
       *
       * 戻すのは次の task。断片の $(function(){}) は ready の解決を待つので
       * この task の後、次の task の前に動く。組み直しを持たない画面でも
       * 必ず戻るため、隠したままになることはない。
       */
      load: function(e, ui) {
        var $container = ui.panel.find(TABLE_CONTAINER);

        if (!$container.length) {
          ui.panel.css("min-height", "");

          return;
        }

        $container.css("visibility", "hidden");

        // jQuery UI はここへ来るまでに中身を差し替えており、読み込み中の
        // 表示はもう残っていない。しかも組み直しは次の task なので、その前に
        // 一度描画が入る。ここで置き直さないと、組み直しに掛かる時間ぶん
        // (年別集計で 1 秒以上) タブが真っ白になる。
        var $waiting = $(loadingMarkup()).appendTo(ui.panel.addClass(TAB_WAITING_CLASS));

        // 隠した表は場所を取ったままなので、確保した高さで頭を止める。
        // 止めないとパネルが表の高さまで伸び、被せた表示が画面の外へ行く。
        //
        // 表の側を絶対配置にして流れから外す手もあるが、そうすると幅が
        // パネルの内側ではなく padding を含む側で決まり、tablefix へ渡す幅が
        // 20px ほど広くなる。組み上がった表がタブの枠から出る。
        ui.panel.css("max-height", ui.panel.css("min-height"));

        setTimeout(function() {
          $waiting.remove();
          ui.panel.removeClass(TAB_WAITING_CLASS).css({ "min-height": "", "max-height": "" });
          $container.css("visibility", "");
        }, 0);
      }
    });
  }

  /**
   * 同じ画面を開き直すフォームに、今のタブを持たせる。
   *
   * GET のフォームは送信時にクエリを自分の入力欄から組み直すため、URL に
   * 乗せただけの tab は落ちる。月を変える、詳細検索を掛ける、のたびに
   * 既定のタブへ戻ってしまう。
   *
   * 詳細検索のモーダルは ajax で後から差し込まれるので、描画のときではなく
   * 送信のときに入れる。
   *
   * 行き先が今の画面と違うフォームは対象外。帯の検索は日別集計へ送るため、
   * 入れても読まれずクエリに残るだけになる。
   */
  $(document).on("submit", "form", function() {
    var tab = currentTab();

    if (tab === null) {
      return;
    }

    var $form = $(this);
    var action = new URL($form.attr("action") || window.location.href, window.location.href);

    if (action.pathname !== window.location.pathname) {
      return;
    }

    var selector = "input[name='" + TAB_PARAM + "']";

    if (!$form.find(selector).length) {
      $form.append($("<input>", { type: "hidden", name: TAB_PARAM }));
    }

    $form.find(selector).val(tab);
  });

  /**
   * 同じ名前でページのパスに紐付いた Cookie を消す。
   *
   * 以前使っていた jquery.cookie は path を指定せずに書いていた。その場合の
   * 保存先はブラウザが決め、URL の「最後の / まで」になる (/summary/monthly
   * で書いたものは /summary に付く)。js-cookie は "/" に書く。
   *
   * 両方が残るとブラウザは document.cookie でパスの長いほうを先に並べ、
   * js-cookie は最初に見つけたものを返して打ち切るため、移行前の古い値が
   * 新しい値を隠し続ける。月別集計でタブを選んでリロードすると集計表に戻る、
   * という形で表に出た。移行前からの利用者だけに起きるため、まっさらな
   * ブラウザでは再現しない。
   *
   * 古い保存先を 1 つに決め打ちできないので、今のページの上位パスを順に消す。
   * "/" は今の保存先なので対象にしない。
   *
   * タブはクッキーをやめて URL に持たせたため (@see $.fn.startTabs)、
   * 今これが要るのはセレクトの保持だけ。
   */
  function dropLegacyCookie(cookie_name) {
    var segments = window.location.pathname.split("/").filter(Boolean);
    var path = "";

    for (var i = 0; i < segments.length; i++) {
      path += "/" + segments[i];
      Cookies.remove(cookie_name, { path: path });
    }
  }

  /**
   * セレクトの選択状態をクッキーへ保持し、リロードしても直前の選択が残る
   * ようにする。
   *
   * タブと違って URL には乗せない。グラフの中の絞り込みでしかないため、
   * 画面を指す URL にまで出すと何を指しているのか読みにくくなる。
   */
  $.fn.rememberSelect = function(cookie_name) {
    var $element = $(this);

    dropLegacyCookie(cookie_name);

    var saved = Cookies.get(cookie_name);

    // 値が空文字の選択肢もあるため、未保存かどうかは型で判定する。
    if (typeof saved === "string" && $element.find("option").filter(function() {
      return this.value === saved;
    }).length) {
      $element.val(saved);
    }

    $element.change(function() {
      Cookies.set(cookie_name, $element.val(), { expires: 10, path: "/" });
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

