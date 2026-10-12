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
     * The parts of the details a change is asked for one at a time, as the details page shows them;
     * other is a request in words only.
     */
    public const PARTS = ['customer', 'contacts', 'billing', 'installation', 'other'];

    /**
     * Where the session keeps a change while the customer checks it.
     */
    private const DRAFT = 'Portal.changeDraft';

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
     * The form for a change of one part of the details, as the details page shows them, and the
     * changes it makes for the customer to check before they are sent. Without a part it holds
     * them all.
     *
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return \Cake\Http\Response|null
     */
    public function change(CustomerPortalApi $crm): ?Response
    {
        $request = $this->getRequest();
        $details = $crm->customer($this->customer()->token);
        $part = self::part($request->is('post') ? $request->getData('part') : $request->getQuery('part'));

        if ($request->is('post')) {
            $form = array_diff_key((array)$request->getData(), array_flip(['_csrfToken', 'part']));
            $change = ChangeRequest::fromForm($details, $form);
            if ($change->isEmpty()) {
                $this->Flash->info(__('Nothing has changed, there is nothing to send.'));

                return $this->redirect(['action' => 'change', '?' => array_filter(['part' => $part])]);
            }
            $request->getSession()->write(self::DRAFT, ['part' => $part, 'form' => $form]);
            $this->set([
                'part' => $part,
                'change' => $change,
                'title' => __('Check your request'),
            ]);

            return $this->render('confirm');
        }

        // back from checking: the form as the customer left it
        $draft = $request->getQuery('edit') !== null ? $request->getSession()->read(self::DRAFT) : null;
        if (is_array($draft) && ($draft['part'] ?? null) === $part) {
            $this->setRequest($request->withParsedBody((array)($draft['form'] ?? [])));
        }

        $this->set([
            'details' => $details,
            'part' => $part,
            'title' => __('Request a change'),
        ]);

        return null;
    }

    /**
     * Sends the change the customer has checked, by mail, to be checked and entered.
     *
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return \Cake\Http\Response|null
     */
    public function send(CustomerPortalApi $crm): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        $session = $this->getRequest()->getSession();
        $draft = $session->read(self::DRAFT);
        if (!is_array($draft)) {
            return $this->redirect(['action' => 'index']);
        }

        $customer = $this->customer();
        $details = $crm->customer($customer->token);
        $change = ChangeRequest::fromForm($details, (array)($draft['form'] ?? []));
        if ($change->isEmpty()) {
            $session->delete(self::DRAFT);
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
                    'request' => $change,
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

            return $this->redirect([
                'action' => 'change',
                '?' => array_filter(['part' => $draft['part'] ?? '']) + ['edit' => 1],
            ]);
        }

        $session->delete(self::DRAFT);
        $this->Flash->success(
            __('Your details have been sent for processing. They change once they have been checked.'),
        );

        return $this->redirect(['action' => 'index']);
    }

    /**
     * One of PARTS, or empty for all of them.
     *
     * @param mixed $part As the request names it.
     * @return string
     */
    private static function part(mixed $part): string
    {
        return in_array($part, self::PARTS, true) ? $part : '';
    }
}
