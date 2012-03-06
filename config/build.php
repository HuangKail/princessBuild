<?php
include_once("./source/PageDef.php");
$siteDefinition = include_once ("site.php");

$templatePath = "../template";
$staticPath = "../static";
$modulesPath = "../modules";

if (is_dir("$templatePath")) {
    system("rm -rf $templatePath");
}
if (is_dir("$staticPath")) {
    system("rm -rf $staticPath");
}

//
// start merging templates
//
system("mkdir $templatePath");
system("mkdir $staticPath");
system("mkdir $staticPath/js");
system("mkdir $staticPath/css");
system("mkdir $staticPath/img");

foreach ($siteDefinition['pageDefinitions'] as $page) {
    $modules = include_once ("$page");
    $pageName = substr($page, 0, strripos($page, '.'));
    $pageDef = new PageDef($pageName, &$modules, $modulesPath, $staticPath, $templatePath);
    $pageDef->process();
}
?>
