<?php

if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../../../') . '/');

require_once DOKU_INC . 'inc/init.php';
require_once DOKU_INC . 'inc/io.php';

require DOKU_INC . 'lib/plugins/quickstats/GEOIP/cc_arrays_dat.php';
//display_post_data();
global $UserAgentArray, $INPUT;
global $PAGE_USERS_ARRAY;
$UserAgentArray = false;
$PAGE_USERS_ARRAY = false;
if(isset($_REQUEST['qs_script_max_time'])) {
    $script_max_time = $INPUT->int('qs_script_max_time');
}
else {
   $script_max_time = 60;
}

 if( !ini_get('safe_mode') ){
          set_time_limit($script_max_time);
 } 

$qs_start_time=time();

$priority = "";
qs_formatQuery() ;
echo "<div id='quickstats_admin_disp'>"; 
if(isset($_POST['priority']) && $_POST['priority']) {
    $priority = $INPUT->str('priority');
    if($priority == 'country' && isset($_POST['user_agent'])) {
        $priority = 'agent';
    }
}

switch ($priority) {
case 'page':
    $page = rawurldecode($INPUT->str('page'));
    $keys =array_keys($_POST);
    foreach($keys as $key) {
         if(strpos($key,'date') !== false) {
          $temp = array();  
          $month = rawurldecode($INPUT->str($key)); 
          $temp =  qs_process_pages ($page,$month);
          qs_format_pages($temp, $month);
         }
    }
    break;
case 'ip':
   if(isset($_POST['ip']) && $_POST['ip']) {
      echo rawurlencode(ip_data()) . "\n";
   }
    break;
case 'country':
   if(isset($_POST['country_code']) && $_POST['country_code']) {
      qs_process_country($_POST['country_code'],$_POST['country_name']) . "\n";
   }
    break;
case 'agent':
   if(isset($_POST['user_agent'])) {
      qs_process_agents($_POST['user_agent']);
   }
    break;
default:
  echo "Please check your query.  It cannot be completed in its present form.  You do not seem to have chosen a priority.<br />";
  echo "If this persists, please post an error report either to  the quickstats site at https://github.com/turnermm/quickstats<br />or to the quickstats page at http://www.dokuwiki.org/plugin:quickstats.";
  
          
}
echo "<b>Total Accesses: " . qs_total_accesses(0) . '</b>';
$extime = time() - $qs_start_time;
if($extime) {
    echo "<br /><b>Execution Time: " . $extime . ' seconds</b>';
}
echo '</div>';
exit;

function qs_total_accesses($n) {
static $total=0;
    if(is_numeric($n)) {
        $total += $n;
   }    
   return $total;
}
function ip_data($ip=false,$p_brief=false) {
   $row = "";
   $table = '<table border=1 cellspacing="0">';   
   $date = rawurldecode($_POST['date']);   
   if($ip === false)  $ip =rawurldecode($_POST['ip']);
 
  
  if($p_brief) {    
    $result = ip_row(array('ip','ua'), $ip,$date); 
    if($result) {
        $table .= '<tr>' . cell("$ip ",'caption',false,$p_brief) . '</tr>';      
        $table .= qs_header(array('month','access','country','agent'));
        $table .= $result;
    }
  }  
  else  {
      $result = ip_row(array('ip','page_users','ua', 'qs_data'), $ip,$date); 
      if($result) {
          $table .= '<tr>' . cell("Data for IP address:  $ip ",'caption',false,$p_brief) . '</tr>';
          $table .= qs_header(array('month','access','page','country','agent','search','ns','name','val'));  
          $table .= $result;
      }
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
           if(!is_array($ar)) return cell('&nbsp;&nbsp;&nbsp;');
           foreach($ar as $word =>$data) {			  
               $word = htmlentities($word);
               $word = str_replace('%','&#37;',$word);
                if(isset($data[$ip])) {
                   $search_terms .= "&nbsp;&nbsp;&nbsp;$word (" .  $data[$ip] . ')<br />'; 
                }
            }
            if($search_terms) {
                return cell($search_terms);
             }
             else  return cell('&nbsp;&nbsp;&nbsp;');


}

function qs_check_time() {
   global $qs_start_time, $script_max_time;
   $tm=time();
   if($tm-$qs_start_time > $script_max_time-1) {
      echo "<b>Timed out after $script_max_time seconds.  See the Query How-To </b><br /><br />";
	  exit;
   }
}

function ip_row($which,$index,$date,$show_country=true, $show_ip=false,$check_agent=false) {
   global $UserAgentArray;
   global $PAGE_USERS_ARRAY;
   $accesses = 0;
   if(isset($_POST['country_code'])) {
     $country_code = rawurldecode($_POST['country_code']);
   }  
   else $country_code = false;
qs_check_time();
   $row = '<tr>';
   if($show_ip) {
      $row .= cell($index,'th');
   }
   $row .= cell(str_replace('_','/',$date). '&nbsp;&nbsp;&nbsp;');
   
   foreach($which as $type) { 
     if($type == 'ua' && $UserAgentArray !== false) {
         $temp = $UserAgentArray;
     }
     else if ($type == 'page_users' && $PAGE_USERS_ARRAY !== false) {
         $temp = $PAGE_USERS_ARRAY;
     } 
     else $temp = load_data($type,$date);  
     
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
                        //return cell("Country of IP ($country) does not match: " . qs_get_country_name($country_code),'td',9 ); 
                        return null;
                    }    
                    $uas = '&nbsp;&nbsp;' . implode('<br />&nbsp;&nbsp;',$data);                   
                    if($check_agent && strpos($uas,  $check_agent) === false) return null;                  
                    if($show_country) $row .= cell('&nbsp;&nbsp;&nbsp;' ." $country");
                    $row .= cell($uas);
              }
              else {
                  $row .= cell($temp[$index]);
                  $accesses += $temp[$index];
              }
     }
     else {
         $row .= cell('&nbsp;');
    }
  }
  if($row) {
     qs_total_accesses($accesses);
  }
  return $row . '</tr>';
}

