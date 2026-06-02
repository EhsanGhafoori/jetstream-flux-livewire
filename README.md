# Jetstream Flux Livewire

Personal fork of [Laravel Jetstream](https://github.com/laravel/jetstream) by [Ehsan Ghafoori](https://github.com/EhsanGhafoori).

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
            "url": "https://github.com/EhsanGhafoori/jetstream-flux-livewire"
        }
    ],
    "require": {
        "ehsanghafoori/jetstream-flux-livewire": "dev-master"
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
composer require ehsanghafoori/jetstream-flux-livewire
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
- Customized and maintained by [EhsanGhafoori](https://github.com/EhsanGhafoori)

## License

MIT. See [LICENSE.md](LICENSE.md).
