<?php
require_once __DIR__ . '/../src/Htaccess.php';
use HtaccessManager\Htaccess;
$ht = new Htaccess();
/*
  //generate raw contents for .htaccess file using .htaccess file existing rules and new rules which given by you
  //$filePath eq to your .htaccess file path
  //$newRules eq to php array rules data which need to merge in .htaccess file
  //single rule as 
    $newRules = [
    'type' => 'root',
    'data' => [
        'RewriteEngine On',
        ]
    ];
    //multi rules adding
    $newRules = [
    'type' => 'root',
    'data' => [
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
        ]
    ];
    //multi & nested rules as mention below:
    $newRules = [
    'type' => 'root',
    'data' => [
            [
                'type' => 'ifmodule',
                'rules' => 'mod_expires.c',
                'data' => [
                    '#Cache Expiry Settings',
                    'ExpiresActive On',
                    'ExpiresDefault "access plus 1 month"',
                    'ExpiresByType application/pdf "access plus 1 month"',
                    [
                        'type' => 'ifmodule',
                        'rules' => 'mod_headers.c',
                        'data' => [
                            'Header unset ETag',
                            'Header unset Pragma',
                        ]
                    ]
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
            ]
          ]
        ];
*/
$response = $ht->generate($newRules,$filePath);
/*
  $response as array 
  incase of success
  ["status"=>"success","data"=>"raw rules data for htaccess file here"]
  incase of error
  ["status"=>"error","message"=>"error message here"]
*/
print_r($response);
