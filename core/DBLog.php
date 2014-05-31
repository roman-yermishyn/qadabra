<?php

// database query log (stored as a file)

class DBLog
	{
	var $date;
	var $query;
	var $time_start;
	var $time; // execution time in milliseconds
	var $result;

	var $filename;
	var $nouse;

	function __construct($filename = null)
		{
		$this->use = defined('DBLOG_FILE') && defined('DBLOG') && defined('DBLOG_USER') && (DBLOG_USER == 1 || DBLOG_USER == GetUser());
		if (!$this->use)
			{ return; }
		$this->filename = Util::prepare_DIR(LOGS_DIR).(isset($filename) ? $filename : DBLOG_FILE);
		}

	function __destruct()
		{
		try
			{
			if ($this->use && $this->time_start)
				{
				$this->time = microtime(true) - $this->time_start;
				$this->save(4);
				}
			}
		catch (Exception $ex)
			{
			Log::Out(Util::Replacer(
				'Collector::__destruct catched Exception "%ex%"'.CR.'Exception suppressed/ignored!!!',
				Array('ex' => $ex)));
			}
		}

	public function begin($query)
		{
		if ($this->use)
			{
			$this->date = Util::Timestamp('now', 'Europe/Kiev'); //date('y-m-d H:i:s');
			$this->query = $query;
			$this->time_start = microtime(true);
			$this->result = null;
			$this->save();
			}
		}

	public function data($result)
		{
		if ($this->use)
			{
			$this->time = microtime(true) - $this->time_start;
			$this->result = $result;
//			$this->save(2);
			}
		}

	public function end()
		{
		if ($this->use)
			{
			$this->time = microtime(true) - $this->time_start;
			$this->save(5);
			}
		}

	public function Error($error)
		{
		if ($this->use)
			{
			$this->time = microtime(true) - $this->time_start;
			$this->result = $error;
			$this->save(3);
			}
		}

	public function lookup($query, &$result)
		{
		return false; // interface stub
		}

	protected function save($line = 1)
		{
		$f = @fopen($this->filename, 'a');
		if ($f)
			{
			switch ($line)
				{
				case 1:
					$text = $this->date.CR.$this->query;
					break;
				case 2:
					$text = sprintf('%7.3f', $this->time);
					if (count($this->result) == 1 && count($this->result[0] == 1) && isset($this->result[0]['id']))
						{ $text .= ' res='.$this->result[0]['id']; }
					else // procedure result
						{ $text .= ' '.count($this->result); }
					break;
				case 3:
					$text = sprintf('%7.3f', $this->time).' ERROR: '.$this->result;
					break;
				case 4:
					$text = sprintf('%7.3f', $this->time).' RESULT DROP';
					$this->time_start = null;
					break;
				case 5:
					$text = sprintf('%7.3f', $this->time);
					if (isset($this->result))
						{
						if (count($this->result) == 1 && count($this->result[0] == 1) && isset($this->result[0]['id']))
							{ $text .= ' res='.$this->result[0]['id']; }
						else // procedure result
							{ $text .= ' '.count($this->result); }
						}
					$this->time_start = null;
					break;
				}
			fwrite($f, $text.CR);
			fclose($f);
			}
		}
	}

?>