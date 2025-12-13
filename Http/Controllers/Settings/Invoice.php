<?php

namespace Modules\JalaliDate\Http\Controllers\Settings;

use Illuminate\View\View;

class Invoice
{
    public function compose(View $view): void
    {
        $view->setPath(view('jalali-date::settings.invoice.edit')->getPath());
    }
}
