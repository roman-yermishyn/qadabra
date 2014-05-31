<?php
require_once 'DBLog.php';
require_once './core/EDoc/Log.php';

function isNull($v)
	{ return !isset($v); }

function isTrue($v)
	{ return !isNull($v) && ('t' === $v || 'f' !== $v && !!$v); }

function isFalse($v)
	{ return !isNull($v) && ('f' === $v || 't' !== $v && !$v); }

function toBool($v)
	{ return isNull($v) ? null : (isTrue($v) ? 't' : 'f'); }
function EpochDate($str)
	{
	// 0123456789
	// 2010-01-01
	return mktime(0,0,0, substr($str,5,2), substr($str,8,2), substr($str,0,4));
	}

function array_pluck($key, Array $input)
	{
	if (!is_array($input))
		{ return null; }
	$array = array();
	foreach ((Array)$key as $k)
		{
		foreach($input as $i => $j)
			{
			if (array_key_exists($k, $j))
				{ $array[$i][$k] = $j[$k]; }
			}
		}
	return $array;
	}

function array_select(Array $input, Array $keys, $force = false)
	{
	$ret = array();
	foreach ($keys as $k)
		{
		if (array_key_exists($k, $input))
			{ $ret[$k] = $input[$k]; }
		elseif ($force)
			{ $ret[$k] = null; }
		}
	return $ret;
	}

function array_ReassocById(Array $input)
	{
	$ret = Array();
	foreach ($input as &$row)
		{ $ret[$row['id']] = &$row; }
	return $ret;
	}

function PluckRecordSet(Array $RecordSet, Array $PluckFilter)
	{
	$rows = Array();
	foreach ($RecordSet as &$row)
		{
		$val = Array();
		foreach ($PluckFilter as $key => &$Pluck)
			{
			$FieldName = is_numeric($key) ? $Pluck : $key;
			$val[$Pluck] = $row[$FieldName];
			}
		$rows[$row['id']] = $val;
		}
	return $rows;
	}

