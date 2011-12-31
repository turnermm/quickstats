<?php
if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../../../') . '/');
//if(!defined('QS_META')) define ('QS_META', DOKU_INC . 'data/meta/quickstats/12_2011/');
if(!defined('QS_META')) define ('QS_META', DOKU_INC . 'data/meta/quickstats/');


 function qs_pages_search_i ($needle = null,$month)
 {
   
   $pages = unserialize(file_get_contents(QS_META . $month .'/pages.ser'));
    $ret_ar = array();
    foreach($pages['page'] as $key => $val)
    {
        
        if(stristr($key, $needle) !== false) {
             $ret_ar[$key] = $val;
         }

    }
  
    return $ret_ar;

 }
 
 function qs_process_pages ($page,$month) {
    $file = QS_META . $month . '/page_users.ser'; 
    $page_users = unserialize(file_get_contents($file));
 
   $found = qs_pages_search_i($page,$month);
   if(!$found) { echo "no data\n"; exit; }
   
   foreach($found as $page=>$accesses) {
      echo "$page=>$accesses\n";
      print_r($page_users[md5($page)]);
   }
  }
  qs_process_pages ($argv[1],'12_2011') ;
?>

