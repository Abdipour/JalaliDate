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
use App\Events\Document\DocumentTemplates;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use App\Events\Document\DocumentPrinting;

class Main extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->loadConfig();
        /* $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];
            $locale = $app['config']['app.locale'];

            $translator = new \Modules\JalaliDate\Http\Overrides\CustomTranslator($loader, $locale);
            $translator->setFallback($app['config']['app.fallback_locale']);

            return $translator;
        });

        $this->app->alias('translator', \Illuminate\Contracts\Translation\Translator::class); */

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

        //Transaction::observe(JalaliObserver::class);
        //Document::observe(JalaliObserver::class);
        //DocumentTotal::observe(JalaliObserver::class);

        /* if (class_exists('Modules\Employees\Models\Employee', false)) {
            \Modules\Employees\Models\Employee::observe(JalaliObserver::class);
        } */

        FacadesView::composer('components.script', function (View $view) {
            //$view->getFactory()->startPush('scripts', view('jalali-date::jalali-flatpickr-init'));
            $view->getFactory()->startPush('scripts', view('jalali-date::jalali-datepicker-init'));
        });

        FacadesView::composer('components.form.group.date', function (View $view) {
            $data = $view->getData();
            $name = $data['name'] ?? null;
            if (!$name) {
                return;
            }
            $stackName = $name . '_input_end';
            $settings = setting('jalali-date');
            $switchHtml = Blade::render(
                '<div class="datepicker-switcher" style="display:none">
                    <input id="tgl_{{$dateName}}" type="checkbox" {{ $active? "checked":""}}>
                    <label class="tgl-btn" data-tg-off="{{$disable}}" data-tg-on="{{$enable}}" for="tgl_{{$dateName}}"></label>
                </div>',
                [
                    'dateName' => $name,
                    'enable' => trans('jalali-date::general.datepicker_switcher.enable'),
                    'disable' => trans('jalali-date::general.datepicker_switcher.disable'),
                    'active' => (bool)$settings['jalalidatepicker']
                ]
            );

            $view->getFactory()->startPush($stackName, $switchHtml);

            /*if (isset($data['value']) && str_contains($data['value'], '-')) {
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
            } */
        });

        /* FacadesView::composer('components.documents.form.metadata', function (View $view) {
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
        }); */

        FacadesView::composer('components.transactions.show.create', function (View $view) {
            $settings = setting('jalali-date');
            if ((bool)$settings['jalalidate']) {
                $data = $view->getData();
                $data['transaction']->created_at = \Morilog\Jalali\Jalalian::fromCarbon($data['transaction']->created_at);
                $view->with($data);
            }
        });

        FacadesView::composer('components.documents.show.create', function (View $view) {
            $settings = setting('jalali-date');
            if ((bool)$settings['jalalidate']) {
                $data = $view->getData();
                $data['created_date'] = '<span class="font-medium">' . \Morilog\Jalali\Jalalian::fromCarbon($data['document']->created_at)->format($data['document']->getCompanyDateFormat()) . '</span>';
                $view->with($data);
            }
        });

        FacadesView::composer('components.documents.show.send', function (View $view) {
            $settings = setting('jalali-date');
            if ((bool)$settings['jalalidate']) {
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
            }
        });

        FacadesView::composer('components.date', function (View $view) {
            $settings = setting('jalali-date');
            if ((bool)$settings['jalalidate']) {
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
            }
        });

        \Blade::directive('date', function ($expression) {
            $settings = setting('jalali-date');
            if ((bool)$settings['jalalidate']) {
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
            }
        });

        FacadesView::composer('components.layouts.print.head', function (View $view) {
            $view->getFactory()->startPush('stylesheet', view('jalali-date::typography-css'));
        });
        FacadesView::composer('jalali-date::components.documents.template.persian', function (View $view) {
            $view->getFactory()->startPush('stylesheet', view('jalali-date::persian-invoice-css'));
        });
        FacadesView::composer('components.layouts.admin.head', function (View $view) {
            $view->getFactory()->startPush('stylesheet', view('jalali-date::typography-css'));
        });

        Event::listen(DocumentTemplates::class, function (DocumentTemplates $event) {
            if ($event->type != 'invoice') {
                return;
            }

            $event->templates->templates->push([
                'id' => 'persian',
                'name' => 'فارسی',
                'image' => asset('modules/JalaliDate/img/persian_preview.png'),
                'template' => 'persian'
            ]);
        });

        \Illuminate\Support\Facades\View::composer(
            ['settings.invoice.edit'],
            'Modules\JalaliDate\Http\Controllers\Settings\Invoice'
        );

        Blade::component('documents.template.persian', \Modules\JalaliDate\View\Components\Documents\Template\Persian::class);

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
