<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Brick\Money\Currency;
use Symfony\Component\Uid\Ulid;
use BarAssistant\Domain\Bar\Bar;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Slug;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\BarStatus;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Bar\BarSettings;
use Kami\Cocktail\Models\Bar as ModelBar;
use BarAssistant\Domain\Bar\BarRepository;
use Kami\Cocktail\Models\Image as ModelImage;
use BarAssistant\Domain\Common\RecordTimestamps;

final class EloquentBarRepository implements BarRepository
{
    public function save(Bar $bar): Bar
    {
        $model = ModelBar::findOrNew($bar->getId()?->value);

        $model->name = (string) $bar->getName();
        $model->subtitle = $bar->getSubtitle();
        $model->description = $bar->getDescription();
        $model->is_public = $bar->isPublic();
        $model->status = match ($bar->getStatus()) {
            BarStatus::Active => 'active',
            BarStatus::Provisioning => 'provisioning',
            BarStatus::Deactivated => 'deactivated',
        };
        $model->created_at = $bar->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
        $model->created_user_id = $bar->getAuthors()->getCreatedBy()->value;
        if ($bar->getRecordTimestamps()->wasUpdated()) {
            $model->updated_at = $bar->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }
        if ($bar->getAuthors()->isUpdated() && $bar->getAuthors()->getUpdatedBy() !== null) {
            $model->updated_user_id = $bar->getAuthors()->getUpdatedBy()->value;
        }

        $settings = $model->settings ?? [];
        if ($bar->getDefaultUnits()) {
            $settings['default_units'] = $bar->getDefaultUnits()->value;
        }
        if ($bar->getDefaultCurrency()) {
            $settings['default_currency'] = $bar->getDefaultCurrency()->getCurrencyCode();
        }
        $model->settings = $settings;

        if ($bar->isInviteCodeEnabled()) {
            $model->invite_code = (string) new Ulid();
        } else {
            $model->invite_code = null;
        }

        $model->save();

        if (count($bar->getImages()) > 0) {
            $imageModels = ModelImage::findOrFail(array_map(fn (ImageId $img): int => $img->value, $bar->getImages()));
            $model->attachImages($imageModels);
        }

        return self::map($model);
    }

    public function findById(BarId $id): ?Bar
    {
        $model = ModelBar::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function delete(BarId $id): void
    {
        $model = ModelBar::find($id->value);
        if ($model === null) {
            return;
        }

        $model->delete();
    }

    private static function map(ModelBar $model): Bar
    {
        $modelBarSettings = is_array($model->settings) ? $model->settings : [];
        $defaultUnits = $modelBarSettings['default_units'] ?? null;
        $defaultCurrency = $modelBarSettings['default_currency'] ?? null;

        $barSettings = BarSettings::create(
            isInviteCodeEnabled: $model->invite_code !== null,
            defaultUnits: is_string($defaultUnits) ? Unit::from($defaultUnits) : null,
            defaultCurrency: is_string($defaultCurrency) || is_int($defaultCurrency) ? Currency::of($defaultCurrency) : null,
        );

        $createdAt = $model->created_at?->toDateTimeImmutable();
        if ($createdAt === null) {
            throw new \RuntimeException('Cannot map bar without a creation timestamp.');
        }

        $bar = Bar::create(
            name: Name::fromString($model->name),
            authors: Authors::createdBy(new UserId($model->created_user_id))->updatedBy($model->updated_user_id ? new UserId($model->updated_user_id) : null),
            recordTimestamps: RecordTimestamps::createdAt($createdAt)->updatedAt($model->updated_at?->toDateTimeImmutable()),
            settings: $barSettings,
            subtitle: $model->subtitle,
            description: $model->description,
        )->setId(new BarId($model->id));

        if ($model->is_public) {
            $bar->makePublic();
        } else {
            $bar->makePrivate();
        }

        if (is_string($model->slug)) {
            $bar->setSlug(Slug::fromString($model->slug));
        }

        return $bar;
    }
}
