<?php

if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../../../') . '/');
require DOKU_INC . 'inc/io.php';
require DOKU_INC . 'lib/plugins/quickstats/GEOIP/cc_arrays_dat.php';
//display_post_data();
if(isset($_POST['page']) && $_POST['page']) {
   $temp =  qs_process_pages ($_POST['page']);
   if(!$temp) echo "no data";
   qs_format_pages($temp);
}
else if(isset($_POST['ip']) && $_POST['ip']) {
    echo rawurlencode(ip_data()) . "\n";
}
else if(isset($_POST['country_code']) && $_POST['country_code']) {
    qs_process_country($_POST['country_code'],$_POST['country_name']) . "\n";
}

exit;

function ip_data($ip=false,$p_brief=false) {
   $row = "";
   $table = '<table border=1 cellspacing="0">';   
   $date = rawurldecode($_POST['date']);   
   if($ip === false)  $ip =rawurldecode($_POST['ip']);
 
 
  if($p_brief) { 
      $table .= '<tr>' . cell("$ip ",'caption',false,$p_brief) . '</tr>';
      $table .= '<tr>' . cell('Month','th') . cell('Accesses','th'). cell('Country','th') . cell('User Agent','th') . '</tr>'; 
  
    $table .= ip_row(array('ip','ua'), $ip,$date); 
  }  
  else  {
      $table .= '<tr>' . cell("Data for IP address:  $ip ",'caption',false,$p_brief) . '</tr>';
      $table .= '<tr>' . cell('Month','th') . cell('Accesses','th'). cell('Pages','th') . cell('Country','th') . cell('User Agent','th')  . cell('Search Terms','th') 
                         . cell('Name Spaces','th')    .  cell('Query String Names','th') .cell('Query String Values','th') . '</tr>';
     $table .= ip_row(array('ip','page_users','ua', 'qs_data'), $ip,$date); 
  }  
  
  $keys = $keys =array_keys($_POST);
  foreach($keys as $key) {
     if(strpos($key,'date_') !== false) {
         $date = rawurldecode($_POST[$key]);   
         $table .= '<tr>' . cell('&nbsp;', $type='td', $colspan='9') . '</tr>';
         if($p_brief) {
            $table .= ip_row(array('ip','ua'), $ip,$date); 
          }  
            else  {
            $table .= ip_row(array('ip','page_users','ua', 'qs_data'), $ip,$date); 
            }
     }
  }
  return $table . '</table>';
}

function qs_data(&$ar,$ip) {
           $search_terms = "";
           if(!is_array($ar)) return cell('&nbsp;&nbsp;&nbsp;nothing found');
            foreach($ar as $word =>$data) {
                if(isset($data[$ip])) {
                   $search_terms .= "&nbsp;&nbsp;&nbsp;$word (" .  $data[$ip] . ')<br />'; 
                }
            }
            if($search_terms) {
                return cell($search_terms);
             }
             else  return cell('&nbsp;&nbsp;&nbsp;nothing found');


}

function ip_row($which,$index,$date,$show_country=true, $show_ip=false) {
   if(isset($_POST['country_code'])) {
     $country_code = rawurldecode($_POST['country_code']);
   }  
   else $country_code = false;
   
   $row = '<tr>';
   if($show_ip) {
      $row .= cell($index,'th');
   }
   $row .= cell(str_replace('_','/',$date). '&nbsp;&nbsp;&nbsp;');
   
   foreach($which as $type) {
     $temp = load_data($type,$date);  
     
        if($type == 'qs_data' ) {  
             if(!empty($temp)) {
                $row .= qs_data($temp['words'] ,$index); 
                $row .= qs_data($temp['ns'] ,$index); 
                $row .= qs_data($temp['extern']['name'] ,$index);              
                $row .= qs_data($temp['extern']['val'] ,$index);              
             }
             else $row .= cell('&nbsp;') . cell('&nbsp;') . cell('&nbsp;') . cell('&nbsp;');
      }
      
      else if(!empty($temp) && isset($temp[$index])) {          
              if($type == 'page_users') {
                  sort($temp[$index]);
                  $row .= cell(implode('<br />',$temp[$index]));
              }
              else if($type == 'ua') {
                   $data = $temp[$index];          
                    $cc = array_shift($data);                   
                    $country=qs_get_country_name($cc) ;
                    if($country_code && $cc != $country_code) {
                        return cell("Country of IP ($country) does not match: " . qs_get_country_name($country_code),'td',9 ); 
                    }    
                    $uas = '&nbsp;&nbsp;&nbsp;' . implode(',&nbsp;',$data);                    
                    if($show_country) $row .= cell('&nbsp;&nbsp;&nbsp;' ." $country");
                    $row .= cell($uas);
              }
              else $row .= cell($temp[$index]);
     }
     else {
         $row .= cell('no data');
    }
  }
  return $row . '</tr>';
}

