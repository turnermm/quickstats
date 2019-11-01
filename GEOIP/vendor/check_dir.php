<?php

if(!defined('GeoLite2_DIR')) define('GeoLite2_DIR', realpath(dirname(__FILE__) ) . '/'); //  '/GeoLite2-City/');
echo GeoLite2_DIR . "\n";  exit;
global $City_dnld_dir;
function listdir($start_dir='.', $found=false) {
   global $City_dnld_dir;
  
  if (is_dir($start_dir)) {
    $fh = opendir($start_dir);
    while (($file = readdir($fh)) !== false) {
      # loop through the files, skipping . and .., and recursing if necessary
      if (strcmp($file, '.')==0 || strcmp($file, '..')==0) continue;
      $filepath = $start_dir . '/' . $file;
      if ( is_dir($filepath) ) {
          if($found) { 
             $found = false;
              return;
          }
          if(preg_match('/GeoLite2-City_*\d+/',$filepath)) {
              $found = true;
              $City_dnld_dir = $filepath;
              echo "Directory: $filepath \nFiles:\n"; 
          }
           listdir($filepath,$found);
        }  
        else if($found  && preg_match('/GeoLite2-City_*\d+/',$filepath)) {
            if(preg_match("/GeoLite2-City.mmdb/",$filepath)) {
                   unlink("./GeoLite2-City/GeoLite2-City.mmdb");
                  copy ($filepath , "./GeoLite2-City/GeoLite2-City.mmdb") ;
            }
            echo "unlinking: $filepath \n"; 
            unlink($filepath);
          }
          else if($found) {
              echo "returning\n";
              return;
          }
    }
    closedir($fh);
  } else {
      return false;
      # false if the function was called with an invalid non-directory argument
    
  }

  return true;

}


listdir('.');
echo   $City_dnld_dir ."\n";
rmdir($City_dnld_dir);

?>
