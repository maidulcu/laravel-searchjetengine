# Contributing

Thanks for helping improve SearchJet for Laravel.

## Reporting bugs

Open a [GitHub issue](https://github.com/maidulcu/laravel-searchjetengine/issues) with:

- the package, PHP and Laravel versions;
- the code you ran and what you expected;
- the exception or response you got (remove API keys first).

Security issues go by email instead: see [SECURITY.md](SECURITY.md).

## Pull requests

1. Fork the repository and create a branch from `main`.
2. Install dependencies: `composer install`.
3. Make your change and add tests. Tests use `Http::fake()` and never call the real API.
4. Run `composer test` and `composer format` before pushing.
5. Add a line to the `Unreleased` section of [CHANGELOG.md](CHANGELOG.md).

Only add methods for endpoints the SearchJet API actually provides. If you are unsure whether an endpoint exists, open an issue first.

## Versioning

The package follows [Semantic Versioning](https://semver.org).
