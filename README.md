# clicktrail/twig

Render-only [Twig 3](https://twig.symfony.com/) helpers for
[ClickTrail](https://github.com/vizuh) attribution.

**Safe Twig rendering only — logic belongs to platform adapters.** This package
never makes HTTP calls, never touches a database, never persists anything, and
never evaluates legal validity of consent. Callers (October components, Craft
modules, Symfony bundles) precompute attribution values and pass them in;
these functions only format and escape markup.

## Install

```bash
composer require clicktrail/twig
```

Requires PHP >= 8.1, Twig ^3.0, and `clicktrail/php-sdk` (dev-main while
pre-release).

## Functions

### `clicktrail_head(array $config): string`

Renders the first-party loader `<script>` tag plus `data-ct-*` configuration
attributes. `$config` keys:

- `script_src` (required): URL of the first-party loader script.
- Every other scalar key becomes a `data-ct-<key>` attribute
  (underscores become hyphens), e.g. `site_id` → `data-ct-site-id`.

```twig
{{ clicktrail_head({script_src: '/ct/loader.js', site_id: site_id}) }}
```

### `clicktrail_hidden_attribution_inputs(array $attribution): string`

Renders hidden `<input>` fields carrying the full attribution context into
forms — same field list as the ClickTrail GTM attribution variables and the
October `AttributionHidden` component: visitor/session/event/site IDs,
`utm_*` values, all 10 ad click IDs, landing page, initial referrer, and
consent state.

`$attribution` is a flat precomputed map, e.g.
`['visitor_id' => ..., 'session_id' => ..., 'event_id' => ..., 'site_id' => ...,
'utm_source' => ..., 'gclid' => ..., 'landing_page' => ..., 'initial_referrer' => ...,
'consent_state' => 'granted']`. Empty values are skipped.

```twig
<form method="post">
    {{ clicktrail_hidden_attribution_inputs(attribution)|raw }}
</form>
```

### `clicktrail_consent_state(array $snapshot): string`

Renders normalized consent state as `data-ct-consent-*` attributes from a
`ClickTrailConsentSnapshot`-shaped array (functional, analytics_storage,
advertising_storage, ad_user_data, ad_personalization). Intended for use
inside an opening tag:

```twig
<body{{ clicktrail_consent_state(consent_snapshot)|raw }}>
```

## Escaping

Every dynamic value is escaped with `htmlspecialchars(..., ENT_QUOTES)`.
Returned strings are pre-escaped markup; mark call sites `|raw` only where the
function output forms tags/attributes.

## Positioning

Layer-1 framework package (ADR-0001 polyrepo). Platform adapters own effects
(clock, storage, HTTP, consent resolution); this package owns none of them.

MIT © 2026 Vizuh OÜ.
