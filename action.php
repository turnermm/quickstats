<?php
/**
 * Creates Simple statistics files based on incoming IP
 *
 * @author  Myron Turner <turnermm02@shaw.ca>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('QUICK_STATS')) define ('QUICK_STATS',DOKU_PLUGIN . 'quickstats/');
require_once DOKU_PLUGIN.'action.php';
/* for backward compatiblity */
if(!function_exists('utf8_strtolower')) {  
require_once(DOKU_INC.'inc/common.php'); 
require_once(DOKU_INC.'inc/utf8.php'); 
}

/*
error_reporting(E_ALL);
ini_set('display_errors','1');
*/

class action_plugin_quickstats extends DokuWiki_Action_Plugin {
    private $page_file;
    private $ip_file;
    private $misc_data_file;
    private $pages;
    private $ips;
    private $misc_data;
    private $page_totals_file;
    private $is_edit_user=false;
    private $year_month;
    private $totals;
    private $SEP = '/';    
    private $show_date;
    private $ua_file;
    private $helper;
    private $ipaddr;
    private $qs_file;
    private $dw_tokens; // query string names to omit from stats
    private $page_users_file;
    
    function __construct() {
    
         $ip = $_SERVER['REMOTE_ADDR'];         
         if($this->is_excluded($ip, true)){          
           exit("403: Not Available");
         }         
        $this->ipaddr = $ip;
        $today = getdate();
            
        $ns_prefix = "quickstats:";
        $ns =  $ns_prefix . $today['mon'] . '_'  . $today['year'] . ':'; 
        $this->page_file = metaFN($ns . 'pages' , '.ser');  
        $this->ua_file = metaFN($ns . 'ua' , '.ser');  
        $this->ip_file = metaFN($ns . 'ip' , '.ser');  
        $this->misc_data_file = metaFN($ns . 'misc_data' , '.ser');  
        $this->qs_file = metaFN($ns . 'qs_data' , '.ser');  
        $this->page_users_file = metaFN($ns . 'page_users' , '.ser');  
        $this->page_totals_file = metaFN($ns_prefix . 'page_totals' , '.ser');  
        
        $this->year_month = $today['mon'] . '_'  .$today['year'];
        
        if( preg_match('/WINNT/i',  PHP_OS) ) {    
                    $this->SEP='\\';                
        }
        $this->show_date=$this->getConf('show_date');
        $this->dw_tokens=array('do',  'sectok', 'page', 's[]','id','rev','idx');
        $conf_tokens = $this->getConf('xcl_name_val');
        if(!empty($conf_tokens)) {
            $conf_tokens = explode(',',$conf_tokens);            
            if(!empty($conf_tokens)) {
                $this->dw_tokens = array_merge($this->dw_tokens,$conf_tokens);
            }
        }
        $this->helper = $this->loadHelper('quickstats', true); 
    }
        /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(&$controller) {
    
       $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'set_cookies');
       $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'search_queries');              
    
        $controller->register_hook('DOKUWIKI_DONE', 'BEFORE', $this,
                                   'add_data');
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'load_js');                                   
    }
    
    function isQSfile() {
         global $ID;
         if(!$this->helper->is_inConfList($ID) ) { 
            return $this->helper->is_inCache($ID) ;
         }
         return true;
    }

    function load_js(&$event, $param) {    
           global $ACT, $ID;
           if($ACT != 'show' && $ACT != 'preview') return;  // don't load the sortable script unless it's potentially needed
           
           if(!$this->isQSfile()) return;

           $event->data["script"][] = array (
          "type" => "text/javascript",
          "src" => DOKU_BASE."lib/plugins/quickstats/scripts/sorttable-cmpr.js",
          "_data" => "",
        );
    }
    
    function set_cookies(&$event, $param) {    
    
    global $ACT;
    global $ID;
            
        if(is_array($ACT) || $ACT=='edit') {                    
                 $expire = time()+3600;
                 setcookie('Quick_Stats','abc', $expire, '/');                  
                 $this->is_edit_user=true;
                 return;
         }
         
      if(isset($_COOKIE['Quick_Stats'])) {                         
                setcookie("Quick_Stats", 'abc', time()-7200, '/');
                $this->is_edit_user=true;
     }

   }
       
    function search_queries(&$event, $param) {
        global $ACT;
        
        if(is_array($ACT) || $this->is_edit_user)  return;
        if($ACT !='show' && $ACT != 'search') return;
        //login,admin,profile, revisions,logout
        
        if(empty($_SERVER['QUERY_STRING']) || $this->is_excluded($this->ipaddr)) return;
            
        $queries = unserialize(io_readFile($this->qs_file,false));         
        if(!$queries) $queries = array('words'=>array(), 'ns'=>array(), 'extern'=>array() );        
        
       $elems = explode('&',html_entity_decode($_SERVER['QUERY_STRING'])) ;
      
        $data_found = false;
        if(count($elems > 1)) 
        {
            $words = array();
            $temp = array();
            foreach ($elems as $el) {
                if(isset($el) && $el) {                  
                   list($name,$value) = explode('=',$el);
                   $temp[$name]=$value;
                }
            }
            if(isset($temp['do']) && $temp['do'] == 'search') {
                 $data_found = true;
                 if(function_exists ('idx_get_indexer')) {
                        $ar = ft_queryParser(idx_get_indexer(), urldecode($temp['id']));
                 }
                 else $ar = ft_queryParser(urldecode($temp['id']));         

                 if(!empty($ar['phrases']) && !empty($ar['not'])) {
                     $words = array_diff($ar['words'],$ar['not']);
                }
                else {
                       $words = $ar['words'];                       
                    }
            
                if(!empty($words)) {    
                    foreach($words as $word) {
                        $this->set_queries($queries,$word,'words');
                   }
                }
                
                if(!empty($ar['ns'])) {                
                    foreach($ar['ns'] as $ns) {
                        $this->set_queries($queries,$ns,'ns');
                    }            
                }
            }
            else {
                
                foreach($this->dw_tokens as $t) {
                    if(isset($temp[$t])) {
                        unset($temp[$t]);
                   }
                }                

                if(count($temp)) {
                    $keys = array_keys($temp);
                    foreach($keys as $k) {
                         if(preg_match('/rev\d*\[\d*\]/', $k)) {
                             unset($temp[$k]);
                         }
                    }
                    if(count($temp)) $data_found = true;
                }
                
                foreach($temp as $name=>$val) {
                   $this->set_queries($queries['extern'],urldecode($name),'name');
                   if(!$val) $val = '_empty_';                   
                   $this->set_queries($queries['extern'],urldecode($val),'val');
				   $this->set_named_values($queries['extern']['name'][urldecode($name)],urldecode($val));
                }
            }
          
            if($data_found) {                 
                io_saveFile($this->qs_file,serialize($queries));
            }
        }
    }   
    
	function set_named_values(&$queries,$val="_empty_") {		
	
	    if(!isset($queries['values'])) {
		       $queries['values'] = array();
        }		
	    if(!isset($queries['values'][$this->ipaddr])) {
	          $queries['values'][$this->ipaddr] = array();
       }
	   if(!in_array($val, $queries['values'][$this->ipaddr])) {
	            $queries['values'][$this->ipaddr][] = $val;
	   }
	}
	
    function set_queries(&$queries,$word,$which) {
            if(!isset($queries[$which][$word])) {
                $queries[$which][$word]['count'] = 1;
            }
            else {
                $queries[$which][$word]['count'] += 1;
            }
            if(!isset($queries[$which][$word][$this->ipaddr])) {
                $queries[$which][$word][$this->ipaddr] = 1;
            }
            else $queries[$which][$word][$this->ipaddr] += 1;    
    }
    
    function msg_dbg($what,$prefix="",$type="1") {
        if(is_array($what)) {
            $what = print_r($what,true);
        }        
        msg("<pre>$prefix $what</pre>",$type);
    }
    function load_data() {
        
        $this->pages = unserialize(io_readFile($this->page_file,false));
        if(!$this->pages) $this->pages = array();
        
        $this->ips = unserialize(io_readFile($this->ip_file,false));
        if(!$this->ips) $this->ips = array();
        
        $this->totals = unserialize(io_readFile($this->page_totals_file,false));
        if(!$this->totals) $this->totals = array();
    }

     function save_data() {
         io_saveFile($this->ip_file,serialize($this->ips));
         io_saveFile($this->page_file,serialize($this->pages));    
         $this->totals[$this->year_month] = $this->pages['site_total'] ;
         io_saveFile($this->page_totals_file,serialize($this->totals));
     }
     
     function is_excluded($ip,$abort=false) {        
       if(!$abort) {
            $xcl = $this->getConf('excludes');
        }
        else  $xcl = $this->getConf('aborts');    
        $xcl=str_replace("\040", '',$xcl);
    
        if(!$xcl) return false;
        
        $xcludes=explode(',',$xcl);
        $regex = '#'. implode('|',$xcludes) . '#';

        if(preg_match($regex,$ip)){
            return true;
        }
        return false;
     }
     
    /**
     * adds new data to stats files  
     *
     * @author  Myron Turner <turnermm02@shaw.ca>
     */
    function add_data(&$event, $param) {
    global $ID;
    global $ACT;
   
    if($this->is_edit_user) return;
    if($ACT != 'show') return;

        $this->load_data();

        require_once("GEOIP/geoipcity.inc");
        require_once('db/php-local-browscap.php');       

        $ip = $_SERVER['REMOTE_ADDR'];              

         if($this->is_excluded($ip)){        
             return;
         }         
           
        $this->misc_data = unserialize(io_readFile($this->misc_data_file,false));
        if(!$this->misc_data) $this->misc_data = array();

        $country = $this->get_country($ip);
        if($country) {
            if(!isset($this->misc_data['country'] [$country['code']])) { 
               $this->misc_data['country'] [$country['code']] =1;            
            }
            else {
                $this->misc_data['country'] [$country['code']] +=1;
            }
          }
          
         $browser =  $this->get_browser();      
        
          io_saveFile($this->misc_data_file,serialize($this->misc_data));          
          unset($this->misc_data);
          
          $wiki_file = wikiFN($ID);
            if(file_exists($wiki_file)) {
                 if(!$this->pages) {             
                    $this->pages['site_total'] = 1;
                    $this->pages['page'][$ID] = 1;
                    $this->ips['uniq'] = 0;
                }
                else {
                    $this->pages['site_total'] += 1;
                    $this->pages['page'][$ID]  += 1;                        
                }
            }    
            
            if(!array_key_exists($ip, $this->ips)) {
                 $this->ips[$ip] = 0;
                 $this->ips['uniq'] = (!isset($this->ips['uniq'])) ? 1 : $this->ips['uniq'] += 1;
            }
        
         $this->ips[$ip] += 1;
         if($this->show_date) {
            $this->pages['date'][md5($ID)] = time();
         }
         $this->save_data();
         $this->pages=array();
         $this->ips=array();
       
        
        $this->ua = unserialize(io_readFile($this->ua_file,false));
        if(!$this->ua) $this->ua = array();
        if(!isset($this->ua['counts'])) {
              $this->ua['counts'] = array();
         }
    
        if(!isset($this->ua['counts'][$browser])) {
            $this->ua['counts'][$browser]=1;
        }
        else $this->ua['counts'][$browser]++;
        
        if(!isset($this->ua[$ip])) {
              $this->ua[$ip] = array($country['code']);
         }
        if(isset($browser) && !in_array($browser, $this->ua[$ip])) {           
             $this->ua[$ip][]=$browser;    
        } 
         io_saveFile($this->ua_file,serialize($this->ua));
        $this->ua = array();

        $this->pusers = unserialize(io_readFile($this->page_users_file,false));
        if(!$this->pusers) $this->pusers = array();
        $page_md5 = md5($ID);
        if(!isset($this->pusers[$page_md5])) {
            $this->pusers[$page_md5] = array();
        }
        if(!isset($this->pusers[$ip])) {
            $this->pusers[$ip] = array();
        }
        $pushed_new = false;
        if(!in_array($ip,$this->pusers[$page_md5])) {
            $pushed_new = true;
            array_push($this->pusers[$page_md5],$ip);
        }
        if(!in_array($ID,$this->pusers[$ip],$ID)) {
            $pushed_new = true;
            array_push($this->pusers[$ip],$ID);
        }
        if($pushed_new) {
            io_saveFile($this->page_users_file,serialize($this->pusers)); 
        }    
    }
    
   function get_browser() {
   
    $db= QUICK_STATS . 'db/php_browscap.ini';
    $browser=get_browser_local(null,true,$db);
    
    if(!isset($browser['browser'])) return;

    $this->set_browser_value($browser['browser']);    
        
    if(!isset($browser['platform'])) return;    
    $this->set_browser_value($browser['platform'],'platform');    
        
    if(!isset($browser['version'])) return;
    $this->set_browser_value($browser['parent'],'version');    
    if(isset($browser['parent']) && $browser['parent']) {
       return $browser['parent'];
    }   
    return $browser['browser'];
    
  }   
 
    function set_browser_value($val, $which='browser') {
        if(!isset($this->misc_data[$which][$val])) { 
           $this->misc_data[$which] [$val] =1;            
        }
        else {
            $this->misc_data[$which] [$val] +=1;
        }

   }    
   
    function get_country($ip=null) {
  
        if(!$ip) return null;        
        
        if($this->getConf('geoplugin')) {        
            $country_data = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip=' .$ip));        
            return (array('code'=>$country_data['geoplugin_countryCode'],'name'=>$country_data['geoplugin_countryName']));
        }
        
        if($this->getConf('geoip_local')) {
             $giCity = geoip_open(QUICK_STATS. 'GEOIP/GeoLiteCity.dat',GEOIP_STANDARD);        
        }
        else {
            $gcity_dir = $this->getConf('geoip_dir');                
            $gcity_dat=rtrim($gcity_dir, "\040,/\\") . $this->SEP  . 'GeoLiteCity.dat';                        
            $giCity = geoip_open($gcity_dat,GEOIP_STANDARD);
        }
       
        $record = geoip_record_by_addr($giCity, $ip);     
        if(!isset($record)) {
             return array();
        }
       
        return (array('code'=>$record->country_code,'name'=>$record->country_name));
    }
    

}