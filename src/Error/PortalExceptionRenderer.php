<?php
declare(strict_types=1);

namespace App\Error;

use App\Crm\SignedOut;
use App\Portal\SignedIn;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Http\Response;

/**
 * The error pages, and the way back to signing in when the CRM no longer takes the customer's
 * token.
 */
class PortalExceptionRenderer extends WebExceptionRenderer
{
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
}
