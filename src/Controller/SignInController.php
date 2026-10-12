<?php
declare(strict_types=1);

namespace App\Controller;

use App\Crm\CustomerPortalApi;
use App\Crm\SignInStatus;
use App\Portal\SignedIn;
use Cake\Http\Response;

/**
 * Signing in and out. The CRM checks the login and password, counts failed attempts by login
 * and address, and refuses more for a while.
 */
class SignInController extends AppController
{
    protected array $open = ['index', 'signOut'];

    /**
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return \Cake\Http\Response|null
     */
    public function index(CustomerPortalApi $crm): ?Response
    {
        if ($this->customer !== null) {
            return $this->redirect('/');
        }

        $login = '';
        if ($this->getRequest()->is('post')) {
            $login = trim((string)$this->getRequest()->getData('login'));
            $password = (string)$this->getRequest()->getData('password');
            if ($login === '' || $password === '') {
                $this->Flash->error(__('Fill in your login and password.'));
            } else {
                $answer = $crm->signIn($login, $password, $this->getRequest()->clientIp());
                if ($answer->granted()) {
                    SignedIn::fromAnswer($answer, $this->getRequest()->clientIp())
                        ->store($this->getRequest()->getSession());

                    return $this->redirect($this->returnTo());
                }
                $this->Flash->error(match ($answer->status) {
                    SignInStatus::Locked => __('Account locked'),
                    SignInStatus::Blocked => __('Too many failed attempts, please try again in a while'),
                    default => __('The login or the password is not right.'),
                });
            }
        }

        $this->set(compact('login'));
        $this->set('title', __('Sign in'));

        return null;
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function signOut(): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        SignedIn::forget($this->getRequest()->getSession());
        $this->Flash->success(__('You have signed out.'));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * The page that asked for signing in: a page of the portal, never another site.
     */
    private function returnTo(): string
    {
        $returnTo = $this->getRequest()->getSession()->consume('Portal.returnTo');

        return is_string($returnTo) && preg_match('#^/(?![/\\\\])#', $returnTo) ? $returnTo : '/';
    }
}
