<?php
declare(strict_types=1);

namespace App\Test\TestCase\Portal;

use App\Crm\Invoice;
use App\Portal\PaymentCode;
use App\Portal\PaymentCodeFormat;
use App\Portal\PaymentCodes;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;

#[CoversClass(PaymentCodes::class)]
#[UsesClass(PaymentCode::class)]
class PaymentCodesTest extends TestCase
{
    private const CZ_IBAN = 'CZ6508000000192000145399';

    private const HR_IBAN = 'HR1210010051863000160';

    public function testQrPlatbaPaysWhatIsLeftOfTheInvoiceAtOnce(): void
    {
        $codes = new PaymentCodes(PaymentCodeFormat::Spayd, self::CZ_IBAN, 'Example*ISP, s.r.o.', '', 'CZK');

        $this->assertSame(
            'SPD*1.0*ACC:CZ6508000000192000145399*AM:740.50*CC:CZK*MSG:Invoice 2026100231'
                . '*RN:Example%2AISP, s.r.o.*X-VS:110009',
            $codes->text($this->invoice('110009', 740.5)),
        );
    }

    public function testTheHub3BarcodeSplitsTheModelFromTheReference(): void
    {
        $codes = new PaymentCodes(
            PaymentCodeFormat::Hub3,
            self::HR_IBAN,
            'Example ISP d.o.o.',
            'Main street 7, 21300 Springfield',
            'EUR',
        );

        $this->assertSame(
            implode("\n", [
                'HRVHUB30', 'EUR', '000000000012355', '', '', '',
                'Example ISP d.o.o.', 'Main street 7', '21300 Springfield',
                'HR1210010051863000160', 'HR01', '126101264', '', 'Invoice 2026100231',
            ]),
            $codes->text($this->invoice('01 126101264', 123.55)),
        );
    }

    public function testTheHub3BarcodeTakesOnlyItsCharactersAndLengths(): void
    {
        $codes = new PaymentCodes(
            PaymentCodeFormat::Hub3,
            self::HR_IBAN,
            'Kovač & Sons, a company with a long name d.o.o.',
            'Unit 3, Žitna street 7, 21300 Springfield',
            'EUR',
        );

        $lines = explode("\n", (string)$codes->text($this->invoice('HR00 2026-100231', 10.0)));

        $this->assertSame('Kovač Sons, a company wit', $lines[6]);
        $this->assertSame('Unit 3, Žitna street 7', $lines[7]);
        $this->assertSame('21300 Springfield', $lines[8]);
        $this->assertSame(['HR00', '2026-100231'], [$lines[10], $lines[11]]);
    }

    public function testNoCodeForAPaidInvoiceOrAVariableSymbolTheFormatDoesNotTake(): void
    {
        $spayd = new PaymentCodes(PaymentCodeFormat::Spayd, self::CZ_IBAN, 'Example ISP', '', 'CZK');
        $hub3 = new PaymentCodes(PaymentCodeFormat::Hub3, self::HR_IBAN, 'Example ISP', '', 'EUR');
        $none = new PaymentCodes(null, '', 'Example ISP', '', 'EUR');

        $this->assertNull($spayd->text($this->invoice('110009', 0.0)));
        $this->assertNull($spayd->text($this->invoice('')));
        $this->assertNull($spayd->text($this->invoice('01 126101264')));
        $this->assertNull($hub3->text($this->invoice('126101264')));
        $this->assertNull($none->text($this->invoice('110009')));
        $this->assertNull($none->image($this->invoice('110009')));
    }

    public function testTheCodesAreImagesShownAtHalfTheirSize(): void
    {
        $qr = (new PaymentCodes(PaymentCodeFormat::Spayd, self::CZ_IBAN, 'Example ISP', '', 'CZK'))
            ->image($this->invoice('110009'));
        $pdf417 = (new PaymentCodes(PaymentCodeFormat::Hub3, self::HR_IBAN, 'Example ISP', '', 'EUR'))
            ->image($this->invoice('01 126101264'));

        foreach ([$qr, $pdf417] as $code) {
            $this->assertInstanceOf(PaymentCode::class, $code);
            $this->assertStringStartsWith('data:image/png;base64,', $code->src);
            $size = getimagesizefromstring((string)base64_decode(substr($code->src, 22), true));
            $this->assertIsArray($size);
            $this->assertSame([$code->width * 2, $code->height * 2], [$size[0], $size[1]]);
        }
        $this->assertSame($qr?->width, $qr?->height);
        $this->assertGreaterThan(2 * (int)$pdf417?->height, (int)$pdf417?->width);
    }

    public function testTheSettingsPickTheFormat(): void
    {
        Configure::write('Portal.payment', ['code' => 'hub3', 'iban' => self::HR_IBAN]);
        Configure::write('Portal.operator.address', 'Main street 7, 21300 Springfield');

        $codes = PaymentCodes::fromConfig();

        $this->assertSame(PaymentCodeFormat::Hub3, $codes->format);
        $this->assertSame(self::HR_IBAN, $codes->iban);
        $this->assertSame('Test Operator, s.r.o.', $codes->recipient);
        $this->assertSame('Main street 7, 21300 Springfield', $codes->address);
        $this->assertSame('EUR', $codes->currency);
    }

    public function testAnUnknownFormatStopsThePortal(): void
    {
        Configure::write('Portal.payment', ['code' => 'girocode', 'iban' => self::CZ_IBAN]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PAYMENT_CODE "girocode" is not known');

        PaymentCodes::fromConfig();
    }

    public function testAWrongIbanStopsThePortal(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PAYMENT_IBAN is not a valid IBAN');

        new PaymentCodes(PaymentCodeFormat::Spayd, 'CZ6608000000192000145399', 'Example ISP', '', 'CZK');
    }

    public function testHub3IsOnlyForEuro(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CURRENCY=EUR');

        new PaymentCodes(PaymentCodeFormat::Hub3, self::HR_IBAN, 'Example ISP', '', 'CZK');
    }

    /**
     * An invoice with what is left to pay of it.
     */
    private function invoice(string $variableSymbol, float $debt = 1490.0): Invoice
    {
        return new Invoice(
            '0b0f0b2c-6a36-4a3c-9f5e-9d1c5d6f1a01',
            '2026100231',
            $variableSymbol,
            '2026-10-01',
            '2026-10-15',
            '',
            'Internet 10/2026',
            1490.0,
            $debt,
        );
    }
}
