# Repository Guidelines

## Project Structure & Module Organization
- Plugin root `StructureImEx.php` wires LimeSurvey events and delegates into PSR-4 classes under `src/` (`export/`, `import/`, `validation/`, `views/`).
- Shared helpers such as `AppTrait.php` and `PersistentWarningManager.php` live beside feature directories; keep additions under the `tonisormisson\ls\structureimex` namespace.
- Test suites reside in `tests/unit` and `tests/functional`; bootstrap logic and fixtures live in `tests/bootstrap.php` and `tests/support/`.
- Reference specifications, attribute maps, and planning notes in `docs/` and `tasks/`; update these when altering spreadsheet formats or question metadata.

## Build, Test, and Development Commands
- `composer install` – install required PHP libraries after cloning or pulling.
- `composer install-dev` – include PHPStan and other dev tooling for local analysis.
- `composer test`, `composer test-unit`, `composer test-functional` – execute the full PHPUnit suite or targeted test groups defined by `phpunit.xml`.
- `composer test-setup` – run `bin/setup-test-db.sh` to provision a LimeSurvey-compatible test database.
- `vendor/bin/phpstan analyse -c phpstan.neon` – perform static analysis; switch to `phpstan-dev.neon` for stricter local checks.
- `XDEBUG_MODE=coverage composer test-coverage` – build HTML coverage in `coverage/` when validating persistence or import flows.

## Coding Style & Naming Conventions
- Follow PSR-12: 4-space indentation, opening braces on the next line, imports grouped alphabetically.
- Classes use `PascalCase`; methods, variables, and helper functions use `camelCase`; config keys and dataset names prefer `snake_case`.
- Prefer explicit data transfer arrays with documented keys; only catch specific exceptions and rethrow with repository-specific context.

## Testing Guidelines
- Mirror new code with nearby unit tests (`tests/unit/*Test.php`) and add functional coverage when touching database or LimeSurvey APIs (`tests/functional/*Test.php`).
- Name test classes `{Subject}Test` and data providers `{method}Provider`; keep fixtures under `tests/support/fixtures/`.
- Ensure `composer test` and PHPStan both pass before opening a pull request; document any new environment variables in `tests/bootstrap.php`.

## Commit & Pull Request Guidelines
- Use imperative, conventional titles (`feat: add quota import validation`, `fix: harden relevance parser`) and update `CHANGELOG.md` for user-facing changes.
- Pull requests should outline scope, link to GitHub issues or LimeSurvey tickets, list manual and automated test commands executed, and include screenshots or sample exports when UI or file formats change.

## Security & Configuration Tips
- Never commit `.env` or credential dumps; rely on `.env.example` for shared defaults.
- Reset the test database with `composer test-setup` after schema migrations, and review `config.xml` whenever adding survey settings to keep permission flags accurate.
