<?php
require_once('ChineseRobotstxtParserClass.php');
$mogujieParser = new ChineseRobotsTxtParser('http://www.mogujie.com/robots.txt');
$lvmamaParser = new ChineseRobotsTxtParser('http://www.lvmama.com/robots.txt');
$tuniuParser = new ChineseRobotsTxtParser('http://menpiao.tuniu.com/robots.txt');

var_dump($mogujieParser->isAllowed('http://www.mogujie.com/trade/123/'));
var_dump($mogujieParser->isAllowed('http://www.mogujie.com/trade/123/','Baiduspider'));
var_dump($mogujieParser->isAllowed('http://www.mogujie.com/trade/123/','Googlebot'));

var_dump($tuniuParser->isAllowed('http://menpiao.tuniu.com/s_主题乐园'));
var_dump($tuniuParser->isAllowed('http://menpiao.tuniu.com/s_%E4%B8%BB%E9%A2%98%E4%B9%90%E5%9B%AD'));
var_dump($tuniuParser->isAllowed('http://menpiao.tuniu.com/s_真人扎金花'));

var_dump($lvmamaParser->getSitemap());
?>
