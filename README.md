# Waterhole User Cover

A Waterhole extension that adds customizable cover images to user profiles.

## Features

- Upload a cover image to a user profile.
- Adjust the cover image position.
- Remove an existing cover image.
- Allow administrators to manage user covers.
- Allow users with the `waterhole.user-cover` permission to manage their own cover.
- Display a generated background color when no cover image is configured.
- Store uploaded images using the configured Waterhole uploads disk.

<img width="1128" height="489" alt="螢幕擷取畫面 2026-11-02 150958" src="https://github.com/user-attachments/assets/364af9d1-47cb-441e-b4ff-c054ef43368c" />

## Image handling

Supported upload formats: JPEG, PNG, and WebP.

Uploaded images are resized down to fit within a maximum dimension of 1600 x 1600 pixels while preserving their aspect ratio. Images are encoded as WebP at quality 80. Images smaller than the configured maximum dimensions are not enlarged.

<img width="433" height="524" alt="螢幕擷取畫面 2026-11-02 151034" src="https://github.com/user-attachments/assets/9ed98542-aa2f-4afe-b8d1-7061502cd642" />

## Permissions

The extension uses the `waterhole.user-cover` permission. Administrators can manage user covers. Other users can manage their own cover when they have the required permission. Users cannot manage another user's cover unless they have administrative privileges.

## Storage

Uploaded files use the configured Waterhole uploads filesystem disk. The extension does not require a hard-coded storage path.

## Requirements

- PHP version compatible with the installed Waterhole version.
- Waterhole Core 0.7 or 0.8.
- `intervention/image-laravel` 4.x.

Waterhole Core 0.7 and 0.8 have been tested with this extension.

## Installation

Install the extension using Composer:

```bash
composer require subarist/waterhole-user-cover:^1.0
```

## Post-installation

Run the database migrations and clear the Laravel caches:

```bash
php artisan migrate --force
php artisan optimize:clear
```

The migration creates the database table required by the extension. Laravel only runs pending migrations, so this command does not recreate tables that have already been migrated.

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE).
