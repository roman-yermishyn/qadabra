<?php

require_once './core/consts.php';
require_once './core/sync.php';

class Util
	{
	static $nTab = 0;
	public static function outs()
		{
		foreach (func_get_args() as $strHtml)
			{
			Log::Out(Array('$strHtml' => $strHtml));
			if (is_array($strHtml))
				{
				call_user_func_array('Util::outs', $strHtml);
				continue;
				}
			if (is_object($strHtml) && method_exists($strHtml, 'out'))
				{
				$strHtml->out();
				continue;
				}
			if ($strHtml === null || $strHtml === '')
				{ continue; }
			$d = ('</' === substr($strHtml, 0, 2) ? -1 : 0);
//			echo $strHtml;
			echo str_pad('', self::$nTab + $d, TAB),$strHtml,CR;
			self::$nTab += substr_count($strHtml, '<');
			self::$nTab -= 2*substr_count($strHtml, '</');
			self::$nTab -= substr_count($strHtml, '/>');
			}
		}

	static public function getClassVar($ClassName, $VarName)
		{
		$vars = get_class_vars($ClassName);
		return array_key_exists($VarName, $vars) ? $vars[$VarName] : null;
		}

	private static function MakeParams(Array &$params)
		{
		$aAttr = new Attr();
		if (array_key_exists('attr', $params))
			{
			foreach ($params['attr'] as $name => &$value)
				{ $aAttr->setAttr($name, $value); }
			}
		$text = (array_key_exists('text', $params) ? $params['text'] : '');
		return Array($aAttr, $text);
		}

	public static function makeActionButton($attr, $text = null)
		{
		return Tag::Make('button')
			->setAttr($attr)
			->addClass('action_button')
			->append($text);
		}

	public static function makeLink($attr, $text = null)
		{
		return Tag::Make('div')
			->setAttr('href', 'javascript:void(0)')
			->setAttr($attr)
			->addClass('action_a')
			->append($text);
		}

	public static function makeScriptText($text)
		{
		return Tag::Make('script')
			->setAttr('type', 'text/javascript')
			->append($text);
		}

	public static function makePager($params)
		{ return Util::makeScriptText('initPager('.json_encode($params).');'); }

	public static function MakeShortText($text, $strip_text = null)
		{
		return Tag::Make('span')
			->addClass('short')
			->append(Tag::Make('div')
				->addClass('title')
				->append($text))
			->append($strip_text !== null ? $strip_text : strip_tags($text));
		}

	public static function MakeLinkShowPersone($personId, $personFIO, $short = false)
		{
		return Util::makeLink(
			Array('href' => 'javascript:void(0)', 'onclick' => "showPerson('{$personId}')", 'class' => 'show_person'),
			$short ? Util::MakeShortText($personFIO) : $personFIO);
		}

	public static function ObjectReduce(Array $Obj, $func, $init = null)
		{
		foreach ($Obj as $key => &$val)
			{ $init = call_user_func($func, $key, $val, $init); }
		return $init;
		}

	public static function makeLinkWindow($params)
		{
		$a = Util::makeLink($params['attr'], $params['text']);
		if (!$a->hasAttr('onclick'))
			{
			$href = $a->getAttr('href');
			$target = $a->getAttr('target');
			$parWindowOpen = Array(); //'width' => 1000, 'height' => 800, 'left' => 200, 'top' => 200, 'toolbar' => 0, 'menubar' => 0);
			if (isset($params['width']))
				{ $parWindowOpen['width'] = 'width='.$params['width']; }
			if (isset($params['height']))
				{ $parWindowOpen['height'] = 'height='.$params['height']; }
			if (isset($params['innerWidth']))
				{ $parWindowOpen['innerWidth'] = 'innerWidth='.$params['innerWidth']; }
			if (isset($params['outerHeight']))
				{ $parWindowOpen['outerHeight'] = 'outerHeight='.$params['outerHeight']; }
			if (isset($params['left']))
				{ $parWindowOpen['left'] = 'left='.$params['left']; }
			if (isset($params['top']))
				{ $parWindowOpen['top'] = 'top='.$params['top']; }
			if (isset($params['toolbar']))
				{ $parWindowOpen['toolbar'] = 'toolbar='.(isTrue($params['toolbar']) ? 1 : 0); }
			if (isset($params['menubar']))
				{ $parWindowOpen['menubar'] = 'menubar='.(isTrue($params['menubar']) ? 1 : 0); }
			$open_params = htmlentities(implode(',', $parWindowOpen), ENT_COMPAT, 'UTF-8');
			$a->setAttr('onclick', "window.open('$href', '$target', '$open_params'); return false;");
			}
		return $a;
		}

	static public function Doter($str, $length)
		{
		$len = mb_strlen($str, 'utf-8');
		if ($len <= $length)
			{ return $str; }
		$str = preg_replace('/[\r\n]\s*/m', ' ', $str);
		$length -= 3;
		return mb_substr($str, 0, $length, 'utf-8').'...';
		}

	static public function print_r($value, $ArrayPrefix = 'Array')
		{
		if (is_array($value))
			{
			$s = '';
			foreach ($value as $key => &$val)
				{ $s .= "[$key] => ".Util::print_r($val).PHP_EOL; }
			$s = $ArrayPrefix.implode(PHP_EOL.'   ', explode(PHP_EOL, PHP_EOL.'('.PHP_EOL.$s.')'));
			return $s;
			}
		$Type = gettype($value);
		switch ($Type)
			{
			case 'object':
				if (method_exists($value, 'conv2String'))
					{ return get_class($value).' '.Util::print_r($value->conv2String(), ''); }
				return Util::Doter(print_r($value, true), 120);
			case 'boolean':
				return $Type.' '.($value ? 'true' : 'false');
			}
		return $Type.' '.print_r($value, true);
		}

	static public function Replacer($Templ, Array $args = null)
		{
		if (!is_array($args) && 0 === count($args))
			{ return $Templ; }
		return preg_replace_callback(
				'/%([\w\d_]+)%/',
				function ($matches) use ($args)
					{
					if (array_key_exists($matches[1], $args))
						{
						$val = &$args[$matches[1]];
						if (is_string($val) || is_float($val) || is_int($val))
							{ return $val.''; }
						if (is_bool($val))
							{ return $val ? 'TRUE' : 'FALSE'; }
						if (is_null($val))
							{ return 'NULL'; }
						return Util::print_r($val);
						}
					return '%'.$matches[1].'%';
					},
				$Templ);
		}

	static public function prepare_PATH($path)
		{ return str_replace('/', DIRECTORY_SEPARATOR, $path); }

	static public function prepare_DIR($dir)
		{
		$path = Util::prepare_PATH($dir);
		return ($path[strlen($path)-1] != DIRECTORY_SEPARATOR ? $path.DIRECTORY_SEPARATOR : $path);
		}

	static function rrmdir($dir)
		{
		if (is_dir($dir))
			{
			$dirscan = scandir($dir);
			foreach ($dirscan as $object)
				{
				if ($object != "." && $object != "..")
					{
					if (is_link($dir."/".$object)) # object is symlink
						{
						if (!unlink($dir."/".$object))
							{ return FALSE; }
						}
					elseif (is_dir($dir."/".$object)) # object is folder
						{
						if (!rrmdir($dir."/".$object))
							{ return FALSE; }
						}
					else # object is file
						{
						if (!unlink($dir."/".$object))
							{ return FALSE; }
						}
					}
				}
			reset($dirscan);
			if (!rmdir($dir))
				{ return FALSE; }
			return TRUE;
			}
		return FALSE;
		}

	static public function GUID()
		{
		if (function_exists('com_create_guid') === true)
			{ return trim(com_create_guid(), '{}'); }
		return sprintf(
				'%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
				mt_rand(0, 65535),
				mt_rand(0, 65535),
				mt_rand(0, 65535),
				mt_rand(16384, 20479),
				mt_rand(32768, 49151),
				mt_rand(0, 65535),
				mt_rand(0, 65535),
				mt_rand(0, 65535));
		}

	static public function COALESCE()
		{
		foreach (func_get_args() as $arg)
			{
			if (isset($arg))
				{ return $arg; }
			}
		return null;
		}

	static public function At(Array &$Arr, $Id, $defaultId = null)
		{ return array_key_exists($Id, $Arr) ? $Arr[$Id] : (array_key_exists($defaultId, $Arr) ? $Arr[$defaultId] : null); }

	static public function killEndBR($str)
		{ return (preg_match('/\s*(\<br\s*\/?\>\s*)+$/', $str, $args) ? substr($str, 0, -strlen($args[0])) : $str); }

	static public function array_rtrim(Array $arr, Array $trim)
		{
		while (in_array(end($arr), $trim, true))
			{ array_pop($arr); }
		return $arr;
		}

	static public function explodeBR(&$str, $br = '<br>')
		{ return isset($str) && is_string($str) ? Util::array_rtrim(explode($br, $str), Array('', null)) : Array(); }

	static public function fileExtToMimType($ext)
		{
		static $mims = Array(
			0 => 'application/force-download',
			'.pdf' => 'application/pdf',
			'.txt' => 'text/plain',
			'.html' => 'text/html',
			'.htm' => 'text/html',
			'.exe' => 'application/octet-stream',
			'.zip' => 'application/zip',
			'.doc' => 'application/msword',
			'.xls' => 'application/vnd.ms-excel',
			'.ppt' => 'application/vnd.ms-powerpoint',
			'.gif' => 'image/gif',
			'.png' => 'image/png',
			'.jpeg'=> 'image/jpg',
			'.jpg' =>  'image/jpg',
			'.php' => 'text/plain',
			'.rtf' => 'application/msword');
		if ($ext[0] !== '.')
			{ $ext = '.'.$ext; }
		return $mims[array_key_exists($ext, $mims) ? $ext : 0];
		}

	static public function Str2DateObject($str, $tz = 'UTC')
		{
		if (null === $str)
			{ return $str; }
		static $tzUTC = null;
		if (!isset($tzUTC))
			{ $tzUTC = new DateTimeZone('UTC'); }
		$dt = new DateTime($str, 'UTC' === $tz ? $tzUTC : new DateTimeZone($tz));
		return $dt;
		}

	static public function DateObject2Str($dt, $tz = 'UTC')
		{
		if (null === $dt)
			{ return $dt; }
		static $tzUTC = null;
		if (!isset($tzUTC))
			{ $tzUTC = new DateTimeZone('UTC'); }
		$dt->setTimezone('UTC' === $tz ? $tzUTC : new DateTimeZone($tz));
		return $dt->format('UTC' === $tz ? 'Y-m-d\TH:i:s\Z' : 'Y-m-d\TH:i:sP');
		}

	static public function FormatDate($str, $tzFrom = 'UTC', $format='Y-m-d H:i')
		{
		$dt = Util::Str2DateObject($str, $tzFrom);
		$dt->setTimezone(new DateTimeZone('Europe/Kiev'));
		return $dt->format($format);
		}

	static public function FormatOnlyDate($str, $tzFrom = 'UTC', $format='Y-m-d')
		{ return Util::FormatDate($str, $tzFrom, $format); }

	static public function Timestamp($str, $tzTo = 'UTC', $tzFrom = 'UTC')
		{ return Util::DateObject2Str(Util::Str2DateObject($str, $tzFrom), $tzTo); }

	static public function minTimestamp(Array $arryaStr, $tzTo = 'UTC', $tzFrom = 'UTC')
		{
		Log::Out(Array('minTimestamp $arryaStr' => $arryaStr));
		$ret = Util::DateObject2Str(array_reduce($arryaStr, function($min, $str) use ($tzFrom)
			{
			$date = Util::Str2DateObject($str, $tzFrom);
			return null === $min || $date < $min ? $date : $min;
			}, null));
		Log::Out(Array('$ret' => $ret));
		return $ret;
		}

	static public function maxTimestamp(Array $arryaStr, $tzTo = 'UTC', $tzFrom = 'UTC')
		{
		Log::Out(Array('maxTimestamp $arryaStr' => $arryaStr));
		$ret = Util::DateObject2Str(array_reduce($arryaStr, function($max, $str) use ($tzFrom)
			{
			$date = Util::Str2DateObject($str, $tzFrom);
			return null === $max || $date > $max ? $date : $max;
			}));
		Log::Out(Array('$ret' => $ret));
		return $ret;
		}

/*
$from = Util::Str2DateObject($str, 'Europe/Kiev'); // Конвертирем во временную зону в которой надо получить диапазон
$from->setTime(0, 0); // Это сбросит время в 0 (зона уже соответсвует, см строку выше)
$to = clone $from; // Теперь надо конец диапазона
$to->modify('+1 day'); // Допустим 1 день
// А теперь получаем строки для запроса в БД (в зоне UTC)
$from = Util::DateObject2Str($from);
$to = Util::DateObject2Str($to);
*/


	static public function uniqid($prefix = "", $more_entropy = false)
		{
		static $inc = 0;
		static $base = null;
		static $more = null;
		if (!$more_entropy)
			{
			if (null === $base)
				{ $base = uniqid(); }
			return $prefix.$base.(++$inc);
			}
		if (null === $more)
			{ $more = uniqid('', true); }
		return $prefix.$more.(++$inc);
		}


	/**
	 * function imageSmoothAlphaLine() - version 1.0
	 * Draws a smooth line with alpha-functionality
	 *
	 * @param   ident    the image to draw on
	 * @param   integer  x1
	 * @param   integer  y1
	 * @param   integer  x2
	 * @param   integer  y2
	 * @param   integer  red (0 to 255)
	 * @param   integer  green (0 to 255)
	 * @param   integer  blue (0 to 255)
	 * @param   integer  alpha (0 to 127)
	 *
	 * @access  public
	 *
	 * @author  DASPRiD <d@sprid.de>
	 */
	static public function imageSmoothAlphaLine ($image, $x1, $y1, $x2, $y2, $r, $g, $b, $alpha=0) {
		$icr = $r;
		$icg = $g;
		$icb = $b;
		$dcol = imagecolorallocatealpha($image, $icr, $icg, $icb, $alpha);

		if ($y1 == $y2 || $x1 == $x2)
			imageline($image, $x1, $y2, $x1, $y2, $dcol);
		else {
			$m = ($y2 - $y1) / ($x2 - $x1);
			$b = $y1 - $m * $x1;

			if (abs ($m) <2) {
				$x = min($x1, $x2);
				$endx = max($x1, $x2) + 1;

				while ($x < $endx) {
					$y = $m * $x + $b;
					$ya = ($y == floor($y) ? 1: $y - floor($y));
					$yb = ceil($y) - $y;

					$trgb = ImageColorAt($image, $x, floor($y));
					$tcr = ($trgb >> 16) & 0xFF;
					$tcg = ($trgb >> 8) & 0xFF;
					$tcb = $trgb & 0xFF;
					imagesetpixel($image, $x, floor($y), imagecolorallocatealpha($image, ($tcr * $ya + $icr * $yb), ($tcg * $ya + $icg * $yb), ($tcb * $ya + $icb * $yb), $alpha));

					$trgb = ImageColorAt($image, $x, ceil($y));
					$tcr = ($trgb >> 16) & 0xFF;
					$tcg = ($trgb >> 8) & 0xFF;
					$tcb = $trgb & 0xFF;
					imagesetpixel($image, $x, ceil($y), imagecolorallocatealpha($image, ($tcr * $yb + $icr * $ya), ($tcg * $yb + $icg * $ya), ($tcb * $yb + $icb * $ya), $alpha));

					$x++;
				}
			} else {
				$y = min($y1, $y2);
				$endy = max($y1, $y2) + 1;

				while ($y < $endy) {
					$x = ($y - $b) / $m;
					$xa = ($x == floor($x) ? 1: $x - floor($x));
					$xb = ceil($x) - $x;

					$trgb = ImageColorAt($image, floor($x), $y);
					$tcr = ($trgb >> 16) & 0xFF;
					$tcg = ($trgb >> 8) & 0xFF;
					$tcb = $trgb & 0xFF;
					imagesetpixel($image, floor($x), $y, imagecolorallocatealpha($image, ($tcr * $xa + $icr * $xb), ($tcg * $xa + $icg * $xb), ($tcb * $xa + $icb * $xb), $alpha));

					$trgb = ImageColorAt($image, ceil($x), $y);
					$tcr = ($trgb >> 16) & 0xFF;
					$tcg = ($trgb >> 8) & 0xFF;
					$tcb = $trgb & 0xFF;
					imagesetpixel ($image, ceil($x), $y, imagecolorallocatealpha($image, ($tcr * $xb + $icr * $xa), ($tcg * $xb + $icg * $xa), ($tcb * $xb + $icb * $xa), $alpha));

					$y ++;
				}
			}
		}
	} // end of 'imageSmoothAlphaLine()' function

	public static function hex2rgb($hex) {
		$hex = str_replace("$", "", $hex);
		$hex = str_replace("#", "", $hex);

		if(strlen($hex) == 3) {
			$r = hexdec(substr($hex,0,1).substr($hex,0,1));
			$g = hexdec(substr($hex,1,1).substr($hex,1,1));
			$b = hexdec(substr($hex,2,1).substr($hex,2,1));
		} else {
			$r = hexdec(substr($hex,0,2));
			$g = hexdec(substr($hex,2,2));
			$b = hexdec(substr($hex,4,2));
		}
		$rgb = array($r, $g, $b);
		//return implode(",", $rgb); // returns the rgb values separated by commas
		return $rgb; // returns an array with the rgb values
	}

	public static function sendPOST($hostname, $data, $strCookie = '', $headers = 0 )
	{
		$ch = curl_init();
		$url=$hostname;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		if(!empty($strCookie))
			curl_setopt($ch, CURLOPT_COOKIE, $strCookie);
		$data_res = curl_exec($ch);
		$errno=curl_errno($ch);
		$error=curl_error($ch);

		if ($errno!=0)	{
			Log::Out("sendPOST::$errno, $error");
			Log::Out(Array("sendPOST::", '$data'=>$data));
		}

		if ($headers) {

			$header=substr($data_res, 0, curl_getinfo($ch,CURLINFO_HEADER_SIZE));
			$body=substr($data_res, curl_getinfo($ch,CURLINFO_HEADER_SIZE));
			preg_match_all("/Set-Cookie: (.*?)=(.*?);/i",$header,$res);
			$cookie='';
			foreach ($res[1] as $key => $value) {
				$cookie.= $value.'='.$res[2][$key].'; ';
			};

			$data_res = array(
				'body' => $body,
				'cookies' => $cookie
			);
		}

		curl_close($ch);

		return $data_res;
	}

}