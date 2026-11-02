<?php

declare(strict_types=1);

namespace Subarist\WaterholeUserCover\Forms\Fields;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Subarist\WaterholeUserCover\Models\UserCover;
use Subarist\WaterholeUserCover\Services\UserCoverService;
use Waterhole\Forms\Field;
use Waterhole\Models\User;

class UserCoverField extends Field
{
    public function __construct(
        public User $model,
    ) {}

    public function shouldRender(): bool
    {
        $actor = auth()->user();

        return $actor !== null
            && ($actor->is($this->model) || $actor->isAdmin());
    }

    public function render(): string
    {
        return <<<'blade'
                <div class="field" role="group">
                    <div class="field__label">
                        {{ __('waterhole-user-cover::cover.label') }}
                    </div>

                    <div class="stack gap-md">
                        @if ($cover = \Subarist\WaterholeUserCover\Models\UserCover::where('user_id', $model->id)->first())
                            <div class="user-cover-preview">
                                <img
                                    src="{{ $cover->url }}"
                                    alt=""
                                    class="user-cover-preview__image"
                                    style="object-position: {{ $cover->position_x }}% {{ $cover->position_y }}%"
                                >
                            </div>
                        @endif

                        <x-waterhole::field
                            name="cover"
                            :description="__('waterhole-user-cover::cover.description')"
                        >
                            <input
                                type="file"
                                name="cover"
                                accept=".jpg,.jpeg,.png,.webp"
                            >
                        </x-waterhole::field>

                        @if ($cover)
                            <label class="choice">
                                <input
                                    type="checkbox"
                                    name="remove_cover"
                                    value="1"
                                >
                                {{ __('waterhole-user-cover::cover.remove') }}
                            </label>
                        @endif

                        <div class="row gap-md">
                            <x-waterhole::field
                                name="cover_position_x"
                                :label="__('waterhole-user-cover::cover.position-x')"
                            >
                                <input
                                    type="number"
                                    name="cover_position_x"
                                    min="0"
                                    max="100"
                                    step="1"
                                    value="{{ old('cover_position_x', $cover?->position_x ?? 50) }}"
                                >
                            </x-waterhole::field>

                            <x-waterhole::field
                                name="cover_position_y"
                                :label="__('waterhole-user-cover::cover.position-y')"
                            >
                                <input
                                    type="number"
                                    name="cover_position_y"
                                    min="0"
                                    max="100"
                                    step="1"
                                    value="{{ old('cover_position_y', $cover?->position_y ?? 50) }}"
                                >
                            </x-waterhole::field>
                        </div>
                    </div>
                </div>
            blade;
    }

    public function validating(Validator $validator): void
    {
        $validator->addRules([
            'cover' => [
                'nullable',
                File::types([
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ])->extensions([
                    'jpg',
                    'jpeg',
                    'png',
                    'webp',
                ])->max(
                    (int) config('waterhole.uploads.max_upload_size', 5120)
                ),
            ],
            'cover_position_x' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'cover_position_y' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
        ]);
    }

    public function saved(FormRequest $request): void
    {
        $cover = UserCover::where('user_id', $this->model->id)->first();

        $positionX = (int) $request->validated(
            'cover_position_x',
            $cover?->position_x ?? 50,
        );

        $positionY = (int) $request->validated(
            'cover_position_y',
            $cover?->position_y ?? 50,
        );

        $service = app(UserCoverService::class);

        if ($request->boolean('remove_cover')) {
            $service->remove($this->model);

            return;
        }

        $file = $request->file('cover');

        if (!$file) {
            if ($cover) {
                $service->updatePosition($this->model, $positionY);

                if ($cover->position_x !== $positionX) {
                    $cover->update([
                        'position_x' => $positionX,
                    ]);
                }
            }

            return;
        }

        $service->upload(
            $this->model,
            $file,
            $positionX,
            $positionY,
        );
    }

}
