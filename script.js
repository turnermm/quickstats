
function QuickstatsShowPage(ns) { 
    var page = DOKU_BASE +'doku.php?&id=' + ns;
    window.open(page,'quickstats_win',"width=900,height=600,scrollbars=yes");
}

function uncheck(key) {
   
   var dom = document.getElementById(key);
   dom.style.backgroundColor='white';
   key = key + '_1';
    var dom = document.getElementById(key);
    
    dom.style.backgroundColor='white';
}