# Contributing

Thank you for your interest in this project. This is primarily a **portfolio / reference** repository, but corrections, tests, and documentation improvements are welcome.

## Before you start

1. Read [SECURITY.md](SECURITY.md) — do not include real ERP credentials, tenant URLs, or customer data in issues or PRs.
2. Search [existing issues](https://github.com/sameh-bakleh/laravel-ecommerce-erp-integration-api/issues) to avoid duplicates.
3. For large changes, open an issue first to discuss scope.

## Development setup

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
# Set DB_CONNECTION=sqlite and DB_DATABASE=database/database.sqlite in .env
composer install
php artisan migrate
```

Run tests:

```bash
composer test
```

Check code style:

```bash
vendor/bin/pint --test
```

## Pull request guidelines

- Keep PRs **focused** — one concern per PR when possible.
- Add or update **tests** for behaviour changes.
- Update **README** or `docs/` if endpoints, security, or setup steps change.
- Ensure CI passes (PHPUnit + Pint).
- Do not commit `.env`, `.env.docker`, `vendor/`, or generated cache files.

## Code conventions

- Follow existing patterns in `app/Integration/` (services, mappers, contracts).
- Controllers stay thin — dispatch jobs, return 202, no business logic.
- Use `final` classes and typed properties where consistent with surrounding code.
- German ERP field names belong in mappers, not scattered across services.

## Reporting bugs

Use the [bug report template](.github/ISSUE_TEMPLATE/bug_report.yml). Include PHP version, steps to reproduce, and expected vs actual behaviour.

## Suggesting features

Use the [feature request template](.github/ISSUE_TEMPLATE/feature_request.yml). Explain the integration problem, not only the solution.

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
