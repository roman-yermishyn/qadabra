<?php

require_once("./conf/uri.php");
require_once("./conf/module.php");
require_once("./core/base_util.php");

class URI
	{
	protected $uri;
	protected $is_found;
	protected $page_uri;
	protected $page_name;
	protected $page_data;

	function __construct($uri)
		{
		$this->uri = $uri;
		$this->is_found = false;

		global $page_uri_map;

		foreach ($page_uri_map as $page_name => $page_uri)
			{
			if (preg_match('/^\/'.preg_quote($page_uri, '/').'\/?(.*)$/', $uri, $arg))
				{
				$this->is_found = true;
				$this->page_uri = $page_uri;
				$this->page_name = $page_name;
				$this->page_data = $arg[1];
				break;
				}
			}
		}

	function isFound()
		{ return $this->is_found; }

	function getPage()
		{ return $this->page_name; }

	function getData()
		{ return $this->page_data; }

	function getPageURI()
		{ return $this->page_uri; }

	function getURI()
		{ return $this->uri; }

	function setData($new_data)
		{ $this->page_data = $new_data; }

	function setMenuParams()
		{
		$um = Menu::getUserMenu();
		$menu_items = $um->getModel();
		//print_r($menu_items); die;
		//print_r($this->page_uri); die;
		global $node_id;
		foreach($menu_items as $menu_item)
			{
			if(($menu_item[3]=='/'.$this->page_uri || strpos($menu_item[3], "\"/{$this->page_uri}\"")!==false) && !empty($menu_item[1]))
				{
				$node_id=$menu_item[0];
				//echo $node_id; die;
				$_SESSION['current_tab_id']=$menu_item[1];
				//echo $_SESSION['current_tab_id'];
				return;
				}
			}
		if(isset($_SESSION['current_tab_id'])) unset($_SESSION['current_tab_id']);
		unset($node_id);
		}
	} // class URI

// page id and name

require_once("./conf/page.php");

function page2id($page_name)
	{
	global $page_id_map;
	return isset($page_id_map[$page_name])? $page_id_map[$page_name]: 0;
	}

function id2page($id_page)
	{ return GetNameByID($id_page); }

function GetNameByID($id)
	{
	global $page_id_map;
	foreach ($page_id_map as $k => $v)
		{
		if ($id == $v)
			{ return $k; }
		}
	return 0;
	}

function GetPageId($name)
	{ return page2id($name); }

function ParseID ($id, &$page_num)
	{
	sscanf ($id, "%dc%d", $page_id, $page_num);
	return GetNameByID ($page_id);
	}

function getPageURL($page_name)
	{
	global $page_uri_map;
	return (isset($page_uri_map[$page_name])) ?
		'/'.$page_uri_map[$page_name] :
		"/x";
	}

// Text processing

function QuoteMessage($text, $delimiter = "\n", $quoter = "> ")
// performs $text quoting starting from $quoter deviding by $delimiter
	{ return $quoter.str_replace($delimiter, $quoter, $text); }

//
function GetTimeZone()
	{ return (array_key_exists('time_zone',$_SESSION) && null != $_SESSION['time_zone']) ? $_SESSION['time_zone'] : 0; }

// Не знаю для чего использовалась зона в часах. Решил не трогать (noro 23/08/2013)
function SetTimeZone($tz)
	{ $_SESSION['time_zone'] = $tz; }

// Значение этой зоны будет передаваться в том числеи в sql селекторов AS IS! 
function GetUserTimeZone()
	{ return (array_key_exists('user_time_zone',$_SESSION) && null != $_SESSION['user_time_zone']) ? $_SESSION['user_time_zone'] : 'UTC'; }

function SetUserTimeZone($tz)
	{ $_SESSION['user_time_zone'] = $tz; }
	
function out2($msg)
	{
	$id = isset($_SESSION['id_user'])? $_SESSION['id_user']: 0;
	$f = fopen('log', 'a');
	fwrite($f, date('H:i:s ').$id." ".$msg."\n");
	fclose($f);
	}

function stringStartsWith($str, $head)
	{ return strncmp($str, $head, strlen($head)) == 0; }

function stringEndsWith($str, $tail)
	{
	$pos = strrpos($str, $tail);
	return $pos + strlen($tail) == strlen($str);
	}

function getArrayPairs($arr, $index_from, $separator)
	{
	$res = array();
	for ( ; $index_from < count($arr) ; $index_from++)
		{ $res[] = explode($separator, $arr[$index_from]); }
	return $res;
	}

// cache management

function getCache($name, $value, $data_getter)
	{
	$isCached = false;
	$isCached = true;
//print "$name, $value, $data_getter) ";
	if (isset($value))
		{
		if ($isCached && isset($_SESSION[$name][$value]))
			{ $res = $_SESSION[$name][$value]; }
		else
			{
//print '$res = '.$data_getter.';+';
			eval('$res = '.$data_getter.';');
			$_SESSION[$name][$value] = $res;
			}
		}
	else
		{
		if ($isCached && isset($_SESSION[$name]))
			{ $res = $_SESSION[$name]; }
		else
			{
//print '$res = '.$data_getter.';-';
			eval('$res = '.$data_getter.';');
			$_SESSION[$name] = $res;
			}
		}
		return $res;
	}

