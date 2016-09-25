$(function () {
  $.fn.loadPieChart = function(params) {
    var $element = $(this);

    $.get("/summary/monthly/pie-chart-data",
      params,
      function(data) {
        var result = [];

        $.each(data.constituents, function(key, value) {
           result.push(new Array(key, value));
        });

        if (result.length) {
          drawPieChart(result);
        } else {
          $element.html('<p>データがありません。</p>');
        }
      },
      "json"
    );

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
          colors.push(Highcharts.Color(base).brighten((i - 3) / 7).get());
        }
        return colors;
      }());

      $element.highcharts({
        chart: {
          plotBackgroundColor: null,
          plotBorderWidth: null,
          plotShadow: false
        },
        title: {
          text: ''
        },
        tooltip: {
          pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
          pie: {
            allowPointSelect: true,
            cursor: 'pointer',
            dataLabels: {
              enabled: (window.innerWidth > 768) ? true : false,
              format: '<b>{point.name}</b>: {point.percentage:.1f} % {}',
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


