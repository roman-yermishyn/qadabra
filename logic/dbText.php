<?php
    require_once('./core/db.php');

class Text
{
	function getById($id_text, $id_lang = null)
	{
		return dbGetText(DB(), $id_text, -1);
	}

	function getByName($text_name, $id_lang = null)
	{
		if ($id_lang == null)
			$id_lang = GetLang();
		return Cache::get('textbyname', $text_name.$id_lang, "dbGetTextByName(DB(), $text_name)");
	}
 
	function add($text, $id_lang = null)
    {
		if ($id_lang == null)
			$id_lang = GetLang();
		$tid = dbAddText(DB(), $text, $id_lang);
		return fetchValue(dbSetText(DB(), $tid, $text, $id_lang), 'set_text');
    }
	
	function getDictionary($table_name, $tid_col_name = 'id_name', $id_col_name = 'id')
	{
		$rs = DB()->execute("select t.$id_col_name, tr.text from $table_name as t left join text_res as tr on tr.id_text = t.$tid_col_name and tr.id_lang = ".GetLang());
		return fetchHash($rs, $id_col_name, 'text');
	}
}

    // TEXT

    function dbSetText(Database &$db, $id_text_name, $text, $id_lang)
    {
       return $db->executeProcedure('SET_TEXT', array($id_text_name, $text, $id_lang, GetUser()));
    }

    function dbAddText(Database &$db, $text, $id_lang=-1)
    {
        if(-1 == $id_lang) $id_lang=GetLang();
        $id_text = $db->executeFunction('ADD_TEXT', array('', GetUser()));
        dbSetText($db, $id_text, $text, $id_lang);    
        return $id_text;
    }

    function dbGetText(Database &$db, $id_text, $id_lang=-1)
    {
       if(-1 == $id_lang) $id_lang=GetLang();
       //$row = $db->executeProcedure('GET_TEXT', array($id_text, $id_lang));
       //return $row[0]['text'];
       return DB()->executeFunction('GET_TEXT', array($id_text, $id_lang));
    }

    function dbGetTextName(Database &$db, $id_text)
    {
        $row = $db->executeProcedure('GET_TEXT_NAME', array($id_text));
		return $row[0]['name'];
    }

    function dbGetTextByName(Database &$db, $name)
    {
        $row = $db->executeProcedure('GET_TEXT_BYNAME', array($name, GetLang()));
        return $row[0]['text'];
    }

    // TEXT NAMES IN GROUP
    
    function dbFindTextNameId(Database &$db, $text_name, $id_group)
    {
        return $db->executeFunction('FIND_TEXT_NAME_ID', array($text_name, $id_group));
    }

    function dbAddTextName(Database &$db, $text_name, $id_group)
    {
        return $db->executeFunction('ADD_TEXT_NAME', array($text_name, $id_group, GetUser()));
    }

    function dbSetTextByName(Database &$db, $name, $text, $id_group, $id_lang)
    {
        $id_text_name = dbFindTextNameId($db, $name, $id_group);
        if ($id_text_name == null)
           $id_text_name = dbAddTextName($db, $name, $id_group);
        dbSetText($db, $id_text_name, $text, $id_lang);
    }

    // TEXT GROUP
    
    function dbFindTextGroupId(Database &$db, $group_name)
    {
        return $db->executeFunction('FIND_TEXT_GROUP_ID', array($group_name));
    }

    function dbAddTextGroup(Database &$db, $group_name)
    {
        return $db->executeFunction('ADD_TEXT_GROUP', array($group_name, GetUser()));
    }

    function dbGetTextGroup(Database &$db, $id_group)
    {
        $rs = $db->executeProcedure('GET_TEXT_GROUP', array($id_group, GetLang(), null));
        $data = array();
        for ($i = 0; $i < count($rs); $i++) {
          $data[$rs[$i]["name"]] = $rs[$i]["text"];
        }
		return $data;
    }

    function dbSetTextGroup(Database &$db, $texts, $id_group, $id_lang)
    {
        while(list($name, $text) = each($texts))
           dbSetTextByName($db, $name, $text, $id_group, $id_lang, GetUser());
    }

    function dbSetTextGroupByName(Database &$db, $texts, $group_name, $id_lang)
    {
       $id_group = dbFindTextGroupId($db, $group_name);
       if ($id_group == null)
         $id_group = dbAddTextGroup($db, $group_name);
       dbSetTextGroup($db, $texts, $id_group, $id_lang);
    }

    function dbGetTextGroupByName2(Database &$db, $group_name)
    {
	   $id_group = dbFindTextGroupId($db, $group_name);
       return dbGetTextGroup($db, $id_group);
    }

    //
	
	function &dbGetTextGroupByName(Database &$db, $group_name, $LangId = null)
		{
		//global $TEXTS;
		static $groups = Array();
		if (null === $LangId)
			{ $LangId = GetLang(); }
		$key = $group_name.'.'.$LangId;
		if (!array_key_exists($key, $groups))
			{
			$TEXTS = Array();
			if (defined('TEXTS_FROM_FILE'))
				{
				$fname = './text/'.$key.'.php'; 
				if (file_exists($fname))
					{
					include($fname);
					}
				}
			else
				{ $TEXTS = LoadTextsFromDB($db, $group_name, $LangId); }
			$groups[$key] = $TEXTS;
			}
		return $groups[$key];
		}

    function getTextGroupByName($group_name, $id_lang)
    {
		return fetchHash(DB()->executeProcedure('GET_TEXT_GROUP_BYNAME', array('$group_name', $id_lang, null)), 'name', 'text');
	}
    function LoadTextsFromDB(Database &$db, $group_name, $id_lang)
    {
/*		$rs = Cache::get('text_group4', 'all', "fetchHash(DB()->executeProcedure('GET_TEXT_GROUP_BYNAME', array('$group_name', $id_lang, null)), 'name', 'text')");			
return $rs;
*/
		//$rs = $db->executeProcedure('GET_TEXT_GROUP_BYNAME', array($group_name, $id_lang, null));
		//return fetchHash($rs, 'name', 'text');

//		return getCache('text_group2', $group_name.$id_lang,
	//		"fetchHash(DB()->executeProcedure('GET_TEXT_GROUP_BYNAME', array('$group_name', $id_lang, null)), 'name', 'text')");

		return Cache::get('text_grp', $group_name.$id_lang,
			"fetchHash(DB()->executeProcedure('GET_TEXT_GROUP_BYNAME', array('$group_name', $id_lang, null)), 'name', 'text')");
    }
  
    // TEXT DICTIONARY

   function dbSetDictionary(Database &$db, $table, $data, $id_lang)
   {
	  $fl = $db->dbGetMetaTable($table);

	  $lock=false;
	  foreach( $fl as $f )  {
		  if($f['Field']=='change_lock')
			  $lock=true;
		  }
      
	   while (list($id, $name) = each($data))
       {
          // chack if entry is present
          $rs = $db->execute("SELECT * FROM $table WHERE id = $id");
          
          if (count($rs) > 0)
          {
              // update existing entry
              $id_name = $rs[0]['id_name'];
              $name_db = dbGetText($db, $id_name, $id_lang);
              if ($name_db != $name)      
                 dbSetText($db, $id_name, $name, $id_lang, GetUser());
          }
          else
          {
              // create new entry
              $id_name = dbAddText($db, $name, $id_lang);
              $db->execute("INSERT INTO $table (id,id_name) VALUES ($id,$id_name)");
          }
		  if($lock)
	          $db->execute("UPDATE $table set change_lock=1 WHERE id = $id");
       }
   }
  
   function dbSetDictionary2(Database &$db, $table, $data, $id_lang)
   {
       while (list($id, $names) = each($data))
       {
          // chack if entry is present
          $rs = $db->execute("SELECT * FROM $table WHERE id = $id");
          
          if (count($rs) > 0)
          {
              // update existing entry
              $id_name = $rs[0]['id_name'];
              $id_description = $rs[0]['id_description'];
              $name_db = dbGetText($db, $id_name, $id_lang);
              if ($name_db != $names[0])      
                 dbSetText($db, $id_name, $names[0], $id_lang, GetUser());
              $description_db = dbGetText($db, $id_description, $id_lang);
              if ($description_db != $names[1])      
                 dbSetText($db, $id_description, $names[1], $id_lang, GetUser());
          }
          else
          {
              // create new entry
              $id_name = dbAddText($db, $names[0], $id_lang);
              $id_description = dbAddText($db, $names[1], $id_lang);
              $db->execute("INSERT INTO $table (id,id_name,id_description) VALUES ($id,$id_name,$id_description)");
          }
       }
   }

	function getDictionaryTable($table_name)
	{
		$db = DB();
		$res = array();
		$langs_with_dirs=false;
		if($table_name == 'langs_with_dirs') {
			$langs_with_dirs=true;
			$table_name = 'langs';
		}
		$rs = $db->execute("select t.id, text from $table_name as t left join text_res as tr on tr.id_text = id_name and tr.id_lang = ".GetLang());
		if($langs_with_dirs) {
			$langs = fetchHash($rs, 'id', 'text');
			foreach($langs as $lang_id=>$name) {
				$res[$lang_id] = array('name'=>$name, 'direction'=>'');
			}
			try {
				$rs = $db->execute("select t.id, b.html_dir as text
									from ct_languages t
										inner join ct_language_directions b on t.ct_language_direction_id = b.id
									");
				$dirs = fetchHash($rs, 'id', 'text');
				foreach($dirs as $lang_id=>$dir) {
					if (isset($res[$lang_id])) {
						$res[$lang_id]['direction'] = $dir;
					}
				}
			}
			catch (Exception $e){}
		} else {
			$res = fetchHash($rs, 'id', 'text');
		}
		return $res;
	}
	
	function dbGetDictionary(Database &$db, $table)
	{
/*      $rs = $db->execute("SELECT * FROM $table");
      foreach ($rs as &$record)
          $record['id_name'] = dbGetText($db, $record['id_name']);
      return fetchHash($rs, 'id', 'id_name');
*/
		//$rs = $db->execute("select t.id, text from $table as t left join text_res as tr on tr.id_text = id_name and tr.id_lang = ".GetLang());
		//return fetchHash($rs, 'id', 'text');

		return Cache::get('text_dict', $table, "getDictionaryTable('$table')");
	}

   function dbGetDictionary2(Database &$db, $table)
   {
	   // ���������� �������� �����������, � ������������ ���� �������� � �� ��������
      $rs = $db->execute("SELECT * FROM $table");
      foreach ($rs as &$record)
	  {
          $record['id_name'] = dbGetText($db, $record['id_name']);
          $record['id_description'] = dbGetText($db, $record['id_description']);
	  }
      return $rs;
   }

   function dbSetDictionaryValue(Database &$db, $table, $id, $name, $id_lang)
   {
	  // check if entry is present
	  $rs = $db->execute("SELECT * FROM $table WHERE id = $id");
	  if (count($rs) > 0)
	  {
		  // update existing entry
		  $id_name = $rs[0]['id_name'];
  		  dbSetText($db, $id_name, $name, $id_lang, GetUser());
	  }
	  else
	  {
		  // create new entry
		  $id_name = dbAddText($db, $name, $id_lang);
		  if($id==null)
			  $db->execute("INSERT INTO $table (id_name) VALUES ($id_name)");
		  else
			  $db->execute("INSERT INTO $table (id,id_name) VALUES ($id,$id_name)");
	  }
   }

	function dbDeleteDictionaryValue(Database &$db, $table, $id)
	{
		 // �������� �� ������� ���� change_lock
		  $fl = $db->dbGetMetaTable($table);

		// ��� ���������� ������� �������� ������������� �� �����		
		  $check_lock="";
		  foreach( $fl as $f )  {
			  if($f['Field']=='change_lock')
				  $check_lock=" and change_lock!=1";
			  }

		// ������� �������� �����������
		$db->execute("DELETE FROM $table where id=$id".$check_lock);
	}
?>