<?php

namespace Modules\JalaliDate\Http\Overrides;

use Illuminate\Translation\Translator;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;

class CustomTranslator extends Translator implements TranslatorContract
{
    /**
     * Translate the given text and then apply the solar corrections..
     * * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @param bool $fallback
     * @return string|array
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        if (isset($replace['date'])) {
            $replace['date'] = $this->jalali_translate_month_names($replace['date']);
        }

        return parent::get($key, $replace, $locale, $fallback);
    }

    function jalali_translate_month_names(string $text): string
    {
        $month_map = [
            'ژانویه' => 'فروردین',
            'فوریه' => 'اردیبهشت',
            'مارس' => 'خرداد',
            'آوریل' => 'تیر',
            'مه' => 'مرداد',
            'ژوئن' => 'شهریور',
            'ژوئیه' => 'مهر',
            'اوت' => 'آبان',
            'سپتامبر' => 'آذر',
            'اکتبر' => 'دی',
            'نوامبر' => 'بهمن',
            'دسامبر' => 'اسفند',
        ];

        $patterns = [];
        $replacements = [];

        uksort($month_map, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        foreach ($month_map as $persian_miladi => $jalali_name) {
            $patterns[] = '/(?<!\p{L})' . preg_quote($persian_miladi, '/') . '(?!\p{L})/iu';
            $replacements[] = $jalali_name;
        }

        return preg_replace($patterns, $replacements, $text);
    }
}
