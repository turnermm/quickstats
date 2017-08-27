
function QuickstatsShowPage(ns) { 
    var page = DOKU_BASE +'doku.php?&id=' + ns;
    window.open(page,'quickstats_win',"width=900,height=600,scrollbars=yes,resizable=yes");
}

function uncheck(key) {
   
   var dom = document.getElementById(key);
   dom.style.backgroundColor='white';
   key = key + '_1';
    var dom = document.getElementById(key);
    
    dom.style.backgroundColor='white';
}

function qs_close_panel(which) {
   var dom = document.getElementById(which);     
   dom.style.display = 'none';       
}

function qs_open_panel(which) {
   var dom = document.getElementById(which);     
   dom.style.display = 'block';       
}

function qs_open_info(which) {
   var dom = document.getElementById(which) ;
   if(dom.style.display == 'block') {
       qs_close_panel(which);
       return;
   }
   qs_open_panel(which);
   qs_open_panel('quick__stats');     
   qs_close_panel('qs_general_intro');
}

function toggle_panel(which) {    
    qs_close_panel('qs_general_intro');
    var dom = document.getElementById(which);   
    var display = dom.style.display;
    
    if(display == 'block') {
       dom.style.display = 'none';       
    }
    else if(display == 'none') {
       dom.style.display = 'block';
    }    
    else dom.style.display = 'block';
      
}

function qs_check_year(year) {
 if(!year) year = document.getElementById('year');
 
   if(parseInt(year.value) < 2010) {
        alert("Year values must have four digits, e.g 2012");
        return false;
   }
    return true;
}

function checkforJQuery() {  }

function onChangeQS(which) {   
  if(which.selectedIndex == 0)  {
      alert('You must select a <namespace:>page');
      return;
  }
  QuickstatsShowPage(which.options[which.selectedIndex].value) ; 
 }

 function qs_priority_error(err) {
    elems = err.split(';;');
    alert('You have selected ' + elems[0] + ' priority, but have not  ' + elems[1] + ' in your query');
 }

var qs_timer_on=false; 
var qs_tid;
var qs_seconds=0;
function set_timer(dom,immediate_display) {
    qs_timer_on=true;
    var max_script_time = document.getElementById('qs_script_max_time').value;
    var throbber = DOKU_BASE + 'lib/plugins/quickstats/throbber.gif';
    dom.innerHTML = "<div id='qs_throbber_div' style='display:none'><center>Loading<br /><br /><img src='" + throbber +"'><br /><span id='qs_throbber_tm'></span></center></div>";	
	
    qs_tid=setInterval("qs_timer()", 1025);	
	var dom = document.getElementById("qs_throbber_div");
    if(!immediate_display) {
	dom.style.display='none';
}
     else dom.style.display='block';
    
}
 function qs_timer() {
  if(qs_seconds && !qs_timer_on) {
      clearInterval(qs_tid);
	  return;
  }
   qs_seconds++;
   if(qs_seconds < 8) return;
   var dom = document.getElementById("qs_throbber_div");
   if(dom.style.display=='none' || dom.style.display=='') dom.style.display='block';
   
   var dom = document.getElementById("qs_throbber_tm");
   if(!dom) {
      clearInterval(qs_tid);
	  return; 
   }
   dom.innerHTML=qs_seconds ;
 }
 
