<?php 

require_once('common.php');

$menuItems = array();
Plugin::callHook("menubar_pre_home", array(&$menuItems));
uasort ($menuItems , function($a,$b){return $a['sort']>$b['sort']?1:-1;});


$tpl->assign('menuItems',$menuItems);
?>

