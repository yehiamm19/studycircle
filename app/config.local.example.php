<?php

declare(strict_types=1);

/**
 * Copy this file to config.local.php on the server (same folder as config.php).
 * config.local.php overrides defaults and should NOT be committed if it contains secrets.
 *
 * Hosting checklist:
 * - chmod 755 database uploads (folders); SQLite file must be writable by PHP (often 644 or 664).
 * - Ensure mod_rewrite (Apache) or equivalent routes all requests to index.php.
 * - Use HTTPS; set trust_https_proxy => true if SSL ends at the proxy.
 * - Keep debug => false unless diagnosing issues.
 * - demo_quick_login defaults to true (buyer previews); set demo_quick_login => false for strict production.
 *
 * @return array<string,mixed>
 */
return [
    // 'app_url' => '/studycircle',
    // 'timezone' => 'Africa/Cairo',
    // 'trust_https_proxy' => true,
    // 'demo_quick_login' => false,
];
