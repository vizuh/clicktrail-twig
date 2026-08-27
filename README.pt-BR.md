[English](README.md) | [Português](README.pt-BR.md) | [Deutsch](README.de.md) | [中文](README.zh-CN.md)

<div align="center">

**clicktrail/twig**

Helpers render-only para [Twig 3](https://twig.symfony.com/) da atribuição
ClickTrail. Seu adapter pré-computa os valores; estas funções apenas renderizam
e escapam a marcação.

</div>

[![CI](https://github.com/vizuh/clicktrail-twig/actions/workflows/ci.yml/badge.svg)](https://github.com/vizuh/clicktrail-twig/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## Índice

- [Por quê](#por-quê)
- [Instalação](#instalação)
- [Início rápido](#início-rápido)
- [Tag do script de loader](#tag-do-script-de-loader)
- [Inputs ocultos de atribuição](#inputs-ocultos-de-atribuição)
- [Atributos de estado de consentimento](#atributos-de-estado-de-consentimento)
- [Como é diferente](#como-é-diferente)
- [Testes](#testes)
- [Licença](#licença)

## Por quê

A marcação de atribuição costuma crescer à mão dentro dos templates: tags de
loader copiadas, inputs ocultos sem sincronia com a lista de variáveis do GTM e
atributos de consentimento que defasam. O `clicktrail/twig` oferece a components
October, módulos Craft e bundles Symfony uma forma canônica de renderizar a
marcação ClickTrail, sem controlar a lógica de atribuição.

## Instalação

```bash
composer require clicktrail/twig
```

Requer PHP >= 8.1, Twig ^3.0 e `clicktrail/php-sdk`.

## Início rápido

```twig
{{ clicktrail_head({script_src: '/ct/loader.js', site_id: site_id}) }}
{# renderiza <script src="/ct/loader.js" data-ct-site-id="acme-store" async></script> #}
```

Esse é o contrato inteiro: valores entram, marcação escapada sai. Sem chamadas
HTTP, persistência ou decisões de consentimento.

## Tag do script de loader

### `clicktrail_head(array $config): string`

Renderiza a tag `<script>` do loader first-party mais os atributos de configuração `data-ct-*`. Chaves de `$config`:

- `script_src` (obrigatória): URL do script de loader first-party.
- Toda outra chave escalar vira um atributo `data-ct-<key>` (underscores viram hífens), ex.: `site_id` → `data-ct-site-id`.

```twig
{{ clicktrail_head({script_src: '/ct/loader.js', site_id: site_id}) }}
{# toda chave escalar vira data-ct-<key>; script_src ausente renderiza '' #}
```

## Inputs ocultos de atribuição

### `clicktrail_hidden_attribution_inputs(array $attribution): string`

Renderiza campos `<input>` ocultos que levam o contexto de atribuição
documentado aos formulários. A lista coincide com a variável de atribuição GTM
do ClickTrail e o component `AttributionHidden` do October: IDs de
visitor/session/event/site, valores `utm_*`, 10 ad click IDs, landing page,
referrer inicial e estado de consentimento.

`$attribution` é um mapa plano pré-computado, ex.:
`['visitor_id' => ..., 'session_id' => ..., 'event_id' => ..., 'site_id' => ...,
'utm_source' => ..., 'gclid' => ..., 'landing_page' => ..., 'initial_referrer' => ...,
'consent_state' => 'granted']`. Valores vazios são ignorados.

```twig
<form method="post">
    {{ clicktrail_hidden_attribution_inputs(attribution)|raw }}
</form>
<!-- um <input type="hidden" name="ct_<field>"> por campo não vazio,
     na ordem canônica; campo vazio não gera input algum -->
```

## Atributos de estado de consentimento

### `clicktrail_consent_state(array $snapshot): string`

Renderiza o estado de consentimento normalizado como atributos `data-ct-consent-*` a partir de um array no formato `ClickTrailConsentSnapshot` (functional, analytics_storage, advertising_storage, ad_user_data, ad_personalization). Pensado para uso dentro de uma tag de abertura:

```twig
<body{{ clicktrail_consent_state(consent_snapshot)|raw }}>
<!-- <body data-ct-consent-functional="granted"
          data-ct-consent-analytics_storage="granted" ...> -->
```

Chaves ausentes ou desconhecidas não rendem nada. A função não decide o estado
de consentimento.

## Como é diferente

| Helpers de template típicos | clicktrail/twig |
|---|---|
| Buscam, rastreiam ou persistem dentro do template | Apenas renderizam: nunca HTTP, nunca persistência, nunca efeitos colaterais |
| Avaliam se o consentimento era legalmente válido | Renderizam o snapshot que o chamador passar |
| Escapamento ad hoc por helper | Todo valor dinâmico passa por `htmlspecialchars(..., ENT_QUOTES)` |

Posicionamento: pacote de framework layer-1 (polyrepo ADR-0001). Os adapters de plataforma donos dos efeitos (clock, armazenamento, HTTP, resolução de consentimento); este pacote não é dono de nenhum deles.

## Testes

```bash
php tests/_runner.php   # runner de asserts autônomo, sem composer install
```

Ou dentro de um container:

```bash
podman run --rm -v "$PWD":/app:Z wordpress:php8.3-apache php tests/_runner.php
```

## Licença

MIT © 2026 Vizuh OÜ.
