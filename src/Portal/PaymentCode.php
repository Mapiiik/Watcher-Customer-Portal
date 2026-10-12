<?php
declare(strict_types=1);

namespace App\Portal;

/**
 * The payment code of an invoice, as an image for the page.
 */
final readonly class PaymentCode
{
    /**
     * @param string $src A data URI of the PNG.
     * @param int $width The size to show it at, in CSS pixels.
     * @param int $height
     */
    public function __construct(
        public string $src,
        public int $width,
        public int $height,
    ) {
    }
}
