 <?php
//echo var_export(unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_SERVER['REMOTE_ADDR'])));
//echo var_export(unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$argv[1])));
$ar = var_export(unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$argv[1])));
print_r($ar);

?>
