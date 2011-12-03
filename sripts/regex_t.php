<?php
//$aborts=array('219.154','219.155','219.156',  '219.157');
$str = '219.154';
$str='219.154,  219.155,219.156, 219.157';
$str=str_replace("\040", '',$str);
$aborts=explode(',',$str);
print_r($aborts);
$regex = '#'. implode('|',$aborts) . '#';
echo "$regex\n";  
 $ip='219.154.191.222';
 
 if(preg_match($regex,$ip))
    {
     exit ("abort\n");
   }
  echo "did not exit\n";
?>