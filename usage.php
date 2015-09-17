<?php
require_once('ChineseRobotstxtParserClass.php');
$parser = new ChineseRobotsTxtParser('http://www.baidu.com/robots.txt');
var_dump($parser->isAllowed('http://www.baidu.com/s?wd=seo','Baiduspider'));
var_dump($parser->getSitemap());
?>
