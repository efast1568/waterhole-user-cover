<?php

declare(strict_types=1);

namespace Subarist\WaterholeUserCover\Actions;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\HtmlString;
use Waterhole\Actions\Link;
use Waterhole\Models\Model;
use Waterhole\Models\User;

class EditCover extends Link
{
    public function appliesTo(EloquentModel $model): bool
    {
        return $model instanceof User;
    }

    public function authorize(?User $user, EloquentModel $model): bool
    {
        return $user !== null
            && $model instanceof User
            && (
                $user->isAdmin()
                || (
                    $user->is($model)
                    && $user->can('waterhole.user-cover', User::class)
                )
            );
    }

    public function label(Collection $models): string
    {
        return __('waterhole-user-cover::cover.action');
    }

    public function icon(Collection $models): ?string
    {
        return 'tabler-photo';
    }

    public function url(Model $model): string
    {
        return route('users.cover.edit', ['user' => $model]);
    }

    public function attributes(Collection $models): array
    {
        return ['data-turbo-frame' => 'modal'];
    }

    public function render(Collection $models, array $attributes): HtmlString
    {
        unset($attributes['data-turbo-frame']);

        return parent::render($models, $attributes);
    }
}