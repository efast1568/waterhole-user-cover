<?php

declare(strict_types=1);

namespace Subarist\WaterholeUserCover\Services;

use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Subarist\WaterholeUserCover\Models\UserCover;
use Waterhole\Models\User;

class UserCoverService
{
    public const MAX_UPLOAD_KB = 2048;

    /**
     * Upload and replace the user's cover image.
     */
    public function upload(
        User $user,
        UploadedFile $file,
        int $positionX = 50,
        int $positionY = 50,
    ): UserCover {
        $this->validatePosition($positionX, $positionY);

        // Validate the detected content MIME type, not only the filename.
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, [
            'image/jpeg',
            'image/png',
            'image/webp',
        ], true)) {
            throw ValidationException::withMessages([
                'cover' => '請上傳有效的 JPG、PNG 或 WebP 圖片。',
            ]);
        }

        if ($file->getSize() > self::MAX_UPLOAD_KB * 1024) {
            throw ValidationException::withMessages([
                'cover' => '封面圖片大小不可超過 2 MB。',
            ]);
        }

        try {
            $image = Image::decode($file)->scaleDown(1600, 1600);
            $encoded = $image->encode(new WebpEncoder(quality: 80));
            $contents = $encoded->toString();
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'cover' => '圖片無法解碼，請確認檔案是有效的 JPG、PNG 或 WebP 圖片。',
            ]);
        }

        $cover = UserCover::where('user_id', $user->id)->first();
        $disk = $this->disk();

        $newPath = sprintf(
            'covers/%d/%s.webp',
            $user->id,
            bin2hex(random_bytes(16)),
        );

        if (!$disk->put($newPath, $contents, ['visibility' => 'public'])) {
            throw new \RuntimeException('Unable to store user cover image.');
        }

        // The new file must be removed if the database transaction rolls back.
        DB::afterRollBack(function () use ($disk, $newPath): void {
            $this->deleteFileQuietly($disk, $newPath);
        });

        try {
            if ($cover) {
                $oldPath = $cover->path;

                $cover->update([
                    'path' => $newPath,
                    'position_x' => $positionX,
                    'position_y' => $positionY,
                ]);
            } else {
                $oldPath = null;

                $cover = UserCover::create([
                    'user_id' => $user->id,
                    'path' => $newPath,
                    'position_x' => $positionX,
                    'position_y' => $positionY,
                ]);
            }

            // Never remove the old file before the DB transaction commits.
            if ($oldPath && $oldPath !== $newPath) {
                DB::afterCommit(function () use ($disk, $oldPath): void {
                    $this->deleteFileQuietly($disk, $oldPath);
                });
            }
        } catch (\Throwable $e) {
            throw $e;
        }

        return $cover;
    }

    /**
     * Update only the Y position while preserving X.
     */
    public function updatePosition(User $user, int $positionY): ?UserCover
    {
        if ($positionY < 0 || $positionY > 100) {
            throw ValidationException::withMessages([
                'position_y' => '垂直位置必須介於 0 到 100。',
            ]);
        }

        $cover = UserCover::where('user_id', $user->id)->first();

        if (!$cover) {
            return null;
        }

        $cover->update([
            'position_y' => $positionY,
        ]);

        return $cover->refresh();
    }

    /**
     * Remove the user's cover.
     */
    public function remove(User $user): bool
    {
        $cover = UserCover::where('user_id', $user->id)->first();

        if (!$cover) {
            return false;
        }

        $oldPath = $cover->path;
        $disk = $this->disk();

        $cover->delete();

        DB::afterCommit(function () use ($disk, $oldPath): void {
            $this->deleteFileQuietly($disk, $oldPath);
        });

        return true;
    }

    private function disk(): FilesystemContract
    {
        return Storage::disk(config('waterhole.uploads.disk'));
    }

    private function validatePosition(int $positionX, int $positionY): void
    {
        if ($positionX < 0 || $positionX > 100) {
            throw ValidationException::withMessages([
                'position_x' => '水平位置必須介於 0 到 100。',
            ]);
        }

        if ($positionY < 0 || $positionY > 100) {
            throw ValidationException::withMessages([
                'position_y' => '垂直位置必須介於 0 到 100。',
            ]);
        }
    }

    private function deleteFileQuietly(
        FilesystemContract $disk,
        string $path,
    ): void {
        try {
            if (!$disk->delete($path)) {
                Log::warning('Unable to delete a user cover file.', [
                    'path' => $path,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Error while deleting a user cover file.', [
                'path' => $path,
                'exception' => $e,
            ]);
        }
    }
}