abstract class Database
	{
	abstract protected function exec($query);
	abstract protected function getError($exec_result);
	abstract protected function fetch($exec_result);
	abstract protected function free_result($exec_result);
	abstract protected function prepareProcedureCall($name, $inputParams);
	abstract protected function &_newTransaction(Database &$db);
	abstract protected function isValid();

	abstract public function dbGetMetaTable($name);
	abstract public function getDoctrineConnectionString();
	abstract public function escapeString($string);

	public function conv2String()
		{ return Array('Database::' => null); }

	/**
	 * @return _Transaction
	 */
	public function &newTransaction()
		{ return $this->_newTransaction($this); }

	public function execute($query)
		{
		$exec_result = $this->exec(ltrim(rtrim($query)));
		$recordset = $this->fetch($exec_result);
		$this->free_result($exec_result);
//		Log::Out(Array('Database::execute($query)' => $query, '$recordset' => $recordset));
		return $recordset;
		}

	public function executeCached($query)
		{
			if(!SelectorBuilderCache::Exists('query_cache'))
			{
				$ar = array();
				SelectorBuilderCache::SetValue('query_cache', $ar);
			}

			$query_cache = SelectorBuilderCache::GetValue('query_cache');

			if(!isset($query_cache[md5($query)]))
			{
				$query_cache[md5($query)] = $this->execute($query);
				SelectorBuilderCache::SetValue('query_cache', $query_cache);
			}

			return $query_cache[md5($query)];
		}

	public function query($query)
		{ $this->free_result($this->exec(ltrim(rtrim($query)))); }

	// создать экземпляр концепта на основе $data
	public function addConceptInstance($table_name, $data) 
	{
		// добавить системные поля
		$data['id'] = "\"GetObjectID\"(0)";
		$data['dt'] = "now()";
		$data['counter'] = "0";
		$data['uid'] = GetRealUser();
		
		// создать запись с новым id
		return $this->getValue("INSERT INTO $table_name (".
			implode(',', array_keys($data)).") VALUES (".
			implode(',', array_values($data)).") RETURNING id"); 
	}

	// проверить существование экземплярв концепта на основе $data
	public function hasConceptInstance($table_name, $data) 
	{
		$filter = array();
		foreach ($data as $key => $value)
			$filter[] = "$key = $value";
			
		$res = $this->getValue("SELECT * FROM $table_name WHERE ".
			implode(' AND ', $filter)." LIMIT 1");
		return count($res) > 0;
	}
	
	public function executeFunction($func_name, $arg_list = null, $quote = false)
		{
		if (is_array($arg_list))
			{
			$list = null;
			foreach ($arg_list as $key => &$value)
				{
				if (isset($list))
					$list .= ',';
				if (is_bool($value))
					$list .= $value ? 1 : 0;
				else
					if ($value === '' || $value === null)
						$list .= 'NULL';
					else
						$list .= '\''.@addslashes(@preg_replace('/\'/', "`", $value)).'\'';
				}
			}
		if (!isset($list))
			$list = '';

		if ($quote)
			$func_name = "\"$func_name\"";
		$row = $this->execute("select $func_name($list) as id_new");
		if ($row == null || $row[0] == null)
			return null;

		return $row[0]['id_new'];
		}

	public function executeProcedure($name, $input = null, $output = null, $withoutOutput = false, $orderByClause = null, $top = null, $skip = null)
		{
		if(is_array($input))
			{
			foreach ($input as $key => &$value)
				{
				if(is_bool($value))
					$value = $value ? 1 : 0;
				else
					if($value === '' || $value === null)
						$value = 'NULL';
					else
						$value = '\''.@addslashes(@preg_replace('/\'/', "`", $value)).'\'';
				}
			$inputParams = join($input, ', ');
			$inputParams = "($inputParams)";
			}
		else
			$inputParams = '';

		if(is_array($output))
			$outputParams = join($output, ', ');
		else
			$outputParams = '*';

		$query = $this->prepareProcedureCall($name, $inputParams);

		return $this->execute($query);
		}

	//

	public function get($query)
		{
		$rs = $this->execute($query);
		return isset($rs[0])? $rs[0]: array();
		}

	public function getAll($query)
		{ return $this->execute($query); }

	public function getArray($query, $col_name = 'id')
		{ return fetchArray($this->execute($query), $col_name); }

	public function getValue($query, $col_name = 'id')
		{ return fetchValue($this->execute($query), $col_name); }

	public function getResult($func_name, $arg_list = null)
		{ return $this->executeFunction($func_name, $arg_list, 1); }

	public function getCount($table_name, $where = '')
		{ return $this->getValue("select count(*) from $table_name $where", 'count'); }

	public function has($table_name, $where = '')
		{ return $this->getValue("select case when exists (select id from $table_name where $where) then 1 else 0 end as id"); }

	public function PrepareString($value)
		{
		return empty($value) ?
			('0' === strval($value) ? '0' : 'null') :
			'\''.$this->escapeString($value).'\'';
		}

	} // abstract class Database

class DatabaseLog extends Database
	{
	private $db;
	/**
	 * @var $dblog DBLog
	 */
	private $dblog;

	public function __construct(Database &$db, $filename = null)
		{
		$this->db = &$db;
		$this->filename = $filename;
		}

	protected function exec($query)
		{
		$this->dblog = new DBLog($this->filename);
		$this->dblog->begin($query);
		return $this->db->exec($query);
		}

	protected function getError($exec_result)
		{
		$error = $this->db->getError($exec_result);
		if ($error)
			{ $this->dblog->Error($error); }
		return $error;
		}

	protected function fetch($exec_result)
		{
		$data = $this->db->fetch($exec_result);
		$this->dblog->data($data);
		return $data;
		}

	protected function free_result($exec_result)
		{
		$this->dblog->end();
		return $this->db->free_result($exec_result);
		}

	protected function prepareProcedureCall($name, $inputParams)
		{ return $this->db->prepareProcedureCall($name, $inputParams); }

	public function dbGetMetaTable($name)
		{ return $this->db->dbGetMetaTable($name); }

	public function getDoctrineConnectionString()
		{ return $this->db->getDoctrineConnectionString(); }

	public function escapeString($string)
		{ return $this->db->escapeString($string); }

	public function conv2String()
		{
		return parent::conv2String() + Array(
				'DatabaseLog::db' => $this->db,
				'DatabaseLog::filename' => $this->filename);
		}

	protected function &_newTransaction(Database &$db)
		{ return $this->db->_newTransaction($db); }

	protected function isValid()
		{ return $this->db->isValid(); }

	} // class DatabaseLog

