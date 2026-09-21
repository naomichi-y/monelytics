$(function () {
  /**
   * 年別集計の推移グラフ。
   * 横軸は集計表と同じ刻み (年単位か月単位)、系列は科目。
   */
  $.fn.loadYearlyTrend = function(params) {
    var $element = $(this);

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

    function drawLineChart(labels, series) {
      // 支出は正に揃えてあるので通常は 0 以上しかない。軸の空いた側を
      // 描かないよう 0 起点にするが、返金が上回って負になる科目もあるため
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
          }
        },
        title: {
          text: ''
        },
        xAxis: {
          categories: labels,
          // 月単位で年をまたぐと目盛りが詰まるため間引かせる。
          labels: {
            step: (labels.length > 24) ? Math.ceil(labels.length / 24) : 1
          }
        },
        yAxis: {
          min: hasNegative ? null : 0,
          title: {
            text: '金額'
          },
          labels: {
            formatter: function() {
              // 既定の桁区切りは空白なので明示する。
              return Highcharts.numberFormat(this.value, 0, '.', ',');
            }
          }
        },
        tooltip: {
          shared: true,
          pointFormatter: function() {
            var amount = Highcharts.numberFormat(this.y, 0, '.', ',');

            // 共有ツールチップは同じ目盛りの科目を全て並べるため、指している
            // ものが埋もれる。捉えた科目は太字にし、残りは薄く落とす。
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
