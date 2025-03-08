<p align="center">
    <a href="https://barassistant.app" target="_blank"><img src="resources/art/readme-logo.png" alt="Bar assistant Logo" width="500"></a>
</p>

<p align="center">
    <img src="https://img.shields.io/docker/v/barassistant/server?style=for-the-badge&sort=semver" alt="Docker image">
    <img src="https://img.shields.io/github/license/karlomikus/bar-assistant?style=for-the-badge" alt="Docker image">
    <img src="https://img.shields.io/github/actions/workflow/status/karlomikus/bar-assistant/php.yml?branch=master&style=for-the-badge&label=Tests" alt="Tests">
    <a href="https://hub.docker.com/r/barassistant/server"><img src="https://img.shields.io/docker/pulls/barassistant/server?style=for-the-badge" alt="Pulls"></a>
</p>

## 🍸 Bar Assistant

Bar Assistant is all-in-one solution for managing your home bar. Compared to other recipe management software that usually tries to be more for general use, Bar Assistant is made specifically for managing cocktail recipes. This means that there are a lot of cocktail-oriented features, like ingredient substitutes, first-class ingredients, ABV calculations, unit switching and more.

This repository only contains the API server, if you are looking for easy to use web client, take a look at [Salt Rim](https://github.com/karlomikus/vue-salt-rim).

<p align="center">
    <a href="https://demo.barassistant.app/bar/docs" target="_blank">Click here to view API demo.</a>
    <br>
    <a href="https://demo.barassistant.app" target="_blank">Click here to view frontend demo.</a>
    <br>
    <strong>Email:</strong> admin@example.com &middot; <strong>Password:</strong> password
</p>

## Features
- [Includes over 300 cocktail recipes with detailed information](https://github.com/bar-assistant/data)
- Includes over 150 base ingredients with categories
- Add and manage multiple bars and bar members
- Fine-grained user control with user roles
- Endpoints for managing and filtering ingredients and cocktails
- Filter recipes by ABV, base ingredient, tags and more
- Filter recipes based on whether you have the right ingredients or not
- Detailed cocktail and ingredient information
- Support for assigning multiple images to resources and image sorting
- Shopping list generation based on missing ingredients in your inventory
- Automatic indexing of data with Meilisearch
- Support for custom cocktail ingredient substitutes
- Support for glass types, utensils, tags, ingredient categories and more
- Cocktail recipe importing via URL, JSON, YAML or custom collections
- Support for cocktail ratings
- Create user-specific cocktail collections for easy referencing and sharing
- Support for cocktail and ingredient notes
- Supports sharing recipes by public links, custom recipe images and printing
- Create public bar menus
- Manage custom API personal access tokens with custom permissions set by users
- Detailed statistics about recipes and user tastes
- Data export support in various formats
- Support for multiple ingredient prices
- Automatic cocktail price calculation based on ingredients

## Container images

Bar Assistant is available as a Docker image on [Docker Hub](https://hub.docker.com/r/barassistant/server) and [GitHub Container Registry](https://github.com/karlomikus/bar-assistant/pkgs/container/barassistant). There is no `latest` tag, so you need to specify version in the tag. For example:

- `barassistant/server:v4.4.1` - This will pull the exact version
- `barassistant/server:v4.4` - This will pull the latest minor release
- `barassistant/server:v4` - This will pull the latest major release

We recommend that you always use the latest major release, as it will always be the most stable version.

## Managed instance

Bar Assistant will always be open-source and MIT-licensed, but if you want to support the project or don't want to self-host, you can try officialy managed instance. Visit [barassistant.app](https://barassistant.app/) for more information about our cloud offering.

![Cloud offering screenshot](/resources/art/art1.png)

## Documentation

[Documentation is available here.](https://bar-assistant.github.io/docs/)

## Contributing

Contributions Welcome!

For more details, see [CONTRIBUTING.md](/CONTRIBUTING.md).

## Support and Donations

Bar Asistant is free, but maintaining any open source project takes time and resources. If you find Bar Assistant valuable and want to support its future development, consider donating.

[Donate with PayPal](https://www.paypal.com/ncp/payment/9L8T4YJZBRXAS)

## License

The Bar Assistant API is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
