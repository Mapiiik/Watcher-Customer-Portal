<?php
declare(strict_types=1);

namespace App\Controller;

use App\Portal\SignedIn;
use Cake\Event\EventInterface;
use Override;

/**
 * Renders the error pages, signed in or not. A signed-in customer keeps the menu and the way to
 * sign out.
 */
class ErrorController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The event.
     * @return void
     */
    #[Override]
    public function beforeFilter(EventInterface $event): void
    {
        $this->customer = SignedIn::fromSession($this->getRequest()->getSession(), $this->getRequest()->clientIp());
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The event.
     * @return void
     */
    #[Override]
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);
        $this->viewBuilder()->setTemplatePath('Error');
    }
}
