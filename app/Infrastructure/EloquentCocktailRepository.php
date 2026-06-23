<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Kami\Cocktail\Models\Tag;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Slug;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Cocktail\GlassId;
use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Cocktail\PublicId;
use BarAssistant\Domain\Cocktail\UtensilId;
use Kami\Cocktail\Models\Cocktail as Model;
use BarAssistant\Domain\Cocktail\CocktailId;
use Kami\Cocktail\Models\Image as ModelImage;
use BarAssistant\Domain\Cocktail\PublicStatus;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Cocktail\CocktailIngredient;
use BarAssistant\Domain\Cocktail\CocktailRepository;
use BarAssistant\Domain\Cocktail\CocktailIngredientSubstitute;

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
        $model->author = $cocktail->getAuthor();
        $model->glass_id = $cocktail->getGlassId()?->value;
        $model->cocktail_method_id = $cocktail->getMethodId()?->value;
        $model->abv = $cocktail->getABV()->toFloat();
        $model->public_id = $cocktail->getPublicStatus()->publicId?->value;
        $model->public_at = $cocktail->getPublicStatus()->publicAt;
        $model->public_expires_at = $cocktail->getPublicStatus()->publicExpiresAt;
        $model->parent_cocktail_id = $cocktail->getVariantOf()?->value;
        $model->created_user_id = $cocktail->getAuthors()->getCreatedBy()->value;
        $model->created_at = $cocktail->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
        if ($cocktail->getAuthors()->isUpdated()) { // TODO: Change...
            $model->updated_user_id = $cocktail->getAuthors()->getUpdatedBy()?->value;
            $model->updated_at = $cocktail->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }
        $model->save();

        Model::unguard();
        $currentIngredients = [];
        foreach ($cocktail->getIngredients() as $cocktailIngredient) {
            $currentIngredients[] = $cocktailIngredient->ingredientId->value;
            $cocktailIngredientModel = $model->ingredients()->updateOrCreate([
                'ingredient_id' => $cocktailIngredient->ingredientId->value
            ], [
                'amount' => $cocktailIngredient->amountWithUnits->amountMin,
                'units' => $cocktailIngredient->amountWithUnits->units->value,
                'optional' => $cocktailIngredient->isOptional,
                'sort' => $cocktailIngredient->sortIndex,
                'amount_max' => $cocktailIngredient->amountWithUnits->amountMax,
                'note' => $cocktailIngredient->note,
                'is_specified' => $cocktailIngredient->isSpecific,
            ]);

            $currentSubIngredients = [];
            foreach ($cocktailIngredient->substitutes as $cocktailIngredientSubstitute) {
                $currentSubIngredients[] = $cocktailIngredientSubstitute->ingredientId->value;
                $cocktailIngredientModel->substitutes()->updateOrCreate([
                    'ingredient_id' => $cocktailIngredientSubstitute->ingredientId->value
                ], [
                    'amount' => $cocktailIngredientSubstitute->amountWithUnits?->amountMin,
                    'amount_max' => $cocktailIngredientSubstitute->amountWithUnits?->amountMax,
                    'units' => $cocktailIngredientSubstitute->amountWithUnits?->units->value,
                ]);
            }

            $cocktailIngredientModel->substitutes()->whereNotIn('ingredient_id', $currentSubIngredients)->delete();
        }
        Model::reguard();

        $model->ingredients()->whereNotIn('ingredient_id', $currentIngredients)->delete();

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

        $model->tags()->sync(array_unique($tagModels));
        $model->utensils()->sync(array_map(
            static fn (UtensilId $utensilId): int => $utensilId->value,
            $cocktail->getUtensils(),
        ));

        if (count($cocktail->getImages()) > 0) {
            $imageModels = ModelImage::findOrFail(array_map(fn (ImageId $img): int => $img->value, $cocktail->getImages()));
            $model->attachImages($imageModels);
        }

        return self::map($model);
    }

    public function findById(CocktailId $id): ?Cocktail
    {
        $model = Model::find($id->value)?->loadDefaultRelations();
        if (!$model) {
            return null;
        }

        return self::map($model);
    }

    private static function map(Model $model): Cocktail
    {
        $cocktail = Cocktail::create(
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
            description: $model->description,
            garnish: $model->garnish,
            source: $model->source,
            dilution: Dilution::fromFloat($model->method->dilution_percentage ?? 0.0),
            year: $model->year,
            glassId: $model->glass_id ? new GlassId($model->glass_id) : null,
            methodId: $model->cocktail_method_id ? new MethodId($model->cocktail_method_id) : null,
            variantOf: $model->parent_cocktail_id ? new CocktailId($model->parent_cocktail_id) : null,
            author: $model->author,
        )
        ->setId(new CocktailId($model->id))
        ->setSlug(Slug::fromString($model->slug));

        foreach ($model->ingredients as $cocktailIngredient) {
            $substitutes = [];
            foreach ($cocktailIngredient->substitutes as $cocktailIngredientSubstitute) {
                $amountWithUnits = null;
                if ($cocktailIngredientSubstitute->amount !== null && $cocktailIngredientSubstitute->units !== null) {
                    $amountWithUnits = AmountWithUnits::from($cocktailIngredientSubstitute->amount, Unit::from($cocktailIngredientSubstitute->units), $cocktailIngredientSubstitute->amount_max);
                }

                $substitutes[] = CocktailIngredientSubstitute::create(
                    ingredientId: new IngredientId($cocktailIngredientSubstitute->ingredient_id),
                    amountWithUnits: $amountWithUnits,
                );
            }

            $cocktail->addIngredient(CocktailIngredient::create(
                ingredientId: new IngredientId($cocktailIngredient->ingredient_id),
                amountWithUnits: AmountWithUnits::from($cocktailIngredient->amount, Unit::from($cocktailIngredient->units), $cocktailIngredient->amount_max),
                abv: ABV::from($cocktailIngredient->ingredient->strength ?? 0.0),
                isOptional: $cocktailIngredient->optional,
                isSpecific: $cocktailIngredient->is_specified,
                note: $cocktailIngredient->note,
                substitutes: $substitutes,
            ));
        }

        foreach ($model->tags as $tag) {
            $cocktail->addTag($tag->name);
        }

        foreach ($model->utensils as $utensil) {
            $cocktail->addUtensil(new UtensilId($utensil->id));
        }

        return $cocktail;
    }
}