function qs_header($which) {
    $thds = array('ip'=>'IP', 'month'=>'Month',  'access'=>'Accesses', 'page'=>'Pages','country'=>'Country', 'agent'=>'UserAgent', 'search'=>'Search Terms',    
                      'ns'=>'Namespaces', 'name'=>'Query String<br />Names', 'val'=>'Query String<br />Values');
    $header ='<tr>';
    foreach($which as $th) {
       $header .= cell($thds[$th],'th');
    }
    return $header . '</tr>';
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
  
     
   return "<$type class='$class' $colspan nowrap valign='top'>$data</$type>";
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

function get_page_row($ip, $date, $p_brief=false,$agent=false) {
  $table = "";
  if($p_brief) {    
    $result = ip_row(array('ip','ua'), $ip,$date,true,true,$agent); 
    if($result) {
        $table .= $result;
    }
  }  
  else  {                                                                                     
      $result = ip_row(array('ip','page_users','ua', 'qs_data'), $ip,$date,true,false,$agent); 
      if($result) {
          $table .= '<tr>' . cell("Data for IP address:  $ip ",'caption',false,$p_brief) . '</tr>';
          $table .= qs_header(array('month','access','page','country','agent','search','ns','name','val'));  
          $table .= $result;
      }
  }  
  return $table;
 } 

/**
 *  @param $pages: array returned from qs_process_pages()
 *  outputs header of page name and page access, then ip data for the ip using ip_row
 */
function qs_format_pages($pages,$month) { 
   global $PAGE_USERS_ARRAY;   
   $PAGE_USERS_ARRAY = load_data('page_users',$month);
   if(isset($_POST['user_agent'])) {
       $agent = $_POST['user_agent'];
   }   
    else $agent = false;
    
    if(isset($_POST['p_brief'])) {
        foreach($pages as $page=>$ar) {
            $page = rawurlencode($page);
            $header = "<h1>$page</h1>\n";
            $header .=  '<div class="level2 qs_brief"><h3>Total accesses for  ' . $page . ': '  . $ar['accesses'] . '</h3>' . "\n";     
            $header .=  "<table cellspacing='0' class='qs_brief_table'>\n";
            
            $header_displayed = false;
            $close_div = false;
            foreach($ar['ips'] as $ip) {   
                $ipdata = get_page_row($ip, $month, isset($_POST['p_brief']),$agent) ;
                if($ipdata) {         
                    if(!$header_displayed) {
                        echo $header;
                        echo qs_header(array('ip','month','access','country','agent'));
                        $header_displayed = true;
                        $close_div = true;
                    }
                    echo rawurlencode($ipdata);             
                }
            }
            if($close_div) echo "</table></div>\n";
        }
    }
    else {
        foreach($pages as $page=>$ar) {
            $page = rawurlencode($page);
            foreach($ar['ips'] as $ip) {        
                $ipdata = get_page_row($ip, $month, isset($_POST['p_brief']),$agent) ;
                if($ipdata) {
                    echo "<h1>$page</h1>\n";
                    echo '<div class="level2"><h3>Total accesses for  ' . $page . ': '  . $ar['accesses'] . '</h3>' . "\n<table>\n";
                    echo rawurlencode($ipdata);
                    echo "</table></div>\n";
                }
            }
        }     
    }
}

  /**
  *  @param $needle: page name or partial name from $_POST
  *  @param $month: formatted for path name: month_year 
  *  @return:  array of page_names=>accesses for qs_format_pages()
  */
 function qs_pages_search_i ($needle = null,$month)
 {
  
    $pages = load_data('pages',$month);
    if(!isset($pages['page'])) {
       return array();
    }   
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
  
  /**
  *  @param $page: page name or partial name from $_POST
  *  @return array of [page_names] = array(accesses=>integer, ips=>array(ip_addresses)) 
  */
 function qs_process_pages ($page, $month) {   
   $temp = array();   
   $page_users = load_data('page_users',$month);
   $found = qs_pages_search_i($page,$month);
   if(!$found) { 
       return $temp; 
    }
   
   foreach($found as $page=>$accesses) {
       if(isset($page_users[md5($page)])) {
           $temp[$page] = array('accesses'=>$accesses, 'ips'=>$page_users[md5($page)]);
       }
   }
  
   return $temp;
 }
 
 function qs_output_countries($date,$cc,$country) {
   global $UserAgentArray;
    $ua_data = load_data('ua',$date);
    $UserAgentArray = $ua_data;
    if(!empty($ua_data)) {
        foreach($ua_data as $ip=>$ar) {
           if(isset($ar[0]) && $ar[0] == $cc) { 
               echo rawurlencode(ip_row(array('ip','page_users','ua', 'qs_data'), $ip,$date,false,true)); 
               echo "\n";
           }
        }
   }
 $UserAgentArray = false;
 }
 
 function qs_process_country($cc,$country_name) {
    $p_brief = false;
    echo '<table border cellspacing="0">';
    echo rawurlencode(cell($country_name,'caption') ."\n");
    echo qs_header(array('ip','month','access','page','agent','search','ns','name','val'));    
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

 function qs_output_agents($agents,$date)  {
          foreach($agents as $ip) {
              echo rawurlencode(ip_row(array('ip','page_users','ua', 'qs_data'), $ip,$date,true,true)); 
          }
       
 }
 
 function qs_process_agents($agent=null) {
   if(!$agent) return;
    echo '<table border cellspacing="0">';
    echo rawurlencode(cell($agent,'caption') ."\n");

    echo qs_header(array('ip','month','access','page','country','agent','search','ns','name','val'));
    $date = rawurldecode($_POST['date']);           
    $agents = qs_agent_search_i ($agent,$date);
    qs_output_agents($agents,$date) ;

   $keys =array_keys($_POST);
    foreach($keys as $key) {
         if(strpos($key,'date_') !== false) {
           $date = rawurldecode($_POST[$key]); 
          $agents = qs_agent_search_i ($agent,$date) ;
           qs_output_agents($agents,$date) ;
         }
       }    
       echo '</table>';
 }
 
 function qs_agent_search_i ($needle = null,$month)
 {
  global $agents;
   $agents = load_data('ua',$month);
   $ret_ar = array();
    
    foreach($agents as $ip => $val)
    {        
        if(isset($val[1]) && stristr($val[1], $needle) !== false) {
             //$ret_ar[$key] = $val;
             $ret_ar[] = $ip;
         }
    }
    return $ret_ar;

 }

function qs_formatQuery() {
    global $INPUT;
    $months = array("",'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
    $fields = array('country_name'=>'Country',  'user_agent'=>'User Agent',  'priority'=>'Priority',  'ip'=>'IP Address', 
                           'page'=>'&lt;Namespace:&gt;Page',  'date'=>'Month/Year' );
    
    $ip_set = false;
    $priority = false;
	
    echo "\n" . '<table><tr><th class="thead">Query</th><tr>' . "\n";
    $str = "";    
    foreach($fields as $field=>$label) {
        if(!isset($_POST[$field])) continue;
        $value = rawurldecode($INPUT->str($field));
        switch($field) {      
            case 'date':
                $str .= "<th align='right'>$label:&nbsp;</th><td>";      
                $keys=array_keys($_POST);
                foreach($keys as $key) {
                    if(strpos($key,'date') !== false) {                      
                       list($mon,$year) =   explode('_',$_POST[$key]);
                       $str .=  $months[$mon] . ' ' . $year .' &nbsp;';                                        
                    }
                }               
                $str .= '&nbsp;</td>'; 
                break; 
             case 'priority':   
                $priority = $value;   
                $str .= "<th align='right'>$label:&nbsp;</th><td>";
                $str .= $value . '&nbsp;&nbsp;&nbsp;&nbsp;</td>';
                break;
            case 'ip':
                $str .= "<th align='right'>$label:&nbsp;</th><td>";
                $str .= $value . '&nbsp;&nbsp;&nbsp;&nbsp;</td>';            
                $ip_set = true;            
                break;
            default: 
                $str .= "<th align='right'>$label:&nbsp;</th><td>";
                $str .= $value . '&nbsp;&nbsp;&nbsp;&nbsp;</td>';
                break;
        }
 
    }
   
    if($ip_set && $priority != 'ip') {
        $str .= '<caption align="bottom">IP Addresses are matched only where priority is set to IP (and secondary fields are ignored)</caption>';    
    }
    $str .= "</table>\n";
    echo "$str</b><br />";
	
}
function display_post_data() {
    $keys =array_keys($_POST);
  
   $data = "<pre>" . print_r($_POST,true)  . '</pre>';
   echo $data;
   $data .= "<pre>" . print_r($keys,true)  . '</pre>';
   echo $data . DOKU_INC;
   
   exit;
}
?>


