# Jetstream Flux Livewire

Personal fork of [Laravel Jetstream](https://github.com/laravel/jetstream) maintained by **ehgh1996**.

Livewire-only stack with:

- **Livewire 4**
- **Tailwind CSS 4** (Vite + `@tailwindcss/vite`)
- **Flux UI** views (adapted from [jetstream-flux](https://github.com/grpaiva/jetstream-flux))

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- [Flux license](https://fluxui.dev) for `livewire/flux`

## Installation

Add the repository to your app's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ehgh1996/jetstream-flux-livewire"
        }
    ],
    "require": {
        "ehgh1996/jetstream-flux-livewire": "dev-main"
    }
}
```

Configure Flux credentials in `auth.json` (project root):

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

Then:

```bash
composer require ehgh1996/jetstream-flux-livewire
php artisan jetstream:install --teams --api --verification
npm install && npm run build
php artisan migrate
```

## Install options

```bash
php artisan jetstream:install
php artisan jetstream:install --teams
php artisan jetstream:install --api --verification --dark --pest
```

## Credits

- [Laravel Jetstream](https://github.com/laravel/jetstream) — MIT
- [jetstream-flux](https://github.com/grpaiva/jetstream-flux) — Flux UI views — MIT
- Customized and maintained by [ehgh1996](https://github.com/ehgh1996)

## License

MIT. See [LICENSE.md](LICENSE.md).
