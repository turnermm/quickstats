
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
function checkforJQuery() {

  if(!window.jQuery) {
     window.jQuery = {
      ajax: function(obj) {
         var s = new sack(obj.url); 
         s.asynchronous = obj.async;
         s.onCompletion = function() {
        	if (s.responseStatus && s.responseStatus[0] == 200) {   
                  obj.success(s.response);
        	}
         };
         s.runAJAX(obj.data);
     
      },
      post: function(url,params,callback,context) {
         var s = new sack(url);
         s.onCompletion = function() {
        	if (s.responseStatus && s.responseStatus[0] == 200) {   
                  callback(s.response);
        	}
         };
         s.runAJAX(params);
      }
     };
  }
  
}

function onChangeQS(which) {   
  if(which.selectedIndex == 0)  {
      alert('You must select a <namespace:>page');
      return;
  }
  QuickstatsShowPage(which.options[which.selectedIndex].value) ; 
 }

function getExtendedData(f,DOKU_INCL) {

    var params="doku_inc="+encodeURIComponent(DOKU_INCL);
    var inp = f.getElementsByTagName('input');
    for(el in inp) {
      if(inp[el].type == 'hidden') {
          var p = '&' + inp[el].name + '=' + inp[el].value;
          params += p;      
      }
    }

 
    var p_brief = document.getElementById('qs_p_brief');
    if(p_brief.checked) params+="& p_brief=1";
    var months = document.getElementById('month');
    if(months.selectedIndex == 0 && !whole_year.checked) {
       alert("You must select a month");
       return;
    }
    else month = months.selectedIndex;
    
    var countries=document.getElementById('country_names');
    var option = countries.options[countries.selectedIndex];
    var country_set = false;
    if(option.value != 0) {
        params+="&country_name=" + encodeURIComponent(option.text); 
        params+="&country_code=" + encodeURIComponent(option.value); 
        country_set = true;
    }
    
    var page = document.getElementById('page').value;
    var ip = document.getElementById('ip').value;
    
     if(!country_set && !page && !ip) {
         alert('Query term(s) missing: Page/IP/Country');
         return; 
    }     
    
    if(ip != "") {
        params+="&ip=" + ip;
    }
    if(page !="") {
        params+="&page=" + page;
    }
    var year = document.getElementById('year').value;
    date = '&date=' +month + '_' + year;
    date_multiples = false;
    for(i=months.selectedIndex+1; i<months.options.length; i++) {
        if(months.options[i].selected) { 
            date += '&date_' + i + '=' + i + '_' + year;
            date_multiples=true;
        }
    }

    if(page && date_multiples) {
       alert("Page queries can be made for only one month at a time.");
       return;
    }
    
    params += date;
    
    
    checkforJQuery();
    var dom = document.getElementById('extended_data');
    jQuery.post(
    DOKU_BASE + 'lib/plugins/quickstats/scripts/extended_data.php',
    params,
    function (data) {            
          dom.innerHTML = decodeURIComponent(decodeURIComponent(data));      
          //dom.innerHTML = decodeURIComponent(dom.innerHTML);      
         //dom.innerHTML = decodeURI(decodeURIComponent(data));      
        
    },
    'html'
   );

}

function qs_country_search() {

    checkforJQuery();
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