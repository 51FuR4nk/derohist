@extends('app')

@section('content')
  <div class="row">
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
