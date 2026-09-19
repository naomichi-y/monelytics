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

      $element.highcharts({
        chart: {
          type: 'line',
          zoomType: 'x'
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
              // Highcharts 4 の既定の桁区切りは空白なので明示する。
              return Highcharts.numberFormat(this.value, 0, '.', ',');
            }
          }
        },
        tooltip: {
          shared: true,
          pointFormatter: function() {
            return '<span style="color:' + this.series.color + '">●</span> '
              + this.series.name + ': <b>' + Highcharts.numberFormat(this.y, 0, '.', ',') + '</b><br/>';
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
