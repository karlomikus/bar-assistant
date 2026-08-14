# Ubiquitous Language

## Core Domain

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Bar** | Main tenant resource representing a bar and its associated data. | Tenant |
| **Bar Status** | Operational state of a bar: Provisioning (initial setup), Active (normal), or Deactivated. | State |
| **Bar Settings** | Configurable options for a bar: invite-code toggle, default units, default currency. | Preferences |
| **Cocktail** | A recipe for a drink containing a mixture of ingredients. | Drink, Recipe |
| **Public Cocktail** | A cocktail made accessible via a shareable link, with optional expiration date. | Shared cocktail |
| **Cocktail Variant** | A riff on a cocktail recipe linked to a parent cocktail via `variantOf`. | Riff, Variation |
| **Ingredient** | A basic item used to make a drink. | Component |
| **Complex Ingredient** | An ingredient made up of one or more other ingredients. | Compound |
| **Ingredient Part** | A constituent ingredient of a complex ingredient. | Sub-ingredient |
| **Ingredient Variant** | An ingredient that has a parent ingredient relationship (e.g., "London Dry Gin" is a variant of "Gin"). | Sub-type, Child |
| **Ingredient Origin** | The geographical place an ingredient comes from. | Provenance |
| **Ingredient Distillery** | The distillery that produced the ingredient (for spirits). | Producer |
| **Materialized Path** | A list of ingredient IDs representing an ingredient's hierarchy. | Hierarchy Path |
| **Tag** | A free-text label attached to a cocktail for categorization, used in recommendations. | Label, Category |

## Inventory & Pricing

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Price Category** | A classification for ingredient prices, often by locale. | Market, Region |
| **Ingredient Price** | A monetary value for an ingredient amount in a price category. | Cost |
| **Cocktail Price** | A monetary value associated with a cocktail recipe. | Cost |
| **Amount** | A specific quantity of an ingredient with numeric value and units. | Quantity, Measure |
| **Rating** | A numerical score (1–5) a user assigns to a cocktail. Each user may have at most one rating per cocktail; submitting again updates the existing value. Ratings drive the recommendation engine and feed `average_rating` and `user_rating` aggregates. | Score, Review |
| **Note** | A free-form private text annotation a user attaches to a cocktail. A user may create multiple notes on the same cocktail, and only the author can view or delete them. | Comment, Annotation |

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
| **Member Role** | A member's permission level within a bar: Admin, General, or Guest. | Permission |
| **Authors** | Users that created or modified a resource. | Creator, Editor |
| **Invite Code** | A code a bar owner shares with users to allow them to join the bar as members. | Join code |

## Preparation

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Cocktail Ingredient** | An ingredient within a recipe, including amount, units, and substitutes. | Recipe Item |
| **Cocktail Ingredient Substitute** | An alternative ingredient for a cocktail ingredient. | Replacement |
| **Specific Cocktail Ingredient** | A mandatory ingredient without automatic substitute suggestions. | Required Item |
| **Optional Cocktail Ingredient** | An ingredient that may optionally be used in a recipe. | Extra |
| **Cocktail Garnish** | An ingredient used to decorate or enhance presentation. | Decoration |
| **Cocktail Instructions** | Step-by-step directions for preparing a cocktail. | Recipe steps |
| **Cocktail Method** | A preparation technique (e.g., stirring) with an associated dilution percentage that affects final ABV. | Technique |
| **Dilution** | The percentage of water/ice added during preparation, used in ABV calculation. | Water addition |
| **ABV** | Alcohol By Volume; a measure of alcohol concentration (0.0–100.0). | Alcohol Content |
| **Strength** | An ingredient's alcohol by volume percentage. Synonym for ABV when on an ingredient. | Proof |
| **Sugar Content** | An ingredient's sugar measured in grams per milliliter (g/ml). | Sweetness |
| **Acidity** | An ingredient's acidity measured as a percentage. | Sourness |
| **Color** | The visual color of an ingredient. | Hue |
| **Glass** | A type of vessel used to serve cocktails. | Glassware |
| **Utensils** | Tools used in cocktail preparation (e.g., shakers). | Equipment |
| **Calculator** | A tool for calculating ingredient amounts for infusions. | Custom tool |
| **Calculator Block** | An input or evaluation component within a calculator. | Input field |
| **Cocktail Year** | The year a cocktail recipe was created or first published. | Vintage |

