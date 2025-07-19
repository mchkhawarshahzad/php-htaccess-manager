<?php
require_once __DIR__ . '/../src/Htaccess.php';
use HtaccessManager\Htaccess;
$ht = new Htaccess();
/*
  //put raw contents to .htaccess file
  //$filePath eq to your .htaccess file path
  //$contents eq to raw rules content which need to replace in .htaccess file
*/
$response = $ht->put($filePath,$contents);
/*
  $response as array 
  incase of success
  ["status"=>"success","message"=>"success message here"]
  incase of error
  ["status"=>"error","message"=>"error message here"]
*/
print_r($response);
