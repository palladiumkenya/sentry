<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;

class ToggleEtlStatus extends Action implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function handle(ActionFields $fields, Collection $models)
    {
        $models->each(function ($model) use ($fields) {
            try {
                $model->etl = $fields->status;
                $model->save();
                $this->markAsFinished($model);
            } catch (\Exception $e) {
                $this->markAsFailed($model, $e);
            }
        });
    }

    public function fields()
    {
        return [
            Select::make('Status')->options([
                1 => 'Activate',
                0 => 'Deactivate',
            ])->required(),
        ];
    }
}
