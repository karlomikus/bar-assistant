# v0.4.0
- Refactor image uploading and handling
- Updates for some base ingredients
- Add more popular cocktails
- Finish cocktail ingredient substitutes endpoints
- Finish glass type endpoints

# v0.3.0
- Move "Uncategorized" ingredient category to id: 1
- Cocktail save/update methods now save glass type
- Update docker entrypoint with better up/down handling
- Use `laravel/sluggable` package for ingredient and cocktail slug generation
- Added currently used Meilisearch host to user endpoint response
- Update Meilisearch cocktail index settings
- Add `user_id` to cocktail index
- Implement ingredient varieties feature #6
- Increased API request rate limiting
- Add cocktail ingredient substitutes feature
- Add a new cocktail source list
- Exclude optional cocktail ingredients from shelf cocktail matching
- Implement all ingredient category resource endpoints
- Update postman collection
- Implement more tests

# v0.2.0
- Add docker build action
- Add glasses
- Update base cocktail db
- Update error handling
- Add logout

# v0.1.0
- Initial release
