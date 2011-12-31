<?php
/**
  * @author     Myron Turner <turnermm02@shaw.ca>
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('QUICK_STATS')) define ('QUICK_STATS',DOKU_PLUGIN . 'quickstats/');
require_once('GEOIP/ccArraysDat.php');
class helper_plugin_quickstats extends Dokuwiki_Plugin {
    private $isCached = false;
    private $script_file;
    private $cache;
    private $cc_arrays;

    
    function getMethods(){
        $result = array();
			
        $result[] = array(
                 'name'   => 'is_inCache',
                 'desc'   => 'is file cached',
                 'params' => array(),
                 'return' => array('result' => 'bool')
                );
                
        $result[] = array(
                 'name'   => 'writeCache',
                 'desc'   => 'write new cache item',
                 'params' => array(),
                 'return' => array('result' => 'bool')
                );        
                
        $result[] = array(
                 'name'   => 'checkWikiFile',
                 'desc'   => 'does wiki file have quickstats syntax line',
                 'params' => array(),
                 'return' => array('result' => 'bool')
                );                        
        $result[] = array(
                 'name'   => 'is_inConfList',
                 'desc'   => 'is file in config options list',
                 'params' => array(),
                 'return' => array('result' => 'bool')
                );                                        
	}
    
    function __construct() {
            $this->script_file = metaFN('quickstats:cache', '.ser');
            $this->cache = unserialize(io_readFile($this->script_file,false));
            if(!$this->cache) $this->cache = array();
            $this->cc_arrays = new ccArraysDat();
    }
    
	function msg($text) {
	    if(is_array($text)) {
		   $text = '<pre>' . print_r($text,true) . '</pre>';
		}
		msg($text,2);
	}
	
    function get_cc_arrays() {
        return $this->cc_arrays;
    }
    function is_inCache($id) {   
  //     msg('<pre>'  .  print_r($this->cache,true) .  '</pre>',2);       
         $md5 = md5($id);
         if(isset($this->cache[$md5])) return true;
         return false;
    }
    
	function pruneCache($confirms,$deletions) {
	     $confirms = explode(',',$confirms);
		 if($deletions) {
		   $diff = array_intersect($confirms,array_keys($deletions));
		 }
		 else $diff = $confirms;
		 //$this->msg($diff);
	      foreach($diff as $del) {	
		      unset($this->cache[$del]);
			  io_saveFile($this->script_file,serialize($this->cache));	
		  }		
		  return $this->cache;
	}

	
    function writeCache($id) {
         if(!$this->is_inCache($id)) {
            $this->cache[md5($id)] = $id;
            io_saveFile($this->script_file,serialize($this->cache));	
             return true;
         }
         return false;
    }
    
    function is_inConfList($id) {
         $sortable_ns = @$this->getConf('sortable_ns');
        if(isset($sortable_ns) && $sortable_ns) {
            $ns_choices = explode(',',$sortable_ns);
            foreach($ns_choices as $ns) {
              $ns = trim($ns);
              if(preg_match("/$ns/",$id))  {                  
                       return true;
             }
            }
            return false;
        }     

    }
    function checkWikiFile() {            
         $file_name = wikiFN($ID);          
         $lines = file($file_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);      
          
             foreach ($lines as $line) {
                if(strpos($line,'~~QUICKSTATS') !== false) {                
                    return true;
                }
            }
            
            return false;
    }
    
     function getCache() {
         return  $this->cache;
     }     
    function metaFilePath($directory=false) {
       if($directory) {
            return preg_replace('/quickstats.*$/','quickstats/',$this->script_file); 
       }
        return $this->script_file;
    }
 }   
	
	