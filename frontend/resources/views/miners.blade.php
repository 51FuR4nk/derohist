@extends('app')

@section('content')
  <div class="row">
    <div class="col-md-6">
      <div class="card z-index-2 h-100">
        <div class="card-header pb-0">
          <h6>Active miners over time (30 days)</h6>
          <p class="text-sm float-end">Updated at <span class="active_miners_over_time_updated_at"></span></p>
        </div>
        <div class="card-body p-3">
          <div class="chart" style="height: 100%; min-height: 300px;">
            <canvas id="chart_active_miners_over_time" class="chart-canvas"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card z-index-2 h-100">
        <div class="card-header pb-0">
          <h6>Top miners ranking trend (30 days)</h6>
          <p class="text-sm float-end">Updated at <span class="top_miners_positions_updated_at"></span></p>
        </div>
        <div class="card-body p-3">
          <div class="chart" style="height: 100%; min-height: 300px;">
            <canvas id="chart_top_miners_positions" class="chart-canvas"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header pb-0">
          <h6 class="mb-1">Miners</h6>
          <p class="text-sm text-muted mb-0">
            Totale miner attivi (24h): {{ number_format($total_active_miners ?? 0, 0, '.', ' ') }}
            @if($miners->count() < ($total_active_miners ?? 0))
              <span class="d-block text-xs">Visualizzati i primi {{ $miners->count() }} indirizzi per miniblock.</span>
            @endif
          </p>
        </div>
        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Address</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Dominance</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Gain 24h (DERO)</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Hashrate (Mh/s)</th>
                </tr>
              </thead>
              <tbody>
                @forelse($miners as $miner)
                  <tr class="{{ !empty($miner->own) ? 'hightlighted' : '' }}">
                    <td style="min-width: 320px;">
                      <div class="d-flex px-2 py-0 align-items-center">
                        <div class="me-3">
                          {{ $miner->position }})
                        </div>
                        <div class="d-flex flex-column justify-content-center">
                          <h6 class="mb-0 text-sm text-mono">
                            <a
                              href="{{ route('dashboard', ['address' => $miner->address]) }}"
                              class="text-decoration-none text-reset"
                              title="{{ $miner->address }}"
                            >
                              {{ $miner->address }}
                            </a>
                          </h6>
                          <p class="text-xs text-muted mb-0">Miniblock (24h): {{ number_format($miner->miniblocks, 0, '.', '') }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="align-middle px-2" style="width: 100%;">
                      <div class="progress-wrapper w-100">
                        <div class="progress-info">
                          <div class="progress-percentage">
                            <span class="text-xs font-weight-bold">{{ number_format($miner->dominance, 2, '.', '') }}%</span>
                          </div>
                        </div>
                        <div class="progress" style="width: 100%;">
                          <div
                            class="progress-bar bg-gradient-primary"
                            role="progressbar"
                            style="width: {{ $max_dominance > 0 ? $miner->dominance * 100 / $max_dominance : 0 }}%;"
                          ></div>
                        </div>
                      </div>
                    </td>
                    <td style="width: 120px;">
                      <div class="d-flex px-2 justify-content-center">
                        <div class="d-flex flex-column justify-content-center">
                          <h6 class="mb-0 text-xs text-mono">{{ number_format($miner->gain, 4, '.', '') }}</h6>
                        </div>
                      </div>
                    </td>
                    <td style="width: 150px;">
                      <div class="d-flex px-2 justify-content-center">
                        <div class="d-flex flex-column justify-content-center">
                          <h6 class="mb-0 text-xs text-mono">{{ number_format($miner->hashrate, 2, '.', '') }}</h6>
                        </div>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">Nessun dato disponibile nelle ultime 24 ore.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('script')
  <script>
    $(function () {
      if (!window.Chart || !$('#chart_active_miners_over_time').length) {
        return;
      }

      const tz = moment.tz.guess();
      const tzParam = encodeURIComponent(tz);

      const canvas = $('<canvas/>').get(0).getContext("2d");
      const gradientStroke = canvas.createLinearGradient(0, 230, 0, 50);
      gradientStroke.addColorStop(1, 'rgba(23,193,232,0.2)');
      gradientStroke.addColorStop(0.2, 'rgba(23,193,232,0.0)');
      gradientStroke.addColorStop(0, 'rgba(23,193,232,0)');

      const hexToRgba = function (hex, alpha) {
        if (!hex) {
          return 'rgba(0,0,0,' + alpha + ')';
        }
        let sanitized = hex.replace('#', '');
        if (sanitized.length === 3) {
          sanitized = sanitized.split('').map(function (c) { return c + c; }).join('');
        }
        const bigint = parseInt(sanitized, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
      };

      const chartActiveMiners = new Chart($('#chart_active_miners_over_time').get(0).getContext("2d"), {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'Active miners',
            tension: 0.4,
            borderWidth: 3,
            pointRadius: 0,
            borderColor: '#17c1e8',
            backgroundColor: gradientStroke,
            fill: true,
            data: [],
            maxBarThickness: 6,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false,
            }
          },
          interaction: {
            intersect: false,
            mode: 'index',
          },
          scales: {
            y: {
              grid: {
                drawBorder: false,
                display: true,
                drawOnChartArea: true,
                drawTicks: false,
                borderDash: [5, 5]
              },
              ticks: {
                callback: function(value) {
                  return value + ' miners';
                },
                display: true,
                padding: 10,
                color: '#b2b9bf',
                font: {
                  size: 11,
                  family: 'Open Sans',
                  style: 'normal',
                  lineHeight: 2,
                },
              }
            },
            x: {
              type: 'time',
              time: {
                tooltipFormat: 'MMM DD, YYYY',
                unit: 'day',
              },
              grid: {
                drawBorder: false,
                display: false,
                drawOnChartArea: false,
                drawTicks: false,
                borderDash: [5, 5]
              },
              ticks: {
                display: true,
                color: '#b2b9bf',
                padding: 20,
                font: {
                  size: 11,
                  family: 'Open Sans',
                  style: 'normal',
                  lineHeight: 2,
                },
              }
            },
          },
        },
      });

      const rankingColors = [
        '#cb0c9f', '#3A416F', '#17c1e8', '#82d616', '#fbcf33',
        '#f53939', '#ea0606', '#7d3cff', '#00a389', '#ff8a65'
      ];

      let chartTopMinersPositions = null;
      let activeRankingIndex = null;

      const applyRankingHighlight = function (activeIndex) {
        activeRankingIndex = activeIndex;
        if (!chartTopMinersPositions) {
          return;
        }
        chartTopMinersPositions.data.datasets.forEach(function (dataset, idx) {
          const baseColor = dataset.metaColor || dataset.borderColor || '#3A416F';
          const dimmedBorder = hexToRgba(baseColor, 0.15);
          const dimmedBackground = hexToRgba(baseColor, 0.05);

          if (activeIndex === null || activeIndex === idx) {
            dataset.borderColor = baseColor;
            dataset.backgroundColor = hexToRgba(baseColor, 0.18);
            dataset.borderWidth = activeIndex === null ? 2 : 3;
            dataset.pointRadius = activeIndex === null ? 1 : 3;
            dataset.pointBackgroundColor = baseColor;
            dataset.pointBorderColor = '#ffffff';
            dataset.pointBorderWidth = activeIndex === null ? 0 : 1;
          } else {
            dataset.borderColor = dimmedBorder;
            dataset.backgroundColor = dimmedBackground;
            dataset.borderWidth = 1.5;
            dataset.pointRadius = 0;
            dataset.pointBackgroundColor = dimmedBorder;
            dataset.pointBorderColor = dimmedBorder;
            dataset.pointBorderWidth = 0;
          }
        });
        chartTopMinersPositions.update('none');
      };

      chartTopMinersPositions = new Chart($('#chart_top_miners_positions').get(0).getContext("2d"), {
        type: 'line',
        data: {
          labels: [],
          datasets: [],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              position: 'bottom',
              labels: {
                usePointStyle: true,
                boxWidth: 12,
                boxHeight: 12,
                padding: 8,
                font: {
                  size: 10,
                  family: 'Open Sans',
                },
              },
              onHover: function (event, legendItem, legend) {
                legend.chart.canvas.style.cursor = 'pointer';
                applyRankingHighlight(legendItem.datasetIndex);
              },
              onLeave: function (event, legendItem, legend) {
                legend.chart.canvas.style.cursor = 'default';
                applyRankingHighlight(null);
              },
            },
            tooltip: {
              enabled: false,
            },
          },
          interaction: {
            intersect: false,
            mode: 'nearest',
            axis: 'x',
          },
          scales: {
            y: {
              reverse: true,
              grid: {
                drawBorder: false,
                display: true,
                drawOnChartArea: true,
                drawTicks: false,
                borderDash: [5, 5]
              },
              ticks: {
                callback: function(value) {
                  return '#' + value;
                },
                display: true,
                padding: 10,
                color: '#b2b9bf',
                stepSize: 1,
                precision: 0,
                font: {
                  size: 11,
                  family: 'Open Sans',
                  style: 'normal',
                  lineHeight: 2,
                },
              }
            },
            x: {
              type: 'time',
              time: {
                tooltipFormat: 'MMM DD, YYYY',
                unit: 'day',
              },
              grid: {
                drawBorder: false,
                display: false,
                drawOnChartArea: false,
                drawTicks: false,
                borderDash: [5, 5]
              },
              ticks: {
                display: true,
                color: '#b2b9bf',
                padding: 20,
                font: {
                  size: 11,
                  family: 'Open Sans',
                  style: 'normal',
                  lineHeight: 2,
                },
              }
            },
          },
        },
      });

      chartTopMinersPositions.canvas.addEventListener('mouseleave', function () {
        applyRankingHighlight(null);
      });

      let chartsDataLoaded = false;
      const loadMinersChartsData = function () {
        if (chartsDataLoaded) {
          return;
        }
        chartsDataLoaded = true;

        $.ajax({
          method: 'GET',
          url: '{{ route('api.charts.get_active_miners_over_time', [], false) }}?tz=' + tzParam,
          success: function (response) {
            $('.active_miners_over_time_updated_at').text(response.updated_at);
            chartActiveMiners.data.labels = response.labels;
            chartActiveMiners.data.datasets[0].data = response.data;
            chartActiveMiners.update();
          }
        });

        $.ajax({
          method: 'GET',
          url: '{{ route('api.charts.get_top_miners_positions_over_time', [], false) }}?tz=' + tzParam,
          success: function (response) {
            $('.top_miners_positions_updated_at').text(response.updated_at);
            chartTopMinersPositions.data.labels = response.labels;

            const sortedSeries = (response.series || []).slice().sort(function (a, b) {
              const posA = a.today_position ?? Number.POSITIVE_INFINITY;
              const posB = b.today_position ?? Number.POSITIVE_INFINITY;
              if (posA === posB) {
                return (a.address || '').localeCompare(b.address || '');
              }
              return posA - posB;
            });

            chartTopMinersPositions.data.datasets = sortedSeries.map(function (item, index) {
              const color = rankingColors[index % rankingColors.length];
              return {
                label: item.label,
                data: item.positions,
                borderColor: color,
                backgroundColor: hexToRgba(color, 0.18),
                fill: false,
                tension: 0.25,
                spanGaps: true,
                pointRadius: 1,
                pointHoverRadius: 4,
                pointHitRadius: 6,
                pointBackgroundColor: color,
                metaTotals: item.totals,
                metaAddress: item.address,
                metaColor: color,
                metaTodayPosition: item.today_position,
              };
            });
            applyRankingHighlight(null);
          }
        });
      };

      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              loadMinersChartsData();
              observer.disconnect();
            }
          });
        }, { threshold: 0.1 });

        const target = document.getElementById('chart_active_miners_over_time');
        if (target) {
          observer.observe(target);
        } else {
          loadMinersChartsData();
        }
      } else {
        loadMinersChartsData();
      }
    });
  </script>
@endsection
