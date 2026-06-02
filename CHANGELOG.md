# Changelog

All notable changes to this fork are documented here.

## [1.0.0] - 2026-06-02

### Added

- Livewire 4 support in installer (`livewire/livewire:^4.0`)
- Tailwind CSS 4 setup (`tailwindcss:^4.0`, `@tailwindcss/vite`)
- Flux UI Blade views (from [jetstream-flux](https://github.com/grpaiva/jetstream-flux))
- `livewire/flux:^2.0` required during install
- Public documentation (README, SECURITY)

### Changed

- Default stack: **Livewire only** (Inertia / Vue stack removed)
- `jetstream:install` no longer requires a stack argument
- Layouts include `@fluxAppearance` and `@fluxScripts`
- Tailwind plugins: `@tailwindcss/forms` and `@tailwindcss/typography` at `^0.5.x` (compatible with Tailwind 4)

### Removed

- Inertia stack stubs, controllers, routes, and installer paths
- `inertiajs/inertia-laravel` dependency

### Security note

No changes to Jetstream authentication, team authorization, or Livewire component business logic. UI and frontend tooling only.

## [1.0.2] - 2026-06-02

### Fixed

- Keep dark mode Tailwind classes on install — Flux `@fluxAppearance` requires `dark:` variants on layouts and auth views
- Add Inter font to guest layout (Flux recommendation)

[1.0.2]: https://github.com/EhsanGhafoori/jetstream-flux-livewire/releases/tag/v1.0.2

## [1.0.1] - 2026-06-02

### Fixed

- Flux 2 compatibility: replace removed `@fluxStyles` with `@fluxAppearance` and import `flux.css` in `app.css` ([Flux installation docs](https://fluxui.dev/docs/installation))

[1.0.1]: https://github.com/EhsanGhafoori/jetstream-flux-livewire/releases/tag/v1.0.1
[1.0.0]: https://github.com/EhsanGhafoori/jetstream-flux-livewire/releases/tag/v1.0.0
