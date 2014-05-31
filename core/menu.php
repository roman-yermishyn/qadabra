<?php

require_once('./logic/dbUser.php');
require_once('./logic/dbMenu.php');
//require_once('./logic/dbService.php');
require_once('./logic/dbOperation.php');
require_once('./logic/dbPerms.php');

	//
	
	/*$cmds = array();
	foreach ($page_uri_map as $key=>$item) {
		$n = isset($item[1])? $item[1]: $item[0];
		$cmds[$n] = array($key);
	}*/	

	//
		
	/*$patterns = array();
    foreach ($cmds as $key=>$value)
	{
		if($key == 'tasks/([0-9]*)')continue;
		if($key == 'task/([0-9]*)')continue;
		if(!isset($patterns[$value[0]]))
//		if(!array_key_exists($value[0], $patterns))
		{
			$patterns[$value[0]] = array($key, $value[0]);
		}
	} */  
	
	function getMenuMapUrl2Cmnd()	
	{
		global $cmds;
		return $cmds;
	}
	
	function getMenuMapCmnd2Url()
	{
		global $patterns;
		return $patterns;
	}

	//
require_once("./conf/menu.php");

function GetPageByMenu($menu)
{
    global $menu_page_map;
	if(isset($menu_page_map[$menu]))
	    return $menu_page_map[$menu];
	else
		return null;
}

function BindMenus()
{
    global $menu_page_map;
    foreach ($menu_page_map as $menu=>$page)
      BindMenu($menu, $page);
}
	//
	
  function BindMenu($menu_name, $temp_name)
  {
    global $menu;
	global $page2menu;
	global $menu2page;
    if ($menu != null)    
	$page2menu[$temp_name] = $menu_name;
	$menu2page[$menu_name] = $temp_name;
  }

  function GetMenu($id_user = null, $id_menu_selected = null)
  {
	return null; // not actual
  }
  
class CUserMenu
{ 
	var $model;
	
	function getModel()
	{		
		return $this->model;
	}

	function load($id_user, $id_menu_selected)
	{
		$this->model = array();
		foreach (getUserMenus() as $menu) 
		{
			$Menu2PageURL = Menu2PageURL($menu['name']);
			
			if($menu['id_parent_menu']==0 && empty($menu['name']))
			{
				$Menu2PageURL = "/gettabinfo?tab={$menu['id']}"."&tk=".SecureTokenKernel::getToken();
			}
			
			if(!isset($menu['icon'])) $menu['icon']="";
			
			$this->model[] = array($menu['id'], $menu['id_parent_menu']==0? null: $menu['id_parent_menu'], $menu['text'], $Menu2PageURL, null, null, null, isset($menu['ico_class'])?$menu['ico_class']:null, $menu['icon']);
		}
	}  
}  

function Menu2PageURL($menu_name)
{
	$page_name = GetPageByMenu($menu_name);
	return getPageURL($page_name);
}

class Menu
	{
	static function getUserMenu()
		{
		$menu = new CUserMenu;
		$id_user = GetUser();
		if (isset($id_user))
			{
			if(!isset($id_menu_selected)) // TODO $id_menu_selected всегда null !!!
				{ $id_menu_selected = null; }
			$menu->load($id_user, $id_menu_selected);
			}
		return $menu;
		}
	}

function GetFirstMenu()
{
	$um = Menu::getUserMenu();
	return $um->model[0][3]? $um->model[0][3]: ORG_START_URI;
}
?>