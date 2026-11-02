<?php

declare(strict_types=1);

namespace Subarist\WaterholeUserCover\Forms;

use Illuminate\View\Component;
use Waterhole\Models\Group;

class GroupUserCoverPermissions extends Component
{
    public function __construct(
        public ?Group $model,
    ) {}

    public function render()
    {
        if ($this->model?->isGuest()) {
            return "";
        }

        return <<<'blade'
<div class="field">
    <div class="field__label">
        {{ __('waterhole-user-cover::cp.group-permission-user-cover-title') }}
    </div>

    <label class="choice">
        <input
            type="hidden"
            name="permissions[user][user-cover]"
            value="0"
        >
        <input
            type="checkbox"
            name="permissions[user][user-cover]"
            value="1"
            @checked(
                old(
                    'permissions.user.user-cover',
                    \Waterhole::permissions()->can(
                        $model,
                        'user-cover',
                        \Waterhole\Models\User::class
                    )
                )
            )
        >
        {{ __('waterhole-user-cover::cp.group-permission-user-cover-label') }}
    </label>
</div>
blade;
    }
}
