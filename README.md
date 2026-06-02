# Jetstream Flux Livewire

A **Livewire-only** fork of [Laravel Jetstream](https://github.com/laravel/jetstream) that ships with **Livewire 4**, **Tailwind CSS 4**, and **[Flux UI](https://fluxui.dev)** views.

Maintained by [Ehsan Ghafoori](https://github.com/EhsanGhafoori).

---

## Why this package exists

[Laravel Jetstream](https://github.com/laravel/jetstream) is a mature, battle-tested authentication and team-management scaffold. Laravel’s newer [Starter Kits](https://laravel.com/starter-kits) are the recommended path for **new** greenfield apps.

Many teams still want **Jetstream’s feature set** (teams, API tokens, 2FA, profile management) on a **modern stack**:

| Need | Official Jetstream (5.x) | This package |
|------|------------------------|--------------|
| Livewire 4 | Livewire 3 | **Livewire 4** |
| Tailwind CSS 4 | Tailwind 3 | **Tailwind 4** |
| Flux UI views | Default Tailwind components | **Flux UI** |
| Inertia / Vue stack | Supported | **Removed** (Livewire only) |

There was no maintained, official Jetstream release that combined **Livewire 4 + Tailwind 4 + Flux UI** in one installable package. This fork fills that gap for developers who prefer Jetstream’s architecture and security model on a current frontend stack.

---

## Security & scope of changes

**This fork is intentionally conservative.** Authentication, authorization, team logic, and backend behaviour remain aligned with upstream Jetstream.

### What we did **not** change

The following are **unchanged from Laravel Jetstream** (same responsibilities, same security model):

- **Authentication** — still powered by [Laravel Fortify](https://github.com/laravel/fortify) (login, registration, 2FA, password reset, email verification)
- **Livewire component logic** — `src/Http/Livewire/*` (profile, teams, API tokens, sessions)
- **Controllers & routes** — `src/Http/Controllers/Livewire/*`, `routes/livewire.php`
- **Team system** — roles, permissions, invitations, policies, actions, events
- **Database migrations** — users, teams, tokens, 2FA columns
- **Middleware** — session authentication, password confirmation
- **Core package API** — `Jetstream`, `Features`, traits (`HasTeams`, `HasProfilePhoto`), contracts

You get the **same Jetstream backend** you already trust. We did not rewrite auth flows or team authorization.

### What we **did** change (presentation & tooling only)

| Area | Change |
|------|--------|
| **Blade views** | Replaced with Flux UI components ([adapted from jetstream-flux](https://github.com/grpaiva/jetstream-flux)) |
| **CSS / Vite** | Tailwind 4 CSS-first setup + `@tailwindcss/vite` |
| **Install command** | Requires Livewire 4, Flux 2, Tailwind 4 npm packages |
| **Stack** | Inertia / Vue stack removed — Livewire only |

**Summary:** UI and frontend tooling updates only. No custom auth logic, no bypass of Fortify or Sanctum, no changes to how teams or permissions work.

---

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- Node.js 20.19+ (or 22.12+) for Vite 8 builds
- [Flux license](https://fluxui.dev) — `livewire/flux` requires Composer authentication

---

## Installation

### 1. Require the package

Add the GitHub repository to your app's `composer.json` (installs into `vendor/` like any other package):

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/EhsanGhafoori/jetstream-flux-livewire"
        }
    ],
    "require": {
        "ehsanghafoori/jetstream-flux-livewire": "^1.0"
    }
}
```

```bash
composer require ehsanghafoori/jetstream-flux-livewire
```

The package is installed under `vendor/ehsanghafoori/jetstream-flux-livewire` — not in your `public/` folder.

### 2. Configure Flux (Composer)

Create or update `auth.json` in your **project root** (never commit this file):

```json
{
    "http-basic": {
        "composer.fluxui.dev": {
            "username": "your-email@example.com",
            "password": "your-flux-license-key"
        }
    }
}
```

Get your license at [fluxui.dev](https://fluxui.dev).

### 3. Run the Jetstream installer

```bash
php artisan jetstream:install
```

**Common options:**

```bash
# Teams + API + email verification
php artisan jetstream:install --teams --api --verification

# Dark mode + Pest tests
php artisan jetstream:install --teams --dark --pest
```

| Option | Description |
|--------|-------------|
| `--teams` | Team management |
| `--api` | Sanctum API tokens |
| `--verification` | Email verification (Fortify) |
| `--dark` | Dark mode classes in views |
| `--pest` | Install Pest test stubs |

### 4. Frontend & database

```bash
npm install
npm run build
php artisan migrate
```

### 5. Register & test

Visit `/register`, create a user, then open `/dashboard`.

---

## What you get after install

- Flux-styled auth pages (login, register, 2FA challenge, etc.)
- Flux navigation header and mobile sidebar
- Profile, team, and API token management UIs
- Tailwind 4 + Vite configuration
- Livewire 4 + Flux 2 registered via Composer

---

## Differences from official Jetstream

1. **Livewire stack only** — no Inertia, Vue, or SSR scaffolding.
2. **Modern frontend** — Livewire 4, Tailwind 4, Flux UI.
3. **Same install command** — `php artisan jetstream:install` (no stack argument).
4. **Drop-in replacement** — same namespace (`Laravel\Jetstream`), same Fortify/Sanctum integration.

Official Jetstream remains the right choice if you need Inertia or Laravel’s default Starter Kits. Use **this package** if you want **Jetstream + Livewire 4 + Tailwind 4 + Flux**.

---

## Upgrading from official `laravel/jetstream`

This is a **separate package** (`ehsanghafoori/jetstream-flux-livewire`), not an in-place upgrade of `laravel/jetstream`.

For **new projects**, require this package instead of `laravel/jetstream` and run `jetstream:install`.

For **existing Jetstream apps**, treat it as a migration project: back up first, compare published views and config, then reinstall scaffolding on a branch.

---

## Reporting issues

Security concerns about **Jetstream core behaviour** should still be reported to [laravel/jetstream](https://github.com/laravel/jetstream/security).

For issues specific to **Flux views**, **Tailwind 4 setup**, or **Livewire 4 compatibility** in this fork:

[github.com/EhsanGhafoori/jetstream-flux-livewire/issues](https://github.com/EhsanGhafoori/jetstream-flux-livewire/issues)

---

## Credits

| Project | Role |
|---------|------|
| [Laravel Jetstream](https://github.com/laravel/jetstream) | Core auth, teams, API, Livewire logic — MIT |
| [jetstream-flux](https://github.com/grpaiva/jetstream-flux) | Flux UI Blade views — MIT |
| [Livewire Flux](https://fluxui.dev) | UI component library |
| [Ehsan Ghafoori](https://github.com/EhsanGhafoori) | Fork maintenance — Livewire 4 / Tailwind 4 / installer updates |

---

## License

MIT — see [LICENSE.md](LICENSE.md).

Copyright (c) Taylor Otwell — original Jetstream  
Copyright (c) Ehsan Ghafoori — fork modifications
