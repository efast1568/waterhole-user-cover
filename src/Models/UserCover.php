<?php

declare(strict_types=1);

namespace Subarist\WaterholeUserCover\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserCover extends Model
{
    protected $table = 'user_covers';

    protected $fillable = [
        'user_id',
        'path',
        'position_x',
        'position_y',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
    ];

    public function getUrlAttribute(): string
    {
        return Storage::disk(config('waterhole.uploads.disk'))->url($this->path);
    }
}
