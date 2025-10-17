<?php

namespace Modules\JalaliDate\Traits;

use Morilog\Jalali\Jalalian;
use Morilog\Jalali\CalendarUtils;
use Illuminate\Support\Facades\Log;

trait JalaliDateConverter
{
    /**
     * Converts date fields from Jalali to Gregorian before saving.
     * This method should be called by an Observer or within the model's saving method.
     *
     * @param array $attributes The model's attributes array.
     * @return void
     */
    public function convertJalaliDatesToMiladi(array &$attributes): void
    {
        $dateFields = [
            'paid_at',
            'issued_at',
            'due_at',
            'deleted_at',
            'date',
            'document_date',
            'invoiced_at',
            'created_at',
            'updated_at',
            'birth_day',
            'hired_at',
        ];

        foreach ($dateFields as $field) {
            if (isset($attributes[$field]) && !empty($attributes[$field])) {
                $dateString = $attributes[$field];
                [$y, $m, $d] = explode('-', substr($dateString, 0, 10));
                $format = 'Y-m-d H:i:s';

                $isJalaliValid = CalendarUtils::checkDate($y, $m, $d, true);
                $isGregorianValid = CalendarUtils::checkDate($y, $m, $d, false);

                // Determine if date is Jalali
                $isJalali = $isJalaliValid && !$isGregorianValid
                    ? true
                    : (!$isJalaliValid && $isGregorianValid ? false : $y < 1800);

                if ($isJalali) {
                    try {
                        $attributes[$field] = Jalalian::fromFormat($format, $dateString)
                            ->toCarbon()
                            ->format($format);
                    } catch (\Exception $e) {
                        Log::error("JalaliDateConverter: Jalali→Miladi conversion failed for {$dateString}: " . $e->getMessage());
                    }
                } else {
                    Log::warning("JalaliDateConverter: Invalid Jalali date: {$dateString}");
                }
            }
        }
    }
}
