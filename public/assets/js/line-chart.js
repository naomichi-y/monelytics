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
          title: {
            text: '金額'
          },
          labels: {
            formatter: function() {
              return Highcharts.numberFormat(this.value, 0);
            }
          }
        },
        tooltip: {
          shared: true,
          pointFormatter: function() {
            return '<span style="color:' + this.series.color + '">●</span> '
              + this.series.name + ': <b>' + Highcharts.numberFormat(this.y, 0) + '</b><br/>';
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
