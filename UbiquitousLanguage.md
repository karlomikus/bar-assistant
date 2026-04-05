# Ubiquitous Language

## Core Domain

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Bar** | Main tenant resource representing a bar and its associated data. | Tenant |
| **Cocktail** | A recipe for a drink containing a mixture of ingredients. | Drink, Recipe |
| **Ingredient** | A basic item used to make a drink. | Component |
| **Complex Ingredient** | An ingredient made up of one or more other ingredients. | Compound |
| **Ingredient Part** | A constituent ingredient of a complex ingredient. | Sub-ingredient |
| **Ingredient Variant** | An ingredient that has a parent ingredient relationship. | Sub-type |
| **Materialized Path** | A list of ingredient IDs representing an ingredient's hierarchy. | Hierarchy Path |

## Inventory & Pricing

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Price Category** | A classification for ingredient prices, often by locale. | Market, Region |
| **Ingredient Price** | A monetary value for an ingredient amount in a price category. | Cost |
| **Cocktail Price** | A monetary value associated with a cocktail recipe. | Cost |
| **Amount** | A specific quantity of an ingredient with numeric value and units. | Quantity, Measure |
| **Rating** | A numerical evaluation of a resource by a user. | Score, Review |

## Menu & Presentation

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Menu** | A curated list of cocktails and ingredients offered by a bar. | Catalog |
| **Menu Category** | A grouping within a menu for organization. | Section |
| **Menu Item** | A cocktail or ingredient entry in a menu with price and sort order. | Listing |
| **Image** | A visual associated with a bar, ingredient, or cocktail. | Photo, Picture |
| **Temporary Image** | An uploaded image not yet associated with a resource. | Pending Image |
| **Main Image** | The primary image for a resource (first in sort order). | Default Image |
| **Slug** | A URL-friendly identifier generated from a resource name. | Permalink |

## Actors

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **User** | An individual who interacts with the system. | Person |
| **Member** | A user assigned to a bar with specific roles and permissions. | Employee |
| **Authors** | Users that created or modified a resource. | Creator, Editor |

## Preparation

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Cocktail Ingredient** | An ingredient within a recipe, including amount, units, and substitutes. | Recipe Item |
| **Cocktail Ingredient Substitute** | An alternative ingredient for a cocktail ingredient. | Replacement |
| **Specific Cocktail Ingredient** | A mandatory ingredient without automatic substitute suggestions. | Required Item |
| **Optional Cocktail Ingredient** | An ingredient that may optionally be used in a recipe. | Extra |
| **Cocktail Garnish** | An ingredient used to decorate or enhance presentation. | Decoration |
| **Cocktail Variant** | A riff on a cocktail recipe with modifications. | Riff, Variation |
| **Cocktail Instructions** | Step-by-step directions for preparing a cocktail. | Recipe steps |
| **Cocktail Method** | A preparation technique (e.g., stirring) affecting ABV dilution. | Technique |
| **ABV** | Alcohol By Volume; a measure of alcohol concentration. | Alcohol Content |
| **Glass** | A type of vessel used to serve cocktails. | Glassware |
| **Utensils** | Tools used in cocktail preparation (e.g., shakers). | Equipment |
| **Calculator** | A tool for calculating ingredient amounts for infusions. | Custom tool |
| **Calculator Block** | An input or evaluation component within a calculator. | Input field |

## Metadata

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Record Timestamps** | Timestamps for resource creation and last modification. | Audit fields |

## Relationships

- A **Bar** contains many **Cocktails**, **Ingredients**, and one **Menu**.
- A **Cocktail** consists of one or more **Cocktail Ingredients**.
- A **Cocktail Ingredient** may have zero or more **Cocktail Ingredient Substitutes**.
- A **Member** is a **User** assigned to one or more **Bars**.
- **Authors** are **Users** who modified a resource.

## Flagged ambiguities

- **Ingredient** vs **Cocktail Ingredient**: An **Ingredient** is the base domain entity; a **Cocktail Ingredient** is a recipe-specific application of an **Ingredient** with additional metadata like amount and units.
- **Author** vs **User**: An **Author** is a role definition for audit tracking, while a **User** is the identity of an individual interacting with the system.
