<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi API — UII Event Management Platform</title>
    <link rel="icon" type="image/png" href="https://upload.wikimedia.org/wikipedia/commons/4/4b/Logo_UII.png">
    
    <!-- Swagger UI CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.11.0/swagger-ui.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .topbar-header {
            background: #0F2B5C;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 16px;
        }
        .brand-logo img {
            height: 36px;
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-action {
            background: #FFC000;
            color: #0F2B5C;
            font-weight: 700;
            font-size: 13px;
            padding: 7px 14px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-action:hover {
            background: #e5ac00;
        }
        .btn-portal {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
            padding: 7px 14px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            transition: all 0.2s;
        }
        .btn-portal:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        /* Custom tweaks for Swagger UI */
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { margin: 25px 0 15px; }
        .swagger-ui .info .title { color: #0F2B5C; font-size: 28px; }
        .swagger-ui .scheme-container {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-radius: 12px;
            margin: 0 0 20px;
            padding: 15px;
        }
        .swagger-ui .opblock {
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <header class="topbar-header">
        <div class="brand-logo">
            <img src="https://upload.wikimedia.org/wikipedia/commons/4/4b/Logo_UII.png" alt="UII">
            <div>
                <span>UII Event Management</span>
                <span style="font-weight: 400; opacity: 0.8; font-size: 13px; display: block;">Interactive API Documentation (Swagger / JS / OpenAPI 3.0)</span>
            </div>
        </div>
        <div class="topbar-actions">
            <a href="/" class="btn-portal">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Portal Web
            </a>
            <a href="/api/v1/openapi.json" target="_blank" class="btn-action">
                <i class="fa-solid fa-code"></i> OpenAPI JSON
            </a>
        </div>
    </header>

    <div id="swagger-ui"></div>

    <!-- Swagger UI Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            // Check if user already logged in on portal to pre-authorize Swagger
            const portalToken = localStorage.getItem('uii_token');

            const ui = SwaggerUIBundle({
                url: "/api/v1/openapi.json",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "BaseLayout",
                persistAuthorization: true,
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                docExpansion: 'list',
                filter: true,
                tryItOutEnabled: true,
                displayRequestDuration: true,
                onComplete: function() {
                    // Pre-fill token if available from portal session
                    if (portalToken) {
                        ui.preauthorizeApiKey("BearerAuth", portalToken);
                    }
                }
            });
            window.ui = ui;
        };
    </script>
</body>
</html>