class _Transaction extends Database
	{
	protected $db;

	public function __construct(Database &$db)
		{
		$this->db = &$db;
		$this->_begin();
		}

	protected function _begin()
		{ $this->db->query('BEGIN;'); }

	protected function exec($query)
		{ return $this->db->exec($query); }

	protected function getError($exec_result)
		{ return $this->db->getError($exec_result); }

	protected function fetch($exec_result)
		{ return $this->db->fetch($exec_result); }

	protected function free_result($exec_result)
		{ return $this->db->free_result($exec_result); }

	protected function prepareProcedureCall($name, $inputParams)
		{ return $this->db->prepareProcedureCall($name, $inputParams); }

	public function dbGetMetaTable($name)
		{ return $this->db->dbGetMetaTable($name); }

	public function getDoctrineConnectionString()
		{ return $this->db->getDoctrineConnectionString(); }

	public function escapeString($string)
		{ return $this->db->escapeString($string); }

	protected function &_newTransaction(Database &$db)
		{ return $this->db->_newTransaction($db); }

	protected function isValid()
		{ return isset($this->db) && $this->db->isValid(); }

	public function Commit()
		{
		if (!$this->isValid())
			{ throw new Exception('Invalid _Transaction::Commit()'); }
		$this->db->query('COMMIT;');
		unset($this->db);
		}

	public function Rollback()
		{
		if (!$this->isValid())
			{ throw new Exception('Invalid _Transaction::Rollback()'); }
		$this->db->query('ROLLBACK;');
		unset($this->db);
		}

	function __destruct()
		{
		if (isset($this->db))
			{
			try { $this->Rollback(); }
			catch (Exception $ex) {}
			}
		}

	public function conv2String()
		{ return parent::conv2String() + Array('Transaction::db' => $this->db); }

	} // class Transaction

// Сообщение-ошибка для отправки браузеру
class DatabaseErrorException extends Exception
	{
	public function conv2String()
		{ return Array('DatabaseErrorException::getMessage()' => $this->getMessage()); }

	public function __construct($ErrorText, $query)
		{ parent::__construct($ErrorText.CR.$query); }
	}

class ErrorThrowDatabase extends Database
	{
	private $db;

	public function __construct(Database &$db)
		{ $this->db = &$db; }

	protected function exec($query)
		{
		$exec_result = $this->db->exec($query);
		$error = $this->getError($exec_result);
		if ($error)
			{
			$this->free_result($exec_result);
			$ex = new DatabaseErrorException($error, $query);
			$stack = $ex->getTrace();
			array_shift($stack);
			LogError('ErrorThrowDatabase::exec() generate DatabaseErrorException:', $ex, $stack);
			if (!defined('IGNORE_SQL_ERROR') || true !== IGNORE_SQL_ERROR || defined('ACTION_EXECUTER_SQL'))
				{ throw $ex; }
			}
		return $exec_result;
		}

	protected function getError($exec_result)
		{ return $this->db->getError($exec_result); }

	protected function fetch($exec_result)
		{
		$data = $this->db->fetch($exec_result);
		return $data;
		}

	protected function free_result($exec_result)
		{ return $this->db->free_result($exec_result); }

	protected function prepareProcedureCall($name, $inputParams)
		{ return $this->db->prepareProcedureCall($name, $inputParams); }

	public function dbGetMetaTable($name)
		{ return $this->db->dbGetMetaTable($name); }

	public function getDoctrineConnectionString()
		{ return $this->db->getDoctrineConnectionString(); }

	public function escapeString($string)
		{ return $this->db->escapeString($string); }

	protected function &_newTransaction(Database &$db)
		{ return $this->db->_newTransaction($db); }

	protected function isValid()
		{ return $this->db->isValid(); }

	public function conv2String()
		{ return parent::conv2String() + Array('ErrorThrowDatabase::db' => $this->db); }

	} // class ErrorThrowDatabase

