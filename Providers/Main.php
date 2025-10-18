<?php

namespace Modules\JalaliDate\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Models\Document\Document;
use App\Models\Document\DocumentTotal;
use App\Models\Banking\Transaction;
use Modules\JalaliDate\Observers\JalaliObserver;
use Illuminate\Support\Facades\View as FacadesView;
use Illuminate\View\View;

class Main extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];
            $locale = $app['config']['app.locale'];

            $translator = new \Modules\JalaliDate\Http\Overrides\CustomTranslator($loader, $locale);
            $translator->setFallback($app['config']['app.fallback_locale']);

            return $translator;
        });

        $this->app->alias('translator', Illuminate\Contracts\Translation\Translator::class);

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

        Transaction::observe(JalaliObserver::class);
        Document::observe(JalaliObserver::class);
        DocumentTotal::observe(JalaliObserver::class);

        if (class_exists(\Modules\Employees\Models\Employee::class)) {
            \Modules\Employees\Models\Employee::observe(JalaliObserver::class);
        }

        FacadesView::composer('components.script', function (View $view) {
            $view->getFactory()->startPush('scripts', view('jalali-date::jalali_date_scripts'));
        });

        FacadesView::composer('components.form.group.date', function (View $view) {
            $data = $view->getData();
            if (isset($data['value']) && str_contains($data['value'], '-')) {
                $parts = explode('-', substr($data['value'], 0, 10));
                if (count($parts) === 3) {
                    [$y, $m, $d] = $parts;
                    $isJalaliValid = \Morilog\Jalali\CalendarUtils::checkDate($y, $m, $d, true);
                    $isGregorianValid = \Morilog\Jalali\CalendarUtils::checkDate($y, $m, $d, false);

                    // Determine if date is Jalali
                    $isJalali = $isJalaliValid && !$isGregorianValid
                        ? true
                        : (!$isJalaliValid && $isGregorianValid ? false : $y < 1800);

                    if (!$isJalali) {
                        if (is_string($data['value'])) {
                            $rawDate = \Carbon\Carbon::parse($data['value']);
                        }

                        try {
                            $jalaliDate = \Morilog\Jalali\Jalalian::fromCarbon($rawDate);
                            $data['value'] = $jalaliDate;
                        } catch (\Exception $e) {
                            \Log::error("Jalali date conversion failed: " . $data['value']);
                        }
                    }

                    $view->with($data);
                }
            }
        });

        FacadesView::composer('components.documents.form.metadata', function (View $view) {
            $data = $view->getData();
            $dateFields = ['issuedAt', 'dueAt'];
            foreach ($dateFields as $field) {
                if (isset($data[$field]) && $data[$field]) {
                    if (is_string($data[$field])) {
                        $rawDate = \Carbon\Carbon::parse($data[$field]);
                    }

                    try {
                        $jalaliDate = \Morilog\Jalali\Jalalian::fromCarbon($rawDate);
                        $data[$field] = $jalaliDate;
                    } catch (\Exception $e) {
                        \Log::error("Jalali date conversion failed for {$field}: " . $data[$field]);
                    }
                }
            }

            $view->with($data);
        });

        FacadesView::composer('components.transactions.show.create', function (View $view) {
            $data = $view->getData();
            $data['transaction']->created_at = \Morilog\Jalali\Jalalian::fromCarbon($data['transaction']->created_at);
            $view->with($data);
        });

        FacadesView::composer('components.documents.show.create', function (View $view) {
            $data = $view->getData();
            $data['created_date'] = '<span class="font-medium">' . \Morilog\Jalali\Jalalian::fromCarbon($data['document']->created_at)->format($data['document']->getCompanyDateFormat()) . '</span>';
            $view->with($data);
        });

        FacadesView::composer('components.documents.show.send', function (View $view) {
            $data = $view->getData();
            if ($data['last_sent']) {
                $data['last_sent_date'] = '<span class="font-medium">' . \Morilog\Jalali\Jalalian::fromCarbon($data['last_sent']->created_at)->format($data['document']->getCompanyDateFormat()) . '</span>';
            }
            if ($data['histories']->count()) {
                foreach ($data['histories'] as $key => $history) {
                    $history->created_at = \Morilog\Jalali\Jalalian::fromCarbon($history->created_at);
                }
            }
            $view->with($data);
        });

        FacadesView::composer('components.date', function (View $view) {
            $data = $view->getData();
            if (isset($data['date']) && $data['date']) {
                $rawDate = $data["rawDate"];
                $format = $data["format"];
                $function = $data["function"];
                if (is_string($rawDate)) {
                    $rawDate = \Carbon\Carbon::parse($rawDate);
                }
                try {
                    $jalali = \Morilog\Jalali\Jalalian::fromCarbon($rawDate);
                    if ($function == 'diffForHumans') {
                        $data['date'] = $jalali->ago();
                    } else {
                        $data['date'] = $jalali->format($format);
                    }

                } catch (\Exception $e) {
                    \Log::error("Jalali conversion failed: " . $data['date']);
                }

                $view->with($data);
            }
        });

        \Blade::directive('date', function ($expression) {
            $format = company_date_format();
            return "<?php 
            if ({$expression} instanceof \\Carbon\\Carbon) {
                try {
                    echo \\Morilog\\Jalali\\Jalalian::fromCarbon({$expression})->format('{$format}');
                } catch (\\Exception \$e) {
                    echo {$expression};
                }
            } else {
                echo {$expression};
            }
        ?>";
        });
    }

    /**
     * Load views.
     *
     * @return void
     */
    public function loadViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'jalali-date');
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
     * Load routes.
     *
     * @return void
     */
    public function loadRoutes()
    {
        if (app()->routesAreCached()) {
            return;
        }

        $routes = [
            'admin.php',
            'portal.php',
        ];

        foreach ($routes as $route) {
            $this->loadRoutesFrom(__DIR__ . '/../Routes/' . $route);
        }
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
}
