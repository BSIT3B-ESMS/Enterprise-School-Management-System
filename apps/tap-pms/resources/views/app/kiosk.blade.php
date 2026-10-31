<!doctype html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><meta name="kiosk-authorized" content="{{ $device ? 'yes':'no' }}">
<title>TAP | Attendance Kiosk</title>
<link rel="stylesheet" href="{{ asset('tap-pms-ui/vendor/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('tap-pms-ui/vendor/bootstrap-icons.min.css') }}">
<link rel="stylesheet" href="{{ asset('tap-pms-ui/css/original-kiosk.css') }}">
</head><body>


  <header class="k-head">
    <div class="k-brand"><span class="tap-wordmark">TAP</span></div>
    <span id="syncBadge" class="badge {{ $device ? 'bg-success' : 'bg-secondary' }}">{{ $device ? 'Ready to scan' : 'Administrator authorization required' }}</span>
  </header>

  <main class="k-main">
    <section class="k-panel">
      <div class="k-clock">
        <div class="digital-clock" id="liveClockDisplay">00:00:00 AM</div>
        <div class="k-date" id="liveDateDisplay"></div>
      </div>

      <div class="scan-target" id="kioskBox">
        <div id="idleDisplay" class="w-100">
          <div class="idle-row d-flex align-items-center gap-4">
          <div class="pulse"><i class="bi bi-broadcast"></i></div>
          <div class="idle-body flex-grow-1">
          <h2>Tap your card</h2>
          <div class="k-input input-group mb-2">
            <input type="text" id="kioskScanBuffer" class="form-control" placeholder="Card UID" autocomplete="off" autofocus>
            <button class="btn btn-primary" type="button" id="btnManualSubmit">Tap</button>
          </div>
          <div class="cam-row d-flex align-items-center gap-3 mt-3">
            <button type="button" class="btn btn-outline-light btn-sm" id="btnEnableNfc">Enable reader</button>
            <video id="camPreview" autoplay playsinline muted class="cam-preview rounded border border-secondary"></video>
          </div>
          <div id="nfcLiveStatus" class="k-status"></div>
          <div id="camStatus" class="k-status mt-0"></div>
          <div id="kioskSuccessCard" class="ks-overlay d-none" aria-live="polite">
            <div class="ks-card">
              <div class="ks-photo-wrap">
                <img id="ksPhoto" src="" alt="">
                <span class="ks-badge"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
              </div>
              <div class="ks-name" id="ksName"></div>
              <div class="ks-meta" id="ksMeta"></div>
              <div class="ks-action" id="ksAction"></div>
              <div class="ks-time" id="ksTime"></div>
              <div class="ks-pill ks-ok" id="ksStatus"></div>
              <div class="ks-date" id="ksDate"></div>
              <div class="ks-sync ks-saving" id="ksSync"></div>
              <div class="ks-bar"><span></span></div>
            </div>
          </div>
          <canvas id="camCanvas" class="d-none"></canvas>
          </div></div>
        </div>

        <div id="verifyDisplay" class="d-none w-100">
          <div class="d-flex align-items-center gap-4">
            <img id="empVerifyImg" src="" class="rounded-circle photo-frame" alt="">
            <div>
              <h2 class="mb-0" id="empVerifyName">--</h2>
              <div class="text-secondary mb-2" id="empVerifyRole">--</div>
              <div class="d-flex gap-2 align-items-center">
                <span class="badge fs-6" id="empVerifyActionBadge"></span>
                <span class="badge fs-6" id="empVerifyStatusBadge"></span>
              </div>
              <div id="verifyNote" class="small mt-2" style="color:#5fd0a0">Recorded</div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>
  @if(session('success'))<div class="k-notice" role="status">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="k-notice error" role="alert">{{ $errors->first() }}</div>@endif
  <div class="k-notice error d-none" id="kioskError" role="alert" aria-live="assertive"></div>
  @unless($device)
  <section class="k-footer" aria-label="Kiosk authorization">
    <strong>Attendance terminal not activated.</strong> Payroll Administrator authorization is required once on this browser; employees never select attendance events or enter device tokens.
    @auth
      @if(auth()->user()->role==='admin' && session('mfa_verified'))
        <form action="/kiosk/authorize" method="post" class="k-admin">@csrf
          <label>Device name <input class="form-control" name="name" value="Attendance Kiosk" maxlength="100"></label>
          <button class="btn btn-sm" type="submit">Activate this browser</button>
        </form>
      @else <p><a href="/login">Administrator login</a> is required to authorize this browser.</p> @endif
    @else <p>Administrator: <a href="/login">Sign in</a>, then return to this page and activate this browser.</p> @endauth
    <details><summary>Already have an authorized device token?</summary>
      <form action="/kiosk/pair" method="post" class="k-admin">@csrf
        <label>One-time token <input type="password" name="device_token" class="form-control" required autocomplete="off"></label>
        <button type="submit" class="btn btn-sm">Activate device</button>
      </form>
    </details>
  </section>
  @endunless
  @if($device)
    <div class="k-footer">This terminal is authorized as <strong>{{ $device->name }}</strong>. Attendance is recorded and verified by the server; photo evidence is private.
    <form action="/kiosk/unpair" method="post" class="d-inline ms-2">@csrf<button class="btn btn-outline-light btn-sm">Deactivate terminal</button></form></div>
  @endif


  <div class="modal fade" id="kioskNoticeModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg kiosk-notice"><div class="modal-header border-0 pb-0"><div class="kiosk-notice-icon"><i class="bi bi-info-circle-fill"></i></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body pt-3"><h5 id="kioskNoticeTitle" class="fw-bold mb-2">Notice</h5><p id="kioskNoticeMessage" class="mb-0 text-secondary"></p></div><div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Okay</button></div></div></div></div>

<script src="{{ asset('tap-pms-ui/vendor/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('tap-pms-ui/js/kiosk-live.js') }}"></script>
</body></html>
