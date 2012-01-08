<?php
if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../../../') . '/');
if(!defined('QS_META')) define ('QS_META', DOKU_INC . 'data/meta/quickstats/');

 function qs_useragent_search_ci ($needle = null, $month)
 {
   $found = array();
   $misc_data_file = QS_META . $month . '/misc_data.ser';
   $data = unserialize(file_get_contents($misc_data_file));
  
    foreach($data['version'] as $key =>$val)
    {
        
        if(stristr($key, $needle) !== false) {         
          $found[] = $key;
         } 
               
    }
     return $found;
 }
  
  $page_totals_file = QS_META . 'page_totals.ser';
  $page_totals = unserialize(file_get_contents($page_totals_file));
  $months = array_keys($page_totals);
  $qs_agents = array();
  
  $search_term = rawurldecode(trim($_POST['other_agent']));
  if(!$search_term) {
     echo "";
     exit;
  }
  foreach($months as $month) {  
     $qs_agents = array_merge($qs_agents,qs_useragent_search_ci ($search_term,$month));
  }
  $qs_agents = array_unique($qs_agents);
  if(!count($qs_agents))  {
     echo "";  
     exit;
   }  
  $ret_str = implode('::',$qs_agents);
  echo rawurlencode(rtrim($ret_str,':'));
  exit;
  
?>
 