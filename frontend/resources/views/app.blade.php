<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="/assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <title>
    DEROHIST
    </title>
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link href="https://fonts.cdnfonts.com/css/droid-sans-mono-2" rel="stylesheet">

    <!-- Nucleo Icons -->
    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/ba36af3172.js" crossorigin="anonymous"></script>
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- CSS Files -->
    <link id="pagestyle" href="/assets/css/soft-ui-dashboard.css" rel="stylesheet" />
    <style>
      .text-mono {
        font-family: 'Droid Sans Mono', monospace;
      }
      tr.hightlighted {
        background: #e9d8f5;
      }
      .menu-toggle {
        background: transparent;
        border: 0;
        color: inherit;
        padding: 0.25rem 0.5rem;
      }
      .menu-toggle:focus {
        outline: none;
        box-shadow: none;
      }
      .side-menu {
        position: fixed;
        top: 0;
        left: 0;
        width: 260px;
        height: 100vh;
        background: rgba(17, 17, 26, 0.95);
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 1100;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
      }
      .side-menu.open {
        transform: translateX(0);
      }
      .side-menu-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
      }
      .side-menu-overlay {
        position: fixed;
        inset: 0;
        background: rgba(17, 17, 26, 0.45);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease;
        z-index: 1090;
      }
      .side-menu-overlay.show {
        opacity: 1;
        visibility: visible;
      }
      .side-menu-close {
        background: transparent;
        border: 0;
        color: #fff;
        padding: 0;
      }
      .side-menu-close:focus {
        outline: none;
      }
      .side-menu-nav {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1;
      }
      .side-menu-link {
        display: block;
        color: #fff;
        font-weight: 600;
        text-decoration: none;
        padding: 0.75rem 0;
      }
      .side-menu-link:hover {
        color: #cb0c9f;
      }
    </style>
  </head>
  <body class="g-sidenav-show bg-gray-100">
    @php
      $activeAddress = request()->query('address');
      $addressQuery = $activeAddress ? ['address' => $activeAddress] : [];
    @endphp
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
      <!-- Navbar Dark -->
      <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-dark z-index-3 py-3">
        <div class="container d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <button id="menuToggle" class="menu-toggle text-white" aria-label="Apri menu" aria-controls="sideMenu" aria-expanded="false">
              <i class="fas fa-bars fa-lg"></i>
            </button>
            <a class="navbar-brand text-white ms-3" href="{{ route('dashboard', $addressQuery) }}" rel="tooltip">
              DEROHIST
            </a>
          </div>
          <div class="row" style="flex-grow: 1; justify-content: end;">
            <form method="get">
              <div class="col-auto" style="flex-grow: 0.8;">
                <div class="bg-white border-radius-lg d-flex me-2">
                  <input type="text" class="form-control border-0 ps-3" placeholder="Your wallet public address..." name="address" value="{{ request('address') }}">
                  <button class="btn bg-gradient-primary my-1 me-1">Apply</button>
                </div>
              </div>
            </form>
            </div>
        </div>
      </nav>
      <div id="sideMenuOverlay" class="side-menu-overlay" role="presentation"></div>
      <aside id="sideMenu" class="side-menu" aria-hidden="true">
        <div class="side-menu-header">
          <span class="text-white fw-bold">Menu</span>
          <button id="menuClose" class="side-menu-close" aria-label="Chiudi menu">
            <i class="fas fa-times fa-lg"></i>
          </button>
        </div>
        <ul class="side-menu-nav">
          <li>
            <a href="{{ route('dashboard', $addressQuery) }}" class="side-menu-link">Home</a>
          </li>
          <li>
            <a href="{{ route('miners.index', $addressQuery) }}" class="side-menu-link">Miners</a>
          </li>
        </ul>
      </aside>
      <!-- End Navbar -->

      <div class="container-fluid py-4">
        @if(isset($syncStatus['is_syncing']) && $syncStatus['is_syncing'])
          <div class="alert alert-warning border border-warning bg-white shadow-sm text-sm mb-4" role="alert">
            <strong>Node is still synchronizing with the DERO network.</strong>
            Data may be incomplete until the local height catches up (local
            {{ number_format($syncStatus['local_height'] ?? 0) }} / network
            {{ number_format($syncStatus['network_height'] ?? 0) }}).
          </div>
        @endif
        @yield('content')

        <footer class="footer pt-3  ">
          <div class="container-fluid">
            <div class="row align-items-center justify-content-lg-between">
              <div class="col-lg-6 mb-lg-0 mb-4">
                <div class="copyright text-center text-sm text-muted text-lg-start"> &copy; {{ date('Y') }}, made with <i class="fa fa-heart"></i> by <span class="font-weight-bold">DEROHIST</span> for a better mining world. </div>
              </div>
              <div class="col-lg-6">
                <ul class="nav nav-footer justify-content-center justify-content-lg-end">
                  <li class="nav-item">
                    <a href="#" class="nav-link pe-0 text-muted" target="_blank">Privacy</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </main>
    <!--   Core JS Files   -->
    <script src="/assets/js/core/popper.min.js"></script>
    <script src="/assets/js/core/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.3/min/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.34/moment-timezone-with-data-10-year-range.js" integrity="sha512-2PxSjpo4UvK8I5Mh1p3SvWVbAqdp7aYCTcGsupAWGuY0gHJqeMIBiyXi1hJbxw2j/nGzk001Vgajwoo92Z2xbw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <!-- Plugin for the charts, full documentation here: https://www.chartjs.org/ -->
    <script src="/assets/js/plugins/chartjs.min.js"></script>
    {{-- <script src="/assets/js/plugins/Chart.extension.js"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@^1"></script>
    <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="/assets/js/soft-ui-dashboard.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const toggleButton = document.getElementById('menuToggle');
        const closeButton = document.getElementById('menuClose');
        const sideMenu = document.getElementById('sideMenu');
        const overlay = document.getElementById('sideMenuOverlay');

        if (!toggleButton || !sideMenu || !overlay) {
          return;
        }

        const setMenuState = (isOpen) => {
          sideMenu.classList.toggle('open', isOpen);
          overlay.classList.toggle('show', isOpen);
          sideMenu.setAttribute('aria-hidden', (!isOpen).toString());
          toggleButton.setAttribute('aria-expanded', isOpen.toString());
        };

        toggleButton.addEventListener('click', (event) => {
          event.preventDefault();
          const isOpen = !sideMenu.classList.contains('open');
          setMenuState(isOpen);
        });

        overlay.addEventListener('click', () => setMenuState(false));

        if (closeButton) {
          closeButton.addEventListener('click', () => setMenuState(false));
        }

        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            setMenuState(false);
          }
        });
      });
    </script>

    @yield('script')
</body>
</html>
