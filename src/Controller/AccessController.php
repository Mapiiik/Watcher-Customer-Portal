<?php
declare(strict_types=1);

namespace App\Controller;

use App\Crm\AccessRequestStatus;
use App\Crm\CustomerPortalApi;
use App\Portal\Operator;
use App\Portal\SignedIn;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;
use Throwable;

/**
 * A customer who forgot their password, or has never had a login, gets one by e-mail.
 *
 * They type their customer number and an e-mail of theirs, and a link to set a password is sent
 * to that e-mail when the two belong together. The page says the same whether they do or not, so
 * that nobody learns from it whose number or e-mail it is; only the owner of the mailbox does.
 */
class AccessController extends AppController
{
    /**
     * @inheritDoc
     */
    protected array $open = ['index', 'setPassword'];

    /**
     * Asks for a link by the customer number and an e-mail.
     *
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return \Cake\Http\Response|null
     */
    public function index(CustomerPortalApi $crm): ?Response
    {
        if ($this->customer !== null) {
            return $this->redirect('/');
        }

        $number = trim((string)$this->getRequest()->getData('customer_number'));
        $email = trim((string)$this->getRequest()->getData('email'));
        if ($this->getRequest()->is('post')) {
            if ($number === '' || $email === '') {
                $this->Flash->error(__('Fill in your customer number and e-mail.'));
            } else {
                $answer = $crm->requestAccess($number, $email, $this->getRequest()->clientIp());
                if ($answer->status === AccessRequestStatus::Blocked) {
                    $this->Flash->error(__('Too many requests, please try again in an hour.'));
                } else {
                    if ($answer->status === AccessRequestStatus::Found) {
                        $this->sendLink($answer->email, $answer->customerNumber, $answer->token);
                    } elseif ($answer->status === AccessRequestStatus::Locked) {
                        $this->mail('access_locked', $answer->email, __('Your access to the customer portal'), [
                            'customerNumber' => $answer->customerNumber,
                        ]);
                    }
                    $this->Flash->success(__(
                        'If the customer number and the e-mail belong together, we have sent a link to the '
                        . 'e-mail. It works for an hour.',
                    ));

                    return $this->redirect(['controller' => 'SignIn', 'action' => 'index']);
                }
            }
        }

        $this->set(compact('number', 'email'));
        $this->set('title', __('Forgotten password or new access'));

        return null;
    }

    /**
     * Sets a password with the link: of the login chosen, or of a new login, and signs in with it.
     *
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return \Cake\Http\Response|null
     */
    public function setPassword(CustomerPortalApi $crm): ?Response
    {
        $token = (string)$this->getRequest()->getQuery('token');
        $offer = $token === '' ? null : $crm->access($token);
        if ($offer === null) {
            $this->Flash->error(__('The link no longer works. Ask for a new one.'));

            return $this->redirect(['action' => 'index']);
        }

        if ($this->getRequest()->is('post')) {
            $password = (string)$this->getRequest()->getData('password');
            $loginId = (string)$this->getRequest()->getData('login_id');
            if ($password !== (string)$this->getRequest()->getData('password_again')) {
                $this->Flash->error(__('The new passwords do not match.'));
            } elseif (mb_strlen($password) < $offer->passwordMinLength) {
                $this->Flash->error(
                    __('The new password has to be at least {0} characters long.', $offer->passwordMinLength),
                );
            } else {
                $ip = $this->getRequest()->clientIp();
                $set = $crm->setAccess($token, $loginId, $password, $ip);
                if ($set->minLength > 0) {
                    $this->Flash->error(
                        __('The new password has to be at least {0} characters long.', $set->minLength),
                    );
                } elseif (!$set->isSaved()) {
                    $this->Flash->error(__('The link no longer works. Ask for a new one.'));

                    return $this->redirect(['action' => 'index']);
                } else {
                    $this->mail('access_set', $offer->email, __('Your access to the customer portal'), [
                        'customerNumber' => $offer->customerNumber,
                        'login' => $set->login,
                        'made' => $offer->logins === [],
                        'ip' => $ip,
                    ]);

                    return $this->signInAs($crm, $set->login, $password);
                }
            }
        }

        $this->set(compact('offer', 'token'));
        $this->set('title', __('Set a password'));

        return null;
    }

    /**
     * Signs in with the password just set, or sends to signing in when that fails.
     *
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @param string $login The login.
     * @param string $password Its password.
     * @return \Cake\Http\Response|null
     */
    private function signInAs(CustomerPortalApi $crm, string $login, string $password): ?Response
    {
        $ip = $this->getRequest()->clientIp();
        $answer = $crm->signIn($login, $password, $ip);
        if (!$answer->granted()) {
            $this->Flash->success(__('Your password has been set. Sign in with the login {0}.', $login));

            return $this->redirect(['controller' => 'SignIn', 'action' => 'index']);
        }

        SignedIn::fromAnswer($answer, $ip)->store($this->getRequest()->getSession());
        $this->Flash->success(__('Your password has been set and you are signed in. Your login is {0}.', $login));

        return $this->redirect('/');
    }

    /**
     * Sends the link to set a password, built on the portal's own address.
     *
     * @param string $to The e-mail typed.
     * @param string $customerNumber The customer's number.
     * @param string $token The link's token.
     * @return void
     */
    private function sendLink(string $to, string $customerNumber, string $token): void
    {
        // the address of a request is whatever site it names, so only in development
        $base = (string)Configure::read('App.fullBaseUrl');
        if ($base === '' && Configure::read('debug')) {
            $base = Router::fullBaseUrl();
        }
        if ($base === '') {
            Log::error('Customer portal: no link to set a password was sent, APP_FULL_BASE_URL is not set.');

            return;
        }

        $this->mail('access_link', $to, __('Your access to the customer portal'), [
            'customerNumber' => $customerNumber,
            'link' => rtrim($base, '/') . Router::url(
                ['controller' => 'Access', 'action' => 'setPassword', '?' => ['token' => $token]],
            ),
        ]);
    }

    /**
     * Sends a mail to the customer. A mail that cannot be sent is only logged, as the page must not
     * tell whether there was one to send.
     *
     * @param string $template The template of the mail.
     * @param string $to The e-mail.
     * @param string $subject The subject.
     * @param array<string, mixed> $vars What the template shows.
     * @return void
     */
    private function mail(string $template, string $to, string $subject, array $vars): void
    {
        try {
            $mailer = new Mailer('default');
            $mailer
                ->setTo($to)
                ->setSubject($subject)
                ->setEmailFormat('text')
                ->setViewVars($vars + ['operator' => Operator::fromConfig()])
                ->viewBuilder()
                ->setTemplate($template);
            $mailer->deliver();
        } catch (Throwable $error) {
            Log::error(sprintf('Customer portal: the %s mail could not be sent: %s', $template, $error->getMessage()));
        }
    }
}
