# AGENTS.md

Guidelines for AI agents working in this repository.

## Commands

All commands are executed via `make`. Do not run `php`, `composer`, `phpunit`, etc. directly.

```bash
make <target> [VARIABLE=value ...]
```

### Variables

| Variable | Description |
|---|---|
| `NO_TTY=1` | **Always pass this** — there is no interactive terminal |
| `ARGS="..."` | Extra arguments passed to the underlying tool |
| `PHP_VERSION=8.3` | Override PHP version (default: 8.4) |

### Targets

| Target | Description |
|---|---|
| `make test` | Run PHPUnit tests (alias for `make phpunit`) |
| `make phpunit` | Run PHPUnit tests |
| `make coverage` | Generate HTML coverage report to `runtime/coverage/` |
| `make infection` | Run mutation testing with Infection (alias: `make mutation`) |
| `make psalm` | Run Psalm static analysis |
| `make php-cs-fixer` | Fix code style with PHP-CS-Fixer (alias: `make cs-fix`) |
| `make composer` | Run Composer |