function cell($data, $type='td', $colspan="",$p_brief=false) {
  $class = "padded";
  if($colspan) $colspan = "colspan='$colspan'";
  if($type == 'caption' && !$p_brief) {
     $class .= ' qs_cap';
   }
   else if($type == 'caption' ) {
       $class .= ' qs_bold_left';
  }   
   if($type == 'th') {
     $wrap = ' NOWRAP ';
     }
     else $wrap ="";
   return "<$type class='$class' $colspan $wrap valign='top'>$data</$type>";
}

function load_data($which,$date) {
    $meta_path = rawurldecode($_POST['meta_path']);
    $file['ip'] =  $meta_path . $date .'/ip.ser';
    $file['misc_data'] = $meta_path . $date .'/misc_data.ser';
    $file['pages'] =$meta_path . $date .'/pages.ser';
    $file['ua'] =$meta_path . $date .'/ua.ser'; 
    $file['qs_data'] =$meta_path . $date .'/qs_data.ser';
    $file['page_users'] =$meta_path . $date .'/page_users.ser';     
    $file['page_totals'] = $meta_path .'page_totals.ser';
    $temp= unserialize(io_readFile($file[$which],false));
    if(!$temp) $temp = array();
    return $temp;
    
}

function qs_format_pages($pages) { 
  foreach($pages as $page=>$ar) {
     $page = rawurlencode($page);
     echo "<h1>$page</h1>\n";
     echo '<div class="level2"><h3>Total accesses for  ' . $page . ': '  . $ar['accesses'] . '</h3>';
     foreach($ar['ips'] as $ip) {        
        $ipdata = ip_data($ip,isset($_POST['p_brief']));
        echo rawurlencode($ipdata);
     }
     echo "</div>\n";
  }
}

 function qs_pages_search_i ($needle = null,$month)
 {
  
    $pages = load_data('pages',$month);
    if(isset($pages['page'][$needle]))return array($needle=>$pages['page'][$needle]);
    $ret_ar = array();
    
    foreach($pages['page'] as $key => $val)
    {
        
        if(stristr($key, $needle) !== false) {
             $ret_ar[$key] = $val;
         }

    }
  
    return $ret_ar;

 }
  
 function qs_process_pages ($page) {
   $month = rawurldecode($_POST['date']);   
   $page_users = load_data('page_users',$month);
   $found = qs_pages_search_i($page,$month);
   if(!$found) { echo "no data\n"; exit; }

   $temp = array();
   foreach($found as $page=>$accesses) {
       if(isset($page_users[md5($page)])) {
           $temp[$page] = array('accesses'=>$accesses, 'ips'=>$page_users[md5($page)]);
       }
       
   }
  
   return $temp;
 }
 
 function qs_output_countries($date,$cc,$country,$p_brief=false) {
    $ua_data = load_data('ua',$date);
    if(!empty($ua_data)) {
        foreach($ua_data as $ip=>$ar) {
           if($ar[0] == $cc) {                      
               echo rawurlencode(ip_row(array('ip','page_users','ua', 'qs_data'), $ip,$date,$p_brief,true)); 
               echo "\n";
           }
        }
   }
 
 }
 function qs_process_country($cc,$country_name) {
    $p_brief = false;
    echo '<table border cellspacing="0">';
    echo rawurlencode(cell($country_name,'caption') ."\n");
    $header ='<tr>' . cell('IP', 'th') . cell('Month','th') . cell('Accesses','th'). cell('Pages','th') . cell('User Agent','th')  . cell('Search Terms','th') 
                         . cell('Name Spaces','th')    .  cell('Query String Names','th') .cell('Query String Values','th') . '</tr>';
     echo  rawurldecode($header);                   
    $date = rawurldecode($_POST['date']);       
    qs_output_countries($date,$cc,$country_name) ;

   $keys =array_keys($_POST);
    foreach($keys as $key) {
         if(strpos($key,'date_') !== false) {
           $date = rawurldecode($_POST[$key]);   
            qs_output_countries($date,$cc,$country_name) ;
         }
       }    
       echo '</table>';
 }



function display_post_data() {
    $keys =array_keys($_POST);
  
   $data = "<pre>" . print_r($_POST,true)  . '</pre>';
    
    $data .= "<pre>" . print_r($keys,true)  . '</pre>';
    echo $data . DOKU_INC;
    
    exit;
}
?>


