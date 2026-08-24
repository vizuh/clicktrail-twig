<?php

declare(strict_types=1);

/**
 * Minimal Twig API stubs so the standalone assert runner can load
 * ClickTrailExtension without a composer-installed twig/twig.
 * ONLY for tests/_runner.php; real Twig provides the real API in production.
 */

namespace Twig\Extension;

class AbstractExtension
{
}

namespace Twig;

class TwigFunction
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $callable,
        public readonly array $options = [],
    ) {
    }
}
