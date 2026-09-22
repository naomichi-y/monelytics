$(function () {
  $.fn.loadPieChart = function(params) {
    var $element = $(this);

    // @see assets/js/line-chart.js の loadYearlyTrend。こちらはタブの中身が
    // 空の div だけなので、待っている間タブが何も無い状態になる。
    if (!$element.children().length) {
      $element.showLoading();
    }

    $.get("/summary/monthly/pie-chart-data",
      params,
      function(data) {
        var result = [];

        $.each(data.constituents, function(index, row) {
           result.push([row.name, row.amount]);
        });

        if (result.length) {
          drawPieChart(result);
        } else {
          $element.html('<p>データがありません。</p>');
        }
      },
      "json"
    );

    /**
     * 金額に単位を添える。画面の他の金額と揃える。
     */
    function formatAmount(amount) {
      return Highcharts.numberFormat(amount, 0, '.', ',') + ' 円';
    }

    function drawPieChart(data) {
      Highcharts.getOptions().plotOptions.pie.colors = (function () {
        var colorSet;

        if (params.balance_type == 1) {
          colorSet = 8;
        } else {
          colorSet = 2;
        }

        var colors = [],
          base = Highcharts.getOptions().colors[colorSet],
          i;
        var j = Object.keys(data).length;

        for (i = 0; i < j; i += 1) {
          // 11 以降、色の生成は小文字の Highcharts.color()。
          colors.push(Highcharts.color(base).brighten((i - 3) / 7).get());
        }
        return colors;
      }());

      // Highcharts 6 で jQuery プラグイン形式が廃止された。
      Highcharts.chart($element[0], {
        chart: {
          plotBackgroundColor: null,
          plotBorderWidth: null,
          plotShadow: false
        },
        title: {
          text: ''
        },
        tooltip: {
          // 金額を主、割合を従にする。桁区切りは既定が空白なので明示する。
          pointFormatter: function() {
            return '<b>' + formatAmount(this.y) + '</b> ('
              + Highcharts.numberFormat(this.percentage, 1) + '%)';
          }
        },
        plotOptions: {
          pie: {
            allowPointSelect: true,
            cursor: 'pointer',
            dataLabels: {
              enabled: (window.innerWidth > 768) ? true : false,
              formatter: function() {
                return '<b>' + this.point.name + '</b> ' + formatAmount(this.y)
                  + ' (' + Highcharts.numberFormat(this.percentage, 1) + '%)';
              },
              style: {
                  color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
              }
            }
          }
        },
        series: [{
          type: 'pie',
          name: '構成比率',
          data: data
        }],
        'credits': {
          enabled: false
        }
      });
    }
  }
});


