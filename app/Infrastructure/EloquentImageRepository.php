<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Common\File;
use BarAssistant\Domain\Image\Image;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use Kami\Cocktail\Models\Image as Model;
use BarAssistant\Domain\Image\ImageRepository;
use BarAssistant\Domain\Common\RecordTimestamps;

final class EloquentImageRepository implements ImageRepository
{
    public function delete(ImageId $id): void
    {
        $image = Model::findOrFail($id);
        $image->delete();
    }

    public function findById(ImageId $id): ?Image
    {
        $model = Model::find($id->value);
        if (!$model) {
            return null;
        }

        return self::map($model);
    }

    public function save(Image $image): Image
    {
        $model = Model::findOrNew($image->getId()?->value);

        $model->file_path = $image->getFile()->path;
        $model->placeholder_hash = $image->getFile()->placeholderHash;
        $model->copyright = $image->getCopyright();
        $model->file_extension = $image->getFile()->extension;
        $model->sort = $image->getSort();
        $model->created_user_id = $image->getAuthors()->getCreatedBy()->value;
        $model->created_at = $image->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
        if ($image->getAuthors()->isUpdated()) {
            $model->updated_user_id = $image->getAuthors()->getUpdatedBy()?->value;
            $model->updated_at = $image->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }
        $model->save();

        return self::map($model);
    }

    private static function map(Model $model): Image
    {
        return Image::create(
            file: File::from($model->file_path, $model->file_extension, $model->placeholder_hash),
            authors: Authors::createdBy(new UserId($model->created_user_id))->updatedBy($model->updated_user_id ? new UserId($model->updated_user_id) : null),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
            copyright: $model->copyright,
            sort: $model->sort,
        )->setId(new ImageId($model->id));
    }
}
