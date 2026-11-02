<?php

declare(strict_types=1);

namespace Subarist\WaterholeUserCover\View\Components;

use Illuminate\View\Component;
use Subarist\WaterholeUserCover\Models\UserCover;
use Waterhole\Models\User;

class UserCoverInfo extends Component
{
    public function __construct(
        public User $user,
    ) {}

    public function render(): string
    {
        $cover = UserCover::where('user_id', $this->user->id)->first();

        if (!$cover) {
            return '';
        }

        return sprintf(
            '<span class="user-cover-data" data-user-cover-url="%s" data-user-cover-x="%d" data-user-cover-y="%d" hidden></span>',
            e($cover->url),
            $cover->position_x,
            $cover->position_y,
        );
    }
}
