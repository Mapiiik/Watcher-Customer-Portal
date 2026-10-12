<?php
declare(strict_types=1);

/**
 * The profile the portal uses when the deployment mounts none at /app/profile, and the values a
 * mounted profile leaves out. $operator is the operator's name (OPERATOR_NAME).
 *
 * A profile is a directory with profile.php (this array, or the part of it that differs),
 * consent.php (what the customer is told about the consents) and assets/ (the files named here,
 * served at /branding/<file>). The language is the deployment's APP_DEFAULT_LOCALE.
 *
 * @var string $operator
 */
return [
    'page_name' => $operator . ' | ' . __('Customer portal'),
    'heading' => __('Customer portal'),
    // files in assets/: a logo for a dark background, and colors.css with the brand colours
    // (--brand-dark, --brand-accent, --brand-topbar, --brand-hover), or null for none
    'logo' => null,
    'colors' => null,
    // the icons of the site, each ['rel' => 'icon' or 'apple-touch-icon', 'file' => ..., 'sizes' => '32x32']
    'icons' => [],
    // the name a downloaded invoice is saved under; {number} and {variable_symbol} are filled in
    'invoice_file' => __('Invoice') . '-{number}.pdf',
];
