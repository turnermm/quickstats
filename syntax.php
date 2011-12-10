<?php
/**  
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author    Myron Turner <turnermm02@shaw.ca>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
define ('QUICK_STATS',DOKU_PLUGIN . 'quickstats/');
require_once('GEOIP/ccArraysDat.php');
//error_reporting(E_ALL);
//ini_set('display_errors','1');
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_quickstats extends DokuWiki_Syntax_Plugin {

  private $page_file;
	private $ip_file;
	private $misc_data_file;
	private $pages;
	private $ips;
	private $misc_data;	
	private $cc_arrays;
	private $long_names =-1;
    private $show_date;
    private $ua_file;
    private $ua_data;
    private $giCity;
    private $SEP = '/';    
	function __construct() {

		$this->cc_arrays = new ccArraysDat();
		$this->long_names = $this->getConf('long_names');
		if(!isset($this->long_names)  || $this->long_names <= 0) $this->long_names = false;
        $this->show_date=$this->getConf('show_date');
        if( preg_match('/WINNT/i',  PHP_OS) ) {    
			$this->SEP='\\';				
		}
	}

   /**
    * Get an associative array with plugin info.    
    */
    function getInfo(){
        return array(
            'author' => 'Myron Turner',
            'email'  => 'turnermm02@shaw.ca',
            'date'   => '2011-11-2',
            'name'   => 'Quickstats Plugin',
            'desc'   => 'Output browser/user stats to wiki page',
            'url'    => 'http://www.dokuwiki.org/plugin:quickstats',
        );
    }

   /**
    * Get the type of syntax this plugin defines. 
    */
    function getType(){
        return 'substition';
    }
	
    /**
     * What kind of syntax do we allow (optional)
     */
//    function getAllowedTypes() {
//        return array();
//    }
   
   /**
    * Define how this plugin is handled regarding paragraphs.
    *   
    * normal:  The plugin can be used inside paragraphs.
    * block: Open paragraphs need to be closed before plugin output.
    * stack  (Special case): Plugin wraps other paragraphs.
    */
    function getPType(){
        return 'block';
    }

   /**
    * Where to sort in?
    *  
    */
    function getSort(){
        return 100;
    }


   /**
    * Connect lookup pattern to lexer.
    *
    * @param $aMode String The desired rendermode.
    * @return none
    * @public
    * @see render()
    */
    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('~~QUICKSTATS:.*?~~',$mode,'plugin_quickstats');
    }
	
