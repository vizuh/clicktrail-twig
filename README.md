[English](README.md) | [Português](README.pt-BR.md) | [Deutsch](README.de.md) | [中文](README.zh-CN.md)

<div align="center">

**clicktrail/twig**

Render-only [Twig 3](https://twig.symfony.com/) helpers for ClickTrail attribution — your adapter precomputes the values, these functions only render and escape the markup.

</div>

[![CI](https://github.com/vizuh/clicktrail-twig/actions/workflows/ci.yml/badge.svg)](https://github.com/vizuh/clicktrail-twig/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## Index

- [Why](#why)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Loader script tag](#loader-script-tag)
- [Hidden attribution inputs](#hidden-attribution-inputs)
- [Consent state attributes](#consent-state-attributes)
- [How it differs](#how-it-differs)
- [Testing](#testing)
- [License](#license)

## Why

Attribution markup usually grows by hand inside templates: copy-pasted loader tags, hidden inputs nobody keeps in sync with the GTM variable list, consent attributes that drift. `clicktrail/twig` gives October components, Craft modules and Symfony bundles one canonical way to render ClickTrail markup — and none of the logic.

## Installation

```bash
composer require clicktrail/twig
```

Requires PHP >= 8.1, Twig ^3.0, and `clicktrail/php-sdk`.

## Quick start

```twig
{{ clicktrail_head({script_src: '/ct/loader.js', site_id: site_id}) }}
{# renders <script src="/ct/loader.js" data-ct-site-id="acme-store" async></script> #}
```

That is the whole contract: values in, escaped markup out. No HTTP calls, no storage, no consent judgment — ever.

## Loader script tag

### `clicktrail_head(array $config): string`

Renders the first-party loader `<script>` tag plus `data-ct-*` configuration attributes. `$config` keys:

- `script_src` (required): URL of the first-party loader script.
- Every other scalar key becomes a `data-ct-<key>` attribute (underscores become hyphens), e.g. `site_id` → `data-ct-site-id`.

```twig
{{ clicktrail_head({script_src: '/ct/loader.js', site_id: site_id}) }}
{# every scalar key lands as data-ct-<key>; missing script_src renders '' #}
```

## Hidden attribution inputs

### `clicktrail_hidden_attribution_inputs(array $attribution): string`

Renders hidden `<input>` fields carrying the full attribution context into forms — same field list as the ClickTrail GTM attribution variables and the October `AttributionHidden` component: visitor/session/event/site IDs, `utm_*` values, all 10 ad click IDs, landing page, initial referrer, and consent state.

`$attribution` is a flat precomputed map, e.g.
`['visitor_id' => ..., 'session_id' => ..., 'event_id' => ..., 'site_id' => ...,
'utm_source' => ..., 'gclid' => ..., 'landing_page' => ..., 'initial_referrer' => ...,
'consent_state' => 'granted']`. Empty values are skipped.

```twig
<form method="post">
    {{ clicktrail_hidden_attribution_inputs(attribution)|raw }}
</form>
<!-- one <input type="hidden" name="ct_<field>"> per non-empty field,
     in canonical order; empty fields produce no input at all -->
```

## Consent state attributes

### `clicktrail_consent_state(array $snapshot): string`

Renders normalized consent state as `data-ct-consent-*` attributes from a `ClickTrailConsentSnapshot`-shaped array (functional, analytics_storage, advertising_storage, ad_user_data, ad_personalization). Intended for use inside an opening tag:

```twig
<body{{ clicktrail_consent_state(consent_snapshot)|raw }}>
<!-- <body data-ct-consent-functional="granted"
          data-ct-consent-analytics_storage="granted" ...> -->
```

Unknown or missing keys render nothing — the function makes no consent judgment of its own.

## How it differs

| Typical template helpers | clicktrail/twig |
|---|---|
| Fetch, track or persist inside the template | Render only: never HTTP, never storage, never side effects |
| Evaluate whether consent was legally valid | Renders whatever snapshot the caller passes in |
| Ad-hoc escaping per helper | Every dynamic value through `htmlspecialchars(..., ENT_QUOTES)` |

Positioning: layer-1 framework package (ADR-0001 polyrepo). Platform adapters own the effects (clock, storage, HTTP, consent resolution); this package owns none of them.

## Testing

```bash
php tests/_runner.php   # standalone assert runner, no composer install needed
```

Or inside a container:

```bash
podman run --rm -v "$PWD":/app:Z wordpress:php8.3-apache php tests/_runner.php
```

## License

MIT © 2026 Vizuh OÜ.
