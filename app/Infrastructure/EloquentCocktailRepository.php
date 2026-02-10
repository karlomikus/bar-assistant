<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Authors;
use Kami\Cocktail\Models\Cocktail as Model;
use Kami\Cocktail\Models\Image as ModelImage;
use BarAssistant\Domain\Cocktail\CocktailRepository;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\PublicId;
use BarAssistant\Domain\Cocktail\PublicStatus;
use BarAssistant\Domain\Image\ImageId;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\CocktailIngredientSubstitute;
use Kami\Cocktail\Models\Tag;

final class EloquentCocktailRepository implements CocktailRepository
{
    public function save(Cocktail $cocktail): Cocktail
    {
        $model = Model::findOrNew($cocktail->getId()?->value);

        $model->bar_id = $cocktail->getBarId()->value;
        $model->name = (string) $cocktail->getName();
        $model->instructions = $cocktail->getInstructions();
        $model->garnish = $cocktail->getGarnish();
        $model->description = $cocktail->getDescription();
        $model->source = $cocktail->getSource();
        $model->year = $cocktail->getYear();
        $model->glass_id = $cocktail->getGlassId()->value;
        $model->cocktail_method_id = $cocktail->getMethodId()->value;
        $model->abv = $cocktail->getABV()->toFloat();
        $model->public_id = $cocktail->getPublicStatus()->publicId->value;
        $model->public_at = $cocktail->getPublicStatus()->publicAt;
        $model->public_expires_at = $cocktail->getPublicStatus()->publicExpiresAt;
        $model->parent_cocktail_id = $cocktail->getVariantOf();
        $model->created_user_id = $cocktail->getAuthors()->getCreatedBy()->value;
        $model->created_at = $cocktail->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
        if ($cocktail->getAuthors()->isUpdated()) {
            $model->updated_user_id = $cocktail->getAuthors()->getUpdatedBy()?->value;
            $model->updated_at = $cocktail->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }
        $model->save();

        foreach ($cocktail->getIngredients() as $cocktailIngredient) {
            $cocktailIngredientModel = new CocktailIngredient();
            $cocktailIngredientModel->ingredient_id = $cocktailIngredient->ingredientId;
            $cocktailIngredientModel->amount = $cocktailIngredient->amountWithUnits->amountMin;
            $cocktailIngredientModel->units = $cocktailIngredient->amountWithUnits->units;
            $cocktailIngredientModel->optional = $cocktailIngredient->isOptional;
            $cocktailIngredientModel->sort = $cocktailIngredient->sortIndex;
            $cocktailIngredientModel->amount_max = $cocktailIngredient->amountWithUnits->amountMax;
            $cocktailIngredientModel->note = $cocktailIngredient->note;
            $cocktailIngredientModel->is_specified = $cocktailIngredient->isSpecific;
            $model->ingredients()->save($cocktailIngredientModel);

            foreach ($cocktailIngredient->substitutes as $cocktailIngredientSubstitute) {
                $substitute = new CocktailIngredientSubstitute();
                $substitute->ingredient_id = $cocktailIngredientSubstitute->ingredientId;
                $substitute->amount = $cocktailIngredientSubstitute->amountWithUnits->amountMin;
                $substitute->amount_max = $cocktailIngredientSubstitute->amountWithUnits->amountMax;
                $substitute->units = $cocktailIngredientSubstitute->amountWithUnits->units;
                $cocktailIngredientModel->substitutes()->save($substitute);
            }
        }

        $tagModels = [];
        foreach (array_filter($cocktail->getTags()) as $tagName) {
            $tag = Tag::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($tagName))])
                ->where('bar_id', $cocktail->getBarId()->value)
                ->first();
            if (!$tag) {
                $tag = new Tag();
                $tag->name = trim($tagName);
                $tag->bar_id = $cocktail->getBarId()->value;
                $tag->save();
            }
            $tagModels[] = $tag->id;
        }

        $model->tags()->attach(array_unique($tagModels));

        if (count($cocktail->getImages()) > 0) {
            $imageModels = ModelImage::findOrFail(array_map(fn (ImageId $img): int => $img->value, $cocktail->getImages()));
            $model->attachImages($imageModels);
        }

        return self::map($model);
    }

    public function findById(CocktailId $id): ?Cocktail
    {
        $model = Model::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    private static function map(Model $model): Cocktail
    {
        $bar = Cocktail::create(
            barId: new BarId($model->bar_id),
            name: Name::fromString($model->name),
            authors: Authors::createdBy(new UserId($model->created_user_id))->updatedBy($model->updated_user_id ? new UserId($model->updated_user_id) : null),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
            instructions: $model->instructions,
            publicStatus: $model->public_id
                ? PublicStatus::createFrom(
                    PublicId::createFrom($model->public_id),
                    $model->public_at->toDateTimeImmutable(),
                    $model->public_expires_at ? $model->public_expires_at->toDateTimeImmutable() : null
                )
                : PublicStatus::createPrivate(),
        )->setId(new CocktailId($model->id));

        return $bar;
    }
}
