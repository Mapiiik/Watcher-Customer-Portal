<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Crm\Invoice;
use App\View\AppView;
use App\View\Helper\FormatHelper;
use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FormatHelper::class)]
class FormatHelperTest extends TestCase
{
    private FormatHelper $format;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->format = new FormatHelper(new AppView());
    }

    #[Override]
    protected function tearDown(): void
    {
        I18n::setLocale('en_US');
        Configure::write('Portal.currency', 'EUR');
        parent::tearDown();
    }

    public function testDatesAndAmountsAsTheLanguageWritesThem(): void
    {
        I18n::setLocale('cs_CZ');
        Configure::write('Portal.currency', 'CZK');

        $this->assertSame('1. 4. 1880', $this->format->date('1880-04-01'));
        $this->assertSame('–', $this->format->date(''));
        $this->assertSame("1\u{a0}490,00\u{a0}Kč", $this->format->money(1490.0));
        $this->assertSame('cs', $this->format->language());
    }

    public function testTheStateOfAnInvoice(): void
    {
        $invoice = static fn(float $debt): Invoice => new Invoice('i', '1', '1', '2026-10-01', '2026-10-15', '', '', 100.0, $debt);

        $this->assertSame(['Paid', 'ok'], $this->format->invoiceState($invoice(0.0), '2026-10-20'));
        $this->assertSame(['Due', 'due'], $this->format->invoiceState($invoice(100.0), '2026-10-15'));
        $this->assertSame(['Overdue', 'late'], $this->format->invoiceState($invoice(100.0), '2026-10-16'));
    }
}