class _TransactionPostgresN extends _Transaction
	{
	static $sp = 0;
	private $name;

	public function __construct(Database &$db, $name = null)
		{
		$this->name = 'sp_'.(isset($name) ? $name : ++self::$sp);
		parent::__construct($db);
		}

	protected function _begin()
		{ $this->db->query('SAVEPOINT '.$this->name); }

	protected function &_newTransaction(Database &$db)
		{
		$db_tr = new _TransactionPostgresN($db);
		return $db_tr;
		}

	public function Commit()
		{
		$this->db->query('RELEASE SAVEPOINT '.$this->name);
		unset($this->db);
		}

	public function Rollback()
		{
		$this->db->query('ROLLBACK TO SAVEPOINT '.$this->name);
		unset($this->db);
		}

	public function conv2String()
		{ return parent::conv2String() + Array('_TransactionPostgresN::name' => $this->name); }

	} // class _TransactionPostgresN

class _TransactionPostgres1 extends _Transaction
	{
	public function __construct(Database &$db)
		{ parent::__construct($db); }

	protected function &_newTransaction(Database &$db)
		{
		$db_tr = new _TransactionPostgresN($db);
		return $db_tr;
		}

	public function conv2String()
		{ return parent::conv2String() + Array('_TransactionPostgres1::' => null); }

	} // class _TransactionPostgres1

class DatabasePostgres extends Database
	{
	private $db_host = null;
	private $db_user = null;
	private $db_pwd = null;
	private $db_base = null;
	private $db_port = null;

	private $con = null;

	public function __construct($db_host, $db_user, $db_pwd, $db_base, $db_port=0)
		{
		$this->db_host = $db_host;
		$this->db_user = $db_user;
		$this->db_pwd = $db_pwd;
		$this->db_base = $db_base;
		$this->db_port = empty($this->db_port) ? $db_port : 5432;
		$this->con = pg_connect("host={$this->db_host} dbname={$this->db_base} user={$this->db_user} password={$this->db_pwd} port={$this->db_port}", PGSQL_CONNECT_FORCE_NEW);
		if (false === $this->con)
			{ throw new DatabaseErrorException(pg_last_error($this->con), ''); }
		$this->query('SET timezone TO \'Etc/UTC\'');
		}

	function __destruct()
		{
		pg_close($this->con);
		$this->con = null;
		}

	protected function exec($query)
		{ return pg_query($this->con, $query); }

	protected function getError($exec_result)
		{
		switch (pg_result_status($exec_result))
			{
			case PGSQL_COMMAND_OK:
			case PGSQL_TUPLES_OK:
			case PGSQL_COPY_OUT:
			case PGSQL_COPY_IN:
				return false; // Это всё не ошибки
			case PGSQL_EMPTY_QUERY:
				$ret = 'PGSQL_EMPTY_QUERY'; // В принципе это тоже не ошибка, но
				break;
			case PGSQL_BAD_RESPONSE:
				$ret = 'PGSQL_BAD_RESPONSE'; // сервер не ответил
				break;
			case PGSQL_NONFATAL_ERROR:
				$ret = 'PGSQL_NONFATAL_ERROR'; // не фатальная ошибка
				break;
			case PGSQL_FATAL_ERROR:
				$ret = 'PGSQL_FATAL_ERROR'; // фатальная ошибка
				break;
			default:
				$ret = 'PGSQL_???_ERROR'; // а это чё за фигулина?!
				break;
			}
		return $ret.CR.pg_result_error($exec_result).pg_last_error($this->con);
		}

	protected function fetch($exec_result)
		{
		$data = $exec_result ? pg_fetch_all($exec_result) : $exec_result;
		return !is_array($data) ? Array() : $data;
		}

	protected function free_result($exec_result)
		{ return $exec_result ? pg_free_result($exec_result) : $exec_result; }

	protected function prepareProcedureCall($name, $inputParams)
		{ return "SELECT * FROM $name$inputParams"; }

	public function dbGetMetaTable($name)
		{ return $this->execute("SELECT column_name FROM information_schema.columns WHERE table_name =' $name'"); }

	public function getDoctrineConnectionString()
		{ return "pgsql://{$this->db_user}:{$this->db_pwd}@{$this->db_host}:{$this->db_port}/{$this->db_base}";  }

	protected function &_newTransaction(Database &$db)
		{
		$db_tr = new _TransactionPostgres1($db);
		return $db_tr;
		}

	protected function isValid()
		{ return isset($this->con) && $this->con; }

	public function escapeString($string)
		{ return pg_escape_string($this->con, $string); }

	public function conv2String()
		{
		return Array('DatabasePostgres::...' => '...');
//			'DatabasePostgres::db_host' => $this->db_host,
//			'DatabasePostgres::db_user' => $this->db_user,
//			'DatabasePostgres::db_pwd ' => $this->db_pwd,
//			'DatabasePostgres::db_base' => $this->db_base,
//			'DatabasePostgres::db_port' => $this->db_port,
//			'DatabasePostgres::con'     => $this->con);
		}

	} // class DatabasePostgres

