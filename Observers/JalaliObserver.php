<?php

namespace Modules\JalaliDate\Observers;

use App\Abstracts\Observer;
use Illuminate\Database\Eloquent\Model;
use Modules\JalaliDate\Traits\JalaliDateConverter;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class JalaliObserver extends Observer
{
    use JalaliDateConverter;

    /**
     * Handle the saving event for the model.
     * Converts Jalali date strings to Gregorian before saving.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function saving(Model $model): void
    {
        $attributes = $model->getAttributes();

        $this->convertJalaliDatesToMiladi($attributes);

        $model->setRawAttributes($attributes);
    }
}
