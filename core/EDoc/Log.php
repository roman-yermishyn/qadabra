<?php
/*
class Log2
	{
	static public $startTime = null;
	static public $id = null;
	static public $old_error_handler = null;
	
	static public function Init($curTime)
		{
		self::$startTime = $curTime;
		self::$id = Util::GUID();
		self::$old_error_handler = set_error_handler("Log2::ErrorHandler");
		}
	
	static public function Out($params)
		{
//		Log::Out(Array('Log2::Out() 1', $mode, $msg, $module));
		$mode = $params['mode'] = array_key_exists('mode', $params) ? $params['mode'] : LOGS_ENV::ON;
		$module = $params['module'] = array_key_exists('module', $params) ? $params['module'] : 'ANY';

		if ($mode > LOGS_ENV::MAX_LEWEL || (array_key_exists($module, LOGS_ENV::$MODE) && $mode > LOGS_ENV::$MODE[$module]))
			{ return; }

		static $ModeText = Array(
				0 => 'OFF',
				1 => 'ERRORS',
				2 => 'MAIN',
				3 => 'WARNINGS',
				4 => 'INFO',
				5 => 'DEBUG');
		$params['ModeText'] = $ModeText[$mode];

		$curTime = $params['curTime'] = microtime(true);
		if (!self::$startTime)
			{ self::Init($curTime); }

//		Log::Out(Array('Log2::Out() 2', $module.TAB.$msg.CR, 3, Util::prepare_DIR(LOGS_ENV::DIR).'Error2.log'));
		$params['id'] = self::$id;
		$params['dt'] = $curTime - self::$startTime;
		error_log(print_r($params, true).CR, 3, Util::prepare_DIR(LOGS_ENV::DIR).LOGS_ENV::FILE);
		}

	static public function Error($params)
		{ $params['mode'] = LOGS_ENV::ERRORS; self::Out($params); }

	static public function Main($params)
		{ $params['mode'] = LOGS_ENV::MAIN; self::Out($params); }

	static public function Warning($params)
		{ $params['mode'] = LOGS_ENV::WARNINGS; self::Out($params); }

	static public function Info($params)
		{ $params['mode'] = LOGS_ENV::INFO; self::Out($params); }

	static public function Debug($params)
		{ $params['mode'] = LOGS_ENV::DEBUG; self::Out($params); }

	static function ErrorHandler($errno, $errstr, $errfile, $errline)
		{
		$ex = new Exception($errstr);
		$stack = $ex->getTrace();
		unset($stack[0]);
		switch ($errno)
			{
			case E_USER_ERROR:
				$msg = "<b>My ERROR</b> [$errno] $errstr<br />\n".
					"  Fatal error on line $errline in file $errfile, PHP ".PHP_VERSION." (".PHP_OS.")<br />\n".
					"Aborting...";
				self::Error(Array('msg' => $msg, 'module' => 'SYS', 'stack' => $stack));
				exit(1);
				break;

			case E_USER_WARNING:
				$msg = "<b>My WARNING</b> [$errno] $errstr";
				self::Warning(Array('msg' => $msg, 'module' => 'SYS', 'stack' => $stack));
				break;

			case E_USER_NOTICE:
				$msg = "<b>My NOTICE</b> [$errno] $errstr";
				self::Info(Array('msg' => $msg, 'module' => 'SYS', 'stack' => $stack));
				break;

			case E_WARNING:
				$msg = "<b>Warnings</b> on line $errline in file $errfile<br />\n".
					"[$errno] $errstr";
				self::Warning(Array('msg' => $msg, 'module' => 'SYS', 'stack' => $stack));
				break;

			case E_NOTICE:
				$msg = "<b>Notice</b> on line $errline in file $errfile<br />\n".
					"[$errno] $errstr";
				self::Info(Array('msg' => $msg, 'module' => 'SYS', 'stack' => $stack));
				break;

			default:
				$msg = "Unknown error type: [$errno] $errstr";
				self::Debug(Array('msg' => $msg, 'module' => 'SYS', 'stack' => $stack));
				break;
		}

		// Don't execute PHP internal error handler 
		return true;
		}
		
	}

Log2::Init(microtime(true));
*/
class Log
	{

	static protected $StFileName = null;
	static $log = null;

	private $FileName;

	public function __construct($FileName = null)
		{
		if (!isset($FileName))
			{
			if (!isset(self::$StFileName))
				{ self::$StFileName = Util::FormatDate('now', 'UTC', 'U').'.log'; }
			$FileName = self::$StFileName;
			}
		$this->FileName = Util::prepare_DIR(LOGS_DIR).$FileName;
		}

	public function &__invoke($Message)
		{ return $this->_out($Message); }

	private function &_out($Message)
		{
		$f = @fopen($this->FileName, 'a');
		if (! $f)
			{ return $this; }
		list($usec, $tmp) = explode(" ", microtime());
		list($tmp, $usec) = explode(".", $usec);
		$time = Util::FormatDate('now', 'UTC', 'd H:i:s').'.'.$usec.' ';
		//$time = date('d H:i:s').'.'.$usec.' ';
		if (flock($f, LOCK_EX))
			{
			fwrite($f, $time.Util::print_r($Message).PHP_EOL);
			flock($f, LOCK_UN);
			}
		fclose($f);
		return $this;
		}

	static public function Out($Message)
		{
		if (!(defined('LOG')&&!defined('LOG_UNDER_USER') || defined('LOG') && defined('LOG_UNDER_USER') && 0==strcmp(GetUser(), LOG_UNDER_USER)))
			{ return null; }
		if (!isset(self::$log))
			{ self::$log = new Log(); }
		return self::$log->_out($Message);
		}

	} // class Log

function LogError()
	{
	static $LogError = null;
	if (null === $LogError)
		{ $LogError = new Log('Error.log'); }
	$LogError(func_get_args());
	}

function &LogTaskTrap()
	{
	static $LogTrap = null;
	if (null === $LogTrap)
		{ $LogTrap = new Log('tasks/TaskTrap.log'); }
	$LogTrap(func_get_args());
	return $LogTrap;
	}
