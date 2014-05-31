<?php
require_once './core/base_util.php';
if (!file_exists("./conf/define.php"))
	{
	echo 'Rename "/conf/define.php.template" to "/conf/define.php" and set up the file properties';
	die;
	}
require_once './conf/define.php';
require_once('./logic/dbCT.php');
require_once('./logic/dbCTGraph.php');
require_once('./dev/devLib.php');

if (!isset($_SESSION['lang']))
	{ $_SESSION['lang'] = 'ru'; }

if (ORG_LANG_COUNT == 1)
	{ $_SESSION['id_lang'] = ORG_LANG_START; }
else if (!isset($_SESSION['id_lang']))
	{
	$_SESSION['id_lang'] = $_COOKIE['cookie_id_lang'];
	if (!isset($_SESSION['id_lang']))
		{ $_SESSION['id_lang'] = ORG_LANG_START; }
	}
$id_lang = $_SESSION['id_lang'];

function out($component, $message)
	{
	return;
	print "$component: $message<br>";
	}

// lang

function GetLang()
	{ return $_SESSION['id_lang']; }

function SetLang($id_lang)
	{
	$_SESSION['id_lang'] = $id_lang;
	setcookie("cookie_id_lang", $id_lang, time() + COOKIE_LIFETIME);
	}

// user

// Авторизация через Керберос
function AuthKerberos()
	{
	// Проверяем аторизовал ли апач юзера
	if (isset($_SERVER['PHP_AUTH_USER']))
		{
		$login = dbPrepareString($_SERVER['PHP_AUTH_USER']);
		//dbGetCTId(DB(), 'users');
		// Если да, тянем по нему инфу из БД
		$sql  = "select * from ct_users where login = $login";
		$rows = DB()->execute($sql);
		// Если юзер с таким логином найден, то инитим его
		if (count($rows) > 0)
			{
			InitNewUser($rows[0]['id']);
			return true;
			}
		else
			{ return false; }
		}
	}

function CheckIfUserLogged()
	{
	if (!isset($_SESSION['id_user']))
		{
		/*if(AuthKerberos())
	  {
		  PAGE_ROUTE_URI(ORG_START_URI);
	  }
	  else*/
			{
			PAGE_ROUTE("Home");
			}
		}
	}

// Выполняет подготовку под нового пользователя после успешной авторизации
function InitNewUser($id_user, $_db=null)
	{
	if(!empty($_db))
		$db = $_db;
	else
		$db = DB();
	Cache::clear();
	SetUser($id_user);
	SetWorkAsUser($id_user, $db);
	//$user_info = dbGetUser($db, $id_user);
	//SetTimeZone($user_info['time_zone']);
	SetTimeZone(2);
	
	// В будущем зону нужно будет вытаскивать из настроек пользователя
	SetUserTimeZone('Europe/Kiev');
	
	User::autologin($id_user, $db);

	$sql = "
				update ct_users set login_fails = 0
				where id = $id_user";
	$db->query($sql);

	$rows      = $db->execute("select * from ct_users where id = $id_user");
	$user_info = $rows[0];

	if (!empty($user_info['ct_default_enterprise_id']))
		{ $_SESSION['default_enterprise_id'] = $user_info['ct_default_enterprise_id']; }

	// Не совсем понятно зачем это уже надо:
	//GetAvailableCommunities();

	$context = GetCurrentContext(null, $db);
	//secretar_of
	$rows = dbExecSelectorByName($db, "secretar_of", $context, GetLang());

	if (count($rows) > 0)
		{ EnableLoggedUnderMode(); }

	// create message folders fro the user
	if (MessageFolder::countUserFolders($id_user, $db) == 0)
		{ MessageFolder::createUserFolders($id_user, $db); }

	// Определяем рабочее место текущего пользователя
	$context = GetCurrentContext(null, $db);
	$ctid = dbGetCTId($db, 'persons');
	// Получаем person_id текущего пользователя
	$person_id = $db->getValue("select person_id from ct_users where id = $id_user", "person_id");
	$context['tag_context']['all'][$ctid] = array(array($person_id));
	$rows = dbExecSelectorByName($db, "all_employees", $context, GetLang());
	if (count($rows) > 0)
		{
		$eus_id = $rows[0]['id'];

		$rows = $db->execute("select a.id, b.id as is_ewu_id, b.ct_ewu_id from ct_is_eus a inner join ct_is_ewus b on a.ct_is_ewu_id = b.id where ct_eu_id = $eus_id");

		if (count($rows) > 0)
			{
			$is_eus_id = $rows[0]['id'];
			$is_ewu_id = $rows[0]['is_ewu_id'];
			$ewu_id    = $rows[0]['ct_ewu_id'];

			$_SESSION['eus_id']    = $eus_id;
			$_SESSION['is_eus_id'] = $is_eus_id;
			$_SESSION['is_ewu_id'] = $is_ewu_id;
			$_SESSION['ewu_id']    = $ewu_id;
			}

		$_SESSION['person_id'] = $person_id;
		}

	}

