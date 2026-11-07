<?php
declare(strict_types=1);

namespace App\Error;

use App\Crm\SignedOut;
use App\Portal\SignedIn;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Response;

/**
 * The error pages, and the way back to signing in when the CRM no longer takes the customer's
 * token.
 */
class PortalExceptionRenderer extends WebExceptionRenderer
{
    /**
     * Where a form goes back to from an address that takes nothing but a form.
     *
     * @var array<string, string>
     */
    private const SENT_FROM = [
        '/sign-out' => '/sign-in',
        '/details/consents' => '/details',
        '/details/change/send' => '/details',
    ];

    /**
     * The customer's sign-in expired, or their login was locked meanwhile: they sign in again.
     *
     * @param \App\Crm\SignedOut $exception The exception.
     * @return \Cake\Http\Response
     */
    public function signedOut(SignedOut $exception): Response
    {
        if ($this->request !== null) {
            SignedIn::forget($this->request->getSession());
            $this->request->getFlash()->error(__('Your sign-in has expired, please sign in again.'));
        }

        return (new Response())->withLocation('/sign-in')->withStatus(303);
    }

    /**
     * A form sent after its session expired: nothing of it is taken, and the customer goes back to
     * its page instead of an error. A page that asks for signing in comes back after it.
     *
     * @param \Cake\Http\Exception\InvalidCsrfTokenException $exception The exception.
     * @return \Cake\Http\Response
     */
    public function invalidCsrfToken(InvalidCsrfTokenException $exception): Response
    {
        $page = '/sign-in';
        if ($this->request !== null) {
            $this->request->getFlash()->error(__('The page was open for too long, please send the form again.'));
            $target = $this->request->getRequestTarget();
            if (isset(self::SENT_FROM[$this->request->getPath()])) {
                $page = self::SENT_FROM[$this->request->getPath()];
            } elseif (preg_match('#^/(?![/\\\\])#', $target) === 1) {
                $page = $target;
            }
        }

        return (new Response())->withLocation($page)->withStatus(303);
    }
}
