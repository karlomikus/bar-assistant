<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Image\Image;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Image\ImageRepository;
use BarAssistant\Domain\Support\Authors;
use BarAssistant\Domain\Support\RecordTimestamps;
use BarAssistant\Domain\User\UserId;
use Kami\Cocktail\Models\Image as Model;

final class EloquentImageRepository implements ImageRepository
{
    public function save(Image $image): Image
    {
        $model = Model::findOrNew($image->getId()?->value);

        $model->file_path = $image->getPath();
        $model->placeholder_hash = $image->getPlaceholderHash();
        $model->copyright = $image->getCopyright();
        $model->sort = $image->getSort();
        $model->updated_user_id = $image->getAuthors()->getCreatedBy()->value;
        $model->created_at = $image->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
        if ($image->getAuthors()->isUpdated()) {
            $model->updated_user_id = $image->getAuthors()->getUpdatedBy()?->value;
            $model->updated_at = $image->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }
        $model->save();

        return self::map($model);
    }

    public function findMany(array $ids): array
    {
        $models = Model::whereIn('id', array_map(fn(ImageId $id) => $id->value, $ids))->get();

        $ingredients = [];
        /** @var Model $model */
        foreach ($models as $model) {
            $ingredients[] = self::map($model);
        }

        return $ingredients;
    }

    private static function map(Model $model): Image
    {
        return new Image(
            path: $model->file_path,
            authors: Authors::createdBy(new UserId($model->created_user_id))->updatedBy($model->updated_user_id ? new UserId($model->updated_user_id) : null),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
            placeholderHash: $model->placeholder_hash,
            copyright: $model->copyright,
            sort: $model->sort,
        )->setId(new ImageId($model->id));
    }
}
