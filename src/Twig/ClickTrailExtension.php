<?php

declare(strict_types=1);

namespace ClickTrail\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * RENDER-ONLY CONTRACT (do not violate):
 *
 * - This extension NEVER makes HTTP calls.
 * - It NEVER queries a database or any external service.
 * - It NEVER persists anything (no session, no cookie, no file, no cache).
 * - It NEVER evaluates the legal validity of consent; it renders whatever
 *   snapshot state the caller passes in.
 *
 * Every function accepts precomputed values via its options/context array.
 * Computing attribution state, generating event IDs, resolving consent, and
 * all other effects belong to platform adapters (October components, Craft
 * modules, Symfony bundles, ...), never here.
 *
 * All dynamic output is escaped with htmlspecialchars(..., ENT_QUOTES).
 */
final class ClickTrailExtension extends AbstractExtension
{
    /** Canonical hidden-input field order, mirroring the October AttributionHidden component / GTM attribution variables. */
    private const HIDDEN_FIELD_ORDER = [
        'visitor_id',
        'session_id',
        'event_id',
        'site_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'utm_id',
        // All 10 ad click IDs (Stable::CLICK_ID_KEYS parity with php-sdk).
        'gclid',
        'gbraid',
        'wbraid',
        'fbclid',
        'msclkid',
        'ttclid',
        'twclid',
        'li_fat_id',
        'sccid',
        'epik',
        'landing_page',
        'initial_referrer',
        'consent_state',
    ];

    /** Consent snapshot signals of the normalized ClickTrailConsentSnapshot contract. */
    private const CONSENT_SIGNALS = [
        'functional',
        'analytics_storage',
        'advertising_storage',
        'ad_user_data',
        'ad_personalization',
    ];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('clicktrail_head', [$this, 'renderHead'], ['is_safe' => ['html']]),
            new TwigFunction('clicktrail_hidden_attribution_inputs', [$this, 'hiddenAttributionInputs'], ['is_safe' => ['html']]),
            new TwigFunction('clicktrail_consent_state', [$this, 'consentStateAttributes'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Renders the first-party loader script tag plus data-ct-* config
     * attributes from $config. Key "script_src" becomes the src attribute;
     * every other scalar key becomes data-ct-<key> (underscores -> hyphens).
     */
    public function renderHead(array $config): string
    {
        $src = $config['script_src'] ?? '';
        if (!is_string($src) || $src === '') {
            return '';
        }

        $html = '<script src="' . $this->esc($src) . '"';

        foreach ($config as $key => $value) {
            if ($key === 'script_src' || !is_scalar($value)) {
                continue;
            }
            $attr = 'data-ct-' . str_replace('_', '-', (string) $key);
            $html .= ' ' . $this->esc($attr) . '="' . $this->esc((string) $value) . '"';
        }

        return $html . ' async></script>';
    }

    /**
     * Renders hidden form inputs carrying the full attribution context.
     * Field names are ct_-prefixed; empty values are skipped. Values are
     * precomputed by the caller — see the class-level render-only contract.
     *
     * @param array<string, mixed> $attribution flat name => value map
     */
    public function hiddenAttributionInputs(array $attribution): string
    {
        $html = '';
        foreach (self::HIDDEN_FIELD_ORDER as $field) {
            if (!array_key_exists($field, $attribution)) {
                continue;
            }
            $value = $attribution[$field];
            if ($value === null || (is_string($value) && $value === '')) {
                continue;
            }
            $name = 'ct_' . $field;
            $html .= '<input type="hidden" name="' . $this->esc($name)
                . '" value="' . $this->esc((string) $value) . '">' . "\n";
        }

        return $html;
    }

    /**
     * Renders normalized consent state as data-ct-consent-* attributes for
     * use inside an opening tag. Unknown/missing keys render nothing; this
     * function makes no consent judgment of its own.
     *
     * @param array<string, mixed> $snapshot normalized consent snapshot values
     */
    public function consentStateAttributes(array $snapshot): string
    {
        $html = '';
        foreach (self::CONSENT_SIGNALS as $signal) {
            if (!isset($snapshot[$signal]) || !is_scalar($snapshot[$signal])) {
                continue;
            }
            $value = (string) $snapshot[$signal];
            if ($value === '') {
                continue;
            }
            $html .= ' data-ct-consent-' . str_replace('_', '-', $signal)
                . '="' . $this->esc($value) . '"';
        }

        return $html;
    }

    /** Escape for HTML attribute/text context. ENT_QUOTES covers both quote styles. */
    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
