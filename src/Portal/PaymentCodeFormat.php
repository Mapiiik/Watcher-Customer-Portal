<?php
declare(strict_types=1);

namespace App\Portal;

/**
 * The formats of the payment codes, as PAYMENT_CODE names them.
 */
enum PaymentCodeFormat: string
{
    // QR Platba of Czech banks
    case Spayd = 'spayd';

    // the barcode of Croatian payment slips (HUB 3A)
    case Hub3 = 'hub3';
}
