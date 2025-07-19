<?php
require_once __DIR__ . '/../src/Htaccess.php';
use HtaccessManager\Htaccess;
$ht = new Htaccess();
/*
  //get raw contents of .htaccess file
  //$filePath eq to your .htaccess file path
*/
$response = $ht->get($filePath);
/*
  $response as array 
  incase of success
  ["status"=>"success","data"=>$htaccesscontent]
  incase of error
  ["status"=>"error","message"=>"error message here"]
*/
print_r($response);
