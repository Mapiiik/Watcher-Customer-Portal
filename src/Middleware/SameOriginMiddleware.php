<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Refuses a form sent from another site, as the browser tells where a request comes from. It
 * stands beside the CSRF token, for browsers that say and in case a token ever leaks.
 */
class SameOriginMiddleware implements MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The rest of the application.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true) && $this->comesFromElsewhere($request)) {
            return (new Response())->withStatus(403)->withStringBody('');
        }

        return $handler->handle($request);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return bool
     */
    private function comesFromElsewhere(ServerRequestInterface $request): bool
    {
        $site = $request->getHeaderLine('Sec-Fetch-Site');
        $origin = $request->getHeaderLine('Origin');

        return !in_array($site, ['', 'same-origin', 'none'], true)
            || ($origin !== '' && $origin !== 'https://' . $request->getHeaderLine('Host'));
    }
}
