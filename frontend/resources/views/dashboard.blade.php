@extends('app')

@section('content')
        <div class="row">
          <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
              <div class="card-body p-3">
                <div class="row">
                  <div class="col-8">
                    <div class="numbers">
                      <p class="text-sm mb-0 text-capitalize font-weight-bold">Value (DERO / USD)</p>
                      <h5 class="font-weight-bolder mb-0"> ${{ number_format($price['dero']['usd'], 2, ',', '.') }} <span class="{{ $price['dero']['usd_24h_change'] > 0 ? 'text-success' : 'text-danger' }} text-sm font-weight-bolder">{{ $price['dero']['usd_24h_change'] > 0 ? '+' : '' }}{{ number_format($price['dero']['usd_24h_change'], 2, ',', '.') }}%</span>
                      </h5>
                    </div>
                  </div>
                  <div class="col-4 text-end">
                    <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                      <i class="fas fa-money-bill-trend-up text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
              <div class="card-body p-3">
                <div class="row">
                  <div class="col-8">
                    <div class="numbers">
                      <p class="text-sm mb-0 text-capitalize font-weight-bold">Market Cap.</p>
                      <h5 class="font-weight-bolder mb-0"> ${{ number_format($price['dero']['usd_market_cap'], 0,',','.') }}
                      </h5>
                    </div>
                  </div>
                  <div class="col-4 text-end">
                    <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                      <i class="fas fa-sack-dollar text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
              <div class="card-body p-3">
                <div class="row">
                  <div class="col-8">
                    <div class="numbers">
                      <p class="text-sm mb-0 text-capitalize font-weight-bold">Volume (24h)</p>
                      <h5 class="font-weight-bolder mb-0"> ${{ number_format($price['dero']['usd_24h_vol'], 0, ',', '.') }}
                      </h5>
                    </div>
                  </div>
                  <div class="col-4 text-end">
                    <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                      <i class="fas fa-money-bill-transfer text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-sm-6">
            <div class="card">
              <div class="card-body p-3">
                <div class="row">
                  <div class="col-8">
                    <div class="numbers">
                      <p class="text-sm mb-0 text-capitalize font-weight-bold">Chain Height</p>
                      <h5 class="font-weight-bolder mb-0"> {{ number_format($height, 0, ',', '.') }}
                      </h5>
                    </div>
                  </div>
                  <div class="col-4 text-end">
                    <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                      <i class="fas fa-layer-group text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mt-4">

          <div class="col-md-6">
            <div class="card z-index-2 h-100">
              <div class="card-header pb-0">
                <h6>AVG Difficulty (30 days)</h6>
                <p class="text-sm avg_difficulty_diff"></p>
              </div>
              <div class="card-body p-3">
                <div class="chart" style="height: 100%; min-height: 300px;">
                  <canvas id="avg_difficulty_per_last_n_days_v2" class="chart-canvas"></canvas>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header pb-0">
                <div class="row">
                  <div class="col-lg-6 col-7">
                    <h6>Top Miners (24h)</h6>
                    <p class="text-sm mb-0">
                      <i class="fas fa-person-digging"></i>
                      Total Active Miners <span class="font-weight-bold">{{ $active_in_last_24h }}</span>
                    </p>
                  </div>
                </div>
              </div>
              <div class="card-body px-0 pb-2">
                <div class="table-responsive">
                  <table class="table align-items-center mb-0 table-sm" style="font-size: 0.8em; border-collapse: collapse;">
                    {{-- <thead>
                      <tr>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Address</th>
                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">%</th>
                      </tr>
                    </thead> --}}
                    <tbody>
                    <tr class="{{ isset($m->own) && $m->own ? 'hightlighted' : '' }}">
                          <td style="width: 100px;">
                            <div class="d-flex px-2 {{-- py-1 --}} justify-content-around">
                              <div class="d-flex flex-column justify-content-center">
                                <h6 class="mb-0 text-xs text-mono">Address</h6>
                              </div>
                            </div>
                          </td>
                          <td class="align-middle" colspan="2">
                            <div class="progress-wrapper w-90 mx-auto">
                              <h6 class="mb-0 text-xs text-mono">Dominance</h6>
                            </div>
                          </td>
                        </tr>
                      @foreach($top_miners_in_last_24h as $i => $m)
                        <tr class="{{ isset($m->own) && $m->own ? 'hightlighted' : '' }}">
                          <td style="width: 100px;">
                            <div class="d-flex px-2 {{-- py-1 --}} justify-content-around">
                              <div class="me-3">
                                {{ $m->pos+1 }})
                              </div>
                              <div class="d-flex flex-column justify-content-center">
                                <h6 class="mb-0 text-sm text-mono">
                                  <a
                                    href="{{ route('dashboard', ['address' => $m->address_full]) }}"
                                    class="text-sm text-mono text-decoration-none text-reset"
                                    title="{{ $m->address_full }}"
                                  >
                                    ***{{ $m->address }}
                                  </a>
                                </h6>
                              </div>
                            </div>
                          </td>
                          <td class="align-middle">
                            <div class="progress-wrapper w-90 mx-auto">
                              <div class="progress-info">
                                <div class="progress-percentage">
                                  <span class="text-xs font-weight-bold">{{ $m->perc }}%</span>
                                </div>
                              </div>
                              <div class="progress" style="width: 100%;">
                                <div class="progress-bar bg-gradient-primary" role="progressbar" style="width: {{ $m->perc*100/$top_miners_in_last_24h->first()->perc }}%;"></div>
                              </div>
                            </div>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        @if ($address)
          <div class="row mt-4">
            <div class="col-md-6">
              <div class="card z-index-2 h-100">
                <div class="card-header pb-0">
                  <h6>Your Hourly Gain (48 H)</h6>
                  <p class="text-sm float-end">Updated at <span class="wallet_hourly_gain_updated_at"></span></p>
                </div>
                <div class="card-body p-3">
                  <div class="chart" style="height: 100%; min-height: 300px;">
                    <canvas id="chart_wallet_hourly_gain" class="chart-canvas"></canvas>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="card z-index-2 h-100">
                <div class="card-header pb-0">
                  <h6>Your Daily Gain</h6>
                  <p class="text-sm float-end">Updated at <span class="wallet_daily_gain_updated_at"></span></p>
                  <p clasS="text-sm wallet_daily_gain_diff"></p>
                </div>
                <div class="card-body p-3">
                  <div class="chart" style="height: 100%; min-height: 300px;">
                    <canvas id="chart_wallet_daily_gain" class="chart-canvas"></canvas>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row mt-4">
            

            <div class="col-md-6">
              <div class="card z-index-2 h-100">
                <div class="card-header pb-0">
                  <h6>Hourly Difficulty (48 H)</h6>
                  <p class="text-sm float-end">Updated at <span class="hourly_difficulty_updated_at"></span></p>
                </div>
                <div class="card-body p-3">
                  <div class="chart" style="height: 100%; min-height: 300px;">
                    <canvas id="chart_hourly_difficulty" class="chart-canvas"></canvas>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card z-index-2 h-100">
                <div class="card-header pb-0">
                  <h6>Your Daily AVG Compute Power</h6>
                  <p class="text-sm float-end">Updated at <span class="wallet_daily_power_updated_at"></span></p>
                  <p clasS="text-sm avg_power_diff"></p>
                </div>
                <div class="card-body p-3">
                  <div class="chart" style="height: 100%; min-height: 300px;">
                    <canvas id="chart_wallet_compute_power" class="chart-canvas"></canvas>
                  </div>
                </div>
              </div>
            </div>  
          </div>
        @endif