class Cache
	{
	static function isCaching($id_cache = null)
		{ return true; }

	static function has($id_cache, $id_item)
		{ return isset($_SESSION['caches'][$id_cache][$id_item]); }

	static function get($id_cache, $id_item, $data_getter = 'array()')
		{
		if ($id_item == null)
			{ return null; }
		if (! self::isCaching() || ! isset($_SESSION['caches'][$id_cache][$id_item]))
			{
			eval('$data = '.$data_getter.';');
			self::set($id_cache, $id_item, $data);
			}
		return $_SESSION['caches'][$id_cache][$id_item];
		}

	static function set($id_cache, $id_item, $data = null)
		{
		if (! self::isCaching())
			{ return; }
		$_SESSION['caches'][$id_cache][$id_item] = $data;
		}

	static function clear($id_cache = null)
		{
		if (is_null($id_cache))
			{ unset($_SESSION['caches']); }
		else
			{ unset($_SESSION['caches'][$id_cache]); }
		}

	static function log($id_cache = null)
		{
		if (! isset($_SESSION['caches']) || ($id_cache != null && ! isset($_SESSION['caches'][$id_cache])))
			{
			print "EMPTY";
			return;
			}

		$caches = isset($id_cache)? $_SESSION['caches'][$id_cache]: $_SESSION['caches'];

		if (! isset($id_cache))
			{
			foreach ($caches as $k=>$v)
				{ print $k." "; }
			print "<hr>";
			print_r($caches);
			}
		}

	} // class Cache

function GetParam($name, $init_value = '0')
	{
	$init = 'isset($_COOKIE["'.$name.'"])? $_COOKIE["'.$name.'"]: "'.$init_value.'"';
	return Cache::get($name, 'all', $init);
	}

function log_message ($priority, $message)
	{
	if ($priority < 3)
		{
		$log_file = fopen (Util::prepare_DIR(LOGS_DIR).date("Ymd").".txt","a+");
		fwrite ($log_file, date("H:i:s ").$message."\n");
		fclose ($log_file);
		}
	if ($priority < 2)
		{ print $message; }
	}

function checkPageAccess($page_name)
	{
//	return 1;
	global $page_module_map;
	if (isset($page_module_map[$page_name]))
		{ return CheckPerm(OP_Access_Module, array(page2id($page_name))); }
	return 1;
	}

function strip_unicode_prefix(&$f)
	{
	$len = strlen($f);
	if ($len >= 3 && ord($f[0]) == 239 && ord($f[1]) == 187 && ord($f[2]) == 191)
		{ $f = substr($f, 3, $len - 3); }
	}

function array_insert_at(& $arr, $index, $value, $count = 1)
	{
/*	if (! ($index > 0 && $index < count($arr)))
		{ return false; }
*/
	while ($count--)
		{
		for ($i = count($arr); $i != $index; $i--)
			{ $arr[$i] = $arr[$i - 1]; }
		$arr[$index] = $value;
		}
	return true;
	}

function hash2array($hash)
	{
	$result = array();
	foreach ($hash as $k=>$v)
		{ $result[] = $k; }
	return $result;
	}

function print_ar($var)
	{
	$input =var_export($var,true);
	$input = preg_replace("! => \n\\W+ array \\(!Uims", " => Array ( ", $input);
	$input = preg_replace("!array \\(\\W+\\),!Uims", "Array ( ),", $input);
	echo "<pre>".str_replace('><?', '>', highlight_string($input, true))."</pre>";
	}

class Site
{
	static function isOff()
	{
		$time = Site::getOffTime();
		return ! empty($time);
	}

	static function getOffTime()
		{
		$fname = Util::prepare_DIR(UPLOAD_DIR).'off.data';
		$handle = @fopen($fname, 'rb');
		if (! $handle)
			{ return null; }
		$time = @fread($handle, filesize($fname));
		fclose($handle);
		return $time;
		}

	public static function setOffTime($time)
		{
		$fname = Util::prepare_DIR(UPLOAD_DIR).'off.data';
		if (isset($time))
			{
			$handle = fopen($fname, 'wb');
			if ($handle)
				{
				fwrite($handle, $time);
				fclose($handle);
				}
			}
		else
			{ unlink($fname); }
		}

	// режим технических работ

	function setTechMode($mode = 1)
	{
		$_SESSION['tech'] = $mode;
	}

	function isTechMode()
	{
		return isset($_SESSION['tech']) && $_SESSION['tech'] == 1;
	}

}

// профилирование выполнения отдельных операций на странице

function profile($tag = null)
{
	static $prev;
	static $start;

	// начальные значения
	if ($prev == null)
		$start = $prev = microtime(true);

	$now = microtime(true);
	$delta = $now - $prev;
	$prev = $now;

	$f = @fopen("profile", "a");
	if (! $f)
		return;
	// текущее время
	fprintf($f, "%6.3f", $now - $start);

	// замер времени
	if ($tag != null)
		fprintf($f, " %6.3f %s", $delta, $tag);
	fprintf($f, "\n");
	fclose($f);
}

function CreateGUID()
{
	if (function_exists('com_create_guid')){
		return substr(com_create_guid(), 1, -1);
	}else{
		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
		$charid = strtoupper(md5(Util::uniqid(rand(), true)));
		$hyphen = chr(45);// "-"
		$uuid = chr(123)// "{"
				.substr($charid, 0, 8).$hyphen
				.substr($charid, 8, 4).$hyphen
				.substr($charid,12, 4).$hyphen
				.substr($charid,16, 4).$hyphen
				.substr($charid,20,12)
				.chr(125);// "}"
		return substr($uuid, 1, -1); 
	}
}

function utf8_substr($str,$from,$len){
# utf8 substr

  return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$from.'}'.
                       '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s',
                       '$1',$str);
}
