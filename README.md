# machinima-app

Core of the Machinima platform — a Symfony application that works as a regular website and, at the same time, as a Telegram Mini App, without hard-coding Telegram anywhere in the core.

## Architecture

The core knows nothing about specific login platforms. Everything platform-specific (Telegram, or any other) lives in separate adapters, plugged in through contracts:

- **`App\Contract\PlatformAdapterInterface`** — a platform adapter: declares its own name (`getPlatformName()`), an optional JS module for zero-click bootstrap (`getBootstrapModulePath()`), an optional JS module for presentational UI hints (theme, back button — `getUiHintsModulePath()`), and the session's UI context (`getUiContext()`).
- **`App\Contract\IdentityProviderPort`** — an identity provider: validates an assertion (an OIDC id_token, a signed initData string, etc.) and returns an `IdentityAssertion`.
- **`App\Contract\BootstrapOnlyIdentityProvider`** — a marker for providers that are only ever used via zero-click bootstrap and must never appear as a button on `/login`.

Zero-click login (e.g. from a Telegram Mini App) goes through a single, generic `POST /api/auth/bootstrap` endpoint: the platform's bootstrap module detects its runtime in the browser on its own, builds an opaque `assertion`, and sends `{provider, assertion}` — with no custom HTTP headers or other platform-specific workarounds involved.

Currently the only real adapter is [`machinima-telegram-adapter`](https://github.com/ChernegaSergiy/machinima-telegram-adapter), pulled in as a separate composer package.

## Run profiles

The application supports several profiles (`config/profiles/`) that enable a different set of bundles and configuration via `APP_PROFILE`:

- **`core-only`** — core only, no platform adapter.
- **`telegram-webapp`** — core + Telegram Mini App adapter (zero-click login, UI hints).
- **`telegram-bot`** — core + Telegram bot (notifications, commands).

## Tech stack

- PHP 8.4+, Symfony 8.1
- Doctrine ORM + PostgreSQL
- Mercure (real-time notifications)
- Symfony Messenger
- Twig + Turbo (Hotwire) on the frontend

## Installation

```bash
composer install
```

Copy `.env.example` to `.env.local` and fill in the values:

```bash
cp .env.example .env.local
```

Main environment variables:

| Variable | Purpose |
|---|---|
| `APP_PROFILE` | `core-only` / `telegram-webapp` / `telegram-bot` |
| `DATABASE_URL` | PostgreSQL connection string |
| `TELEGRAM_BOT_TOKEN` | bot token (needed by the Telegram adapter and for verifying Mini App `initData`) |
| `TELEGRAM_DSN` | DSN for Symfony Notifier (Telegram notifications) |
| `MERCURE_URL` / `MERCURE_PUBLIC_URL` / `MERCURE_JWT_SECRET` | Mercure hub for real-time updates |
| `MESSENGER_TRANSPORT_DSN` | transport for asynchronous messages |

Bring up infrastructure (PostgreSQL, Mercure) via Docker Compose:

```bash
docker compose up -d
```

Database migrations:

```bash
php bin/console doctrine:migrations:migrate
```

Local run via Symfony CLI:

```bash
symfony server:start -d
```

## Project structure

```text
machinima-app/
+-- config/
|   \-- profiles/       # configuration per run profile
+-- migrations/         # Doctrine migrations
+-- src/
|   +-- Contract/       # platform-agnostic contracts (adapters, identity providers, UI context)
|   +-- Controller/     # web + API controllers
|   +-- Entity/         # Doctrine entities
|   +-- Event/          # domain events (e.g. UserAuthenticatedEvent)
|   +-- EventListener/  # event subscribers
|   +-- Security/       # authentication/authorization
|   +-- Service/        # adapter/provider registries and other business logic
|   +-- Twig/           # Twig extensions/runtime
|   \-- Kernel.php
\-- templates/          # Twig templates
```

## Contributing

Contributions are welcome and appreciated! Here's how you can contribute:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and adhere to the existing coding style.

## License

This project is licensed under the CSSM Unlimited License v2.0 (CSSM-ULv2). See the [LICENSE](LICENSE) file for details.
