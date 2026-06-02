# Security Policy

## Scope

**Jetstream Flux Livewire** is a fork of [Laravel Jetstream](https://github.com/laravel/jetstream). Security-sensitive behaviour (authentication, team authorization, API tokens, 2FA) is inherited from upstream Jetstream and Laravel Fortify.

## What this fork changes

Only **UI layer** files are modified:

- Blade views under `stubs/livewire/resources/views/`
- Frontend assets (`stubs/resources/css/app.css`, Vite config stubs)
- Installer dependencies (Livewire 4, Tailwind 4, Flux)

**Not modified for custom logic:**

- `src/Http/Livewire/` — Livewire component PHP
- `src/Http/Controllers/` — HTTP controllers
- `src/Actions/`, `src/Contracts/`, `src/Events/`
- Database migrations
- Fortify / Sanctum integration patterns

## Reporting vulnerabilities

1. **Upstream Jetstream / Fortify issues** — report to [Laravel Jetstream](https://github.com/laravel/jetstream/security) if the issue exists in official Jetstream too.

2. **This fork only** (Flux views, installer, Tailwind setup) — open a private security advisory or email the maintainer via GitHub: [EhsanGhafoori/jetstream-flux-livewire](https://github.com/EhsanGhafoori/jetstream-flux-livewire).

Please do not disclose security issues in public GitHub issues before a fix is available.
