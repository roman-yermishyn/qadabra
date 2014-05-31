<?php

require_once('./core/base_util.php');
require_once('./conf/define.php');

class MyFileSessionHandler
	{
	private $SavePath = null;
	private $Name = null;
	private $fp = null;
	private $fp_lock = null;

	private function __construct($key)
		{ $this->key = $key; }

	static public function Init($key)
		{
		static $handler = null;
		if (!$handler)
			{
			// we'll intercept the native 'files' handler, but will equally work
			// with other internal native handlers like 'sqlite', 'memcache' or 'memcached'
			// which are provided by PHP extensions.
			ini_set('session.save_handler', 'files');
			$handler = new MyFileSessionHandler($key);
			session_set_save_handler(
					Array($handler, 'open'),
					Array($handler, 'close'),
					Array($handler, 'read'),
					Array($handler, 'write'),
					Array($handler, 'destroy'),
					Array($handler, 'gc'));
			// the following prevents unexpected effects when using objects as save handlers
			register_shutdown_function('session_write_close');
			}
		else
			{ $handler->key = $key; }
		}

	public function open($SavePath, $Name)
		{
		$this->SavePath = (defined('SESSION_DIR') && SESSION_DIR) ? SESSION_DIR : $SavePath;
		if (empty($this->SavePath))
			{ return false; }
		if (!is_dir($this->SavePath))
			{ mkdir($this->SavePath, 0777); }
		$this->Name = $Name;
		return true;
		}

	public function close()
		{
		if ($this->fp)
			{
			fclose($this->fp); 
			$this->fp = null;
			} 
		if ($this->fp_lock)
			{
			flock($this->fp_lock, LOCK_UN); 
			fclose($this->fp_lock); 
			$this->fp_lock = null;
			} 
		return true; 
		} 

	public function read($id)
		{
		$File = Util::prepare_PATH($this->SavePath.'/sess_'.$id);
		$this->fp_lock = @fopen($File.'.lock', 'w');
		if ($this->fp_lock)
			{
			if (flock($this->fp_lock, LOCK_EX))
				{
				$this->fp = @fopen($File, 'c+');
				if ($this->fp)
					{
					$size = filesize($File);
					return $this->readSessionDataCallback($id, empty($size) ? '' : fread($this->fp, $size), $size);
					}
				flock($this->fp_lock, LOCK_UN); 
				}
			fclose($this->fp_lock);
			$this->fp_lock = null; 
			}
		return '';
		}

	public function write($id, $Data)
		{
		$File = Util::prepare_PATH($this->SavePath.'/sess_'.$id);
		if (!$this->fp_lock)
			{
			$this->fp_lock = @fopen($File.'.lock', 'w');
			if (!$this->fp_lock)
				{ return false; }
			if (!flock($this->fp_lock, LOCK_EX))
				{
				fclose($this->fp_lock);
				return false;
				}
			}
		if (!$this->fp)
			{
			$this->fp = @fopen($File, 'w');
			if (!$this->fp)
				{
				flock($this->fp_lock, LOCK_UN);
				fclose($this->fp_lock);
				return false;
				}
			}
		else
			{ fseek($this->fp, 0); }
		return fwrite($this->fp, $this->writeSessionDataCallback($id, $Data));
		}

	public function destroy($id)
		{ return $this->close() && $this->deleteSession($id); }

	public function gc($maxlifetime)
		{
		foreach (glob(Util::prepare_DIR($this->SavePath).'sess_*') as $File)
			{
			clearstatcache(true, $File);
			if (file_exists($File) && max(fileatime($File), filemtime($File)) + $maxlifetime < time())
				{
				$n_left = strrpos($File, '_');
				$n_right = strrpos($File, '.');
				$id = substr($File, $n_left+1, $n_right - $n_left - 1);
				$this->deleteSession($id);
				}
			}
		return true;
		}

	public function deleteSession($id)
		{
		$File = Util::prepare_PATH($this->SavePath.'/sess_'.$id);
		return @$this->deleteSessionCallback($id) && @unlink($File) && @unlink($File.'.lock');
		}

	function readSessionDataCallback($id, $Data, $size)
		{
		return $Data;
		}

	function writeSessionDataCallback($id, $Data)
		{
		return $Data;
		}
	
	function deleteSessionCallback($id)
		{
		$db = dbNew();
		$sql = 'DELETE FROM ct_user_sessions WHERE php_session_id = \''.$db->escapeString($id).'\'';
		$db->execute($sql);
		return true;
		}

	} // class MyFileSessionHandler

MyFileSessionHandler::Init('');
