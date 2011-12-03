#!/usr/bin/php
<pre>
<?php

if($argc > 1) {
  echo $argv[1], "\n";
  get_data($argv[1]);
}

else {
  foreach (glob("*.gz") as $filename) {
      get_data($filename) ;
     
  }
}
echo "\n</pre>\n";
exit;


function get_data($filename) {

     if(preg_match('/.*?\.php/', $filename)) return;
     if(is_dir($filename)) return;
     echo "$filename size " . filesize($filename) . "\n";

     if(substr($filename,-3) == '.gz') {
       $inf_str = join('', gzfile($filename));       
       
      //print_r(unserialize($inf_str));  
      //return;
      $ar = unserialize($inf_str);
//       print_r($ar);
//       $inf = array_shift($ar);
      // print_r($inf);
     //  return;
       for($i=0;$i<count($ar);$i++) {
          echo "Name: " . $ar[$i]['name'] ."\n";
          echo "Title: " . $ar[$i]['title'] ."\n";
        //  echo "Item: " . $ar[$i]['item'] ."\n\n";
       }
       return;
     }
     else {
      $inf_str = file_get_contents($filename);
       $inf = unserialize($inf_str);
      //
       print_r($inf);
     }


}
?>
