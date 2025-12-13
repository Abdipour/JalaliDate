<?php

namespace Modules\JalaliDate\View\Components\Documents\Template;

use App\Abstracts\View\Components\Documents\Template as Component;

class Persian extends Component
{

    public $companyWebsite;

    public function render()
    {
        $settings = setting('jalali-date');
        $fields = $settings['invoice_fields'];
        $this->hideCompanyLogo = !in_array('company_logo', $fields);
        $this->hideCompanyName = !in_array('company_name', $fields);
        $this->hideCompanyAddress = !in_array('company_address', $fields);
        $this->hideCompanyTaxNumber = !in_array('company_tax_number', $fields);
        $this->hideCompanyPhone = !in_array('company_phone', $fields);
        $this->hideCompanyEmail = !in_array('company_email', $fields);
        $this->hideContactName = !in_array('contact_name', $fields);
        $this->hideContactAddress = !in_array('contact_address', $fields);
        $this->hideContactTaxNumber = !in_array('contact_tax_number', $fields);
        $this->hideContactPhone = !in_array('contact_phone', $fields);
        $this->hideContactEmail = !in_array('contact_email', $fields);
        $this->hideOrderNumber = !in_array('order_number', $fields);
        $this->hideDocumentNumber = !in_array('document_number', $fields);
        $this->hideIssuedAt = !in_array('issued_at', $fields);
        $this->hideDueAt = !in_array('due_at', $fields);

        $this->textContactInfo = $settings['invoice_contact_info'] ?? trans('jalali-date::invoices.bill_to');

        $this->companyWebsite = $settings['company_website'] ?? '';

        return view('jalali-date::components.documents.template.persian');
    }
}
