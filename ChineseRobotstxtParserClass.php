<?php
/**
*
* 专门解析中文robots.txt的类，特点为支持解析robots.txt中的纯简体中文及urlencode后的简体中文。
* 支持的robots.txt字符编码为gb2312以及utf-8。
*
* @author 道哥（http://www.seodug.com/）
*
* robots.txt相关文档： 
* @link http://bar.baidu.com/robots/
* @link http://support.google.com/webmasters/answer/6062596?hl=zh-Hans&ref_topic=6061961
*
*/
class ChineseRobotsTxtParser {
	
	private $robotstxt='';
	private $parsedRule=array();
	private $invalidRobotstxt=false;
	private $sitemap=array();

	public function __construct($fileAddr)
	{
		$this->robotstxt=file_get_contents($fileAddr);
		//robots.txt获取失败
		if (!$this->robotstxt) {
			$this->invalidRobotstxt=true;
			return ;
		}

		//从gb2312或utf8统一转码至utf-8
		$this->robotstxt=mb_convert_encoding($this->robotstxt, 'UTF-8','GB2312,UTF-8');
		//去除BOM头
		$this->robotstxt=str_replace("\xEF\xBB\xBF","",$this->robotstxt);
		$this->robotstxt=str_replace("\xef\xbb\xbf","",$this->robotstxt);
		// dos2unix mac2unix
		$this->robotstxt=str_replace("\r\n", "\n", $this->robotstxt);
		$this->robotstxt=str_replace("\r", "\n", $this->robotstxt);
		//去除空行、注释行
		$this->robotstxt=array_filter(explode("\n", $this->robotstxt));
		$this->robotstxt=array_filter($this->robotstxt,array($this,"rmComments"));
		//对编码后的URL做解码，并且将解码后的文字编码变为utf8，目的是为了兼容gb2312编码格式的url
		$this->robotstxt=array_map(array($this,'urldecode_AND_Encode2UTF8'), $this->robotstxt);

		$i=0;
		foreach ($this->robotstxt as $row) {
			//第一个有效行不是user-agent
			if (!preg_match('/user\-agent\s*\:\s*(.+)/i', $row) && $i==0) {
				$this->invalidRobotstxt=true;
				return ;
			}

			if(preg_match('/user\-agent\s*\:\s*(.+)/i', $row, $matches)){
				$curUA=$matches[1];
				if (!isset($this->parsedRule[$curUA])) $this->parsedRule[$curUA]=array('allow'=>array(),'disallow'=>array());
			} elseif(preg_match('/(allow|disallow)\s*\:\s*(.+)/i', $row, $matches)){
				// 非法规则需要直接过滤。非法规则：指令中包含空格||指令不是以/开头||指令内容为空
				if (preg_match('/\s/', $matches[2]) || !preg_match('/^\//', $matches[2]) || empty($matches[2])) continue;
				$curRule=$matches[2];
				$curRule=str_replace('/', '\/', $curRule);
				$curRule=str_replace('?', '\?', $curRule);
				$curRule=str_replace('.', '\.', $curRule);
				$curRule=str_replace('*', '.*', $curRule);
				$curRule='^'.$curRule;
				$this->parsedRule[$curUA][mb_strtolower($matches[1])][]=$curRule;
			} elseif (preg_match('/(sitemap)\s*\:\s*(.+)/i', $row, $matches)) {
				$this->sitemap[]=$matches[2];
			}
			$i++; 
		}

	}

	//去除robots.txt中的注释字段
	public function rmComments($v)
	{
		return !preg_match('/#/', $v);
	}

	//对传参进行url解码，并且将解码后的传参从gb2312或utf8统一转码至utf-8
	public function urldecode_AND_Encode2UTF8($v)
	{
		return mb_convert_encoding(urldecode($v), 'UTF-8','GB2312,UTF-8');
	}

	public function getSitemap()
	{
		return $this->sitemap;
	}

	//对给定的URL或URI判断是否允许抓取
	public function isAllowed($value='/',$userAgent='*')
	{
		if ($this->invalidRobotstxt) return true;
		//如果没有设置UA且不是*，则直接调用*的默认规则用于判断
		if (!isset($this->parsedRule[$userAgent])){
			if ($userAgent!='*') {
				return $this->isAllowed($value);
			} else {
				return true;
			}
		} else {
			$value=$this->urldecode_AND_Encode2UTF8(preg_replace('/^http:\/\/[^\/]+/i', '', trim($value)));
			if (!empty($this->parsedRule[$userAgent]['disallow'])) {
				$isD=false;
				$ruleD_length=0;
				foreach ($this->parsedRule[$userAgent]['disallow'] as $ruleRegex){
					if (preg_match('/'.$ruleRegex.'/', $value)) {
						$isD=true;
						$ruleD_length=strlen($ruleRegex)>$ruleD_length?strlen($ruleRegex):$ruleD_length;
					}
				}
			} else {
				$isD=false;
			}

			//如果被disallow屏蔽了，那么需要查看allow有没有解封
			if ($isD) {
				if (!empty($this->parsedRule[$userAgent]['allow'])) {
					$isA=false;
					$ruleA_length=0;
					foreach ($this->parsedRule[$userAgent]['allow'] as $ruleRegex){
						if (preg_match('/'.$ruleRegex.'/', $value)) {
							$isA=true;
							$ruleA_length=strlen($ruleRegex)>$ruleA_length?strlen($ruleRegex):$ruleA_length;
						}
					}
					if ($isA) return $ruleA_length>=$ruleD_length; 
				}
			} else {
				return true;
			}
			return false;
		}
	}


}