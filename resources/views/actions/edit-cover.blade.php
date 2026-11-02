@php
    $cover = \Subarist\WaterholeUserCover\Models\UserCover::where(
        'user_id',
        $user->id,
    )->first();
@endphp

<div
    class="stack gap-lg user-cover-editor"
    data-upload-url="{{ route('users.cover.upload', $user) }}"
    data-position-url="{{ route('users.cover.position', $user) }}"
    data-remove-url="{{ route('users.cover.remove', $user) }}"
    data-position-x="{{ $cover?->position_x ?? 50 }}"
    data-position-y="{{ $cover?->position_y ?? 50 }}"
>
    <div class="Alert-body">
        {{ __('waterhole-user-cover::cover.recommended-format') }}
        {{ __('waterhole-user-cover::cover.description') }}
    </div>

    <div
        class="user-cover-editor__preview" aria-live="polite">
        @if ($cover)
            <img
                src="{{ $cover->url }}"
                alt="Cover preview"
                class="user-cover-editor__image"
                data-cover-image
                style="object-position: 50% {{ $cover->position_y }}%"
            >
        @else
            <img
                src=""
                alt="Cover preview"
                class="user-cover-editor__image"
                data-cover-image
                hidden
            >
            <div class="user-cover-editor__empty" data-cover-empty>
                {{ __('waterhole-user-cover::cover.no-cover-uploaded') }}
            </div>
        @endif
    </div>

    <div class="user-cover-editor__field">
        <label class="form-label" for="cover-position-y">
            {{ __('waterhole-user-cover::cover.cover-position-desktop') }}
            <span data-position-value>{{ $cover?->position_y ?? 50 }}%</span>
        </label>

        <input
            id="cover-position-y"
            class="user-cover-editor__range"
            type="range"
            min="0"
            max="100"
            step="1"
            value="{{ $cover?->position_y ?? 50 }}"
            @disabled(!$cover)
            data-cover-position
        >

    </div>

    <div class="stack gap-sm user-cover-editor__buttons">
        <button
            type="button"
            class="btn"
            data-cover-save
            @disabled(!$cover)
        >@icon('tabler-photo-pin') {{ __('waterhole-user-cover::cover.save-position') }}</button>

        <input
            type="file"
            accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
            hidden
            data-cover-file
        >

        <button
            type="button"
            class="btn bg-danger"
            data-cover-upload
        >@icon('tabler-upload') {{ __('waterhole-user-cover::cover.upload') }}</button>

        <button
            type="button"
            class="btn user-cover-editor__remove"
            data-cover-remove
            @disabled(!$cover)
        >@icon('tabler-trash') {{ __('waterhole-user-cover::cover.remove-cover') }}</button>
    </div>
</div>
