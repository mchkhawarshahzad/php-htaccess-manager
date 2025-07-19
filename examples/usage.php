<?php
require_once __DIR__ . '/../src/Htaccess.php';
use HtaccessManager\Htaccess;
$rules = [
        'type' => 'root',
        'data' => [
            'RewriteEngine On',
            [
                'type' => 'ifmodule',
                'rules' => 'mod_headers.c',
                'data' => [
                    '#Secure Headers',
                    'Header always unset X-Powered-By',
                    'Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"',
                    'Header edit Set-Cookie ^(.*)$ $1;path=/;HttpOnly;Secure;SameSite=Strict',
                    'Header always set X-Frame-Options "SAMEORIGIN"',
                    'Header always set Cache-Control "max-age=15552000, must-revalidate"',
                    'Header always set Referrer-Policy "strict-origin-when-cross-origin"',
                    'Header always set X-UA-Compatible "IE=edge,chrome=1"',
                    'Header always set X-Permitted-Cross-Domain-Policies "master-only"',
                    'Header always set X-Download-Options "noopen"',
                    'Header always set Access-Control-Allow-Methods "GET, POST"',
                    'Header always set Content-Security-Policy "default-src \'self\'; script-src * \'unsafe-inline\' \'unsafe-eval\'; style-src * \'unsafe-inline\'; img-src * data:; font-src * data:; object-src \'self\'"',
                    'Header always set X-XSS-Protection "1; mode=block"',
                    'Header always set X-Content-Type-Options "nosniff"',
                    'Header always set Permissions-Policy "geolocation=(), midi=(), sync-xhr=(), accelerometer=(), gyroscope=(), magnetometer=(), camera=(), fullscreen=(self)"'
                ]
            ],
            [
                'type' => 'ifmodule',
                'rules' => 'mod_rewrite.c',
                'data' => [
                    '#Rewriting & Routing',
                    '#Options +FollowSymlinks',
                    '#Options +SymLinksIfOwnerMatch',
                    'RewriteCond %{HTTPS} off',
                    'RewriteCond %{HTTP:X-Forwarded-Proto} !https',
                    'RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301,NE]',
                    'RewriteBase /',
                    'RewriteCond %{REQUEST_URI} ^(system|templates|asset)',
                    'RewriteRule ^(.*)$ /index.php?/$1 [L]',
                    'RewriteCond %{REQUEST_FILENAME} !-f',
                    'RewriteCond %{REQUEST_FILENAME} !-d',
                    'RewriteRule ^(.*)$ index.php/$1 [L]',
                    'RewriteRule ^.*\.(git|svn|hg)(/.*)?$ - [F,L]'
                ]
            ],
            [
                'type' => 'ifmodule',
                'rules' => 'mod_expires.c',
                'data' => [
                    '#Cache Expiry Settings',
                    'ExpiresActive On',
                    'ExpiresDefault "access plus 1 month"',
                    'ExpiresByType image/gif "access plus 1 month"',
                    'ExpiresByType image/png "access plus 1 month"',
                    'ExpiresByType image/jpg "access plus 1 month"',
                    'ExpiresByType image/jpeg "access plus 1 month"',
                    'ExpiresByType image/x-ico "access plus 1 year"',
                    'ExpiresByType image/x-icon "access plus 1 year"',
                    'ExpiresByType image/svg+xml "access plus 1 month"',
                    'ExpiresByType text/html "access plus 3 days"',
                    'ExpiresByType text/xml "access plus 1 second"',
                    'ExpiresByType text/plain "access plus 1 second"',
                    'ExpiresByType application/xml "access plus 1 second"',
                    'ExpiresByType application/rss+xml "access plus 1 second"',
                    'ExpiresByType application/json "access plus 1 second"',
                    'ExpiresByType text/css "access plus 1 week"',
                    'ExpiresByType text/javascript "access plus 1 week"',
                    'ExpiresByType application/javascript "access plus 1 week"',
                    'ExpiresByType application/x-javascript "access plus 1 week"',
                    'ExpiresByType application/pdf "access plus 1 month"',
                    [
                        'type' => 'ifmodule',
                        'rules' => 'mod_headers.c',
                        'data' => [
                            'Header unset ETag',
                            'Header unset Pragma',
                            'Header unset Last-Modified',
                            'Header append Cache-Control "public, no-transform, must-revalidate"',
                            'Header set Last-Modified "Tue, 1 Feb 2023 10:10:10 GMT"',
                        ]
                    ]
                ]
            ],
            [
            'type' => 'ifmodule',
            'rules' => 'mod_deflate.c',
            'data' =>[
                    '#gzip size compress for better performance, if brotli not available',
                    'AddOutputFilterByType DEFLATE text/plain',
                    'AddOutputFilterByType DEFLATE text/html',
                    'AddOutputFilterByType DEFLATE text/xml',
                    'AddOutputFilterByType DEFLATE text/css',
                    'AddOutputFilterByType DEFLATE application/xml',
                    'AddOutputFilterByType DEFLATE application/xhtml+xml',
                    'AddOutputFilterByType DEFLATE application/rss+xml',
                    'AddOutputFilterByType DEFLATE application/javascript',
                    'AddOutputFilterByType DEFLATE application/x-javascript',
                    'AddOutputFilterByType DEFLATE application/json',
                ]
            ],
            [
            'type' => 'ifmodule',
            'rules' => 'mod_brotli.c',
            'data' =>[
                '#brotli size compress for better performance, if gzip available or not',
                'AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/xml text/css application/javascript application/x-javascript application/json application/xml',
                ]
            ],
            [
            'type' => 'ifmodule',
            'rules' => 'mod_headers.c',
            'data' =>[
                '#Tell browser to accept encoding headers',
                'Header append Vary Accept-Encoding',
                ]
            ],
            '# Protect sensitive files - File Access Restrictions',
            [
                'type' => 'filesmatch',
                'rules' => '^\.env$',
                'data' => [
                    'Order allow,deny',
                    'Deny from all'
                ]
            ],
            [
                'type' => 'filesmatch',
                'rules' => '^(config)\.php$',
                'data' => [
                    'Order allow,deny',
                    'Deny from all'
                ]
            ],
            [
                'type' => 'filesmatch',
                'rules' => '^.*\.([Hh][Tt][Aa])',
                'data' => [
                    'Order allow,deny',
                    'Deny from all',
                    'Satisfy all'
                ]
            ],
            '# Block robots indexing',
            [
                'type' => 'filesmatch',
                'rules' => '\.pdf$',
                'data' => [
                    'Header set X-Robots-Tag "noindex, nofollow"'
                ]
            ],
            [
                'type' => 'filesmatch',
                'rules' => '\.(png|jpe?g|gif|bmp|psd|txt)$',
                'data' => [
                    'Header set X-Robots-Tag "noindex"'
                ]
            ],
        ]
    ];

$ht = new Htaccess();
$response = $ht->update($rules);
print_r($response);
