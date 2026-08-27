[English](README.md) | [Português](README.pt-BR.md) | [Deutsch](README.de.md) | [中文](README.zh-CN.md)

<div align="center">

**clicktrail/twig**

面向 ClickTrail 归因的只渲染 [Twig 3](https://twig.symfony.com/) 辅助函数。数值由你的适配器预先计算，这些函数只负责渲染和转义标记。

</div>

[![CI](https://github.com/vizuh/clicktrail-twig/actions/workflows/ci.yml/badge.svg)](https://github.com/vizuh/clicktrail-twig/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## 目录

- [为什么](#为什么)
- [安装](#安装)
- [快速上手](#快速上手)
- [加载脚本标签](#加载脚本标签)
- [隐藏归因输入域](#隐藏归因输入域)
- [同意状态属性](#同意状态属性)
- [与其他方案的区别](#与其他方案的区别)
- [测试](#测试)
- [许可证](#许可证)

## 为什么

模板里的归因标记通常是手工堆出来的：复制粘贴的加载脚本标签、没有与 GTM 变量列表同步的隐藏输入域，以及逐渐失真的同意属性。`clicktrail/twig` 为 October 组件、Craft 模块和 Symfony bundle 提供一种规范化方式来渲染 ClickTrail 标记，但不负责归因逻辑。

## 安装

```bash
composer require clicktrail/twig
```

要求 PHP >= 8.1、Twig ^3.0 以及 `clicktrail/php-sdk`。

## 快速上手

```twig
{{ clicktrail_head({script_src: '/ct/loader.js', site_id: site_id}) }}
{# 渲染 <script src="/ct/loader.js" data-ct-site-id="acme-store" async></script> #}
```

这就是全部契约：数值传入，输出转义后的标记。不发 HTTP 请求，不做持久化，也不判断同意状态。

## 加载脚本标签

### `clicktrail_head(array $config): string`

渲染第一方加载器的 `<script>` 标签以及 `data-ct-*` 配置属性。`$config` 的键：

- `script_src`（必填）：第一方加载器脚本的 URL。
- 其余所有标量键都会变成 `data-ct-<key>` 属性（下划线转为连字符），例如 `site_id` → `data-ct-site-id`。

```twig
{{ clicktrail_head({script_src: '/ct/loader.js', site_id: site_id}) }}
{# 每个标量键都变成 data-ct-<key>；缺少 script_src 时渲染 '' #}
```

## 隐藏归因输入域

### `clicktrail_hidden_attribution_inputs(array $attribution): string`

渲染携带已记录归因上下文的隐藏 `<input>` 字段。字段列表与 ClickTrail GTM 归因变量以及 October `AttributionHidden` 组件一致：visitor/session/event/site ID、`utm_*` 值、10 个广告点击 ID、落地页、初始 referrer 以及同意状态。

`$attribution` 是一个扁平的预计算映射，例如
`['visitor_id' => ..., 'session_id' => ..., 'event_id' => ..., 'site_id' => ...,
'utm_source' => ..., 'gclid' => ..., 'landing_page' => ..., 'initial_referrer' => ...,
'consent_state' => 'granted']`。空值会被跳过。

```twig
<form method="post">
    {{ clicktrail_hidden_attribution_inputs(attribution)|raw }}
</form>
<!-- 每个非空字段生成一个 <input type="hidden" name="ct_<field>">，
     按规范顺序排列；空字段完全不产生输入域 -->
```

## 同意状态属性

### `clicktrail_consent_state(array $snapshot): string`

将规范化后的同意状态渲染为 `data-ct-consent-*` 属性，输入为 `ClickTrailConsentSnapshot` 结构的数组（functional、analytics_storage、advertising_storage、ad_user_data、ad_personalization）。用于起始标签内部：

```twig
<body{{ clicktrail_consent_state(consent_snapshot)|raw }}>
<!-- <body data-ct-consent-functional="granted"
          data-ct-consent-analytics_storage="granted" ...> -->
```

未知或缺失的键不会渲染任何内容。该函数不判断同意状态。

## 与其他方案的区别

| 常见模板辅助方案 | clicktrail/twig |
|---|---|
| 在模板内部请求、跟踪或持久化 | 只负责渲染：从不发 HTTP、从不落存储、从无副作用 |
| 评估同意状态是否法律有效 | 调用方传入什么快照，就渲染什么快照 |
| 各辅助函数各自的临时转义 | 所有动态值统一经过 `htmlspecialchars(..., ENT_QUOTES)` |

定位：layer-1 框架包（ADR-0001 polyrepo）。效果（时钟、存储、HTTP、同意解析）由平台适配器持有；本包一样都不持有。

## 测试

```bash
php tests/_runner.php   # 独立断言运行器，无需 composer install
```

或在容器内：

```bash
podman run --rm -v "$PWD":/app:Z wordpress:php8.3-apache php tests/_runner.php
```

## 许可证

MIT © 2026 Vizuh OÜ.
