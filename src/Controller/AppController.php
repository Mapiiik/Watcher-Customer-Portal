<?php
declare(strict_types=1);

namespace App\Controller;

use App\Portal\Operator;
use App\Portal\Profile;
use App\Portal\SignedIn;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Override;

/**
 * Every page but signing in is for a signed-in customer. A page asked for without signing in
 * sends to signing in, and back to it afterwards.
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class AppController extends Controller
{
    /**
     * The actions open without signing in.
     *
     * @var list<string>
     */
    protected array $open = [];

    protected ?SignedIn $customer = null;

    protected Operator $operator;

    protected Profile $profile;

    /**
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Flash');
        $this->operator = Operator::fromConfig();
        $this->profile = Profile::fromConfig($this->operator->name);
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The event.
     * @return void
     */
    #[Override]
    public function beforeFilter(EventInterface $event): void
    {
        $this->customer = SignedIn::fromSession($this->getRequest()->getSession(), $this->getRequest()->clientIp());
        if ($this->customer !== null || in_array($this->getRequest()->getParam('action'), $this->open, true)) {
            return;
        }

        if ($this->getRequest()->is('get')) {
            $this->getRequest()->getSession()->write('Portal.returnTo', $this->getRequest()->getRequestTarget());
        }
        $event->setResult($this->redirect(['controller' => 'SignIn', 'action' => 'index']));
        $event->stopPropagation();
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The event.
     * @return void
     */
    #[Override]
    public function beforeRender(EventInterface $event): void
    {
        // a page is about the customer who asks, so nothing on the way may keep it
        $this->setResponse($this->getResponse()->withHeader('Cache-Control', 'no-store'));
        $this->set([
            'operator' => $this->operator,
            'profile' => $this->profile,
            'customer' => $this->customer,
        ]);
    }

    /**
     * The signed-in customer, for the actions that run only for one.
     */
    protected function customer(): SignedIn
    {
        assert($this->customer !== null);

        return $this->customer;
    }
}
