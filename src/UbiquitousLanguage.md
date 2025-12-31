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
