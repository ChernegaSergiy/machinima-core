<div align="center">

<img src="assets/images/morf-logo.svg" alt="Morf Editorial" width="320" />

# machinima-core

*Core CMS Bundle of the Machinima platform.*

[![Latest Stable Version](https://img.shields.io/packagist/v/morfeditorial/machinima-core.svg?label=Packagist&logo=packagist)](https://packagist.org/packages/morfeditorial/machinima-core)
[![Total Downloads](https://img.shields.io/packagist/dt/morfeditorial/machinima-core.svg?label=Downloads&logo=packagist)](https://packagist.org/packages/morfeditorial/machinima-core)
[![License](https://img.shields.io/packagist/l/morfeditorial/machinima-core.svg?label=Licence&logo=open-source-initiative)](https://packagist.org/packages/morfeditorial/machinima-core)

[Architecture](#architecture) · [Tech stack](#tech-stack) · [Installation](#installation) · [Contributing](#contributing)

---

</div>

This Symfony Bundle provides the core business logic, entities, services, and base UI templates for the Machinima platform. Its unique architecture ensures that the core system remains completely independent and entirely free of any hardcoded platform (like Telegram) dependencies.

## Architecture

The core knows nothing about specific login platforms. Everything platform-specific (Telegram, or any other) lives in separate adapters, plugged in through contracts:

- **`Morfeditorial\MachinimaCoreBundle\Contract\PlatformAdapterInterface`** — a platform adapter: declares its own name (`getPlatformName()`), an optional JS module for zero-click bootstrap (`getBootstrapModulePath()`), an optional JS module for presentational UI hints (theme, back button — `getUiHintsModulePath()`), and the session's UI context (`getUiContext()`).
- **`Morfeditorial\MachinimaCoreBundle\Contract\IdentityProviderPort`** — an identity provider: validates an assertion (an OIDC id_token, a signed initData string, etc.) and returns an `IdentityAssertion`.
- **`Morfeditorial\MachinimaCoreBundle\Contract\BootstrapOnlyIdentityProvider`** — a marker for providers that are only ever used via zero-click bootstrap and must never appear as a button on `/login`.

Zero-click login (e.g. from a Telegram Mini App) goes through a single, generic `POST /api/auth/bootstrap` endpoint: the platform's bootstrap module detects its runtime in the browser on its own, builds an opaque `assertion`, and sends `{provider, assertion}` — with no custom HTTP headers or other platform-specific workarounds involved.

Currently the only real adapter is [`machinima-telegram-adapter`](https://github.com/ChernegaSergiy/machinima-telegram-adapter), pulled in as a separate composer package in the host application.

## Tech stack

This bundle relies on the following core technologies to provide its features:

- **Backend**: PHP 8.4+, Symfony Components (Security, EventDispatcher, Twig)
- **Data Access**: Doctrine ORM
- **Frontend Base**: Twig, Turbo (Hotwire), Vanilla CSS (Utility-First), and Lucide Icons

## Installation

To install this bundle in your Symfony host application:

```bash
composer require morfeditorial/machinima-core
```

Ensure the bundle is registered in your `config/bundles.php`:

```php
return [
    // ...
    Morfeditorial\MachinimaCoreBundle\MachinimaCoreBundle::class => ['all' => true],
];
```

## Project structure

```text
machinima-core/
+-- config/                      # Bundle configuration (services, routes)
+-- src/
|   +-- Command/                 # CLI commands
|   +-- Contract/                # platform-agnostic contracts (adapters, identity providers, UI context)
|   +-- Controller/              # web + API controllers
|   +-- Entity/                  # Doctrine entities
|   +-- Event/                   # domain events (e.g. UserAuthenticatedEvent)
|   +-- EventListener/           # event subscribers
|   +-- Security/                # authentication/authorization
|   +-- Service/                 # adapter/provider registries and other business logic
|   +-- Twig/                    # Twig extensions/runtime
|   \-- MachinimaCoreBundle.php  # Main bundle class
+-- templates/                   # Twig templates
\-- Resources/
    \-- public/                  # Public CSS/JS assets
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
