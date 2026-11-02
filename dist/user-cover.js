(() => {
    const applyUserCovers = () => {
        document.querySelectorAll('.user-profile__card').forEach((card) => {
            const data = card.querySelector('.user-cover-data');

            if (!data) {
                card.style.removeProperty('background-image');
                card.style.removeProperty('background-position');
                return;
            }

            const url = data.dataset.userCoverUrl;

            if (!url) {
                return;
            }

            const escapedUrl = url
                .replace(/\\/g, '\\\\')
                .replace(/"/g, '\\"')
                .replace(/\r/g, '')
                .replace(/\n/g, '');

            const x = Number.parseInt(data.dataset.userCoverX ?? '50', 10);
            const y = Number.parseInt(data.dataset.userCoverY ?? '50', 10);

            card.style.backgroundImage = `url("${escapedUrl}")`;
            card.style.backgroundSize = 'cover';
            card.style.backgroundRepeat = 'no-repeat';
            card.style.backgroundPosition = `${x}% ${y}%`;
        });
    };

    const syncProfileCover = (data) => {
        document.querySelectorAll('.user-profile__card').forEach((card) => {
            if (!data.url) {
                card.style.removeProperty('background-image');
                card.style.removeProperty('background-position');
                return;
            }

            const escapedUrl = data.url
                .replace(/\\/g, '\\\\')
                .replace(/"/g, '\\"')
                .replace(/\r/g, '')
                .replace(/\n/g, '');

            const x = Number.parseInt(data.position_x ?? '50', 10);
            const y = Number.parseInt(data.position_y ?? '50', 10);

            card.style.backgroundImage = `url("${escapedUrl}")`;
            card.style.backgroundSize = 'cover';
            card.style.backgroundRepeat = 'no-repeat';
            card.style.backgroundPosition = `${x}% ${y}%`;
        });
    };

    const getEditor = (element) => element.closest('.user-cover-editor');

    // Status messages are intentionally not rendered in the cover editor.
    const setStatus = (_editor, _message, _isError = false) => {};

    const getCsrfToken = (editor) => {
        return document.querySelector('meta[name="csrf-token"]')?.content
            || editor.closest('form')?.querySelector('input[name="_token"]')?.value
            || document.querySelector('input[name="_token"]')?.value
            || '';
    };

    const requestJson = async (editor, url, options = {}) => {
        const token = getCsrfToken(editor);
        const headers = new Headers(options.headers || {});

        headers.set('Accept', 'application/json');

        if (token) {
            headers.set('X-CSRF-TOKEN', token);
        }

        const response = await fetch(url, {
            ...options,
            headers,
            credentials: 'same-origin',
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            // The error below will report a generic message if the response
            // is not JSON.
        }

        if (!response.ok) {
            const validationMessage = payload.errors
                ? Object.values(payload.errors).flat().join(' ')
                : '';

            throw new Error(
                validationMessage
                || payload.message
                || `Request failed (${response.status}).`
            );
        }

        return payload;
    };

    const setBusy = (editor, busy) => {
        editor.dataset.busy = busy ? 'true' : 'false';

        editor.querySelectorAll(
            '[data-cover-upload], [data-cover-save], [data-cover-remove]'
        ).forEach((button) => {
            button.disabled = busy || button.dataset.unavailable === 'true';
        });

        const range = editor.querySelector('[data-cover-position]');

        if (range) {
            range.disabled = busy || editor.dataset.hasCover !== 'true';
        }
    };

    const updateCover = (editor, data) => {
        const image = editor.querySelector('[data-cover-image]');
        const empty = editor.querySelector('[data-cover-empty]');
        const range = editor.querySelector('[data-cover-position]');
        const positionValue = editor.querySelector('[data-position-value]');

        if (data.url && image) {
            image.src = data.url;
            image.hidden = false;

            if (empty) {
                empty.hidden = true;
            }
        }

        if (typeof data.position_x === 'number') {
            editor.dataset.positionX = String(data.position_x);
        }

        if (typeof data.position_y === 'number') {
            editor.dataset.positionY = String(data.position_y);

            if (range) {
                range.value = String(data.position_y);
            }

            if (positionValue) {
                positionValue.textContent = `${data.position_y}%`;
            }

            if (image) {
                image.style.objectPosition = `50% ${data.position_y}%`;
            }
        }

        editor.dataset.hasCover = data.url ? 'true' : 'false';

        const saveButton = editor.querySelector('[data-cover-save]');
        const removeButton = editor.querySelector('[data-cover-remove]');

        [saveButton, removeButton].forEach((button) => {
            if (button) {
                button.disabled = !data.url;
                button.dataset.unavailable = data.url ? 'false' : 'true';
            }
        });

        if (range) {
            range.disabled = !data.url;
        }

        syncProfileCover(data);
    };

    document.addEventListener('input', (event) => {
        const range = event.target.closest('[data-cover-position]');

        if (!range) {
            return;
        }

        const editor = getEditor(range);

        if (!editor || editor.dataset.busy === 'true') {
            return;
        }

        const y = Number.parseInt(range.value, 10);
        const image = editor.querySelector('[data-cover-image]');
        const positionValue = editor.querySelector('[data-position-value]');

        if (image && !image.hidden) {
            image.style.objectPosition = `50% ${y}%`;
        }

        if (positionValue) {
            positionValue.textContent = `${y}%`;
        }

        editor.dataset.positionY = String(y);
    });

    document.addEventListener('click', async (event) => {
        const uploadButton = event.target.closest('[data-cover-upload]');
        const saveButton = event.target.closest('[data-cover-save]');
        const removeButton = event.target.closest('[data-cover-remove]');

        if (!uploadButton && !saveButton && !removeButton) {
            return;
        }

        const editor = getEditor(event.target);

        if (!editor || editor.dataset.busy === 'true') {
            return;
        }

        if (uploadButton) {
            editor.querySelector('[data-cover-file]')?.click();
            return;
        }

        if (removeButton && !window.confirm('Remove your cover image?')) {
            return;
        }

        setBusy(editor, true);
        setStatus(editor, '');

        try {
            if (saveButton) {
                const range = editor.querySelector('[data-cover-position]');
                const y = Number.parseInt(range.value, 10);

                const data = await requestJson(
                    editor,
                    editor.dataset.positionUrl,
                    {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ position_y: y }),
                    }
                );

                updateCover(editor, data);
                setStatus(editor, 'Cover position saved.');
            } else if (removeButton) {
                await requestJson(editor, editor.dataset.removeUrl, {
                    method: 'DELETE',
                });

                const image = editor.querySelector('[data-cover-image]');
                const empty = editor.querySelector('[data-cover-empty]');

                if (image) {
                    image.removeAttribute('src');
                    image.hidden = true;
                }

                if (empty) {
                    empty.hidden = false;
                }

                updateCover(editor, { url: null, position_x: 50, position_y: 50 });
                setStatus(editor, 'Cover removed.');
            }
        } catch (error) {
            setStatus(editor, error.message || 'Something went wrong.', true);
        } finally {
            setBusy(editor, false);
        }
    });

    document.addEventListener('change', async (event) => {
        const input = event.target.closest('[data-cover-file]');

        if (!input || !input.files?.length) {
            return;
        }

        const editor = getEditor(input);

        if (!editor || editor.dataset.busy === 'true') {
            input.value = '';
            return;
        }

        const file = input.files[0];

        if (file.size > 2 * 1024 * 1024) {
            setStatus(editor, 'The file must not exceed 2 MB.', true);
            input.value = '';
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!allowedTypes.includes(file.type)) {
            setStatus(editor, 'Please choose a JPG, PNG, or WebP image.', true);
            input.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('cover', file);
        formData.append('position_x', editor.dataset.hasCover === 'true'
            ? editor.dataset.positionX || '50'
            : '50');
        formData.append('position_y', editor.dataset.hasCover === 'true'
            ? editor.querySelector('[data-cover-position]').value
            : '50');

        setBusy(editor, true);
        setStatus(editor, 'Uploading cover…');

        try {
            const data = await requestJson(editor, editor.dataset.uploadUrl, {
                method: 'POST',
                body: formData,
            });

            updateCover(editor, data);
            setStatus(editor, 'Cover uploaded successfully.');
        } catch (error) {
            setStatus(editor, error.message || 'Upload failed.', true);
        } finally {
            setBusy(editor, false);
            input.value = '';
        }
    });

    const initializeEditors = (root = document) => {
        root.querySelectorAll('.user-cover-editor').forEach((editor) => {
            const range = editor.querySelector('[data-cover-position]');
            const image = editor.querySelector('[data-cover-image]');

            editor.dataset.hasCover = image && image.getAttribute('src')
                ? 'true'
                : 'false';

            editor.dataset.busy = 'false';

            if (range) {
                editor.dataset.positionY = range.value;
                range.disabled = editor.dataset.hasCover !== 'true';
            }

            editor.querySelectorAll('[data-cover-save], [data-cover-remove]')
                .forEach((button) => {
                    button.dataset.unavailable =
                        editor.dataset.hasCover === 'true' ? 'false' : 'true';
                    button.disabled = editor.dataset.hasCover !== 'true';
                });
        });
    };

    const initialize = () => {
        applyUserCovers();
        initializeEditors();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }

    document.addEventListener('turbo:load', initialize);
    document.addEventListener('turbo:frame-load', initialize);
})();
