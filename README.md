# Watcher Customer Portal

Customer portal of an internet service provider: a customer signs in and sees their details and
consents (and asks for changes), their invoices with the PDFs, and changes their password.
Everything comes from [Watcher CRM](https://github.com/Mapiiik/Watcher-CRM) over its customer
portal API. The portal keeps no data of its own and needs no database.

Runs on **FrankenPHP** behind HAProxy, like the other services of the family, and the image is
built by GitHub Actions.

---

## How it works

- The portal signs in to the CRM as a user of its own with the **customer-portal** role and its
  API key (`CRM_API_KEY`).
- A customer signs in with their login and password, which the CRM checks: in whatever form it is
  stored, storing it as Argon2id when due, and counting failed attempts by login and address.
  The CRM's token for the customer is kept in the session, and every answer is limited to them.
- Changes of the customer's details are not written anywhere, they are sent by mail
  (`CHANGE_REQUESTS_TO`) to be checked and entered. Only the consents are saved directly.

## The operator's profile

Everything that belongs to the operator rather than to the portal is in a **profile**, a directory
the deployment mounts at `/app/profile` (`$DATA_DIR/profile` in the compose files). Without one the
portal uses `profiles/default`, which is neutral and takes the operator's name from `OPERATOR_NAME`.

| File | What it is |
|---|---|
| `profile.php` | an array with what differs from `profiles/default/profile.php`: page name, heading, logos, colours, icons (favicon, home screen icon), the name of a downloaded invoice |
| `consent.php` | what the customer is told about the consents on the customer data page |
| `introduction.php` | the page after signing in |
| `assets/` | the logos, icons and `colors.css` the profile names, served by `branding.php` |

`colors.css` sets the two brand colours:

```css
:root {
  --brand-dark: #000C1A;
  --brand-accent: #46b130;
}
```

A file the profile leaves out is taken from `profiles/default`.

## Languages

The texts in the code are English and go through `__()`, translated as in Watcher CRM from
`resources/locales/<locale>/default.po` (`cs_CZ` and `hr_HR` so far). `APP_DEFAULT_LOCALE` picks
the language, and with `CURRENCY` also how dates and amounts are written. After changing texts in
the code, `php bin/extract-texts.php` brings `default.pot` and the catalogues up to date and lists
what is left to translate, in Poedit or any editor of `.po` files. A new language is a new
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

Sessions are kept in the `sessions` volume, so an update of the container signs nobody out.

## Development

`tests/run.sh` builds the image and tries it against the development Watcher CRM, with the default
profile and with every profile in `data/*/profile`: lint, undefined constants, attacks on signing
in, every page with all errors shown, sending the customer data form. It needs the Watcher CRM
development containers running and puts its test login into their database for the time it runs.

```bash
tests/run.sh
```

## License

[GNU Affero General Public License v3.0](LICENSE.md). The Jost font in `public/fonts` is under the
SIL Open Font License (`public/fonts/OFL.txt`).