## Collections & Favorites

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Collection** | A member-curated group of cocktails, optionally shared with the bar. | Playlist, Folder |
| **Cocktail Favorite** | A cocktail a member has bookmarked as a personal favorite. | Bookmark, Like |

## Inventory & Shopping

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Bar Inventory** | The bar-level tracking of which ingredients are in stock and their status. | Bar shelf |
| **Member Inventory** | A named collection of ingredients a bar member personally has at home. A member can own multiple inventories. | Personal shelf, Home bar |
| **Ingredient Inventory Status** | The stocking status of an ingredient: InStock (exact match), Variant (a descendant is in stock), or Makeable (can be made from in-stock ingredients). | Stock status |
| **Shopping List** | A member's list of ingredients to purchase, each with a quantity. | Grocery list |

## Recommendation

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Recommendation** | An algorithmically suggested cocktail based on a member's taste profile and shelf coverage. | Suggestion |
| **User Taste Profile** | A member's inferred preferences derived from their rating history: favorite tags, favorite ingredients, and ABV distribution. | Preference profile |
| **Shelf Coverage** | The fraction of a cocktail's ingredients that the bar or member has in stock — a key signal in recommendations. | Completeness |

## Metadata

| Term | Definition | Aliases to avoid |
| :--- | :--- | :--- |
| **Record Timestamps** | Timestamps for resource creation and last modification. | Audit fields |
| **Export** | An (a)synchronous process of generating a file containing bar data. | Dump, Backup |
| **File Token** | A temporary identifier used to access an exported file. | Download key |

## Relationships

- A **Bar** contains many **Cocktails**, **Ingredients**, and one **Menu**.
- A **Bar** has one **Bar Inventory** tracking stocked ingredients.
- A **Bar** has **Bar Settings** and a **Bar Status**.
- A **Cocktail** consists of one or more **Cocktail Ingredients**.
- A **Cocktail** may have zero or more **Tags**.
- A **Cocktail** may have zero or more **Utensils**.
- A **Cocktail** may be a **Variant** of a parent **Cocktail**.
- A **Cocktail** may be made **Public** (shared via link) or remain private.
- A **Cocktail Ingredient** may have zero or more **Cocktail Ingredient Substitutes**.
- A **Member** is a **User** assigned to one or more **Bars**.
- A **Member** has a **Member Role** (Admin, General, Guest) within each **Bar**.
- A **Member** may have many **Cocktail Favorites**.
- A **Member** may own multiple **Member Inventories**.
- A **Member** has one **Shopping List** per **Bar**.
- A **Member** owns one or more **Collections**.
- A **Collection** contains zero or more **Cocktails**.
- **Authors** are **Users** who modified a resource.
- A **User** may have many **Notes** on a **Cocktail** (private, multi-note).
- A **User** has at most one **Rating** per **Cocktail** (upsert semantics).
- **Recommendations** are generated from a member's **User Taste Profile** (tags, ingredients, ABV) and **Shelf Coverage**.
- An **Ingredient** may have a **parent ingredient** forming hierarchy with **Materialized Path**.

## Flagged ambiguities

- **Ingredient** vs **Cocktail Ingredient**: An **Ingredient** is the base domain entity; a **Cocktail Ingredient** is a recipe-specific application of an **Ingredient** with additional metadata like amount and units.
- **Author** vs **User**: An **Author** is a role definition for audit tracking, while a **User** is the identity of an individual interacting with the system.
- **Bar Inventory** vs **Member Inventory**: **Bar Inventory** tracks the bar's shared stock; **Member Inventory** tracks a specific member's personal stock at home. They are unrelated.
- **ABV** vs **Strength**: Both refer to alcohol by volume percentage. **Strength** is the term used on the **Ingredient** entity; **ABV** is the computed value on a **Cocktail**. They are the same concept applied to different aggregates.