// db object singleton

function &DB()
	{ return dbOpen(); }

function &dbNew()
	{
	// load DB adapter
	switch (DB)
		{
//		case 'MYSQL':
//			$dbos = new DatabaseMysql(MYSQL_DB_HOST, MYSQL_DB_USERNAME, MYSQL_DB_PASSWORD, MYSQL_DB_BASE);
//			break;
//		case 'CDBASE':
//			$dbos = new CDBase();
//			$dbos->open(DB_HOST.':'.DB_BASE, DB_USERNAME, DB_PASSWORD);
//			break;
		case 'POSTGRESQL':
			$db = new DatabasePostgres(POSTGRESQL_DB_HOST, POSTGRESQL_DB_USERNAME, POSTGRESQL_DB_PASSWORD, POSTGRESQL_DB_BASE, defined('POSTGRESQL_DB_PORT')?POSTGRESQL_DB_PORT:null);
			break;
		default:
			die("Wrong DB parameter");
		}
	if (defined('DBLOG'))
		{
		$db2 = new ErrorThrowDatabase(new DatabaseLog($db));
		return $db2;
		}
	return $db;
	}

function &dbOpen()
	{
	global $dbos;
	if (!isset($dbos))
		{ $dbos = dbNew(); }
	return $dbos;
	}

function dbGetTable(Database &$db, $name)
	{ return $db->execute("SELECT * FROM $name"); }

function dbGetMetaTable(Database &$db, $name)
	{
	switch (DB)
		{
		case 'POSTGRESQL':
			return $db->execute("SELECT column_name FROM information_schema.columns WHERE table_name =' $name'");
		case 'MYSQL':
			return $db->execute("SHOW COLUMNS FROM $name");
		}
	throw new Exception('Not supported DB');
	}

function dbGetCompleteTable(Database &$db, $name)
	{
	return Array(
			dbGetTable($db, $name),
			dbGetMetaTable($db, $name));
	}

// fetch some columns from a recordset, resulting in a 2d array
function fetch2d($recordset, $cols)
	{
	$result = array();
	for ($i = 0; $i < count($recordset); $i++)
		{
		$result[$i] = array();
		for ($j = 0; $j < count($cols); $j++)
			$result[$i][$j] = $recordset[$i][$cols[$j]];
		}
	return $result;
	}

function fetch2d_hash($hash)
	{
	$result = array();
	foreach ($hash as $k=>$v)
		$result[] = array($k, $v);
	return $result;
	}

// fetch 2 columns from a recordset, resulting in a hash
function fetchHash($recordset, $key_col, $value_col)
	{
	$result = array();
	for ($i = 0; $i < count($recordset); $i++)
		{
		$key = $recordset[$i][$key_col];
		$value = $recordset[$i][$value_col];
		$result[$key] = $value;
		}
	return $result;
	}

function fetchMultiHash($recordset, $key_col)
	{
	$result = array();
	foreach ($recordset as $key => $item)
		{
		$key = $item[$key_col];
		$result[$key][] = $item;
		}
	return $result;
	}

