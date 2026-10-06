---
paths:
  - resources/js/**
  - resources/scss/**
  - resources/views/**
  - vite.config.js
  - package.json
---

# Frontend Conventions

## Bundling model

- Everything is bundled by Vite. `resources/scss/app.scss` and `resources/js/app.js` are the only entries (declared in `vite.config.js`), loaded once in `resources/views/layouts/app.blade.php` via `@vite`. No CDN assets, no per-page build entries.
- Compilation order in the bundle follows ES module evaluation order — see jQuery UI below.

## JavaScript (resources/js)

Module roles:

- `app.js` — entry point; imports `bootstrap.js` and `jquery-ui.js`; exposes Bootstrap's JS API as `window.bootstrap`.
- `bootstrap.js` — global wiring (jQuery, Bootstrap). Site-wide JS behavior belongs here.
- `jquery.js` — imports jQuery and assigns `window.jQuery` / `window.$`.
- `jquery-ui.js` — must import `./jquery.js` **first**: jQuery UI's UMD factory reads the global `jQuery` at evaluation time, so the global must exist before jQuery UI runs. The comment in that file documents this — preserve it.

### jQuery

- jQuery UI is imported here via the full bundle `jquery-ui/dist/jquery-ui.min.js` (all widgets and effects). To slim the bundle, replace that line in `jquery-ui.js` with per-widget imports (`jquery-ui/ui/widgets/<name>`, one per widget actually used). Never import jQuery UI anywhere else, and never load the full bundle plus individual widgets.
- Initialize widgets inside `$(function () { ... })` and scope selectors to a page/feature root — never global selectors.
- jQuery 4.0 removed these; use native equivalents, never recreate them:
  - `$.trim()` → `String.prototype.trim`
  - `$.isArray()` → `Array.isArray`
  - `$.isFunction()` → `typeof value === "function"`
  - `$.isNumeric()` / `$.isWindow()` / `$.type()` → feature-detect or `Object.prototype.toString`
  - `$.parseJSON()` → `JSON.parse`
  - `$.now()` → `Date.now()`
  - `$.cssProps`, `$.cssNumber`, `$.camelCase`, `$.nodeName`, `$.unique`, `$.sub` → gone
  - jQuery object prototype `push` / `sort` / `splice` → gone; use arrays
  - `$.event.global`, `$.fx.interval` → gone
  - JSON-to-JSONP auto-promotion in `$.ajax` → removed; set `dataType` explicitly
  - `toggleClass(boolean)` signature → removed; use explicit `addClass` / `removeClass`
- Prefer `.on()` / `.off()` for events. The shorthand methods (`click()`, `submit()`, `hover()` as a handler, etc.) are deprecated and absent from jQuery's slim build.
- Exactly one jQuery instance must exist in the bundle: `npm ls jquery` should show a single `jquery@4.0.0` (jQuery UI 1.14 supports `jquery >=1.12 <5.0`). If a conflict is suspected at runtime, assert `window.jQuery.fn.jquery === $().jquery` and that `$.ui` is registered before using jQuery UI.
- Prefer native DOM APIs (`querySelector`, `addEventListener`, `fetch`) when jQuery adds nothing — it exists for DOM convenience and jQuery UI, not as a requirement.

### jQuery UI

- Use package `jquery-ui` (1.14.x). Do **not** install the obsolete `jquery-ui-dist` (capped at `jquery <4.0.0`, installs a conflicting nested jQuery).
- The theme CSS is `jquery-ui/dist/themes/base/jquery-ui.min.css`, imported in `app.scss`; its `images/` are emitted automatically by Vite.
- Widgets register on `$.fn` as an import side effect.

## Sass (resources/scss)

- `_variables.scss` holds the Bootstrap `!default` overrides and must remain the first import in `app.scss` (before `bootstrap/scss/bootstrap`) so the overrides win. Don't move it after the Bootstrap import.
- Import order in `app.scss`: `variables` (overrides) → `bootstrap/scss/bootstrap` → jQuery UI theme CSS.
- Keep Bootstrap's `@import`-based Sass. Do not convert the partials to `@use` / `@forward` — Bootstrap 5.3 sources use `@import`, and mixing module systems breaks the build. (`@import` deprecation warnings are already silenced in `vite.config.js`.)
- Site styles go in `_name.scss` partials imported from `app.scss`. Prefer Bootstrap utilities/components over bespoke CSS.

## Blade views (resources/views)

- Layout contract (`layouts/app.blade.php`): `@vite(['resources/scss/app.scss', 'resources/js/app.js'])`, `<x-nav />`, `<x-flash-messages />`, `@yield('title')`, `@yield('content')`, `@stack('styles')` before `</head>`, `@stack('scripts')` before `</body>`.
- Views extend `layouts.app` and fill `@section('title')` / `@section('content')`. Reusable UI goes in `resources/views/components/` (Blade components `<x-...>`) — reuse before writing new markup.
- Use Bootstrap classes and drive Bootstrap's JS through `data-bs-*` attributes. Do not attach jQuery to Bootstrap components; the two ecosystems don't interoperate that way.
- Include the accessibility attributes Bootstrap expects (`aria-*`, `role`, `aria-current` on active nav links, etc.).
- Generate internal links with `route()` and named routes — never hard-coded URLs.
- No inline `<script>` or `<style>` in views. Behavior lives in `resources/js` modules; small page-local init may be pushed via `@push('scripts')` only when a module would be overkill.

## package.json / vite.config.js

- Runtime frontend libraries (`bootstrap`, `jquery`, `jquery-ui`) are `dependencies`; tooling is `devDependencies`. Don't add packages without approval.
- `vite.config.js` `input` is the only place bundle entries are declared; don't add entries casually.