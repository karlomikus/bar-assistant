<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>API Docs</title>
    <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/styles.min.css">
</head>
<body>
<elements-api
  apiDescriptionUrl="{{ config('app.url') }}/api/server/openapi"
  router="hash"
  logo="https://barassistant.app/img/favicon.png"
  layout="responsive"
  hideSchemas="true"
/>
</body>
</html>
