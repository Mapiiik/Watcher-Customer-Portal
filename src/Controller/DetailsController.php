<?php
declare(strict_types=1);

namespace App\Controller;

use App\Crm\Consents;
use App\Crm\CustomerPortalApi;
use App\Portal\ChangeRequest;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Throwable;

/**
 * The customer's details and consents. The consents are saved straight away, a change of the
 * details is sent by mail to be checked and entered.
 */
class DetailsController extends AppController
{
    /**
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return void
     */
    public function index(CustomerPortalApi $crm): void
    {
        $this->set([
            'details' => $crm->customer($this->customer()->token),
            'title' => __('My details'),
        ]);
    }

    /**
     * Saves which messages the customer wants, which also confirms the consent to the processing
     * of personal data.
     *
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return \Cake\Http\Response|null
     */
    public function consents(CustomerPortalApi $crm): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        $wants = fn(string $field): bool => filter_var($this->getRequest()->getData($field), FILTER_VALIDATE_BOOLEAN);
        $crm->saveConsents($this->customer()->token, new Consents(
            $wants('agree_mailing_billing'),
            $wants('agree_mailing_outages'),
            $wants('agree_mailing_commercial'),
        ));
        $this->Flash->success(__('Your consents have been saved.'));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * The form for a change of the details, and sending it.
     *
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return \Cake\Http\Response|null
     */
    public function change(CustomerPortalApi $crm): ?Response
    {
        $customer = $this->customer();
        $details = $crm->customer($customer->token);

        if ($this->getRequest()->is('post')) {
            $request = ChangeRequest::fromForm($details, (array)$this->getRequest()->getData());
            if ($request->isEmpty()) {
                $this->Flash->info(__('Nothing has changed, there is nothing to send.'));

                return $this->redirect(['action' => 'index']);
            }

            try {
                $mailer = new Mailer('default');
                $mailer
                    ->setTo((array)Configure::read('Portal.changeRequestsTo'))
                    ->setSubject(__('Change of customer details: {0}', $details->number))
                    ->setEmailFormat('text')
                    ->setViewVars([
                        'details' => $details,
                        'customer' => $customer,
                        'request' => $request,
                        'ip' => $this->getRequest()->clientIp(),
                    ])
                    ->viewBuilder()
                    ->setTemplate('change_request');
                $replyTo = $details->emails[0]->email ?? '';
                if (filter_var($replyTo, FILTER_VALIDATE_EMAIL) !== false) {
                    $mailer->setReplyTo($replyTo);
                }
                $mailer->deliver();
            } catch (Throwable $error) {
                Log::error('Customer portal: the change request could not be sent: ' . $error->getMessage());
                $this->Flash->error(__('Your request could not be sent, please try again later.'));

                return null;
            }

            $this->Flash->success(
                __('Your details have been sent for processing. They change once they have been checked.'),
            );

            return $this->redirect(['action' => 'index']);
        }

        $this->set([
            'details' => $details,
            'title' => __('Request a change'),
        ]);

        return null;
    }
}
