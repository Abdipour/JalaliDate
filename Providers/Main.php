<?php

namespace Modules\JalaliDate\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View as FacadesView;
use Illuminate\View\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use App\Events\Document\DocumentPrinting;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class Main extends ServiceProvider
{
    /** @var array Cached settings to avoid redundant DB calls */
    private static $moduleSettings = null;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->loadConfig();
        $this->loadRoutes();
    }

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViews();
        $this->loadViewComponents();
        $this->loadMigrations();
        $this->loadTranslations();

        // Register composers. Settings will be fetched only when a view is actually rendered.
        $this->registerViewComposers();

        // Register directives and components
        $this->registerBladeDirectives();

        Blade::component('documents.template.persian', \Modules\JalaliDate\View\Components\Documents\Template\Persian::class);

        $this->registerEventListeners();
    }

    /**
     * Lazy-load settings. Returns settings from memory if already fetched.
     */
    private function getSettings()
    {
        if (self::$moduleSettings === null) {
            // This runs only once per request, at the moment it's actually needed.
            self::$moduleSettings = setting('jalali-date', []);
        }
        return self::$moduleSettings;
    }

    /**
     * Check if Jalali feature is enabled based on lazy-loaded settings.
     */
    private function isJalaliEnabled(): bool
    {
        $settings = $this->getSettings();
        return (bool) ($settings['jalalidate'] ?? false);
    }

    private function registerViewComposers()
    {
        // Script injection
        FacadesView::composer('components.script', function (View $view) {
            $view->getFactory()->startPush('scripts', view('jalali-date::jalali-datepicker-init'));
        });

        // Datepicker Switcher (Depends on settings)
        FacadesView::composer('components.form.group.date', function (View $view) {
            $settings = $this->getSettings();
            $data = $view->getData();
            if (empty($data['name'])) return;

            $switchHtml = view('jalali-date::components.datepicker-switcher', [
                'dateName' => $data['name'],
                'active'   => (bool) ($settings['jalalidatepicker'] ?? false)
            ])->render();

            $view->getFactory()->startPush($data['name'] . '_input_end', $switchHtml);
        });

        // Date Converters (Logic runs only if Jalali is enabled)
        FacadesView::composer([
            'components.transactions.show.create',
            'components.documents.show.create',
            'components.documents.show.send',
            'components.date'
        ], function (View $view) {
            if ($this->isJalaliEnabled()) {
                $this->convertDatesToJalali($view);
            }
        });

        // Asset Injections
        FacadesView::composer(['components.layouts.print.head', 'components.layouts.admin.head'], function (View $view) {
            $view->getFactory()->startPush('stylesheet', view('jalali-date::typography-css'));
        });

        FacadesView::composer('jalali-date::components.documents.template.persian', function (View $view) {
            $view->getFactory()->startPush('stylesheet', view('jalali-date::persian-invoice-css'));
        });
    }

    /**
     * Shared logic for converting dates to Jalali within composers.
     */
    private function convertDatesToJalali(View $view)
    {
        $data = $view->getData();
        $viewName = $view->getName();

        // Logic for Transactions
        if (str_contains($viewName, 'transactions.show.create')) {
            $data['transaction']->created_at = Jalalian::fromCarbon($data['transaction']->created_at);
        }

        // Logic for Documents (Invoices/Bills)
        if (str_contains($viewName, 'documents.show')) {
            if (isset($data['document'])) {
                $format = $data['document']->getCompanyDateFormat();
                $data['created_date'] = '<span class="font-medium">' . Jalalian::fromCarbon($data['document']->created_at)->format($format) . '</span>';
            }
            if (isset($data['last_sent']) && $data['last_sent']) {
                $data['last_sent_date'] = '<span class="font-medium">' . Jalalian::fromCarbon($data['last_sent']->created_at)->format($data['document']->getCompanyDateFormat()) . '</span>';
            }
            if (isset($data['histories'])) {
                foreach ($data['histories'] as $history) {
                    $history->created_at = Jalalian::fromCarbon($history->created_at);
                }
            }
        }

        // Logic for Generic Date Component
        if ($viewName === 'components.date' && isset($data['date'])) {
            $rawDate = is_string($data["rawDate"]) ? Carbon::parse($data["rawDate"]) : $data["rawDate"];
            try {
                $jalali = Jalalian::fromCarbon($rawDate);
                $data['date'] = ($data["function"] == 'diffForHumans') ? $jalali->ago() : $jalali->format($data["format"]);
            } catch (\Exception $e) {
                \Log::error("Jalali conversion failed: " . $e->getMessage());
            }
        }

        $view->with($data);
    }

    private function registerBladeDirectives()
    {
        Blade::directive('date', function ($expression) {
            // Note: Settings check is done INSIDE the generated PHP code 
            // because Blade directives are compiled once and cached.
            return "<?php 
                \$isJalali = (bool) (setting('jalali-date.jalalidate') ?? false);
                if (\$isJalali && {$expression} instanceof \Carbon\Carbon) {
                    echo \Morilog\Jalali\Jalalian::fromCarbon({$expression})->format(company_date_format());
                } else {
                    echo {$expression};
                }
            ?>";
        });
    }

    private function registerEventListeners()
    {
        Event::listen(DocumentPrinting::class, function (DocumentPrinting $event) {
            if ($event->document->template === 'persian') {
                $event->document->template_path = 'sales.invoices.print_persian';
            }
        });
    }

    /**
     * Load views.
     *
     * @return void
     */
    public function loadViews()
    {
        $viewPath = realpath(__DIR__ . '/../Resources/views');
        $this->app['view']->getFinder()->prependLocation($viewPath);
        $this->loadViewsFrom($viewPath, 'jalali-date');
    }

    /**
     * Load view components.
     *
     * @return void
     */
    public function loadViewComponents()
    {
        Blade::componentNamespace('Modules\JalaliDate\View\Components', 'jalali-date');
    }

    /**
     * Load migrations.
     *
     * @return void
     */
    public function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    /**
     * Load translations.
     *
     * @return void
     */
    public function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'jalali-date');
    }

    /**
     * Load routes.
     *
     * @return void
     */
    public function loadRoutes()
    {
        if (app()->routesAreCached()) {
            return;
        }

        /* $routes = [
            'admin.php',
            'portal.php',
        ];

        foreach ($routes as $route) {
            $this->loadRoutesFrom(__DIR__ . '/../Routes/' . $route);
        } */
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    public function loadConfig()
    {
        $merge_to_core_configs = ['type'];

        foreach ($merge_to_core_configs as $config) {
            Config::set($config, array_merge_recursive(
                Config::get($config),
                require __DIR__ . '/../Config/' . $config . '.php'
            ));
        }

        $this->mergeConfigFrom(__DIR__ . '/../Config/jalali-date.php', 'jalali-date');
    }
}
