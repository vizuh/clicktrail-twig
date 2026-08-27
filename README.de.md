[English](README.md) | [Português](README.pt-BR.md) | [Deutsch](README.de.md) | [中文](README.zh-CN.md)

<div align="center">

**clicktrail/twig**

Render-only [Twig 3](https://twig.symfony.com/)-Helfer für
ClickTrail-Attribution. Ihr Adapter berechnet die Werte; diese Funktionen
rendern und escapen nur das Markup.

</div>

[![CI](https://github.com/vizuh/clicktrail-twig/actions/workflows/ci.yml/badge.svg)](https://github.com/vizuh/clicktrail-twig/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## Index

- [Warum](#warum)
- [Installation](#installation)
- [Schnellstart](#schnellstart)
- [Loader-Skript-Tag](#loader-skript-tag)
- [Versteckte Attributionsfelder](#versteckte-attributionsfelder)
- [Consent-State-Attribute](#consent-state-attribute)
- [Wie es sich unterscheidet](#wie-es-sich-unterscheidet)
- [Tests](#tests)
- [Lizenz](#lizenz)

## Warum

Attributions-Markup wächst in Templates meist von Hand: kopierte Loader-Tags,
versteckte Inputs ohne Synchronisierung mit der GTM-Variablenliste und
abdriftende Consent-Attribute. `clicktrail/twig` gibt October-Komponenten,
Craft-Modulen und Symfony-Bundles einen kanonischen Weg, ClickTrail-Markup zu
rendern, ohne Attributionslogik zu besitzen.

## Installation

```bash
composer require clicktrail/twig
```

Benötigt PHP >= 8.1, Twig ^3.0 und `clicktrail/php-sdk`.

## Schnellstart

```twig
{{ clicktrail_head({script_src: '/ct/loader.js', site_id: site_id}) }}
{# rendert <script src="/ct/loader.js" data-ct-site-id="acme-store" async></script> #}
```

Das ist der ganze Vertrag: Werte rein, escaptes Markup raus. Keine HTTP-Aufrufe,
kein Storage und keine Consent-Entscheidungen.

## Loader-Skript-Tag

### `clicktrail_head(array $config): string`

Rendert das First-Party-Loader-`<script>`-Tag plus `data-ct-*`-Konfigurationsattribute. `$config`-Schlüssel:

- `script_src` (Pflicht): URL des First-Party-Loader-Skripts.
- Jeder weitere skalare Schlüssel wird zu einem `data-ct-<key>`-Attribut (Unterstriche werden zu Bindestrichen), z. B. `site_id` → `data-ct-site-id`.

```twig
{{ clicktrail_head({script_src: '/ct/loader.js', site_id: site_id}) }}
{# jeder skalare Schlüssel landet als data-ct-<key>; fehlendes script_src rendert '' #}
```

## Versteckte Attributionsfelder

### `clicktrail_hidden_attribution_inputs(array $attribution): string`

Rendert versteckte `<input>`-Felder, die den dokumentierten
Attributionskontext in Formulare tragen. Die Feldliste entspricht der
ClickTrail-GTM-Attributionsvariable und der October-`AttributionHidden`-Komponente:
Visitor-/Session-/Event-/Site-IDs, `utm_*`-Werte, alle 10 Ad-Click-IDs,
Landingpage, initialer Referrer und Consent-State.

`$attribution` ist eine flache vorgerechnete Map, z. B.
`['visitor_id' => ..., 'session_id' => ..., 'event_id' => ..., 'site_id' => ...,
'utm_source' => ..., 'gclid' => ..., 'landing_page' => ..., 'initial_referrer' => ...,
'consent_state' => 'granted']`. Leere Werte werden übersprungen.

```twig
<form method="post">
    {{ clicktrail_hidden_attribution_inputs(attribution)|raw }}
</form>
<!-- ein <input type="hidden" name="ct_<field>"> pro nicht-leerem Feld,
     in kanonischer Reihenfolge; leere Felder erzeugen gar kein Input -->
```

## Consent-State-Attribute

### `clicktrail_consent_state(array $snapshot): string`

Rendert den normalisierten Consent-State als `data-ct-consent-*`-Attribute aus einem Array in `ClickTrailConsentSnapshot`-Form (functional, analytics_storage, advertising_storage, ad_user_data, ad_personalization). Gedacht für die Verwendung in einem öffnenden Tag:

```twig
<body{{ clicktrail_consent_state(consent_snapshot)|raw }}>
<!-- <body data-ct-consent-functional="granted"
          data-ct-consent-analytics_storage="granted" ...> -->
```

Unbekannte oder fehlende Schlüssel rendern nichts. Die Funktion trifft keine
Consent-Entscheidung.

## Wie es sich unterscheidet

| Typische Template-Helfer | clicktrail/twig |
|---|---|
| Holt, trackt oder persistiert im Template | Nur Rendern: nie HTTP, nie Storage, nie Seiteneffekte |
| Bewertet, ob Consent rechtlich gültig war | Rendert genau den Snapshot, den der Aufrufer übergibt |
| Ad-hoc-Escaping pro Helfer | Jeder dynamische Wert durch `htmlspecialchars(..., ENT_QUOTES)` |

Positionierung: Layer-1-Framework-Paket (ADR-0001-Polyrepo). Plattform-Adapter besitzen die Effekte (Clock, Storage, HTTP, Consent-Auflösung); dieses Paket besitzt keinen davon.

## Tests

```bash
php tests/_runner.php   # eigenständiger Assert-Runner, kein composer install nötig
```

Oder im Container:

```bash
podman run --rm -v "$PWD":/app:Z wordpress:php8.3-apache php tests/_runner.php
```

## Lizenz

MIT © 2026 Vizuh OÜ.
