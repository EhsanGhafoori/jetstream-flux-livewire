<?php

namespace Laravel\Jetstream\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

#[AsCommand(name: 'jetstream:install')]
class InstallCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jetstream:install {--dark : Indicate that dark mode support should be installed}
                                              {--teams : Indicates if team support should be installed}
                                              {--api : Indicates if API support should be installed}
                                              {--verification : Indicates if email verification support should be installed}
                                              {--pest : Indicates if Pest should be installed}
                                              {--google : Install Google One Tap login with Flux UI on auth pages}
                                              {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Jetstream components and resources';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        // Publish...
        $this->callSilent('vendor:publish', ['--tag' => 'jetstream-config', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'jetstream-migrations', '--force' => true]);

        $this->callSilent('vendor:publish', ['--tag' => 'fortify-config', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'fortify-support', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'fortify-migrations', '--force' => true]);

        // Storage...
        $this->callSilent('storage:link');

        $this->replaceInFile('/home', '/dashboard', config_path('fortify.php'));

        if (file_exists(resource_path('views/welcome.blade.php'))) {
            $this->replaceInFile('/home', '/dashboard', resource_path('views/welcome.blade.php'));
            $this->replaceInFile('Home', 'Dashboard', resource_path('views/welcome.blade.php'));
        }

        // Fortify Provider...
        ServiceProvider::addProviderToBootstrapFile('App\Providers\FortifyServiceProvider');

        // Configure Session...
        $this->configureSession();

        // Configure API...
        if ($this->option('api')) {
            $this->replaceInFile('// Features::api(),', 'Features::api(),', config_path('jetstream.php'));
        }

        // Configure Email Verification...
        if ($this->option('verification')) {
            $this->replaceInFile('// Features::emailVerification(),', 'Features::emailVerification(),', config_path('fortify.php'));
        }

        // Install Livewire stack...
        if (! $this->installLivewireStack()) {
            return 1;
        }

        // Emails...
        (new Filesystem)->ensureDirectoryExists(resource_path('views/emails'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/resources/views/emails', resource_path('views/emails'));

        // Tests...
        $stubs = $this->getTestStubsPath();

        if ($this->option('pest') || $this->isUsingPest()) {
            if ($this->hasComposerPackage('phpunit/phpunit')) {
                $this->removeComposerDevPackages(['phpunit/phpunit']);
            }

            if (! $this->requireComposerDevPackages(['pestphp/pest', 'pestphp/pest-plugin-laravel'])) {
                return 1;
            }

            copy($stubs.'/Pest.php', base_path('tests/Pest.php'));
            copy($stubs.'/ExampleTest.php', base_path('tests/Feature/ExampleTest.php'));
            copy($stubs.'/ExampleUnitTest.php', base_path('tests/Unit/ExampleTest.php'));
        }

        copy($stubs.'/AuthenticationTest.php', base_path('tests/Feature/AuthenticationTest.php'));
        copy($stubs.'/EmailVerificationTest.php', base_path('tests/Feature/EmailVerificationTest.php'));
        copy($stubs.'/PasswordConfirmationTest.php', base_path('tests/Feature/PasswordConfirmationTest.php'));
        copy($stubs.'/PasswordResetTest.php', base_path('tests/Feature/PasswordResetTest.php'));
        copy($stubs.'/RegistrationTest.php', base_path('tests/Feature/RegistrationTest.php'));

        if ($this->option('google')) {
            if (! $this->installGoogleOneTap()) {
                return 1;
            }
        }
    }

    /**
     * Configure the session driver for Jetstream.
     *
     * @return void
     */
    protected function configureSession()
    {
        $this->replaceInFile('SESSION_DRIVER=cookie', 'SESSION_DRIVER=database', base_path('.env'));
        $this->replaceInFile('SESSION_DRIVER=cookie', 'SESSION_DRIVER=database', base_path('.env.example'));
    }

    /**
     * Install the Livewire stack into the application.
     *
     * @return bool
     */
    protected function installLivewireStack()
    {
        // Install Livewire...
        if (! $this->requireComposerPackages('livewire/livewire:^4.0', 'livewire/flux:^2.0')) {
            return false;
        }

        $this->call('install:api', [
            '--without-migration-prompt' => true,
        ]);

        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            unset($packages['autoprefixer'], $packages['postcss']);

            return [
                '@tailwindcss/forms' => '^0.5.11',
                '@tailwindcss/typography' => '^0.5.19',
                '@tailwindcss/vite' => '^4.0',
                'tailwindcss' => '^4.0',
            ] + $packages;
        });

        // Tailwind Configuration...
        copy(__DIR__.'/../../stubs/livewire/vite.config.js', base_path('vite.config.js'));

        foreach (['tailwind.config.js', 'postcss.config.js'] as $legacyConfig) {
            if (file_exists(base_path($legacyConfig))) {
                (new Filesystem)->delete(base_path($legacyConfig));
            }
        }

        // Directories...
        (new Filesystem)->ensureDirectoryExists(app_path('Actions/Fortify'));
        (new Filesystem)->ensureDirectoryExists(app_path('Actions/Jetstream'));
        (new Filesystem)->ensureDirectoryExists(app_path('View/Components'));
        (new Filesystem)->ensureDirectoryExists(resource_path('css'));
        (new Filesystem)->ensureDirectoryExists(resource_path('markdown'));
        (new Filesystem)->ensureDirectoryExists(resource_path('views/api'));
        (new Filesystem)->ensureDirectoryExists(resource_path('views/auth'));
        (new Filesystem)->ensureDirectoryExists(resource_path('views/components'));
        (new Filesystem)->ensureDirectoryExists(resource_path('views/layouts'));
        (new Filesystem)->ensureDirectoryExists(resource_path('views/profile'));

        (new Filesystem)->deleteDirectory(resource_path('sass'));

        // Terms Of Service / Privacy Policy...
        copy(__DIR__.'/../../stubs/resources/markdown/terms.md', resource_path('markdown/terms.md'));
        copy(__DIR__.'/../../stubs/resources/markdown/policy.md', resource_path('markdown/policy.md'));

        // Service Providers...
        copy(__DIR__.'/../../stubs/app/Providers/JetstreamServiceProvider.php', $provider = app_path('Providers/JetstreamServiceProvider.php'));

        $this->replaceInFile([
            PHP_EOL.'use Illuminate\Support\Facades\Vite;',
            PHP_EOL.PHP_EOL.'        Vite::prefetch(concurrency: 3);',
        ], '', $provider);

        ServiceProvider::addProviderToBootstrapFile('App\Providers\JetstreamServiceProvider');

        // Models...
        copy(__DIR__.'/../../stubs/app/Models/User.php', app_path('Models/User.php'));

        // Factories...
        copy(__DIR__.'/../../database/factories/UserFactory.php', base_path('database/factories/UserFactory.php'));

        // Actions...
        copy(__DIR__.'/../../stubs/app/Actions/Fortify/CreateNewUser.php', app_path('Actions/Fortify/CreateNewUser.php'));
        copy(__DIR__.'/../../stubs/app/Actions/Fortify/UpdateUserProfileInformation.php', app_path('Actions/Fortify/UpdateUserProfileInformation.php'));
        copy(__DIR__.'/../../stubs/app/Actions/Jetstream/DeleteUser.php', app_path('Actions/Jetstream/DeleteUser.php'));

        // Components...
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire/resources/views/components', resource_path('views/components'));

        // View Components...
        copy(__DIR__.'/../../stubs/livewire/app/View/Components/AppLayout.php', app_path('View/Components/AppLayout.php'));
        copy(__DIR__.'/../../stubs/livewire/app/View/Components/GuestLayout.php', app_path('View/Components/GuestLayout.php'));

        // Layouts...
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire/resources/views/layouts', resource_path('views/layouts'));

        // Single Blade Views...
        copy(__DIR__.'/../../stubs/livewire/resources/views/dashboard.blade.php', resource_path('views/dashboard.blade.php'));
        copy(__DIR__.'/../../stubs/livewire/resources/views/navigation-menu.blade.php', resource_path('views/navigation-menu.blade.php'));
        copy(__DIR__.'/../../stubs/livewire/resources/views/terms.blade.php', resource_path('views/terms.blade.php'));
        copy(__DIR__.'/../../stubs/livewire/resources/views/policy.blade.php', resource_path('views/policy.blade.php'));

        // Other Views...
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire/resources/views/api', resource_path('views/api'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire/resources/views/profile', resource_path('views/profile'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire/resources/views/auth', resource_path('views/auth'));

        if (! Str::contains(file_get_contents(base_path('routes/web.php')), "'/dashboard'")) {
            (new Filesystem)->append(base_path('routes/web.php'), $this->livewireRouteDefinition());
        }

        // Assets...
        copy(__DIR__.'/../../stubs/resources/css/app.css', resource_path('css/app.css'));

        // Tests...
        $stubs = $this->getTestStubsPath();

        copy($stubs.'/livewire/ApiTokenPermissionsTest.php', base_path('tests/Feature/ApiTokenPermissionsTest.php'));
        copy($stubs.'/livewire/BrowserSessionsTest.php', base_path('tests/Feature/BrowserSessionsTest.php'));
        copy($stubs.'/livewire/CreateApiTokenTest.php', base_path('tests/Feature/CreateApiTokenTest.php'));
        copy($stubs.'/livewire/DeleteAccountTest.php', base_path('tests/Feature/DeleteAccountTest.php'));
        copy($stubs.'/livewire/DeleteApiTokenTest.php', base_path('tests/Feature/DeleteApiTokenTest.php'));
        copy($stubs.'/livewire/ProfileInformationTest.php', base_path('tests/Feature/ProfileInformationTest.php'));
        copy($stubs.'/livewire/TwoFactorAuthenticationSettingsTest.php', base_path('tests/Feature/TwoFactorAuthenticationSettingsTest.php'));
        copy($stubs.'/livewire/UpdatePasswordTest.php', base_path('tests/Feature/UpdatePasswordTest.php'));

        // Teams...
        if ($this->option('teams')) {
            $this->installLivewireTeamStack();
        }

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } elseif (file_exists(base_path('bun.lockb'))) {
            $this->runCommands(['bun install', 'bun run build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->line('');
        $this->runDatabaseMigrations();

        $this->components->info('Livewire scaffolding installed successfully.');

        return true;
    }

    /**
     * Install Google One Tap login scaffolding.
     *
     * @return bool
     */
    protected function installGoogleOneTap()
    {
        if (! $this->requireComposerPackages('laravel/socialite:^5.0', 'websitinu/laravel-socialite-google-one-tap:^2.7')) {
            return false;
        }

        $stubPath = __DIR__.'/../../stubs/google';

        (new Filesystem)->ensureDirectoryExists(resource_path('views/flux/icon'));
        (new Filesystem)->ensureDirectoryExists(resource_path('views/auth/partials'));
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Controllers/Auth'));

        copy($stubPath.'/resources/views/flux/icon/google.blade.php', resource_path('views/flux/icon/google.blade.php'));
        copy($stubPath.'/resources/views/auth/partials/google-auth.blade.php', resource_path('views/auth/partials/google-auth.blade.php'));
        copy($stubPath.'/resources/views/auth/login.blade.php', resource_path('views/auth/login.blade.php'));
        copy($stubPath.'/resources/views/auth/register.blade.php', resource_path('views/auth/register.blade.php'));
        copy($stubPath.'/resources/views/layouts/guest.blade.php', resource_path('views/layouts/guest.blade.php'));
        copy($stubPath.'/app/Http/Controllers/Auth/GoogleOneTapController.php', app_path('Http/Controllers/Auth/GoogleOneTapController.php'));

        $this->configureGoogleOneTapServices();
        $this->configureGoogleOneTapProvider();
        $this->configureGoogleOneTapRoutes();
        $this->configureGoogleOneTapEnvironment();

        $this->components->info('Google One Tap login installed successfully.');
        $this->components->warn('Add your Google OAuth credentials to .env — see websitinu/laravel-socialite-google-one-tap README.');

        return true;
    }

    /**
     * Add Google One Tap credentials to the services config.
     *
     * @return void
     */
    protected function configureGoogleOneTapServices()
    {
        $path = config_path('services.php');

        if (Str::contains(file_get_contents($path), 'laravel-google-one-tap')) {
            return;
        }

        $this->replaceInFile(
            '];',
            <<<'PHP'
    'laravel-google-one-tap' => [
        'client_id' => env('GOOGLE_ONE_TAP_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ONE_TAP_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_ONE_TAP_LOGIN_URI'),
    ],

];
PHP,
            $path
        );
    }

    /**
     * Register the Google One Tap Socialite driver.
     *
     * @return void
     */
    protected function configureGoogleOneTapProvider()
    {
        $path = app_path('Providers/AppServiceProvider.php');
        $contents = file_get_contents($path);

        if (Str::contains($contents, 'laravel-google-one-tap')) {
            return;
        }

        if (! Str::contains($contents, 'use Illuminate\Support\Facades\Event;')) {
            $this->replaceInFile(
                'use Illuminate\Support\ServiceProvider;',
                "use Illuminate\Support\ServiceProvider;\nuse Illuminate\Support\Facades\Event;\nuse LaravelSocialite\\GoogleOneTap\\LaravelGoogleOneTapServiceProvider;",
                $path
            );
        }

        $this->replaceInFile(
            "public function boot(): void\n    {\n",
            "public function boot(): void\n    {\n        Event::listen(function (\\SocialiteProviders\\Manager\\SocialiteWasCalled \$event) {\n            \$event->extendSocialite('laravel-google-one-tap', LaravelGoogleOneTapServiceProvider::class);\n        });\n\n",
            $path
        );
    }

    /**
     * Add the Google One Tap authentication route.
     *
     * @return void
     */
    protected function configureGoogleOneTapRoutes()
    {
        $path = base_path('routes/web.php');

        if (Str::contains(file_get_contents($path), 'google-one-tap.handler')) {
            return;
        }

        (new Filesystem)->append($path, $this->googleOneTapRouteDefinition());
    }

    /**
     * Append Google One Tap environment variables.
     *
     * @return void
     */
    protected function configureGoogleOneTapEnvironment()
    {
        $envKeys = <<<'ENV'

GOOGLE_ONE_TAP_CLIENT_ID=
GOOGLE_ONE_TAP_CLIENT_SECRET=
GOOGLE_ONE_TAP_LOGIN_URI="${APP_URL}/auth/google/onetap"
ENV;

        foreach ([base_path('.env'), base_path('.env.example')] as $path) {
            if (! file_exists($path)) {
                continue;
            }

            if (! Str::contains(file_get_contents($path), 'GOOGLE_ONE_TAP_CLIENT_ID')) {
                (new Filesystem)->append($path, $envKeys);
            }
        }
    }

    /**
     * Get the route definition for Google One Tap authentication.
     *
     * @return string
     */
    protected function googleOneTapRouteDefinition()
    {
        return <<<'EOF'

use App\Http\Controllers\Auth\GoogleOneTapController;

Route::post('/auth/google/onetap', GoogleOneTapController::class)
    ->middleware('guest')
    ->name('google-one-tap.handler');

EOF;
    }

    /**
     * Install the Livewire team stack into the application.
     *
     * @return void
     */
    protected function installLivewireTeamStack()
    {
        // Directories...
        (new Filesystem)->ensureDirectoryExists(resource_path('views/teams'));

        // Other Views...
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire/resources/views/teams', resource_path('views/teams'));

        // Tests...
        $stubs = $this->getTestStubsPath();

        copy($stubs.'/livewire/CreateTeamTest.php', base_path('tests/Feature/CreateTeamTest.php'));
        copy($stubs.'/livewire/DeleteTeamTest.php', base_path('tests/Feature/DeleteTeamTest.php'));
        copy($stubs.'/livewire/InviteTeamMemberTest.php', base_path('tests/Feature/InviteTeamMemberTest.php'));
        copy($stubs.'/livewire/LeaveTeamTest.php', base_path('tests/Feature/LeaveTeamTest.php'));
        copy($stubs.'/livewire/RemoveTeamMemberTest.php', base_path('tests/Feature/RemoveTeamMemberTest.php'));
        copy($stubs.'/livewire/UpdateTeamMemberRoleTest.php', base_path('tests/Feature/UpdateTeamMemberRoleTest.php'));
        copy($stubs.'/livewire/UpdateTeamNameTest.php', base_path('tests/Feature/UpdateTeamNameTest.php'));

        $this->ensureApplicationIsTeamCompatible();
    }

    /**
     * Get the route definition(s) that should be installed for Livewire.
     *
     * @return string
     */
    protected function livewireRouteDefinition()
    {
        return <<<'EOF'

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

EOF;
    }

    /**
     * Ensure the installed user model is ready for team usage.
     *
     * @return void
     */
    protected function ensureApplicationIsTeamCompatible()
    {
        // Publish Team Migrations...
        $this->callSilent('vendor:publish', ['--tag' => 'jetstream-team-migrations', '--force' => true]);

        // Configuration...
        $this->replaceInFile('// Features::teams([\'invitations\' => true])', 'Features::teams([\'invitations\' => true])', config_path('jetstream.php'));

        // Directories...
        (new Filesystem)->ensureDirectoryExists(app_path('Actions/Jetstream'));
        (new Filesystem)->ensureDirectoryExists(app_path('Events'));
        (new Filesystem)->ensureDirectoryExists(app_path('Policies'));

        // Service Providers...
        copy(__DIR__.'/../../stubs/app/Providers/JetstreamWithTeamsServiceProvider.php', app_path('Providers/JetstreamServiceProvider.php'));

        // Models...
        copy(__DIR__.'/../../stubs/app/Models/Membership.php', app_path('Models/Membership.php'));
        copy(__DIR__.'/../../stubs/app/Models/Team.php', app_path('Models/Team.php'));
        copy(__DIR__.'/../../stubs/app/Models/TeamInvitation.php', app_path('Models/TeamInvitation.php'));
        copy(__DIR__.'/../../stubs/app/Models/UserWithTeams.php', app_path('Models/User.php'));

        // Actions...
        copy(__DIR__.'/../../stubs/app/Actions/Jetstream/AddTeamMember.php', app_path('Actions/Jetstream/AddTeamMember.php'));
        copy(__DIR__.'/../../stubs/app/Actions/Jetstream/CreateTeam.php', app_path('Actions/Jetstream/CreateTeam.php'));
        copy(__DIR__.'/../../stubs/app/Actions/Jetstream/DeleteTeam.php', app_path('Actions/Jetstream/DeleteTeam.php'));
        copy(__DIR__.'/../../stubs/app/Actions/Jetstream/DeleteUserWithTeams.php', app_path('Actions/Jetstream/DeleteUser.php'));
        copy(__DIR__.'/../../stubs/app/Actions/Jetstream/InviteTeamMember.php', app_path('Actions/Jetstream/InviteTeamMember.php'));
        copy(__DIR__.'/../../stubs/app/Actions/Jetstream/RemoveTeamMember.php', app_path('Actions/Jetstream/RemoveTeamMember.php'));
        copy(__DIR__.'/../../stubs/app/Actions/Jetstream/UpdateTeamName.php', app_path('Actions/Jetstream/UpdateTeamName.php'));

        copy(__DIR__.'/../../stubs/app/Actions/Fortify/CreateNewUserWithTeams.php', app_path('Actions/Fortify/CreateNewUser.php'));

        // Policies...
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/app/Policies', app_path('Policies'));

        // Factories...
        copy(__DIR__.'/../../database/factories/UserFactory.php', base_path('database/factories/UserFactory.php'));
        copy(__DIR__.'/../../database/factories/TeamFactory.php', base_path('database/factories/TeamFactory.php'));

        // Seeders...
        copy(__DIR__.'/../../database/seeders/DatabaseSeeder.php', base_path('database/seeders/DatabaseSeeder.php'));
    }

    /**
     * Returns the path to the correct test stubs.
     *
     * @return string
     */
    protected function getTestStubsPath()
    {
        return $this->option('pest') || $this->isUsingPest()
            ? __DIR__.'/../../stubs/pest-tests'
            : __DIR__.'/../../stubs/tests';
    }

    /**
     * Determine if the given Composer package is installed.
     *
     * @param  string  $package
     * @return bool
     */
    protected function hasComposerPackage($package)
    {
        $packages = json_decode(file_get_contents(base_path('composer.json')), true);

        return array_key_exists($package, $packages['require'] ?? [])
            || array_key_exists($package, $packages['require-dev'] ?? []);
    }

    /**
     * Installs the given Composer Packages into the application.
     *
     * @param  mixed  $packages
     * @return bool
     */
    protected function requireComposerPackages($packages)
    {
        $composer = $this->option('composer');

        if ($composer !== 'global') {
            $command = [$this->phpBinary(), $composer, 'require'];
        }

        $command = array_merge(
            $command ?? ['composer', 'require'],
            is_array($packages) ? $packages : func_get_args()
        );

        return ! (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            });
    }

    /**
     * Removes the given Composer Packages as "dev" dependencies.
     *
     * @param  mixed  $packages
     * @return bool
     */
    protected function removeComposerDevPackages($packages)
    {
        $composer = $this->option('composer');

        if ($composer !== 'global') {
            $command = [$this->phpBinary(), $composer, 'remove', '--dev'];
        }

        $command = array_merge(
            $command ?? ['composer', 'remove', '--dev'],
            is_array($packages) ? $packages : func_get_args()
        );

        return (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            }) === 0;
    }

    /**
     * Install the given Composer Packages as "dev" dependencies.
     *
     * @param  mixed  $packages
     * @return bool
     */
    protected function requireComposerDevPackages($packages)
    {
        $composer = $this->option('composer');

        if ($composer !== 'global') {
            $command = [$this->phpBinary(), $composer, 'require', '--dev'];
        }

        $command = array_merge(
            $command ?? ['composer', 'require', '--dev'],
            is_array($packages) ? $packages : func_get_args()
        );

        return (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            }) === 0;
    }

    /**
     * Update the "package.json" file.
     *
     * @param  callable  $callback
     * @param  bool  $dev
     * @return void
     */
    protected static function updateNodePackages(callable $callback, $dev = true)
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Run the database migrations.
     *
     * @return void
     */
    protected function runDatabaseMigrations()
    {
        if (confirm('New database migrations were added. Would you like to re-run your migrations?', true)) {
            (new Process([$this->phpBinary(), 'artisan', 'migrate:fresh', '--force'], base_path()))
                ->setTimeout(null)
                ->run(function ($type, $output) {
                    $this->output->write($output);
                });
        }
    }

    /**
     * Replace a given string within a given file.
     *
     * @param  string  $replace
     * @param  string|array  $search
     * @param  string  $path
     * @return void
     */
    protected function replaceInFile($search, $replace, $path)
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    /**
     * Remove Tailwind dark classes from the given files.
     *
     * @param  \Symfony\Component\Finder\Finder  $finder
     * @return void
     */
    protected function removeDarkClasses(Finder $finder)
    {
        foreach ($finder as $file) {
            file_put_contents($file->getPathname(), preg_replace('/\sdark:[^\s"\']+/', '', $file->getContents()));
        }
    }

    /**
     * Get the path to the appropriate PHP binary.
     *
     * @return string
     */
    protected function phpBinary()
    {
        if (function_exists('Illuminate\Support\php_binary')) {
            return \Illuminate\Support\php_binary();
        }

        return (new PhpExecutableFinder())->find(false) ?: 'php';
    }

    /**
     * Run the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function runCommands($commands)
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL);
            }
        }

        $process->run(function ($type, $line) {
            $this->output->write('    '.$line);
        });
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        collect(multiselect(
            label: 'Would you like any optional features?',
            options: collect([
                'teams' => 'Team support',
                'api' => 'API support',
                'verification' => 'Email verification',
                'dark' => 'Dark mode',
                'google' => 'Google One Tap login',
            ])->sort()->all(),
        ))->each(fn ($option) => $input->setOption($option, true));

        $input->setOption('pest', select(
            label: 'Which testing framework do you prefer?',
            options: ['Pest', 'PHPUnit'],
            default: 'Pest',
        ) === 'Pest');
    }

    /**
     * Determine whether the project is already using Pest.
     *
     * @return bool
     */
    protected function isUsingPest()
    {
        return class_exists(\Pest\TestSuite::class);
    }
}
