<p align="center">
    <a href="https://barassistant.app" target="_blank"><img src="resources/art/readme-logo.png" alt="Bar assistant Logo" width="500"></a>
</p>

<p align="center">
    <a href="https://hub.docker.com/r/barassistant/server"><img src="https://img.shields.io/docker/v/barassistant/server?style=for-the-badge&sort=semver" alt="Docker image"></a>
    <img src="https://img.shields.io/github/license/karlomikus/bar-assistant?style=for-the-badge" alt="Docker image">
    <img src="https://img.shields.io/github/actions/workflow/status/karlomikus/bar-assistant/php.yml?branch=master&style=for-the-badge&label=Tests" alt="Tests">
    <img src="https://img.shields.io/docker/pulls/barassistant/server?style=for-the-badge" alt="Pulls">
</p>

## 🍸 Bar Assistant

Bar Assistant is a self hosted application for managing your home bar. It allows you to search and filter cocktails, add ingredients and create custom cocktail recipes.

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

## Cloud

Visit [barassistant.app](https://barassistant.app/) for more information about our cloud offering.

![Cloud offering screenshot](/resources/art/art1.png)

## Documentation

[Documentation is available here.](https://bar-assistant.github.io/docs/)

## Contributing

Contributions Welcome!

For more details, see [CONTRIBUTING.md](/CONTRIBUTING.md).

## License

The Bar Assistant API is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
