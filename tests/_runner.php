<?php

declare(strict_types=1);

/**
 * Standalone assert runner - no composer needed.
 * Pattern: clicktrail-php/tests/_runner.php. Run directly:
 *   php tests/_runner.php
 * or inside a container: podman run --rm -v "$PWD":/app:Z wordpress:php8.3-apache php tests/_runner.php
 */

if (!class_exists(\Twig\Extension\AbstractExtension::class)) {
    require __DIR__ . '/_twig_stubs.php';
}

spl_autoload_register(function ($class): void {
    $prefix = 'ClickTrail\\Twig\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = __DIR__ . '/../src/Twig/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use ClickTrail\Twig\ClickTrailExtension;

function check(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: $msg\n");
        exit(1);
    }
}

$ext = new ClickTrailExtension();

// --- T1: functions registered -------------------------------------------------
$names = array_map(fn ($f) => $f->name, $ext->getFunctions());
check(in_array('clicktrail_head', $names, true), 'T1 head fn registered');
check(in_array('clicktrail_hidden_attribution_inputs', $names, true), 'T1 inputs fn registered');
check(in_array('clicktrail_consent_state', $names, true), 'T1 consent fn registered');

// --- T2: head() escaping + config --------------------------------------------
$evil = '"><script>alert(1)</script>';
$head = $ext->renderHead([
    'script_src' => '/ct/loader.js?x=1&y=2',
    'site_id' => 'site-001',
    'first_party_endpoint' => 'https://cdn.evil.example/x"onerror="p',
    'nested' => ['ignored'],
]);
check(str_starts_with($head, '<script src="/ct/loader.js?x=1&amp;y=2"'), 'T2 src escaped');
check(strpos($head, 'data-ct-site-id="site-001"') !== false, 'T2 site id attr');
check(strpos($head, 'data-ct-first-party-endpoint="https://cdn.evil.example/x&quot;onerror=&quot;p"') !== false, 'T2 evil attr escaped');
check(strpos($head, '"onerror') === false && strpos($head, 'onerror="p') === false, 'T2 no raw injection');
check(str_ends_with($head, ' async></script>'), 'T2 closing');
check(substr_count($head, '<input') === 0, 'T2 no inputs in head');

// --- T3: full hidden-input field list (mirror October AttributionHidden) ------
$attribution = [
    'visitor_id' => 'v-abc123',
    'session_id' => 's-def456',
    'event_id' => bin2hex(random_bytes(16)),
    'site_id' => 'site-001',
    'utm_source' => 'google',
    'utm_medium' => 'cpc',
    'utm_campaign' => 'summer',
    'utm_content' => '',
    'utm_term' => null,
    'utm_id' => 'cmp-9',
    'gclid' => 'XYZ1',
    'fbclid' => 'F1',
    'li_fat_id' => 'L9',
    'landing_page' => 'https://example.com/promo',
    'initial_referrer' => 'https://news.example.org/a',
    'consent_state' => 'granted',
];
$inputs = $ext->hiddenAttributionInputs($attribution);
$names_rendered = [];
foreach (explode("\n", trim($inputs)) as $line) {
    preg_match('/name="([^"]+)"/', $line, $m);
    $names_rendered[] = $m[1] ?? '?';
}
$expected_names = ['ct_visitor_id', 'ct_session_id', 'ct_event_id', 'ct_site_id',
    'ct_utm_source', 'ct_utm_medium', 'ct_utm_campaign', 'ct_utm_id',
    'ct_gclid', 'ct_fbclid', 'ct_li_fat_id',
    'ct_landing_page', 'ct_initial_referrer', 'ct_consent_state'];
check($names_rendered === $expected_names, 'T3 canonical field order/list');
check(substr_count($inputs, '<input type="hidden"') === count($expected_names), 'T3 input count');

// --- T4: empty utm_* skipped, missing keys skipped ----------------------------
$partial = $ext->hiddenAttributionInputs(['gclid' => 'G1']);
check(trim($partial) === '<input type="hidden" name="ct_gclid" value="G1">', 'T4 partial minimal');

// --- T5: attribute-value escaping in hidden inputs -----------------------------
$xss = $ext->hiddenAttributionInputs([
    'utm_campaign' => '" onmouseover="alert(1)',
    'gclid' => "a'b<c>",
]);
check(strpos($xss, '&quot; onmouseover=&quot;alert(1)') !== false, 'T5 double-quote escaped');
check(strpos($xss, '&#039;b&lt;c&gt;') !== false, 'T5 single-quote/angle escaped');
check(preg_match_all('/value="([^"]*)"/', $xss, $vm) === 2, 'T5 value attr count stable');

// --- T6: consent state attributes ----------------------------------------------
$consent = $ext->consentStateAttributes([
    'functional' => 'granted',
    'analytics_storage' => 'denied',
    'advertising_storage' => 'unknown',
    'ad_user_data' => 'not_applicable',
    'ad_personalization' => 'granted',
    'suppression_reason' => 'withdrawn',
]);
foreach ([
    'data-ct-consent-functional="granted"' => true,
    'data-ct-consent-analytics-storage="denied"' => true,
    'data-ct-consent-advertising-storage="unknown"' => true,
    'data-ct-consent-ad-user-data="not_applicable"' => true,
    'data-ct-consent-ad-personalization="granted"' => true,
] as $needle => $_) {
    check(strpos($consent, $needle) !== false, "T6 contains $needle");
}
check(strpos($consent, 'suppression_reason') === false, 'T6 non-signal key ignored');
check($ext->consentStateAttributes([]) === '', 'T6 empty snapshot -> empty string');
check($ext->renderHead([]) === '', 'T6 head without script_src -> empty string');

echo "TWIG EXTENSION ASSERTIONS PASSED (" . count($expected_names) . " hidden fields verified)\n";