function GetUser()
	{
	return (isset($_SESSION['id_user_under']) && !empty($_SESSION['id_user_under']) ?
			$_SESSION['id_user_under'] :
			(isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null));
	}

function GetRealUser()
	{ return isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null; }

function GetWorkAsUser()
	{ return isset($_SESSION['id_user_under']) ? $_SESSION['id_user_under'] : null; }

function IsSuperApprover()
	{ return isset($_SESSION['is_super_approver']) && $_SESSION['is_super_approver']; }

function  IsStaff()
	{ return isset($_SESSION['is_staff']) && $_SESSION['is_staff']; }

function IsChief()
	{ return isset($_SESSION['is_chief']) && $_SESSION['is_chief']; }

function IsSuperChief()
	{ return isset($_SESSION['is_superchief']) && $_SESSION['is_superchief']; }

function IsArchivator()
	{ return isset($_SESSION['is_archivator']) && $_SESSION['is_archivator']; }

function IsPsychologist()
	{ return isset($_SESSION['is_psychologist']) && $_SESSION['is_psychologist']; }

function GetEus()
	{ return isset($_SESSION['eus_id']) ? $_SESSION['eus_id'] : null; }

function GetIsEus()
	{ return isset($_SESSION['is_eus_id']) ? $_SESSION['is_eus_id'] : null; }

function GetPersonId()
	{ return isset($_SESSION['person_id']) ? $_SESSION['person_id'] : null; }

function GetEwu()
	{ return isset($_SESSION['ewu_id']) ? $_SESSION['ewu_id'] : null; }

function GetIsEwu()
	{ return isset($_SESSION['is_ewu_id']) ? $_SESSION['is_ewu_id'] : null; }

function GetDefaultEnterprise()
	{ return isset($_SESSION['default_enterprise_id']) ? $_SESSION['default_enterprise_id'] : null; }

function SetUser($id_user)
	{
	LogoffUser();
	$_SESSION['id_user'] = $id_user;
	}

function SetWorkAsUser($id_user, $_db=null)
	{
	$_SESSION['id_user_under'] = $id_user;
	if(!empty($_db))
		$db = $_db;
	else
		$db = DB();
	$ct_user_id = dbGetCTId($db, 'users');
	$uid = GetRealUser();
	$context = Array('tag_context' => Array('all' => Array($ct_user_id => $uid)));
	$is_clerk = (0 != count(dbExecSelectorByName($db, 'clerk_access_check', $context, GetLang())));
	$context = Array('tag_context' => Array('all' => Array($ct_user_id => $uid)));
	$is_staff = (0 != count(dbExecSelectorByName($db, 'staff_access_check', $context, GetLang())));
	$context = Array('tag_context' => Array('all' => Array($ct_user_id => $uid)));
	$is_chief = (0 != count(dbExecSelectorByName($db, 'chief_access_check', $context, GetLang())));
	$context = Array('tag_context' => Array('all' => Array($ct_user_id => $uid)));
	$is_superchief = (0 != count(dbExecSelectorByName($db, 'superchief_access_check', $context, GetLang())));
	$context = Array('tag_context' => Array('all' => Array($ct_user_id => $uid)));
	$is_archivator = true; //(0 != count(dbExecSelectorByName($db, 'archivist_access_check', $context, GetLang())));
	$context = Array('tag_context' => Array('all' => Array($ct_user_id => $uid)));
	$is_psychologist = (0 != count(dbExecSelectorByName($db, 'psychologist_access_check', $context, GetLang())));
	$_SESSION['is_super_approver'] = $is_clerk;
	$_SESSION['is_staff']          = $is_staff;
	$_SESSION['is_chief']          = $is_chief;
	$_SESSION['is_superchief']     = $is_superchief;
	$_SESSION['is_archivator']     = $is_archivator;
	$_SESSION['is_psychologist']   = $is_psychologist;
	$sql = 'DELETE FROM ct_user_sessions WHERE php_session_id = \''.$db->escapeString(session_id()).'\'';
	$db->execute($sql);
	$sql = 'SELECT ct_persons.id FROM ct_users JOIN ct_persons ON ct_persons.id = ct_users.person_id WHERE ct_users.id = '.$id_user;
	$person_id = $db->getValue($sql);
	$sql = 'INSERT INTO ct_user_sessions (id, dt, counter, uid, ct_user_id, is_clerk, is_staff, is_chief, is_superchief, is_archivator, ct_person_id, php_session_id) '.
			'VALUES ("GetObjectID"(0), now(), 1, '.$uid.', '.$id_user.', \''.toBool($is_clerk).'\', \''.
			toBool($is_staff) . '\', \'' . toBool($is_chief) . '\', \'' . toBool($is_superchief) . '\', \'' .
			toBool($is_archivator).'\', '.(null === $person_id ? 'null' : $person_id).', \''.
			$db->escapeString(session_id()).'\')'.
			'RETURNING ct_user_sessions.id';
	$_SESSION['db_session_id'] = $db->getValue($sql);
	}

