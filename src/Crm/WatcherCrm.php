<?php
declare(strict_types=1);

namespace App\Crm;

use Cake\Http\Client;
use Cake\Http\Client\Response;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Watcher CRM, asked over its customer portal API. The portal signs in as a user of its own with
 * the customer-portal role (CRM_API_KEY); the token a customer gets on signing in limits every
 * other answer to them.
 */
final readonly class WatcherCrm implements CustomerPortalApi
{
    public const TOKEN_HEADER = 'X-Customer-Token';

    /**
     * @param \Cake\Http\Client $http The client the questions go through.
     * @param string $url The customer portal API, without a slash at the end.
     * @param string $key The portal's API key.
     */
    public function __construct(
        private Client $http,
        private string $url,
        private string $key,
    ) {
    }

    /**
     * @param string $url The customer portal API.
     * @param string $key The portal's API key.
     * @param bool $verifyTls Whether the certificate has to be valid; off only against development servers.
     */
    public static function fromConfig(string $url, string $key, bool $verifyTls): self
    {
        return new self(new Client([
            'timeout' => 20,
            'redirect' => 0,
            'ssl_verify_peer' => $verifyTls,
            'ssl_verify_peer_name' => $verifyTls,
        ]), rtrim($url, '/'), $key);
    }

    /**
     * @inheritDoc
     */
    public function signIn(string $login, string $password, string $ip): SignInAnswer
    {
        $form = ['login' => $login, 'password' => $password, 'ip' => $ip];
        $answer = $this->json($this->send('post', '/login', null, $form));
        $status = SignInStatus::tryFrom($answer->text('status'))
            ?? throw new CrmUnavailable('Watcher CRM answered signing in with an unknown status.');
        if ($status !== SignInStatus::Granted) {
            return SignInAnswer::refused($status);
        }

        $loginAnswer = $answer->object('login');
        $customer = $answer->object('customer');

        return new SignInAnswer(
            $status,
            $answer->text('token'),
            $loginAnswer->text('id'),
            $loginAnswer->text('login'),
            $customer->text('number'),
            $customer->text('name'),
            $answer->int('password_min_length') > 0 ? $answer->int('password_min_length') : 8,
        );
    }

    /**
     * @inheritDoc
     */
    public function customer(string $token): CustomerDetails
    {
        return CustomerDetails::fromAnswer($this->json($this->send('get', '/customer', $token)));
    }

    /**
     * @inheritDoc
     */
    public function saveConsents(string $token, Consents $consents): void
    {
        $this->json($this->send('post', '/consents', $token, $consents->toForm()));
    }

    /**
     * @inheritDoc
     */
    public function setPassword(string $token, string $password): ?int
    {
        $response = $this->send('post', '/password', $token, ['password' => $password]);
        if ($response->getStatusCode() === 422) {
            $minLength = (new Fields((array)$response->getJson()))->int('min_length');

            return max(1, $minLength);
        }
        $this->json($response);

        return null;
    }

    /**
     * @inheritDoc
     */
    public function invoices(string $token): array
    {
        $answer = $this->json($this->send('get', '/invoices', $token));

        return array_map(Invoice::fromFields(...), $answer->list('invoices'));
    }

    /**
     * @inheritDoc
     */
    public function invoicePdf(string $token, string $id): ?string
    {
        $response = $this->send('get', '/invoices/' . rawurlencode($id) . '/download', $token, null, 'application/pdf');
        if ($response->getStatusCode() === 404) {
            return null;
        }
        $this->accept($response);

        return $response->getStringBody();
    }

    /**
     * @param string $method get or post.
     * @param string $path Under the API.
     * @param string|null $token The customer's token.
     * @param array<string, mixed>|null $data The form values of a post.
     * @param string $accept What the answer should be.
     */
    private function send(
        string $method,
        string $path,
        ?string $token,
        ?array $data = null,
        string $accept = 'application/json',
    ): Response {
        $headers = ['Authorization' => 'Bearer ' . $this->key, 'Accept' => $accept];
        if ($token !== null) {
            $headers[self::TOKEN_HEADER] = $token;
        }

        try {
            return $method === 'post'
                ? $this->http->post($this->url . $path, $data ?? [], ['headers' => $headers])
                : $this->http->get($this->url . $path, [], ['headers' => $headers]);
        } catch (ClientExceptionInterface $error) {
            throw new CrmUnavailable(sprintf(
                'Watcher CRM did not answer %s %s: %s',
                strtoupper($method),
                $path,
                $error->getMessage(),
            ), $error);
        }
    }

    /**
     * The answer as a JSON object.
     *
     * @param \Cake\Http\Client\Response $response The answer.
     * @return \App\Crm\Fields
     * @throws \App\Crm\CrmUnavailable When it is not one.
     */
    private function json(Response $response): Fields
    {
        $this->accept($response);
        $data = $response->getJson();
        if (!is_array($data)) {
            throw new CrmUnavailable('Watcher CRM answered with something other than JSON.');
        }

        return new Fields($data);
    }

    /**
     * @throws \App\Crm\SignedOut When the CRM no longer takes the customer's token.
     * @throws \App\Crm\CrmUnavailable When it answered with anything but success.
     */
    private function accept(Response $response): void
    {
        $status = $response->getStatusCode();
        if ($status === 401) {
            throw new SignedOut('Watcher CRM no longer takes the customer token.');
        }
        if ($status !== 200) {
            // a refused key or role is answered with a redirect to the sign-in page
            throw new CrmUnavailable(sprintf(
                'Watcher CRM answered with HTTP %d%s.',
                $status,
                in_array($status, [302, 403], true) ? ', check CRM_API_KEY and its role' : '',
            ));
        }
    }
}
