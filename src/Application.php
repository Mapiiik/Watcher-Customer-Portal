<?php
declare(strict_types=1);

namespace App;

use App\Crm\CustomerPortalApi;
use App\Crm\WatcherCrm;
use App\Middleware\SameOriginMiddleware;
use App\Portal\PaymentCodes;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\SessionCsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use RuntimeException;
use function Cake\Core\env;

/**
 * The customer portal: a customer signs in and sees their details, consents and invoices, all
 * asked of Watcher CRM.
 */
class Application extends BaseApplication
{
    /**
     * Loads the configuration, and stops when a setting the portal cannot do without is missing,
     * or when the payment codes would not pay the operator.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        parent::bootstrap();

        $missing = [];
        foreach ((array)Configure::read('Portal.required') as $name) {
            $value = env((string)$name);
            if (!is_string($value) || trim($value) === '') {
                $missing[] = (string)$name;
            }
        }
        if ($missing !== []) {
            throw new RuntimeException('The customer portal is not configured, set ' . implode(', ', $missing) . '.');
        }
        PaymentCodes::fromConfig();
    }

    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to set up.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())
            // a form is taken only from the portal's own pages: the browser says where it comes
            // from, and the form carries the token of the customer's session
            ->add(new SameOriginMiddleware())
            ->add(new SessionCsrfProtectionMiddleware());
    }

    /**
     * @param \Cake\Core\ContainerInterface $container The container to update.
     * @return void
     */
    public function services(ContainerInterface $container): void
    {
        $container->addShared(
            CustomerPortalApi::class,
            static fn(): CustomerPortalApi => WatcherCrm::fromConfig(
                (string)Configure::read('Portal.crm.url'),
                (string)Configure::read('Portal.crm.key'),
                (bool)Configure::read('Portal.crm.verifyTls'),
            ),
        );
    }
}
