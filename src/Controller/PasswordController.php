<?php
declare(strict_types=1);

namespace App\Controller;

use App\Crm\CustomerPortalApi;
use App\Crm\SignInStatus;
use Cake\Http\Response;

/**
 * Changing the password. The current one is checked by signing in again, so the CRM counts a
 * wrong one like any failed sign-in.
 */
class PasswordController extends AppController
{
    /**
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return \Cake\Http\Response|null
     */
    public function index(CustomerPortalApi $crm): ?Response
    {
        $customer = $this->customer();

        if ($this->getRequest()->is('post')) {
            $current = (string)$this->getRequest()->getData('current_password');
            $new = (string)$this->getRequest()->getData('new_password');
            $confirmation = (string)$this->getRequest()->getData('new_password_again');

            if ($new !== $confirmation) {
                $this->Flash->error(__('The new passwords do not match.'));
            } elseif (mb_strlen($new) < $customer->passwordMinLength) {
                $this->Flash->error(
                    __('The new password has to be at least {0} characters long.', $customer->passwordMinLength),
                );
            } else {
                $answer = $crm->signIn($customer->login, $current, $this->getRequest()->clientIp());
                if (!$answer->granted()) {
                    $this->Flash->error($answer->status === SignInStatus::Blocked
                        ? __('Too many failed attempts, please try again in a while')
                        : __('The current password is not right.'));
                } else {
                    $customer = $customer->withToken($answer->token, $this->getRequest()->getSession());
                    $minLength = $crm->setPassword($customer->token, $new);
                    if ($minLength === null) {
                        $this->Flash->success(__('Your password has been changed.'));

                        return $this->redirect(['controller' => 'Overview', 'action' => 'index']);
                    }
                    $this->Flash->error(__('The new password has to be at least {0} characters long.', $minLength));
                }
            }
        }

        $this->set([
            'minLength' => $customer->passwordMinLength,
            'title' => __('Change password'),
        ]);

        return null;
    }
}
