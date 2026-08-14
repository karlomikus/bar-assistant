<!doctype html>
<html>
  <head>
    <title>Bar Assistant &middot; API Documentation</title>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1" />
  </head>

  <body>
    <div id="app"></div>

    <!-- Load the Script -->
    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>

    <!-- Initialize the Scalar API Reference -->
    <script>
      Scalar.createApiReference('#app', {
        // The URL of the OpenAPI/Swagger document
        url: '{{ config('app.url') }}/api/server/openapi',
        // Avoid CORS issues
        proxyUrl: 'https://proxy.scalar.com',
      })
    </script>
  </body>
</html>