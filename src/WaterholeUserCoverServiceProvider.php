<?php

declare(strict_types=1);

namespace Subarist\WaterholeUserCover;

use Illuminate\Support\Facades\Gate;
use Subarist\WaterholeUserCover\Actions\EditCover;
use Subarist\WaterholeUserCover\Forms\GroupUserCoverPermissions;
use Subarist\WaterholeUserCover\View\Components\UserCoverInfo;
use Waterhole\Extend;
use Waterhole\Models\User;
use Waterhole\Translation\FluentTranslator;

class WaterholeUserCoverServiceProvider extends Extend\ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(FluentTranslator::class)->addNamespace(
            'waterhole-user-cover',
            __DIR__ . '/../resources/lang',
        );

        $this->app['view']->prependNamespace(
            'waterhole',
            __DIR__ . '/../resources/views',
        );

        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'waterhole-user-cover',
        );

        Gate::define(
            'waterhole.user-cover',
            fn (?User $user): bool => $user
                ? \Waterhole::permissions()->can(
                    $user,
                    'user-cover',
                    User::class,
                )
                : false,
        );
    }

    public function register(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadRoutesFrom(__DIR__ . '/../routes/forum.php');

        $this->app->extend(
            \Waterhole\Extend\Forms\GroupForm::class,
            function ($form) {
                $form->permissions->add(
                    GroupUserCoverPermissions::class,
                    'user-cover',
                );

                $permissions = $form->permissions;

                $form->replace(
                    'permissions',
                    function ($model) use ($permissions) {
                        if ($model?->isAdmin()) {
                            return null;
                        }

                        return new \Waterhole\Forms\FormSection(
                            __('waterhole::cp.group-permissions-title'),
                            $permissions->components(compact('model')),
                        );
                    },
                );

                return $form;
            },
        );

        $this->extend(function (Extend\Core\Actions $actions) {
            $actions
                ->for(User::class)
                ->add(EditCover::class, 'edit-cover', 25);
        });

        $this->extend(function (Extend\Ui\UserInfo $info) {
            $info->add(UserCoverInfo::class, 'cover');
        });

        $this->extend(function (Extend\Assets\Script $scripts) {
            $scripts->add(__DIR__ . '/../dist/user-cover.js');
        });

        $this->extend(function (Extend\Assets\Stylesheet $stylesheets) {
            $stylesheets->add(__DIR__ . '/../dist/user-cover.css');
        });
    }
}
