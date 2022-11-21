<!DOCTYPE html>
<html>

<head>
    <title>Docs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@4.15.5/swagger-ui.css">
</head>

<body>
    <div id="ui-wrapper-new">
        Loading....
    </div>
</body>
<script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
<script>
    var swaggerUIOptions = {
      url: "/api/server/openapi",
      dom_id: '#ui-wrapper-new', // Determine what element to load swagger ui
      docExpansion: 'list',
      deepLinking: true, // Enables dynamic deep linking for tags and operations
      filter: true,
      presets: [
        SwaggerUIBundle.presets.apis,
        SwaggerUIBundle.SwaggerUIStandalonePreset
      ],
      plugins: [
        SwaggerUIBundle.plugins.DownloadUrl
      ],
    }

    var ui = SwaggerUIBundle(swaggerUIOptions)

    /** Export to window for use in custom js */
    window.ui = ui
</script>

</html>
