<?php
include_once ('./PageDef.php');

$pageArr = include_once('../page_index.php');

$templatePath = "../../template";
$staticPath = "../../static";
$modulePath = "../../modules";

$pageDef = new PageDef('page_index', &$pageArr, $modulePath, $staticPath, $templatePath);
$pageDef->process();
// 
// $dir = opendir("$modulePath/basicpage/static/css");
// while(($filename = readdir($dir)) !== false){
    // print_r($filename);
    // echo "\n";
// }
// closedir($dir);
?>
