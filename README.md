# Watcher Customer Portal

Customer portal of an internet service provider: a customer signs in and sees what is left to pay,
their invoices with the PDFs, their details, contracts and services, gives or withdraws their consents,
asks for a change of their details and changes their password. Everything comes from
[Watcher CRM](https://github.com/Mapiiik/Watcher-CRM) over its customer portal API. The portal
keeps no data of its own and needs no database.

A [CakePHP](https://cakephp.org) 5 application, like Watcher CRM and Watcher NMS, running on
**FrankenPHP** behind HAProxy like the other services of the family. The image is built by GitHub
Actions.

---

## How it works

- The portal signs in to the CRM as a user of its own with the **customer-portal** role and its
  API key (`CRM_API_KEY`).
- A customer signs in with their login and password, which the CRM checks: in whatever form it is
  stored, storing it as Argon2id when due, and counting failed attempts by login and address.
  The CRM's token for the customer is kept in the session, and every answer is limited to them.
- The consents are saved straight away. A change of the customer's details is not written
  anywhere: it goes by mail (`CHANGE_REQUESTS_TO`) to be checked and entered.
- Changing the password asks for the current one, which the CRM checks like a sign-in, and the
  new one has to be as long as the CRM's settings ask.
- A customer who forgot their password, or has no login yet, types their customer number and an
  e-mail of theirs. Where the two belong together, the portal mails a link that works an hour and
  once, to set the password of a login or to get one made; where every login is locked, the mail
  says to contact the operator. The page says the same either way. The link is built on
  `APP_FULL_BASE_URL`, without which the portal does not run outside `PORTAL_DEBUG`, as a request
  can name any site in its `Host` header.
- A form is taken only from the portal's own pages (the session's CSRF token and the browser's
  `Origin` and `Sec-Fetch-Site`), the session cookie is `__Host-portal`, a customer is signed out
  after `SESSION_IDLE_MINUTES` without a request, and the pages carry a content security policy
  that loads nothing from elsewhere and runs no inline scripts or styles.
- With `PAYMENT_CODE`, every unpaid invoice on the overview has a code to scan in a bank's app:
  QR Platba (`spayd`) or the barcode of Croatian payment slips (`hub3`). It pays what is left of
  that invoice under its variable symbol to `PAYMENT_IBAN`, with no due date, so at once.

## The operator's profile

Everything that belongs to the operator rather than to the portal is in a **profile**, a directory
the deployment mounts at `/app/profile` (`$DATA_DIR/profile` in the compose files), and in the
environment (`.env.example`). Without a profile the portal uses `profiles/default`, which is
neutral and takes the operator's name from `OPERATOR_NAME`.

| File | What it is |
|---|---|
| `profile.php` | an array with what differs from `profiles/default/profile.php`: the name in the window title, the heading, the logo (for a dark background), the colours, the icons (favicon, home screen icon), the name of a downloaded invoice |
| `consent.php` | what the customer is told about the consents on the details page |
| `assets/` | the logo, icons and `colors.css` the profile names, served at `/branding/<file>` |

The pages look like the operator's website: its header with the contacts above the menu, square
buttons and the Jost font, of the same sizes. `colors.css` sets the colours, and how tall the logo
is, which is best as tall as the website's, with a logo cut to its drawing:

```css
:root {
  --brand-dark: #000b23;    /* the header and the footer */
  --brand-accent: #46b130;  /* links, buttons, the active item of the menu */
  --brand-topbar: #000b23;  /* the bar with the contacts, the header's colour when left out */
  --brand-hover: #46b130;   /* links and buttons under the pointer */
  --logo-height: 94px;      /* the logo in the header, 94px when left out */
}
```

A file the profile leaves out is taken from `profiles/default`.

## Languages

The texts in the code are English and go through `__()`, translated as in Watcher CRM from
`resources/locales/<locale>/default.po` (`cs_CZ` and `hr_HR` so far). `APP_DEFAULT_LOCALE` picks
the language, and with `CURRENCY` also how dates and amounts are written. After changing texts in
the code, `composer texts` brings `default.pot` up to date; merge it into the catalogues with
Poedit, or `msgmerge`, and translate what is new. A new language is a new
`resources/locales/<locale>/default.po`.

## Running it

1. In Watcher CRM, create a user with the **customer-portal** role and give it an API key.
2. Copy `.env.example` to `.env` and fill it in. `.env` stays out of git.
3. Put the operator's profile in `$DATA_DIR/profile`, or leave it out for the default.
4. Copy `compose.production.yaml` to `compose.yaml` and start it:

```bash
docker compose up -d
```

Behind HAProxy (TLS passed through by SNI, `send-proxy-v2`), set `LISTENER_MODE=behind-proxy` and
`PROXY_ALLOW` to the proxy's network. The CRM counts failed sign-ins by the customer's address,
so the portal has to see the real one.

Only on an internal network, for a test before DNS points at the server or for good, set
`LISTENER_MODE=internal`: Caddy issues the certificate from its own CA and `SERVER_NAME` can be the
server's IP address. Browsers warn until they trust its root certificate,
`/data/caddy/pki/authorities/local/root.crt` in the container (on Windows
`certutil -addstore -user Root root.crt`).

Sessions are kept in the `sessions` volume, so an update of the container signs nobody out. A
customer is signed out after `SESSION_IDLE_MINUTES` (30) without a request, and when the CRM's
token for them expires.

## Development

The layout is that of a CakePHP application: `src/` (controllers, the CRM client in `src/Crm`, the
portal's own classes in `src/Portal`), `templates/`, `config/`, `webroot/` with the portal's
stylesheet in `css/` and the font in `vendor/jost/`, nothing loaded from elsewhere.

As in Watcher CRM, the development container is `compose.development.yaml` copied to
`compose.yaml`, which stays out of git and is adjusted as needed (ports, the CRM's address, the
language). It serves the working tree on https://localhost with the Caddy configuration of the
production image, sends mail to
Mailpit on http://localhost:8025, and reaches the development CRM through the port it publishes
on the host, `CRM_DEV_ADDRESS`. Signing in needs `CRM_API_KEY` in `.env`, the key of a CRM user
with the **customer-portal** role. The translations are cached in `tmp/`: after changing a
catalogue, `bin/cake cache clear_all` or a restart.

```bash
docker compose up -d
docker compose exec -T webserver-dev composer check   # PHPUnit, PHPCS and PHPStan, as CI runs them
docker compose exec -T webserver-dev composer texts   # default.pot from the texts in the code
tests/run.sh          # the production image against the development Watcher CRM
```

The unit and integration tests run against a fake CRM. `tests/run.sh` builds the image and tries
it against the development Watcher CRM, with the default profile and with every profile in
`data/*/profile`: signing in and out, attacks on the forms, every page with all errors shown, the
consents, a change request caught by Mailpit, the password and the addresses of the old portal.
It needs the Watcher CRM development containers running and puts its test login into their
database for the time it runs.

## License

[GNU Affero General Public License v3.0](LICENSE.md). The Jost font in `webroot/vendor/jost` is
under the SIL Open Font License (`webroot/vendor/jost/OFL.txt`).
