<?php
require_once('VocalInfo.class.php');
$table = new VocalInfo();
$table->drop();
 
$table_configuration = new configuration();
$table_configuration->delete(array('key'=>'plugin_vocalinfo_woeid'));
$table_configuration->delete(array('key'=>'plugin_vocalinfo_place'));

?>