function LogoffUser()
	{
	unset($_SESSION['id_user']);
	unset($_SESSION['id_user_under']);
	unset($_SESSION['is_super_approver']);
	unset($_SESSION['is_staff']);
	unset($_SESSION['is_chief']);
	unset($_SESSION['is_superchief']);
	unset($_SESSION['is_archivator']);
	unset($_SESSION['is_psychologist']);
	unset($_SESSION['eus_id']);
	unset($_SESSION['is_eus_id']);
	unset($_SESSION['person_id']);
	unset($_SESSION['is_ewu_id']);
	unset($_SESSION['ewu_id']);

	UnsetDMode();
	/*  unset($_SESSION['_menu_fid']);
		 unset($_SESSION['_menu_sid']);
		 unset($_SESSION['_menu_tid']);		*/
	unset($_SESSION['MenuSelected2']);
	unset($_SESSION['under_mode']);
	unset($_SESSION['caches']);

	if (isset($_SESSION['autologin']))
		{ unset($_SESSION['autologin']); }

	if (isset($_SESSION['global_search']))
		{ unset($_SESSION['global_search']); }
	}

function GetCurrentTab()
	{
	if (isset($_REQUEST['cur_tab']) && !empty($_REQUEST['cur_tab']))
		{ return $_REQUEST['cur_tab']; }
	else if (isset($_SESSION['current_tab_id']) && !empty($_SESSION['current_tab_id']))
		{
		$tab_id = $_SESSION['current_tab_id'];
		$ar     = explode(':', $tab_id);
		$tab_id = $ar[1];
		return $tab_id;
		}
	return null;
	}

function GetCurrentContext($lang_id = null, $_db=null)
	{
	if (!isset($lang_id))
		{ $lang_id = GetLang(); }

	if(!empty($_db))
		$db = $_db;
	else
		$db = DB();

	$context = array('ct_language_id' => $lang_id);
	$context['tag_context'] = array();
	$context['field_tag_context'] = array();
	$ct_id = dbGetCTId($db, 'users');
	$context['tag_context']['work_as'][$ct_id] = GetUser();
	$context['tag_context']['logged_user'][$ct_id] = GetRealUser();
	$context['field_tag_context']['cur_lang']['ct_language_id'] = $lang_id;

	// Контекст на текущее предприятие
	if (GetDefaultEnterprise() != null)
		{ $context['field_tag_context']['cur_enterprise']['ct_enterprise_id'] = GetDefaultEnterprise(); }

	$tab_id = GetCurrentTab();
	if (!empty($tab_id))
		{
		$ct_tabs_id = dbGetCTId($db, 'tabs');
		$context['tag_context']['cur_tab'][$ct_tabs_id] = $tab_id;
		}
	return $context;
	}

function EnableLoggedUnderMode()
	{ $_SESSION['under_mode'] = 1; }

function IsEnabledLoggedUnderMode()
	{ return isset($_SESSION['under_mode']); }

// PAGE ROUTE

function PAGE_ROUTE($new_page)
	{
	global $page_name;
	$page_name = $new_page;
	}

function PAGE_ROUTE_NAME($page_name)
	{
	$uri = getPageURL($page_name);
	if ($uri == "/x")
		{ PAGE_ROUTE($page_name); }
	else
		{ PAGE_ROUTE_URI($uri); }
	}

function PAGE_ROUTE_URI($uri)
	{
	global $page_uri;
	$page_uri = $uri;
	PAGE_ROUTE('PageRoute');
	}