//    function postConnect() {
//      $this->Lexer->addExitPattern('</TEST>','plugin_quickstats');
//    }


   /**
    * Handler to prepare matched data for the rendering process.
    *
    */
    function handle($match, $state, $pos, &$handler){
	
        switch ($state) {
          case DOKU_LEXER_SPECIAL :		 			
		    $match =  trim(substr($match,13,-2));			
			if($match) {
			    $depth = false;
				if(strpos($match,';;') !== false) {
				      list($match,$depth) = explode(';;',$match);				
				}
				
			     $date = "";
			     if(strpos($match,'&') !== false) {		
				 
				         /* 
						     catch syntax errors 			
						     assumes single parameter with trailing or prepended & 
						 */
					     if($match[strlen($match)-1] == '&'  || $match[0]  == '&') { 
						     $match = trim($match,'&');
							  if($this->is_date_string($match)) {
							       return array('basics',$match,$depth);			       
							  }
						     return array('basics',"",$depth);			       
						 }
						 
						 /* process valid paramter string */
						 list($m1,$m2) = explode('&',$match,2);
						 if($this->is_date_string($m1)) {
						     $date=$m1;
							 $match = $m2;
						 }
						 else {
						 	$date=$m2;
							$match = $m1;
						 }
				 }
				 else  if($this->is_date_string($match)) {
					 $date = $match;
				     $match = 'basics';					 
				 }
			}
			else {
			       return array('basics',"",$depth);			       
            }			
			 
             return array(strtolower($match),$date,$depth);
             break;
        }
        return array();
    }

	function is_date_string($str) {
	     return preg_match('/\d\d_\d\d\d/',$str);
    }	
	
   /**
    * Handle the actual output creation.    
    */
    function render($mode, &$renderer, $data) {
	 
        if($mode == 'xhtml'){
		   
		   list($which, $date_str,$depth) = $data;
		   $this->row_depth('all');
		   if($depth) {
		       $this->row_depth($depth);
			  }
			  		  
		   $this->load_data($date_str,$which);
		   if($which == 'basics') {
				$renderer->doc .= "<div class='quickstats basics' style='margin: auto;width: 820px;'>" ;          		
		   }
		   else {
		        $class = "quickstats $which";
				$renderer->doc .= "<div class='$class'>";
		   }
		    switch ($which) {
			   case 'basics':			   
				$this->misc_data_xhtml($renderer);
				$this->pages_xhtml($renderer);
			    break;
			case 'ip':              
				$this->ip_xhtml($renderer);
			   	break;
			case 'pages':
				$this->pages_xhtml($renderer,true);
				break;
			case 'misc':
				$this->misc_data_xhtml($renderer,true,'misc');
				break;
			case 'countries':
				$this->misc_data_xhtml($renderer,true,'country');
				break;	
            case 'ua':
                $this->ua_xhtml($renderer);
                break;            
			}
        
	       $renderer->doc .= "</div>" ;          	
			 

            return true;
        }
        return false;
    }
	

    function sort(&$array) {
    	uasort($array, 'QuickStatsCmp');
    }   

    function row($name,$val,$num="&nbsp;",$date=false,$is_ip=false) {	    
        $title = "";
        if($is_ip ) { 
    		$record = geoip_record_by_addr($this->giCity, $name);             
		    $title = $record->country_name;
		}

        elseif($this->long_names && (@strlen($name) > $this->long_names)) {
            $title = "$name";              
            $name = substr($name,0,$this->long_names) . '...';
        }
        if($date) {
            $date = date('r',$date);                      
            $title = "$title $date";                      
        }

        if($title) {
                $name = "<a href='javascript:void 0;' title = '$title'>$name</a>";
        }
	    return "<tr><td>$num&nbsp;&nbsp;</td><td>$name</td><td>&nbsp;&nbsp;&nbsp;&nbsp;$val</td></tr>\n";
       
    }	

    function row_depth($new_depth=false) {
	    STATIC $depth = false;
		
		if($new_depth !== false) {
			$depth = $new_depth;
			return;
		}
		
        return $depth;		
	}
   
	function load_data($date_str=null,$which) {
		$today = getdate();
		if($date_str) {
		   list($mon,$yr) = explode('_',$date_str);
		   $today['mon'] = $mon;
		   $today['year'] = $yr;
		}
    	$ns_prefix = "quickstats:";
		$ns =  $ns_prefix . $today['mon'] . '_'  . $today['year'] . ':'; 		
		$this->page_file = metaFN($ns . 'pages' ,'.ser');  
		$this->ip_file = metaFN($ns . 'ip' , '.ser');  
		$this->misc_data_file = metaFN($ns . 'misc_data' , '.ser');  
        $this->ua_file = metaFN($ns . 'ua' , '.ser');  
	
		if($which == 'basics' || $which == 'pages') {
			$this->pages = unserialize(io_readFile($this->page_file,false));
			if(!$this->pages) $this->pages = array();
		}
		if($which == 'basics' || $which == 'ip') {
			$this->ips = unserialize(io_readFile($this->ip_file,false));
			if(!$this->ips) $this->ips = array();
		}
		if($which == 'basics' || $which == 'countries'  || $which == 'misc') {
			$this->misc_data = unserialize(io_readFile($this->misc_data_file,false));
			if(!$this->misc_data) $this->misc_data = array();
		}
		if($which == 'ua') {
   			$this->ua_data = unserialize(io_readFile($this->ua_file,false));
			if(!$this->ua_data) $this->ua_data = array();
        }
	
	}
	
    function geopicity_ini() {
        require_once("GEOIP/geoipcity.inc");
        if($this->getConf('geoip_local')) {
             $this->giCity = geoip_open(QUICK_STATS. 'GEOIP/GeoLiteCity.dat',GEOIP_STANDARD);		
        }
        else {
            $gcity_dir = $this->getConf('geoip_dir');	            
            $gcity_dat=rtrim($gcity_dir, "\040,/\\") . $this->SEP  . 'GeoLiteCity.dat';						           
            $this->giCity = geoip_open($gcity_dat,GEOIP_STANDARD);
        }        
    }
    
	function table($data,&$renderer,$numbers=true,$date=false) {
    
	    if($numbers !== false) 
		   $num = 0;
		 else  $num = "&nbsp;";
    		
      $ip_array = false;
      if($this->getConf('show_country') && is_array($data) ) {    
         list($key,$val) = each($data);               
          if(!$this->getConf('geoplugin') && preg_match('/^\d+\.\d+\.\d+\.\d+$/', $key)) {               
               $ip_array = true;
               $this->geopicity_ini();
          }
          reset($data);
      }
    
	   $ttl = 0;
	   $depth = $this->row_depth();
	   if($depth == 'all') $depth = 0;
	    $renderer->doc .= "<table cellspacing='4' >\n";
		foreach($data as $item=>$count) {		       
            if($numbers) $num++;
            $ttl += $count;
            if($depth  && $num > $depth) continue;      
            $md5 =md5($item);
            $date_str = (is_array($date) &&  isset($date[$md5]) ) ? $date[$md5] : false;                 
            $renderer->doc .= $this->row($item,$count,$num,$date_str, $ip_array);
		}
	   $renderer->doc .= "</table>\n";
	   return $ttl;
	}
	
	function ip_xhtml(&$renderer) {
	   $uniq = $this->ips['uniq'];	     
	   unset($this->ips['uniq']);
	   $this->sort($this->ips);
	 
	   $renderer->doc .= '<div class="quickstats ip">';
	   $renderer->doc .= '<span class="title">Unique IP Addresses</span>';
	   $total_accesses = $this->table($this->ips,$renderer);	
	   $renderer->doc .= "<span class='total'>Total accesses: $total_accesses</span></br>\n"; 
	   $renderer->doc .= "<span class='total'>Total unique ip addresses: $uniq</span></br>\n";  
	   $renderer->doc .= "</div>\n";
	}  
	
	function pages_xhtml(&$renderer, $no_align=false) {		 
		
		if(!$this->pages) return array();            
	    
			$this->sort($this->pages['page']);
	        if($no_align) {
					$renderer->doc .= '<div>';
			}
		    else {
				$renderer->doc .= '<div style="margin: 10px 250px; overflow:auto; padding: 8px; width: 300px;">';
				}
		    $renderer->doc .= '<span class="title">Page Accesses</span>';
            
            $date =($this->show_date && isset($this->pages['date'] )) ? $this->pages['date'] : false;
            $page_count = $this->table($this->pages['page'],$renderer,true,$date);
			$renderer->doc .=  "<span class='total'>Number of pages accessed: " . count($this->pages['page']) . "</span><br />";
		    $renderer->doc .=  "<span class='total'>Total accesses:  " . $this->pages['site_total'] .'</span>';
		    $renderer->doc .= "</div>\n";
		
	}
    function misc_data_xhtml(&$renderer,$no_align=false,$which='all') {
    	

	   $renderer->doc .= "\n";
	
	   if($which == 'all' || $which == 'misc') {
	   
			$browsers = $this->misc_data['browser'];
			$platform = $this->misc_data['platform'];
			$version = $this->misc_data['version'];
			$this->sort($browsers);
			$this->sort($platform);
			$this->sort($version);   
			
              $renderer->doc .= "\n\n<!-- start misc -->\n";
			   if($no_align) {
					$renderer->doc .= '<div>';
			   }
			  else {
					$renderer->doc .= '<div style="float:left;width: 200px; margin-left:20px;">';
				}	
				$renderer->doc .="\n\n";
				$renderer->doc .= '<br /><span class="title">Browsers</span>';
				
				$num=0;
				$renderer->doc .= "<table border='0' >\n";
				foreach($browsers as $browser=>$val) {           				  
				   $num++;
				   $renderer->doc .= $this->row($browser, $val,$num);
				   $renderer->doc .= "<tr><td colspan='3' style='border-top: 1px solid black'>";
				   $v = $this->get_subversions($browser,$version); 		        		   
				   $this->table($v,$renderer, false,false);
				   $renderer->doc .= '</td></tr>';
				}
			   $renderer->doc .= "</table>\n\n";	   
        
		  
			$renderer->doc .= '<span class="title">Platforms</span>';		
			$this->table($platform,$renderer);				
			$renderer->doc .= "</div>\n<!--end misc -->\n\n";
	   }
	   
	    if($which == 'misc') return;
		
		$countries = $this->misc_data['country'];
		$this->sort($countries);
		
			if($no_align) {
					$renderer->doc .= '<div>';
			}
			else {
		           $renderer->doc .= "<div style='float: right; overflow: auto; width: 200px; margin-right: 1px;'>";
		    }
		   $renderer->doc .= '<span class="title">Countries</span>';
		   
				$renderer->doc .= "<table cellspacing='4'>\n";
				$num = 0;
				$total = 0;
				$depth = $this->row_depth();		
                if($depth == 'all') $depth = false;
			
				foreach($countries as $cc=>$count) {		
					if(!$cc) continue;
					 $num++;
					 $total+=$count;					
					 $cntry=$this->cc_arrays->get_country_name($cc) ;
					 if($depth == false)  {
					     $renderer->doc .= $this->row($cntry,$count,$num);
					 }
					 else if ($num <= $depth) {
					      $renderer->doc .= $this->row($cntry,$count,$num);
					 }
				}

			  $renderer->doc .= '</table>';		 
			  $renderer->doc .= "<span class='total'>Total number of countries: " . count($this->misc_data['country'])  . "</span></br>";
			  
			  $renderer->doc .= "<span class='total'>Total accesses: $total</span></br>";
			  
		  
			 $renderer->doc .= "</div>\n";
	     
	    
    } 
	
    function get_subversions($a,$b) {
	    $tmp = array();
	
	     foreach($b as $key=>$val) {	
	        if(strpos($key,$a) !== false) {
		        $tmp[$key] = $val;
		    }
	    }
	   $this->sort($tmp);
	   return  $tmp;
    }
    
    function ua_xhtml(&$renderer) {
    			$renderer->doc .="\n\n<div class=ip_data>\n";
				$renderer->doc .= '<br /><span class="title">IP Data</span>';
				
				$num=0;
				$renderer->doc .= "<table border='0' >\n";
				foreach($this->ua_data as $ip=>$data) {           				  
				   $num++;
                   $cc = array_shift($data);
                   $country=$this->cc_arrays->get_country_name($cc) ;
				   $renderer->doc .= $this->row($ip, $country,$num);
				   $renderer->doc .= "<tr><td colspan='3' style='border-top: 1px solid black'>";		
                   $temp = array();
                   foreach($data as $key=>$val) {
                      $temp[$val]='&nbsp;';  // prevents array numbers from being output by the table foreach
                   }
				   $this->table($temp,$renderer, false);
				   $renderer->doc .= '</td></tr>';
				}
			   $renderer->doc .= "</table>\n</div>\n\n";	
    }
}

  	    function QuickStatsCmp($a, $b) {
			if ($a == $b) {
				return 0;
			}
			return ($a > $b) ? -1 : 1;
		 }

?>