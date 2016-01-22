<?php
/**  
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author    Myron Turner <turnermm02@shaw.ca>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
if(!defined('QUICK_STATS')) define ('QUICK_STATS',DOKU_PLUGIN . 'quickstats/');

//require_once('GEOIP/ccArraysDat.php');
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
    private $helper;
    
    function __construct() {
        
        $this->long_names = $this->getConf('long_names');
        if(!isset($this->long_names)  || $this->long_names <= 0) $this->long_names = false;
        $this->show_date=$this->getConf('show_date');
        if( preg_match('/WINNT/i',  PHP_OS) ) {    
            $this->SEP='\\';                
        }
        $this->helper =  & plugin_load('helper', 'quickstats');
        $this->cc_arrays = $this->helper->get_cc_arrays();
    }

   /**
    * Get an associative array with plugin info.    
    */
    function getInfo(){
        $pname = $this->getPluginName();
        $info  = DOKU_PLUGIN.'/'.$pname.'/plugin.info.txt';
     
        if(@file_exists($info))  {
              return parent::getInfo();
        }   
     
        return array(
            'author' => 'Myron Turner',
            'email'  => 'turnermm02@shaw.ca',
            'date'   => '2011-11-02',
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
    function handle($match, $state, $pos, Doku_Handler $handler){
       global $ID;
        $this->helper->writeCache($ID);
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
         return preg_match('/\d+_\d\d\d/',$str);
    }    
    
   /**
    * Handle the actual output creation.    
    */
    function render($mode, Doku_Renderer $renderer, $data) {
     
        if($mode == 'xhtml'){
           
           list($which, $date_str,$depth) = $data;
           $this->row_depth('all');
           if($depth) {
               $this->row_depth($depth);
              }
                        
           $this->load_data($date_str,$which);
           if($which == 'basics') {
                $renderer->doc .= "<div class='quickstats basics' style='margin: auto;width: 920px;'>" ;                  
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

    function extended_row($num="&nbsp;", $cells, $styles="") {
        $style = "";
        if($styles)  $style = "style = '$styles' "; 
        $row = "<tr><td  $style >$num&nbsp;&nbsp;</td>";
        foreach($cells as $cell_data) {
            $row .= "<td  $style >$cell_data</td>";
        }
        $row .= '</tr>';
        return $row;
    }
    
    function row($name,$val,$num="&nbsp;",$date=false,$is_ip=false) {        
        $title = "";
        $ns = $name;
        if($is_ip  && $this->giCity) { 
            $record = geoip_record_by_addr($this->giCity, $name);             
            $title = $record->country_name;      
            if(isset($this->ua_data[$name])) {            
            $title .= ' (' . $this->ua_data[$name][1] .')';
            }
        }

        elseif($this->long_names && (@strlen($name) > $this->long_names)) {        
            $title = "$name";              
            $name = substr($name,0,$this->long_names) . '...';
        }
        if($date) {
            $date = date('r',$date);                      
            $title = "$title $date";                      
        }
        
        if($title && $is_ip) {
                $name = "<a href='javascript:void 0;' title = '$title'>$name</a>";
        }
        else if(is_numeric($num)  && $date !== false) {
           $name = "<a href='javascript: QuickstatsShowPage(\"$ns\");' title = '$title'>$name</a>";
        }
        else if ($title) {
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
        global $uasort_ip; 
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
        if($which == 'ua' || $which == 'ip') {
               $this->ua_data = unserialize(io_readFile($this->ua_file,false));
            if(!$this->ua_data) $this->ua_data = array();
            if($which == 'ip') {
                $this->ips = unserialize(io_readFile($this->ip_file,false));
                if(!$this->ips) $this->ips = array();
            }
            else {
                $uasort_ip = unserialize(io_readFile($this->ip_file,false));
                if(!$uasort_ip) $uasort_ip = array();
            }
        }
    
    }
    
    function geoipcity_ini() {
    
         if($this->getConf('geoplugin')) {
            return;
         }
        require_once("GEOIP/geoipcity.inc");
        if($this->getConf('geoip_local')) {
             $this->giCity = geoip_open(QUICK_STATS. 'GEOIP/GeoLiteCity.dat',GEOIP_STANDARD);        
        }
        else {
            $gcity_dir = $this->getConf('geoip_dir');                
            $gcity_dat=rtrim($gcity_dir, "\040,/\\") . $this->SEP  . 'GeoLiteCity.dat';                                   
            if(!file_exists( $gcity_dat)) return;
            $this->giCity = geoip_open($gcity_dat,GEOIP_STANDARD);
        }        
    }
    
    function table($data,&$renderer,$numbers=true,$date=false,$ip_array=false) {
    
        if($numbers !== false) 
           $num = 0;
         else  $num = "&nbsp;";
  
       if($ip_array) $this->geoipcity_ini();
  
       $ttl = 0;
       $depth = $this->row_depth();
       if($depth == 'all') $depth = 0;
       
        if($ip_array) {
             $this->theader($renderer, 'IP');
        }
        else if ($date && $numbers) {
            $this->theader($renderer, 'Page');
        }
        else  $renderer->doc .= "<table cellspacing='4'>\n";
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
    
    function theader(&$renderer,$name,$accesses='Accesses',$num="&nbsp;Num&nbsp;",$other="") {         
         if($accesses=='Accesses') $accesses=$this->getLang('accesses');
         $renderer->doc .= "<table cellspacing='4' class='sortable'>\n";
         $js = "<a href='javascript:void 0;' title='sort' class='quickstats_sort_title'>";
         $num = $js . $num . '</a>';
         $name = $js . $name . '</a>';
         $accesses = $js . $accesses . '</a>';
         $renderer->doc .= '<tr><th class="quickstats_sort">'. $num .'</th><th class="quickstats_sort">'.$name .'</th><th class="quickstats_sort">' . $accesses .'</th>';
         if($other) {
               $other = $js . $other .  '</a>';
               $renderer->doc .= '<th class="quickstats_sort">'. $other . '</th>';
         }          
         $renderer->doc .='</tr>';
    }
    
    function ip_xhtml(&$renderer) {
       $uniq = $this->ips['uniq'];         
       unset($this->ips['uniq']);
       $this->sort($this->ips);
     
    //  $renderer->doc .= '<div class="quickstats ip">';
       $renderer->doc .= '<span class="title">' .$this->getLang('uniq_ip') .'</span>';
       $total_accesses = $this->table($this->ips,$renderer,true,true,true);    
       $renderer->doc .= "<span class='total'>" .$this->getLang('ttl_accesses') . "$total_accesses</span></br>\n"; 
       $renderer->doc .= "<span class='total'>" .$this->getLang('ttl_uniq_ip') ."$uniq</span></br>\n";  
      // $renderer->doc .= "</div>\n";
    }  
    
    function pages_xhtml(&$renderer, $no_align=false) {         
        
        if(!$this->pages) return array();            

            $this->sort($this->pages['page']);
            if($no_align) {
                    $renderer->doc .= '<div class="qs_noalign">';
            }
            else {
                //$renderer->doc .= '<div style="margin: 10px 250px; overflow:auto; padding: 8px; width: 300px;">';
                $renderer->doc .= '<div   class="pages_basics"  style="overflow:auto;">';
                }
            $renderer->doc .= '<span class="title">'. $this->getLang('label_page_access') .'</span>';
            
            $date =($this->show_date && isset($this->pages['date'] )) ? $this->pages['date'] : false;
            $page_count = $this->table($this->pages['page'],$renderer,true,$date);
            $renderer->doc .=  "<span class='total'>" . $this->getLang('pages_accessed')  . count($this->pages['page']) . "</span><br />";
            $renderer->doc .=  "<span class='total'>". $this->getLang('ttl_accesses')  . $this->pages['site_total'] .'</span>';
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
                    $renderer->doc .= '<div class="qs_noalign">';
               }
              else {
                    //$renderer->doc .= '<div style="float:left;width: 200px; margin-left:20px;">';
                    $renderer->doc .= '<div class="browsers_basics"  style="float:left;">';
                }    
                $renderer->doc .="\n\n";
                $renderer->doc .= '<br /><span class="title">' . $this->getLang('browsers') .'</span>';
                
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
               //  $renderer->doc .= "<div style='float: right; overflow: auto; width: 200px; margin-right: 1px; margin-top: 12px;'>";
                   $renderer->doc .= "<div  class='countries_basics' style='float: right; overflow: auto;'>";
            }
           $renderer->doc .= '<span class="title">Countries</span>';
                $this->theader($renderer,  $this->getLang('country') );
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
              $renderer->doc .= "<span class='total'>" .$this->getLang('ttl_countries') . count($this->misc_data['country'])  . "</span></br>";
              
              $renderer->doc .= "<span class='total'>" . $this->getLang('ttl_accesses') ."$total</span></br>";
              
          
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

    /*  this sorts ua array by ip accesses 
     *  the keys to both ua and ip arrays are the ip addresses     
     *  $a and $b in ua_Cmp($a,$b) are ip addresses, so $uasort_ip[$a] = number of accesses for ip $a
    */
    function ua_sort(&$array) {
     global $uasort_ip;
    
       
       function ua_Cmp($a, $b) {
            global $uasort_ip; 
        
            $na = $uasort_ip[$a];
            $nb = $uasort_ip[$b];
            
           if ($na == $nb) {            
                return 0;
            }
            return ($na > $nb) ? -1 : 1;
            
         }
       
       uksort($array, 'ua_Cmp');    
    }
  
   
    function ua_xhtml(&$renderer) {                
                global $uasort_ip;   // sorted IP=>acceses
                
                $depth = $this->row_depth();                        
                if($depth == 'all') $depth = false;
                $asize = count($this->ua_data);
                if($depth !== false) {
                        $this->ua_sort($this->ua_data);    
                        if($depth > $asize) $depth = $asize;
                        $header = " ($depth/$asize) ";
                }
                else {                   
                    $header = " ($asize/$asize) ";
                }    
                $total_accesses = $this->ua_data['counts'] ;
                unset($this->ua_data['counts']); 
                $renderer->doc .="\n\n<div class=ip_data>\n";
                $styles = " padding-bottom: 4px; ";
                $renderer->doc .= '<br /><span class="title">'. $this->getLang('browsers_and_ua') . $header   .'</span>';
                $n = 0;
               $this->theader($renderer,'IP', $this->getLang('country'),"&nbsp;" . $this->getLang('accesses'). "&nbsp;", "&nbsp;User Agents&nbsp;");        
                foreach($this->ua_data as $ip=>$data) {     
                    $n++;
                    if($depth !== false && $n > $depth) break;                     
                    $cc = array_shift($data);
                    $country=$this->cc_arrays->get_country_name($cc) ;
                    $uas = '&nbsp;&nbsp;&nbsp;&nbsp;' . implode(',&nbsp;',$data);
                    $renderer->doc .=  $this->extended_row($uasort_ip[$ip], array($ip, "&nbsp;&nbsp;$country",$uas), $styles);
                }
               $renderer->doc .= "</table>\n";    
               
                // Output total table
              $renderer->doc .= '<br /><span class="title">' . $this->getLang('ttl_accesses_ua') .'</span><br />';
               $n=0;
               $this->theader($renderer,"&nbsp;&nbsp;&nbsp;&nbsp;Agents&nbsp;&nbsp;&nbsp;&nbsp;");               
               foreach($total_accesses as $agt=>$cnt) {    
                  $n++;               
                  if($depth !== false && $n > $depth) continue;                     
                  $renderer->doc .= "<tr><td>$n</td><td>$agt&nbsp;</td><td>&nbsp;&nbsp;$cnt</td>\n";
               }
              $renderer->doc .= "</table></div>\n\n";    
    }
}

          function QuickStatsCmp($a, $b) {
            if ($a == $b) {
                return 0;
            }
            return ($a > $b) ? -1 : 1;
         }

?>