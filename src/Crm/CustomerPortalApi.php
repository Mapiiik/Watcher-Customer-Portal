<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * What the portal asks Watcher CRM. Every question but signing in carries the customer's token,
 * and the CRM answers only about that customer.
 */
interface CustomerPortalApi
{
    /**
     * Signs a customer in with the login and password they typed.
     *
     * @param string $ip The customer's address, which the CRM counts failed attempts by.
     * @throws \App\Crm\CrmUnavailable
     */
    public function signIn(string $login, string $password, string $ip): SignInAnswer;

    /**
     * The customer's details, contacts, addresses and contracts.
     *
     * @throws \App\Crm\CrmUnavailable
     * @throws \App\Crm\SignedOut
     */
    public function customer(string $token): CustomerDetails;

    /**
     * @throws \App\Crm\CrmUnavailable
     * @throws \App\Crm\SignedOut
     */
    public function saveConsents(string $token, Consents $consents): void;

    /**
     * Sets a new password.
     *
     * @return int|null Null when it is saved, the shortest length allowed when it is too short.
     * @throws \App\Crm\CrmUnavailable
     * @throws \App\Crm\SignedOut
     */
    public function setPassword(string $token, string $password): ?int;

    /**
     * The customer's invoices, newest first.
     *
     * @return list<\App\Crm\Invoice>
     * @throws \App\Crm\CrmUnavailable
     * @throws \App\Crm\SignedOut
     */
    public function invoices(string $token): array;

    /**
     * The PDF of one of the customer's invoices, or null when there is no such invoice of theirs.
     *
     * @throws \App\Crm\CrmUnavailable
     * @throws \App\Crm\SignedOut
     */
    public function invoicePdf(string $token, string $id): ?string;

    /**
     * Asks for a link to set a password, by the customer number and an e-mail the customer typed.
     *
     * @param string $ip The customer's address, which the CRM counts requests by.
     * @throws \App\Crm\CrmUnavailable
     */
    public function requestAccess(string $customerNumber, string $email, string $ip): AccessRequestAnswer;

    /**
     * What a link to set a password lets the customer do, or null when it no longer works.
     *
     * @throws \App\Crm\CrmUnavailable
     */
    public function access(string $linkToken): ?AccessOffer;

    /**
     * Sets a password with a link: of the login chosen, or of a new one where there is none.
     *
     * @param string $loginId The login chosen, empty where there is one or none.
     * @throws \App\Crm\CrmUnavailable
     */
    public function setAccess(string $linkToken, string $loginId, string $password, string $ip): AccessSet;
}
