$(function () {
  /**
   * 年別集計の推移グラフ。
   * X 軸を月、系列を年にして、年ごとの増減を重ねて比較する。
   */
  $.fn.loadYearlyTrend = function(params) {
    var $element = $(this);
    var months = ['1月', '2月', '3月', '4月', '5月', '6月',
                  '7月', '8月', '9月', '10月', '11月', '12月'];

    $.get("/summary/yearly/line-chart-data",
      params,
      function(data) {
        var series = [];

        $.each(data.trends, function(year, amounts) {
          var points = [];

          // amounts はキーが 1〜12 のオブジェクト。月順に並べ直す。
          for (var month = 1; month <= 12; month++) {
            points.push(amounts[month] === undefined ? null : amounts[month]);
          }

          // 記録が 1 件もない年は凡例だけ増えるので出さない。
          if (points.some(function(value) { return value !== null; })) {
            series.push({ name: year + '年', data: points });
          }
        });

        if (series.length) {
          drawLineChart(series);
        } else {
          $element.html('<p>データがありません。</p>');
        }
      },
      "json"
    );

    function drawLineChart(series) {
      $element.highcharts({
        chart: {
          type: 'line'
        },
        title: {
          text: ''
        },
        xAxis: {
          categories: months
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
              enabled: true
            },
            // 記録のない月で線を切り、0 と誤読させない。
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
