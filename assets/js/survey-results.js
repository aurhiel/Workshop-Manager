require('../css/survey-results.scss');

// import Chart from 'chart.js';
var ChartJS = require('chart.js');

// Doc ready !
(function() {
  var $charts = $('.chart-js');

  $charts.each(function() {
    var $chart = $(this),
        canvas = $chart.find('canvas')
        data_name   = $chart.data('chartjs-data-name'),
        data_type   = $chart.data('chartjs-data-type'),
        chart_type  = $chart.data('chartjs-type'),
        chart_min   = $chart.data('chartjs-min'),
        chart_max   = $chart.data('chartjs-max'),
        display_legend = $chart.data('chartjs-display-legend');

    if (typeof data_name != 'undefined' && typeof window[data_name] != 'undefined') {
      var opts = {};
      var data = window[data_name];

      if (typeof chart_min != 'undefined' && typeof chart_max != 'undefined') {
        opts.scale = {
          ticks : {
            min : chart_min,
            max : chart_max
          }
        };
      }

      if (typeof display_legend != 'undefined') {
        opts.legend = {
          display : display_legend
        }
      }

      // Custom tooltips
      if (typeof data_type != 'undefined') {
        opts.tooltips = {
          callbacks: {
            label: function(tooltipItem, data) {
              // Add % character to percent data type values
              if (data_type == 'percent') {
                var label = tooltipItem.value + '%';
                // Push counting things for percent values
                var datasets = data.datasets[tooltipItem.datasetIndex];
                if (typeof datasets != 'undefined' && typeof datasets.data_count != 'undefined') {
                  label += ' (' + datasets.data_count[tooltipItem.index] + ' ' + datasets.label + ((datasets.data_count[tooltipItem.index] > 1) ? 's': '') + ')';
                }

                return label;
              }
            }
          }
        };
      }

      var chartJS = new Chart(canvas, {
        type : chart_type,
        data : data,
        options : opts
      });
    }
  });

  // Fix for printing (NOTE: but doesn't work...)
  function beforePrintHandler () {
    for (var id in Chart.instances) {
      Chart.instances[id].resize();
    }
  }
  window.addEventListener("beforeprint", beforePrintHandler);
})();
