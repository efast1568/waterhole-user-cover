<x-waterhole::layout :title="__('waterhole-user-cover::cover.edit-title')">
    <div class="container section">
    <turbo-frame id="modal">
        <x-waterhole::dialog title="{{ __('waterhole-user-cover::cover.edit-title') }}" class="dialog--sm user-cover-dialog">
            <x-slot:header>
                <button
                    type="button"
                    class="btn btn--transparent btn--icon -m-xs push-end"
                    aria-label="{{ __('waterhole-user-cover::cover.close') }}"
                    data-action="click->modal#hide"
                >
                    @icon('tabler-x')
                </button>
            </x-slot:header>

            @include('waterhole-user-cover::actions.edit-cover', ['user' => $user])
        </x-waterhole::dialog>
    </turbo-frame>
    </div>
</x-waterhole::layout>
