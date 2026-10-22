# M Talent

MTalent is a technology talent marketplace. The application uses Laravel, PHP 8.3+, MySQL, Blade, Tailwind, Vite, Alpine, and Pest.

## Local setup

```sh
composer setup
```

The setup command installs dependencies, creates the local environment, migrates the database, seeds role and permission reference data, and builds frontend assets. Configure the database, mail transport, and production session/cache/queue services through environment configuration before deployment.

Run the test suite with `composer test`. Format PHP with `vendor/bin/pint`.

## Initial administrator

Create and verify an account through the registration flow, then grant initial administrative access from a trusted deployment shell:

```sh
php artisan identity:grant-super-admin verified.account@example.com
```

The command requires an existing active, email-verified account and asks for confirmation. No administrator password is seeded.

## Architecture

Read [AGENTS.md](AGENTS.md) and the project documents under [docs](docs/) before making changes. Authentication and authorization decisions are recorded in [the architecture document](docs/architecture.md) and [security guidance](docs/security.md).
