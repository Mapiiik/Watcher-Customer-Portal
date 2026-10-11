<?php
/**
 * The profile the portal uses when the deployment mounts none at /app/profile, and the values a
 * mounted profile leaves out.
 *
 * A profile is a directory with profile.php (this array, or the part of it that differs),
 * consent.php (what the customer is told about the consents), introduction.php (the page after
 * signing in) and assets/ (the logos and colors.css it names, served by branding.php).
 */
return [
    // language file in app/lang
    'language' => 'cs',
    'page_name' => OPERATOR_NAME . ' | Uživatelský portál',
    'heading' => 'Uživatelský portál',
    // files in assets/, or null for none
    'logo' => null,
    'menu_logo' => null,
    'colors' => null,
    // the name a downloaded invoice is saved under; {number} and {variable_symbol} are filled in
    'invoice_file' => 'Faktura-{number}.pdf',
];
