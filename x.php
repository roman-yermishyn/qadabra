<?php

	//Error_Reporting(E_ALL & ~E_NOTICE  & ~E_DEPRECATED);ini_set('display_errors',1);
	require_once('./core/base_util.php');
	require_once('./conf/define.php');
	require_once('./core/session.php');	
	require_once './core/EDoc/Log.php';
	require_once './core/db.php';
	require_once './core/common.php';
	require_once './core/EDoc/JS.php';


    global $page_name;

	session_start();

	/*if(defined('DEBUG') && DEBUG==1)
	{
		error_reporting(E_ALL);

	}
	else
	{
		error_reporting($erl);
	}*/

	extract($_REQUEST);

	$uri = $_SERVER["REQUEST_URI"];

	/// under modification
	if (defined('SITE_IS_BEING_MODIFIED'))
		{echo 'Site is being modified. Please have patience'; die;}


	$page_name = "";
	if ($uri == "/" || $uri == "")
	{    $page_name = 'Home'; }

	// parse request and uri, decide on page name
	$URI = new URI($uri);

	if($page_name == "")
	    $page_name = $URI->getPage();

	if ($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		if (isset($page))
			$page_name = $page;
		else if (isset($id))
				$page_name = ParseID ($id, $page_num);
	}
	else if (preg_match('/x\.php/', $uri) || preg_match('/^\/x/', $uri))
	{
		if (isset($page))
			$page_name = $page;
		else
			$page_name = isset($id) ? ParseID ($id, $page_num): HOME_PAGE;
	}

	$dir = ".";
	do
	{
		$page_name_prev = $page_name;
		if (file_exists ($dir."/logic/$page_name.php"))
			include $dir."/logic/$page_name.php";
	}
	while ($page_name != $page_name_prev);
	// generate presentation logics
	if (file_exists ($dir."/templates/$page_name.php"))
	{
		include $dir."/templates/$page_name.php";
	}