function getExtendedData(f,DOKU_INCL) {

    var priority_error = "";
    var priority = "";
    var page = "";
    qs_seconds=0;
    if(!qs_check_year(null)) return;
    var params="doku_inc="+encodeURIComponent(DOKU_INCL);
    var inp = f.getElementsByTagName('input');
    for(el in inp) {
      if(inp[el].type == 'hidden') {
          var p = '&' + inp[el].name + '=' + inp[el].value;
          params += p;      
      }
    }
    var ignore = document.getElementById('qs_ignore').checked;

    var p_brief = document.getElementById('qs_p_brief');
    if(p_brief.checked) params+="&p_brief=1";
    var months = document.getElementById('month');
    if(months.selectedIndex == 0 && !whole_year.checked) {
       alert("You must select a month");
       return;
    }
    else month = months.selectedIndex;
    
    var priority_types = new Array('page','ip','agent','country');
    for(var p in priority_types) {
        var dom = document.getElementById('qs_priority_'+ priority_types[p]);
        if(dom.checked) {
            priority = priority_types[p];
        }
    }
    if(priority != 'ip') {
        var countries=document.getElementById('country_names');
        var option = countries.options[countries.selectedIndex];
        var country_set = false;
        if(option.value != 0) {        
            if(!ignore || priority == 'country') {
                params+="&country_name=" + encodeURIComponent(option.text); 
                params+="&country_code=" + encodeURIComponent(option.value); 
                country_set = true;
            }
        }
       
       var ua_set = false; 
        var ua =document.getElementById('user_agent');    
        var option = ua.options[ua.selectedIndex];    
        if(option.value != 0) {
            if(!ignore || priority == 'agent') {           
                params+="&user_agent=" + encodeURIComponent(option.value); 
               ua_set = true;
            }
        }
        var page = document.getElementById('page').value;
    }
    var ip = document.getElementById('ip').value;
    
     if(!country_set && !page && !ip &&!ua_set) {
         alert('Query term(s) missing: Page/IP/Country/User Agent');
         return; 
    }     
   
       if(priority == 'page' && !page)  {
            priority_error = 'page;;entered a page name';
       }
       else if(priority == 'country' && !country_set)  {
           priority_error = 'country;;chosen a Country';
       }
      else if(priority == 'agent' && !ua_set)  {
         priority_error = 'user agent;;selected a User Agent';
       }
      else if(priority == 'ip' && !ip)  {
         priority_error = 'ip;;entered an IP address';
       }
    
    
    if(priority_error) {
        qs_priority_error(priority_error);
        return;
    }
 
    params +=  '&priority='  + priority;
     if(!ignore || priority == 'ip') {
        if(ip != "") {
            params+="&ip=" + ip;
        }
    }
    if(!ignore || priority == 'page') {
        if(page !="") {
            params+="&page=" + page;
        }
    }
    var year = document.getElementById('year').value;
    date = '&date=' +month + '_' + year;
    for(i=months.selectedIndex+1; i<months.options.length; i++) {
        if(months.options[i].selected) { 
            date += '&date_' + i + '=' + i + '_' + year;           
        }
    }
    
    params += date;
    
    
   
    var dom = document.getElementById('extended_data');
    set_timer(dom,false) ;
    jQuery.post(
    DOKU_BASE + 'lib/plugins/quickstats/scripts/extended_data.php',
    params,
    function (data) {            
	     qs_timer_on=false;
          dom.innerHTML = decodeURIComponent(decodeURIComponent(data));   
    },
    'html'
   );
   
}

function qs_country_search() {

    var select = document.getElementById('country_names');
    
    var params = "";
    params+="doku_inc="+encodeURIComponent(DOKU_BASE);
    var dom=document.getElementById('cc_extra');   
    params += '&cc_cntry=' + dom.value;
       
    jQuery.post(
    DOKU_BASE + 'lib/plugins/quickstats/scripts/country_data.php',
    params,
    function (data) {  
           if(!data) {
               alert("Nothing found  for " + dom.value);
               return;
           }           
           var str =decodeURIComponent(data);              
           var entries = str.split(/\n/);
           for (i=0; i< entries.length; i++) {
               var elems = entries[i].split(/::/); 
               var obj = new Option(elems[1], elems[0],false,false);
                select.add(obj,1);
           }
    },
    'html'
   );
 
}

function qs_agent_search() {

   
    var select = document.getElementById('user_agent');
    
    var params = ""; 
    var dom=document.getElementById('other_agent');   
    params += '&other_agent=' + encodeURIComponent(dom.value);
       
    jQuery.post(
    DOKU_BASE + 'lib/plugins/quickstats/scripts/get_useragent.php',
    params,
    function (data) {        
           if(!data) {
               alert("Nothing found  for " + dom.value);
               return;
           }           
           var str =decodeURIComponent(data);              
           var entries = str.split(/::/);
           if(!entries.length) return;
           for (i=0; i< entries.length; i++) {               
               var obj = new Option(entries[i], entries[i],false,false);
                select.add(obj,1);
           }
          
    },
    'html'
   );
 
}

  function qs_download_GeoLite(geoip_local)  {
        var params="&geoip_local=" + geoip_local;
        var dom = document.getElementById('download_results');     
        qs_seconds=0;       
        set_timer(dom,true);        
       
        jQuery.post(
        DOKU_BASE  + 'lib/plugins/quickstats/scripts/get_geocity.php',
        params,
        function (data) {                        
           dom.innerHTML ='<pre>' +data +'</pre>';           
        },
        'html'
        ); 
}
jQuery(document).ready(function() {
         if(JSINFO['ajax'] == 'ajax') {
           var act = JSINFO['act'] ? JSINFO['act'] : "";
           var params = 'call=quickstats&id=' + JSINFO['id'] + "&qs=" + location.search + '&act='+act;
          jQuery.post(
               DOKU_BASE + 'lib/exe/ajax.php',
               params,
               function(data) {
                   if(data)  alert(data);
                },
                'html'
            );
         }      
   });              
         