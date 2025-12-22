<?php

namespace Modules\JalaliDate\Listeners;

use App\Events\Document\DocumentTemplates;

class AddInvoiceTemplate
{
    public function handle(DocumentTemplates $event): void
    {
        \Log::debug(var_export([$event->type, $event->type != 'invoice' && $event->type != 'invoice-recurring'], true));
        if ($event->type != 'invoice' && $event->type != 'invoice-recurring') {
            return;
        }

        $event->templates->templates->push([
            'id'       => 'persian',
            'name'     => 'فارسی',
            'image'    => asset('modules/JalaliDate/Resources/assets/img/persian_preview.png'),
            'template' => 'persian'
        ]);
    }
}
