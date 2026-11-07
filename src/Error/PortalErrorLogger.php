<?php
declare(strict_types=1);

namespace App\Error;

use Cake\Error\ErrorLogger;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Logs errors without the query of the request: on the page of a link it carries a token that
 * still works.
 */
class PortalErrorLogger extends ErrorLogger
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function getRequestContext(ServerRequestInterface $request): string
    {
        return (string)preg_replace('/\?\S*/', '', parent::getRequestContext($request));
    }
}