function fetchHashWithCollisions($recordset, $key_col, $value_col)
	{
	$result = array();
	foreach ($recordset as $key => $item)
		{
		$k = $item[$key_col];
		$result[$k][] = $item[$value_col];
		}
	return $result;
	}

function fetchArray($recordset, $col)
	{
	$result = array();
	foreach ($recordset as $record)
		array_push($result, $record[$col]);
	return $result;
	}

function fetchValue($recordset, $col)
	{ return isset($recordset[0][$col])? $recordset[0][$col]: null; }

function table_has($arr, $key, $value)
	{
	foreach ($arr as $item)
		if (array_key_exists($key, $item) && $item[$key] == $value)
			return true;
	return false;
	}

function CreateTableIndex($rs, $index_column_name)
	{
	$result = array();
	foreach ($rs as $pos=>$item)
		$result[$item[$index_column_name]] = $pos;
	return $result;
	}

function is_in($value, $arr)
	{
	foreach ($arr as $item)
		if ($item == $value)
			return 1;
	return 0;
	}

function InvertHash($hash)
	{
	$res = array();
	foreach ($hash as $k=>$v)
		$res[$v] = $k;
	return $res;
	}

// convert table to hash
function table2hash($recordset, $key_col)
	{
	$result = array();
	foreach ($recordset as $record)
		$result[$record[$key_col]] = $record;
	return $result;
	}

function dbGetTableColumn(Database &$db, $table_name, $key_column_name, $value_column_name)
	{
	$rs = $db->execute("SELECT $key_column_name, $value_column_name FROM $table_name");
	return fetchHash($rs, $key_column_name, $value_column_name);
	}

function quote($var)
	{
	return "'$var'";
	}

// object model

function dbGetObject(Database &$db, $table_name, $id_object)
	{
	$rs = $db->execute("SELECT * FROM $table_name WHERE id = $id_object");
	return $rs[0];
	}

function dbGetObjectProperty(Database &$db, $table_name, $id_object, $field_name)
	{
	$rs = $db->execute("SELECT $field_name FROM $table_name WHERE id = $id_object");
	return $rs[0][$field_name];
	}

function dbSetObjectProperty(Database &$db, $table_name, $id_object, $field_name, $value)
	{ $db->execute("UPDATE $table_name SET $field_name = $value WHERE id = $id_object"); }

function RESULT($rs)
	{ return $rs[0]['res']; }

// convert subsequent values to ranges, i.e. array(1,2,3,5) results in ((1,3),(5,5))

function values2ranges($values)
	{
	$res = array();

	$k_first = 0;
	$k_last = count($values) - 1;
	$from_value = $to_value = null;

	foreach ($values as $k=>$v)
		{
		if ($k == $k_first)
			{ $from_value = $to_value = $v; }
		elseif ($v == $to_value + 1)
			{ $to_value = $v; }
		else
			{
			$res[] = array($from_value, $to_value);
			$from_value = $to_value = $v;
			}
		if ($k == $k_last)
			$res[] = array($from_value, $to_value);
		}

	return $res;
	}

function dbPrepareString($value)
	{
	//if(gettype($value)=='boolean' && $value===false) $value = "false";
	//else if(gettype($value)=='boolean' && $value===true) $value = "true";
	if(empty($value) && strval($value)!='0') $value = 'null';
	else if(empty($value) && strval($value)=='0') $value = '0';
	else $value = "'".addslashes($value)."'";
	return $value;
	}

 // Проверяет является-ли пользователь администратором
 function IsUserAdmin($user_id)
	{
	$context = Array('tag_context' => Array('all' => Array(DBUtil::GetCTByName('users', DBUtil::CT_ID) => $user_id)));
	return 0 != count(dbExecSelectorByName(DB(), 'admin_access_check', $context, GetLang()));
	}

// получает стециальный шаблон номера документа
function GetSpecialNumPattern(Database &$db, $f_id)
	{
	$context=GetCurrentContext();
	$ctid = dbGetCTId($db, 'df_common_folders');
	$context['tag_context']['all'][$ctid] = array(array('value' => $f_id, 'field_name' => 'id' ));
	$res = dbExecSelectorByName($db, "special_catalogs", $context, GetLang(), 0, '', '', null);
	return $res[0]['num_template'];
	}
