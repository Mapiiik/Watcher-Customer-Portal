<?php
declare(strict_types=1);

namespace App\Portal;

use App\Crm\Invoice;
use Cake\Core\Configure;
use Com\Tecnick\Barcode\Barcode;
use RuntimeException;

/**
 * The codes a bank's app scans to pay an invoice. Each pays what is left of one invoice under its
 * variable symbol, so that the payment pairs with it, and none has a due date, so the bank pays
 * at once.
 */
final readonly class PaymentCodes
{
    /**
     * @param \App\Portal\PaymentCodeFormat|null $format Null for no codes.
     * @param string $iban The account the invoices are paid to.
     * @param string $recipient The operator's name.
     * @param string $address The operator's address, with the town after its last comma.
     * @param string $currency ISO 4217.
     * @throws \RuntimeException When the codes would not pay the operator.
     */
    public function __construct(
        public ?PaymentCodeFormat $format,
        public string $iban,
        public string $recipient,
        public string $address,
        public string $currency,
    ) {
        if ($format !== null && !self::isIban($iban)) {
            throw new RuntimeException('PAYMENT_IBAN is not a valid IBAN, and the payment codes need one.');
        }
        if ($format === PaymentCodeFormat::Hub3 && $currency !== 'EUR') {
            throw new RuntimeException('The HUB3 payment codes are for invoices in euro, set CURRENCY=EUR.');
        }
    }

    /**
     * The codes the environment asks for.
     *
     * @return self
     * @throws \RuntimeException When PAYMENT_CODE is not a known format, or the codes would not pay the operator.
     */
    public static function fromConfig(): self
    {
        $code = (string)Configure::read('Portal.payment.code');
        $format = PaymentCodeFormat::tryFrom($code);
        if ($code !== '' && $format === null) {
            throw new RuntimeException(
                sprintf('PAYMENT_CODE "%s" is not known, set spayd or hub3, or leave it out.', $code),
            );
        }

        return new self(
            $format,
            (string)Configure::read('Portal.payment.iban'),
            (string)Configure::read('Portal.operator.name'),
            (string)Configure::read('Portal.operator.address'),
            (string)Configure::read('Portal.currency'),
        );
    }

    /**
     * The code of an invoice as an image.
     *
     * @param \App\Crm\Invoice $invoice The invoice.
     * @return \App\Portal\PaymentCode|null Null when there is nothing to pay or the invoice fits no code.
     */
    public function image(Invoice $invoice): ?PaymentCode
    {
        $text = $this->text($invoice);
        if ($text === null || $this->format === null) {
            return null;
        }

        // drawn at twice the size it is shown at, so that it stays sharp on fine screens
        $barcode = match ($this->format) {
            PaymentCodeFormat::Spayd => (new Barcode())
                ->getBarcodeObj('QRCODE,M', $text, -8, -8, 'black', [-4, -4, -4, -4]),
            // modules three times as tall as wide and about 9 columns, as the standard has them on paper
            PaymentCodeFormat::Hub3 => (new Barcode())
                ->getBarcodeObj('PDF417,7,4', $text, -2, -3),
        };
        $png = $barcode->setBackgroundColor('white')->getPngData(false);
        [$width, $height] = getimagesizefromstring($png) ?: [0, 0];

        return new PaymentCode('data:image/png;base64,' . base64_encode($png), intdiv($width, 2), intdiv($height, 2));
    }

    /**
     * What the code of an invoice says.
     *
     * @param \App\Crm\Invoice $invoice The invoice.
     * @return string|null Null when there is nothing to pay or the invoice fits no code.
     */
    public function text(Invoice $invoice): ?string
    {
        if ($invoice->isPaid()) {
            return null;
        }
        $message = __('Invoice {0}', $invoice->number);

        return match ($this->format) {
            null => null,
            PaymentCodeFormat::Spayd => $this->spayd($invoice, $message),
            PaymentCodeFormat::Hub3 => $this->hub3($invoice, $message),
        };
    }

    /**
     * QR Platba, as the Czech Banking Association specifies it.
     *
     * @param \App\Crm\Invoice $invoice The invoice.
     * @param string $message For the recipient.
     * @return string|null Null when the variable symbol is not one a Czech bank takes.
     */
    private function spayd(Invoice $invoice, string $message): ?string
    {
        if (preg_match('/^\d{1,10}$/', $invoice->variableSymbol) !== 1) {
            return null;
        }

        $text = 'SPD*1.0';
        foreach (
            [
                'ACC' => $this->iban,
                'AM' => number_format($invoice->debt, 2, '.', ''),
                'CC' => $this->currency,
                'MSG' => mb_substr($message, 0, 60),
                'RN' => mb_substr($this->recipient, 0, 35),
                'X-VS' => $invoice->variableSymbol,
            ] as $key => $value
        ) {
            $text .= '*' . $key . ':' . str_replace('*', '%2A', $value);
        }

        return $text;
    }

    /**
     * The barcode of a HUB 3A payment slip, as the Croatian Banking Association specifies it.
     * The variable symbol is the model and the reference, such as "01 126101264".
     *
     * @param \App\Crm\Invoice $invoice The invoice.
     * @param string $message For the recipient.
     * @return string|null Null when the variable symbol is no model and reference.
     */
    private function hub3(Invoice $invoice, string $message): ?string
    {
        if (preg_match('/^(?:HR)?(\d{2})\s+([0-9-]{1,22})$/', $invoice->variableSymbol, $reference) !== 1) {
            return null;
        }
        $comma = strrpos($this->address, ',');
        $street = $comma === false ? '' : substr($this->address, 0, $comma);
        $town = $comma === false ? $this->address : substr($this->address, $comma + 1);

        return implode("\n", [
            'HRVHUB30',
            $this->currency,
            sprintf('%015d', (int)round($invoice->debt * 100)),
            // the payer, whom the bank knows
            '',
            '',
            '',
            self::hub3Text($this->recipient, 25),
            self::hub3Text($street, 25),
            self::hub3Text($town, 27),
            $this->iban,
            'HR' . $reference[1],
            $reference[2],
            // no purpose code
            '',
            self::hub3Text($message, 35),
        ]);
    }

    /**
     * A text with only the characters HUB3 allows, cut to its field.
     *
     * @param string $text The text.
     * @param int $length The longest the field takes.
     * @return string
     */
    private static function hub3Text(string $text, int $length): string
    {
        $text = (string)preg_replace("~[^0-9A-Za-zČčĆćĐđŠšŽž ,.:+?'/()-]+~u", ' ', $text);

        return trim(mb_substr(trim((string)preg_replace('/ {2,}/', ' ', $text)), 0, $length));
    }

    /**
     * The IBAN is well formed and its check digits fit.
     *
     * @param string $iban Without spaces.
     * @return bool
     */
    private static function isIban(string $iban): bool
    {
        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban) !== 1) {
            return false;
        }
        $digits = '';
        foreach (str_split(substr($iban, 4) . substr($iban, 0, 4)) as $character) {
            $digits .= is_numeric($character) ? $character : (string)(ord($character) - 55);
        }
        // modulo 97 by parts, the number is too long for an integer
        $rest = 0;
        foreach (str_split($digits, 7) as $part) {
            $rest = (int)($rest . $part) % 97;
        }

        return $rest === 1;
    }
}
