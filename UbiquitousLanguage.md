# Ubiquitous Language

| Term | Definition |
|------|------------|
| Bar | Main resource representing a bar. A bar is main tenant for all other resources. |
| Ingredient | A basic item that can be used to make a drink. For example, "lemon", "vodka", and "sugar" are all ingredients. |
| Complex Ingredient | Any ingredient that is made up of multiple ingredients (one or more). For example, a "lemon juice" is made of "lemon". |
| Ingredient Part | An ingredient that is part of a complex ingredient. For example, "lemon" is an ingredient part of "lemon juice". |
| Ingredient Variant | An ingredient that has a parent ingredient. For example, "Rye Whisky" is a variant of "Whisky". |
| Materialized Path | A list of ingredient ids represeting a hierarchy for a given ingredient. |
| Cocktail | A recipe that will make a drink that typically contains a mixture of various ingredients. |
| Image | A picture associated with a bar, ingredient, or cocktail. |
| Temporary Image | An image that is uploaded but not yet associated with any resource. |
| Main image | An image that is designated as the primary image for a resource that contains multiple images. Image is considered as main when it's first in the sort order. |
| Price Category | A classification for prices of ingredients, typically based on market locale, like "Amazon US", "Local Store", etc. |
| Ingredient Price | A monetary value associated with an ingredient amount and units within a specific price category. For example, "10 EUR per 750ml in Amazon DE". |
| Authors | Users that have created or modified a resource. |
| Record Timestamps | Timestamps that indicate when a resource was created and last modified. |
| Cocktail Ingredient | Ingredient that is a part of a cocktail recipe, along with the amount and units required. Can also contain substitutes. |
| Cocktail Ingredient Substitute | An alternative ingredient that can be used in place of a cocktail ingredient, along with the amount and units required. |
| Specific Cocktail Ingredient | An ingredient that must be used in a cocktail recipe, without automatic substitute suggestions. |
| Optional Cocktail Ingredient | An ingredient that may be used in a cocktail recipe, but is completely optional. |
| Cocktail | A recipe that will make a drink that typically contains a mixture of various ingredients. |
| ABV | Alcohol By Volume - a standard measure used worldwide to quantify the amount of alcohol (ethanol) contained in an alcoholic beverage. |
| Cocktail Garnish | An ingredient used to decorate or enhance the presentation of a cocktail, often added on top or to the side of the drink. |
| Cocktail Variant | A variation (also called a riff) of a cocktail recipe that includes modifications such as different ingredients, amounts, or preparation methods. |
| Menu | A curated list of cocktails and ingredients offered by a bar, typically organized into categories and including pricing information. |
| Menu Category | A grouping within a menu that organizes cocktails and ingredients into specific sections, for example: "Tiki", "Gin selection". |
| Menu Item | An entry in a menu representing either a cocktail or an ingredient, along with its price and sort order. |
| Member | A user that is part of a bar, with specific roles and permissions. |