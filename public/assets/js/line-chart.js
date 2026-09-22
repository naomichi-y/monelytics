$(function () {
  /**
   * 年別集計の推移グラフ。
   * 横軸は集計表と同じ刻み (年単位か月単位)、系列は小項目。
   */
  $.fn.loadYearlyTrend = function(params) {
    var $element = $(this);

    // タブの中身が届いた時点では、グラフはまだ取りに行っていない。ここを
    // 空のままにすると、その間タブが絞り込みだけの帯になって中身が消える。
    //
    // 既に描いてあるとき (絞り込みの切り替え) は残す。差し替わるまでの
    // 一瞬だけ消えるほうが目に付く。
    if (!$element.children().length) {
      $element.showLoading();
    }

    $.get("/summary/yearly/line-chart-data",
      params,
      function(data) {
        if (data.series && data.series.length) {
          drawLineChart(data.labels, data.series);
        } else {
          $element.html('<p>データがありません。</p>');
        }
      },
      "json"
    );

    /**
     * 横軸のラベルを何個おきに出すかを、実際の描画幅から決める。
     *
     * 以前は ceil(n / 24) で「24 個に収める」としていたが、24 個入るかどうかは
     * 幅の側の話で、入らないときは黙って重なっていた。'2006/07' は 41px、
     * プロット幅は既定の画面で 996px なので、24 個では 1 つあたり 41.5px しか
     * 取れない。両端のラベルは目盛りを中心に左右へ伸びるぶんさらに食い込み、
     * 20 年分では右端の 2 つが 14px 重なっていた。
     *
     * Highcharts 任せ (step を外す) にはしない。自動の間引きは回転を前提に
     * していて、同じ 20 年分で 13px、5 年分では 39px 重なる。
     *
     * @param {number} plotWidth 目盛りが並ぶ幅
     * @param {number} count ラベルの総数
     * @return {number}
     */
    function labelStep(plotWidth, count) {
      // '2006/07' の実測値と、隣と地続きに見えない最小のすき間。
      var LABEL_WIDTH = 41;
      var LABEL_GAP = 12;

      var fits = Math.max(2, Math.floor(plotWidth / (LABEL_WIDTH + LABEL_GAP)));

      return (count > fits) ? Math.ceil(count / fits) : 1;
    }

    function drawLineChart(labels, series) {
      // 支出は正に揃えてあるので通常は 0 以上しかない。軸の空いた側を
      // 描かないよう 0 起点にするが、返金が上回って負になる小項目もあるため
      // その場合だけ自動範囲に戻す。
      var hasNegative = series.some(function(s) {
        return s.data.some(function(value) {
          return value !== null && value < 0;
        });
      });

      // Highcharts 6 で jQuery プラグイン形式 ($element.highcharts()) が
      // 廃止されたため、DOM 要素を直接渡す。
      Highcharts.chart($element[0], {
        chart: {
          type: 'line',
          // zoomType は 11 で chart.zooming.type へ移動した。
          zooming: {
            type: 'x'
          },
          events: {
            // 間引き幅は描画幅が決まってからでないと出せない。画面幅が
            // 変わったときも引き直されるので、拡大縮小にも追従する。
            // 求めた値と同じなら何もしないため、再描画は繰り返さない。
            render: function() {
              var axis = this.xAxis[0];
              var step = labelStep(this.plotWidth, labels.length);

              // 末尾のラベルは間引きの並びから外れた位置にも出る。間引いて
              // いる間はそこだけ隣と 1px まで詰まるので出さない。全部出して
              // いるとき (step が 1) は詰まりようがないため、最後の月まで
              // 名前を付ける。
              var showLast = (step === 1);

              if (axis.options.labels.step !== step || axis.options.showLastLabel !== showLast) {
                axis.update({ showLastLabel: showLast, labels: { step: step } }, false);
                this.redraw(false);
              }
            }
          }
        },
        title: {
          text: ''
        },
        xAxis: {
          categories: labels,
          // 実際の幅が決まってから chart.events.render が入れ直す。ここでは
          // 1 つ目の描画で全部並べてしまわないよう、控えめな値から始める。
          labels: {
            step: labelStep(600, labels.length)
          }
        },
        yAxis: {
          min: hasNegative ? null : 0,
          title: {
            text: '金額'
          },
          labels: {
            formatter: function() {
              // 既定の桁区切りは空白なので明示する。単位は画面の他の金額と揃える。
              return Highcharts.numberFormat(this.value, 0, '.', ',') + ' 円';
            }
          }
        },
        tooltip: {
          shared: true,
          pointFormatter: function() {
            var amount = Highcharts.numberFormat(this.y, 0, '.', ',') + ' 円';

            // 共有ツールチップは同じ目盛りの小項目を全て並べるため、指している
            // ものが埋もれる。捉えた小項目は太字にし、残りは薄く落とす。
            if (this === this.series.chart.hoverPoint) {
              return '<span style="color:' + this.series.color + '">●</span> '
                + '<b>' + this.series.name + '</b>: <b>' + amount + '</b><br/>';
            }

            // 薄さは fill-opacity で出す。tspan に opacity は効かない。
            return '<span style="color:' + this.series.color + ';fill-opacity:0.45">●</span> '
              + '<span style="fill-opacity:0.45">' + this.series.name + ': ' + amount + '</span><br/>';
          }
        },
        plotOptions: {
          line: {
            marker: {
              // 点が多いときは印を出すと潰れる。
              enabled: (labels.length <= 24)
            },
            // 記録のない期間で線を切り、0 と誤読させない。
            connectNulls: false
          }
        },
        legend: {
          enabled: true
        },
        series: series,
        credits: {
          enabled: false
        }
      });
    }
  }
});