@endsection

@section('script')
    <script>
      $(function () {
        const tz = moment.tz.guess();
        const tzParam = encodeURIComponent(tz);
        console.log(tz);

        var canvas = $('<canvas/>').get(0).getContext("2d");

        var gradientStroke1 = canvas.createLinearGradient(0, 230, 0, 50);

        gradientStroke1.addColorStop(1, 'rgba(203,12,159,0.2)');
        gradientStroke1.addColorStop(0.2, 'rgba(72,72,176,0.0)');
        gradientStroke1.addColorStop(0, 'rgba(203,12,159,0)'); //purple colors

        var gradientStroke2 = canvas.createLinearGradient(0, 230, 0, 50);

        gradientStroke2.addColorStop(1, 'rgba(20,23,39,0.2)');
        gradientStroke2.addColorStop(0.2, 'rgba(72,72,176,0.0)');
        gradientStroke2.addColorStop(0, 'rgba(20,23,39,0)'); //purple colors


        var gradientStroke3 = canvas.createLinearGradient(0, 230, 0, 50);

        gradientStroke3.addColorStop(1, 'rgba(75, 29, 193, 0.2)');
        gradientStroke3.addColorStop(0.2, 'rgba(35, 199, 185, 0.0)');
        gradientStroke3.addColorStop(0, 'rgba(35, 185, 199, 0)'); //azure


        var chart_avg_difficulty_per_last_n_days = new Chart($('#avg_difficulty_per_last_n_days_v2').get(0).getContext("2d"), {
          type: "bar",
          data: {
            labels: [],
            datasets: [{
                label: "MH/s",
                weight: 5,
                borderWidth: 0,
                borderRadius: 4,
                backgroundColor: '#3A416F',
                fill: false,
                maxBarThickness: 35,
                data: [],
              }
            ],
          },
          options: {
            indexAxis: 'x',
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
                      const formatted = Number(value).toLocaleString('en-US', {
                        maximumFractionDigits: 2,
                        useGrouping: false,
                      });
                      return formatted + ' MH/s';
                    },
                    display: true,
                    padding: 10,
                    color: '#b2b9bf',
                  font: {
                    size: 11,
                    family: "Open Sans",
                    style: 'normal',
                    lineHeight: 2
                  },
                }
              },
              x: {
                type: 'time',
                time: {
                    tooltipFormat:'MMM DD, YYYY',
                    unit: 'day'
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
                    family: "Open Sans",
                    style: 'normal',
                    lineHeight: 2
                  },
                }
              },
            },
          },
        });

        $.ajax({
            method: 'GET',
            url: '{{ route('api.charts.avg_difficulty_per_last_n_days', [], false) }}?tz=' + tzParam,
            success: function(data) {
              if (data.diff > 0) {
                $('.avg_difficulty_diff').html('<i class="fa fa-arrow-up text-danger" aria-hidden="true"></i><span class="font-weight-bold">' + Math.abs(data.diff) + '% more</span> today');
              } else {
                $('.avg_difficulty_diff').html('<i class="fa fa-arrow-down text-success" aria-hidden="true"></i><span class="font-weight-bold">' + Math.abs(data.diff) + '% less</span> today');
              }

              chart_avg_difficulty_per_last_n_days.data.labels = data.labels;
              chart_avg_difficulty_per_last_n_days.data.datasets[0].data = data.data;
              chart_avg_difficulty_per_last_n_days.update();
            },
            error: function(error){
                console.log(error)
            }
        });

        @if ($address)
          var chart_wallet_daily_gain = new Chart($('#chart_wallet_daily_gain').get(0).getContext("2d"), {
            type: "line",
            data: {
              labels: [],
              datasets: [{
                  label: "Dero",
                  tension: 0.4,
                  borderWidth: 0,
                  pointRadius: 0,
                  borderColor: "#cb0c9f",
                  borderWidth: 3,
                  backgroundColor: gradientStroke1,
                  fill: true,
                  data: [],
                  maxBarThickness: 6
                },
                {
                  label: "Estimation",
                  tension: 0.4,
                  borderWidth: 0,
                  pointRadius: 0,
                  borderColor: "#a50ccc",
                  borderWidth: 2,
                  borderDash: [3],
                  backgroundColor: gradientStroke1,
                  fill: true,
                  data: [],
                  maxBarThickness: 6
                }
              ],
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
                    callback: function(value, index, values) {
                    return value + ' DERO';
                    },
                    display: true,
                    padding: 10,
                    color: '#b2b9bf',
                    font: {
                      size: 11,
                      family: "Open Sans",
                      style: 'normal',
                      lineHeight: 2
                    },
                  }
                },
                x: {
                  type: 'time',
                  time: {
                      tooltipFormat:'MMM DD, YYYY',
                      unit: 'day'
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
                      family: "Open Sans",
                      style: 'normal',
                      lineHeight: 2
                    },
                  }
                },
              },
            },


          });
          $.ajax({
              method: 'GET',
              url: '{{ route('api.charts.get_wallet_daily_gain', [], false) }}?address=' + '{{ $address }}' + '&tz=' + tzParam,
              success: function(data) {
                if (data.diff > 0) {
                  $('.wallet_daily_gain_diff').html('<i class="fa fa-arrow-up text-success" aria-hidden="true"></i><span class="font-weight-bold">' + Math.abs(data.diff) + '% more</span> today (estimation)');
                } else {
                  $('.wallet_daily_gain_diff').html('<i class="fa fa-arrow-down text-danger" aria-hidden="true"></i><span class="font-weight-bold">' + Math.abs(data.diff) + '% less</span> today (estimation)');
                }

                $('.wallet_daily_gain_updated_at').text(data.updated_at);

                chart_wallet_daily_gain.data.labels = data.labels;
                chart_wallet_daily_gain.data.datasets[0].data = data.data;
                chart_wallet_daily_gain.data.datasets[1].data = data.last_24h;
                chart_wallet_daily_gain.update();
              },
              error: function(error){
                  console.log(error)
              }
          });       

        var chart_wallet_hourly_gain = new Chart($('#chart_wallet_hourly_gain').get(0).getContext("2d"), {
            type: "bar",
            data: {
              labels: [],
              datasets: [{
                  label: "Dero",

                  weight: 5,
                  borderWidth: 0,
                  borderRadius: 4,
                  backgroundColor: '#cb0c9f',
                  fill: false,
                  maxBarThickness: 35,
                  data: [],
                }
              ],
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
                    callback: function(value, index, values) {
                    return value + ' DERO';
                    },
                    display: true,
                    padding: 10,
                    color: '#b2b9bf',
                    font: {
                      size: 11,
                      family: "Open Sans",
                      style: 'normal',
                      lineHeight: 2
                    },
                  }
                },
                x: {
                  type: 'time',
                  time: {
                      tooltipFormat:'MMM DD, YYYY HH:mm',
                      unit: 'hour'
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
                      family: "Open Sans",
                      style: 'normal',
                      lineHeight: 2
                    },
                  }
                },
              },
            },
          });
          $.ajax({
              method: 'GET',
              url: '{{ route('api.charts.get_wallet_hourly_gain', [], false) }}?address=' + '{{ $address }}' + '&tz=' + tzParam,
              success: function(data) {
                $('.wallet_hourly_gain_updated_at').text(data.updated_at);

                chart_wallet_hourly_gain.data.labels = data.labels;
                chart_wallet_hourly_gain.data.datasets[0].data = data.data;
                chart_wallet_hourly_gain.update();
              },
              error: function(error){
                  console.log(error)
              }
          });

          var chart_hourly_difficulty = new Chart($('#chart_hourly_difficulty').get(0).getContext("2d"), {
            type: "bar",
            data: {
              labels: [],
              datasets: [{
                  label: "MH/s",
                  weight: 5,
                  borderWidth: 0,
                  borderRadius: 4,
                  backgroundColor: '#3A416F',
                  fill: false,
                  maxBarThickness: 35,
                  data: []
                }
              ],
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
                    callback: function(value, index, values) {
                    return value + ' MH/s';
                    },
                    display: true,
                    padding: 10,
                    color: '#b2b9bf',
                    font: {
                      size: 11,
                      family: "Open Sans",
                      style: 'normal',
                      lineHeight: 2
                    },
                  }
                },
                x: {
                  type: 'time',
                  time: {
                      tooltipFormat:'MMM DD, YYYY HH:mm',
                      unit: 'hour'
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
                      family: "Open Sans",
                      style: 'normal',
                      lineHeight: 2
                    },
                  }
                },
              },
            },
          });
          $.ajax({
              method: 'GET',
              url: '{{ route('api.charts.get_hourly_difficulty', [], false) }}?tz=' + tzParam,
              success: function(data) {
                $('.hourly_difficulty_updated_at').text(data.updated_at);

                chart_hourly_difficulty.data.labels = data.labels;
                chart_hourly_difficulty.data.datasets[0].data = data.data;
                chart_hourly_difficulty.update();
              },
              error: function(error){
                  console.log(error)
              }
          });

          var chart_wallet_compute_power = new Chart($('#chart_wallet_compute_power').get(0).getContext("2d"), {
            type: "line",
            data: {
            labels: [],
            datasets: [
              {
                  label: "KH/s",
                  tension: 0.4,
                  borderWidth: 0,
                  pointRadius: 0,
                  borderColor: "#4b1dc1",//239cc7",
                  borderWidth: 3,
                  backgroundColor: gradientStroke3,
                  fill: true,
                  data: [],
                  maxBarThickness: 6
                }
            ],
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
                    callback: function(value, index, values) {
                    return value + ' KH/s';
                    },
                    display: true,
                    padding: 10,
                    color: '#b2b9bf',
                    font: {
                      size: 11,
                      family: "Open Sans",
                      style: 'normal',
                      lineHeight: 2
                    },
                  }
                },
                x: {
                  type: 'time',
                  time: {
                      tooltipFormat:'MMM DD, YYYY',
                      unit: 'day'
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
                      family: "Open Sans",
                      style: 'normal',
                      lineHeight: 2
                    },
                  }
                },
              },
            },
          });
        $.ajax({
            method: 'GET',
            url: '{{ route('api.charts.get_wallet_compute_power', [], false) }}?address=' + '{{ $address }}' + '&tz=' + tzParam,
            success: function(data) {
              if (data.diff > 0) {
                  $('.avg_power_diff').html('<i class="fa fa-arrow-up text-success" aria-hidden="true"></i><span class="font-weight-bold">' + Math.abs(data.diff) + '% more</span> today');
                } else {
                  $('.avg_power_diff').html('<i class="fa fa-arrow-down text-danger" aria-hidden="true"></i><span class="font-weight-bold">' + Math.abs(data.diff) + '% less</span> today');
              }

              $('.wallet_daily_power_updated_at').text(data.updated_at);

              chart_wallet_compute_power.data.labels = data.labels;
              chart_wallet_compute_power.data.datasets[0].data = data.data;
              chart_wallet_compute_power.update();
            },
            error: function(error){
                console.log(error)
            }
        });
        @endif
      });
    </script>
@endsection
