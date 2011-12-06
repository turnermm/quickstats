<?php


$ini_array = file('sample.ini',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);

$temp = array();
$header_found = false;
foreach ($ini_array as $entry) {
  //echo "$entry\n";
  if(strpos($entry,'[') !== false){
     $header = trim($entry,'[]');
     $temp[$header] = array();
     $header_found = true;
     echo "header:$header found\n";
     continue;
  }
  if($header_found) {
     $temp[$header][] = trim($entry);
  }  
}
echo "\n";
print_r($temp);
echo "\n";
?>