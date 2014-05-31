<?php
define('JS_NONE', 0);
define('JS_INCLUDE', 1);
define('JS_INSERT', 2);
define('JS_DONE', 4);
class JS
	{
	const GENERATE = 8;

	const js_JQUERY        = 'js/jquery/development-bundle/jquery-1.9.0.min.js';
	const js_UI_CORE       = 'js/jquery/development-bundle/ui/jquery.ui.core.js';
	const js_UI_BUTTON     = 'js/jquery/development-bundle/ui/jquery.ui.button.js';
	const js_UI_DIALOG     = 'js/jquery/development-bundle/ui/jquery.ui.dialog.js';
	const js_UI_DRAGGABLE  = 'js/jquery/development-bundle/ui/jquery.ui.draggable.js';
	const js_UI_DROPPABLE  = 'js/jquery/development-bundle/ui/jquery.ui.droppable.js';
	const js_UI_MOUSE      = 'js/jquery/development-bundle/ui/jquery.ui.mouse.js';
	const js_UI_POSITION   = 'js/jquery/development-bundle/ui/jquery.ui.position.js';
	const js_UI_RESIZEABLE = 'js/jquery/development-bundle/ui/jquery.ui.resizable.js';
	const js_UI_WIDGET     = 'js/jquery/development-bundle/ui/jquery.ui.widget.js';
	const js_UI_TABS       = 'js/jquery/development-bundle/ui/jquery.ui.tabs.js';
	const js_UI_DATE       = 'js/jquery/development-bundle/ui/jquery.ui.datepicker.js';
	const js_UI_DATE_RU    = 'js/jquery/development-bundle/ui/i18n/jquery.ui.datepicker-'; // добавляется суффикс 'ru','ua',.. для языка и .js
	const js_JQUERY_COOKIE = 'js/jquery/development-bundle/external/jquery.cookie.js';


	const js_UI_EFFECT_CORE      = 'js/jquery/development-bundle/ui/jquery.effects.core.js';
	const js_UI_EFFECT_BLIND     = 'js/jquery/development-bundle/ui/jquery.effects.blind.js';
	const js_UI_EFFECT_BOUNCE    = 'js/jquery/development-bundle/ui/jquery.effects.bounce.js';
	const js_UI_EFFECT_CLIP      = 'js/jquery/development-bundle/ui/jquery.effects.clip.js';
	const js_UI_EFFECT_DROP      = 'js/jquery/development-bundle/ui/jquery.effects.drop.js';
	const js_UI_EFFECT_EXPLODE   = 'js/jquery/development-bundle/ui/jquery.effects.explode.js';
	const js_UI_EFFECT_FADE      = 'js/jquery/development-bundle/ui/jquery.effects.fade.js';
	const js_UI_EFFECT_FOLD      = 'js/jquery/development-bundle/ui/jquery.effects.fold.js';
	const js_UI_EFFECT_HIGHLIGHT = 'js/jquery/development-bundle/ui/jquery.effects.highlight.js';
	const js_UI_EFFECT_PULSATE   = 'js/jquery/development-bundle/ui/jquery.effects.pulsate.js';
	const js_UI_EFFECT_SCALE     = 'js/jquery/development-bundle/ui/jquery.effects.scale.js';
	const js_UI_EFFECT_SHAKE     = 'js/jquery/development-bundle/ui/jquery.effects.shake.js';
	const js_UI_EFFECT_SLIDE     = 'js/jquery/development-bundle/ui/jquery.effects.slide.js';
	const js_UI_EFFECT_TRANSFER  = 'js/jquery/development-bundle/ui/jquery.effects.transfer.js';


	const js_JQUERY_PLUGIN_JSON         = 'js/jquery/plugin/jquery.json-2.2.min.js';
	const js_JQUERY_PLUGIN_GUID         = 'js/jquery/plugin/jquery.guid.js';

	const js_QADABRA         = 'js/qadabra.js';

	const js_WIX_UI_LIB		 = 'js/wix-ui-lib/ui-lib.min.js';



	public static function JS_JQUERY()
		{
		return Array(
			JS_INCLUDE, JS::js_JQUERY
		);
		}


	public static function JS_UI_EFFECT()
		{
		return Array(
			JS_NONE, JS::JS_JQUERY(),
			JS_INCLUDE, JS::js_UI_EFFECT_CORE,
			JS_INCLUDE, JS::js_UI_EFFECT_BLIND,
			JS_INCLUDE, JS::js_UI_EFFECT_BOUNCE,
			JS_INCLUDE, JS::js_UI_EFFECT_CLIP,
			JS_INCLUDE, JS::js_UI_EFFECT_DROP,
			JS_INCLUDE, JS::js_UI_EFFECT_EXPLODE,
			JS_INCLUDE, JS::js_UI_EFFECT_FADE,
			JS_INCLUDE, JS::js_UI_EFFECT_FOLD,
			JS_INCLUDE, JS::js_UI_EFFECT_HIGHLIGHT,
			JS_INCLUDE, JS::js_UI_EFFECT_PULSATE,
			JS_INCLUDE, JS::js_UI_EFFECT_SCALE,
			JS_INCLUDE, JS::js_UI_EFFECT_SHAKE,
			JS_INCLUDE, JS::js_UI_EFFECT_SLIDE,
			JS_INCLUDE, JS::js_UI_EFFECT_TRANSFER);
		}

	public static function JS_JQUERY_COOKIE()
		{
		return Array(
			JS_NONE, JS::JS_JQUERY(),
			JS_INCLUDE, JS::js_JQUERY_COOKIE);
		}

	public static function JS_JQUERY_PLUGIN_JSON()
		{
		return Array(
			JS_NONE, JS::JS_JQUERY(),
			JS_INCLUDE, JS::js_JQUERY_PLUGIN_JSON);
		}

	public static function JS_UI_DIALOG()
		{
		return Array(
			JS_NONE, JS::JS_JQUERY(),
			JS_INCLUDE, JS::js_UI_CORE,
			JS_INCLUDE, JS::js_UI_WIDGET,
			JS_INCLUDE, JS::js_UI_BUTTON,
			JS_INCLUDE, JS::js_UI_MOUSE,
			JS_INCLUDE, JS::js_UI_DRAGGABLE,
			JS_INCLUDE, JS::js_UI_POSITION,
			JS_INCLUDE, JS::js_UI_RESIZEABLE,
			JS_INCLUDE, JS::js_UI_DROPPABLE,
			JS_INCLUDE, JS::js_UI_DIALOG);
		}

	public static function JS_UI_TABS()
		{
		return Array(
			JS_NONE, JS::JS_JQUERY(),
			JS_INCLUDE, JS::js_UI_CORE,
			JS_INCLUDE, JS::js_UI_WIDGET,
			JS_INCLUDE, JS::js_UI_TABS);
		}

	public static function JS_QADABRA()
	{
		return Array(
			JS_NONE, JS::JS_JQUERY(),
			JS_INCLUDE, JS::js_QADABRA);
	}

	public static function JS_WIX_UI_LIB()
	{
		return Array(
			JS_NONE, JS::JS_JQUERY(),
			JS_INCLUDE, JS::js_WIX_UI_LIB);
	}

	protected $sj_hash;
	protected $ScriptsList;
	protected $onRender;
	protected $isOutList;

	function __construct()
		{
		$this->ScriptsList = Array();
		$this->isOutList = false;
		$this->sj_hash = Array();
		}

	function Preloaded(Array $isLoaded)
		{
		foreach ($isLoaded as $path_file)
			{ $this->sj_hash[$path_file] = JS_DONE; }
		}

	function &add($items, $default=JS_NONE)
		{
		for ($i = 0; $i < count($items); $i +=2)
			{
			if (is_array($items[$i+1]))
				{ $this->add($items[$i+1], $items[$i]); }
			elseif (isset($this->sj_hash[$items[$i+1]]))
				{ $this->sj_hash[$items[$i+1]] |= (JS_NONE === $items[$i] ? $default : $items[$i]); }
			else
				{ $this->sj_hash[$items[$i+1]] = (JS_NONE === $items[$i] ? $default : $items[$i]); }
			}
		if (isset($this->onRender))
			{ $this->Render($this->onRender); }
		return $this;
		}

	static private function GenerateScriptTagFromFiles(Array &$FileList)
		{
		if (count($FileList))
			{
			foreach(array_chunk($FileList, 2) as $Item)
				{
				$NewFileName = md5(implode(CR, $Item)).'.js';
				$NewFilePath = UTil::prepare_DIR(JS_GEN_DIR).$NewFileName;
				if (!file_exists($NewFilePath))
					{
					if (!copy($Item[0], $NewFilePath))
						{ Log::Out('Error copy from "'.$Item[0].'" to "'.$NewFilePath.'"'); }
					}
				echo '<script type="text/javascript" src="'.JS_GEN_URL.$NewFileName.'"></script>'.'<!--' . $Item[0] .  '-->'.CR;
				}
			}
		$FileList = Array();
		}

	static private function GenerateScriptTagFromText(&$Text)
		{
		if ($Text)
			{ echo '<script type="text/javascript">'.$Text.'</script>'.CR; }
		$Text = '';
		}

	function &Render($default, $isOutList = null)
		{
		if (null !== $isOutList)
			{ $this->isOutList = $isOutList; }
		$Text = '';
		$FileList = Array();
		$this->onRender = $default;
		foreach ($this->sj_hash as $path_file => &$type)
			{
			switch ($type == JS_NONE ? $default : $type)
				{
				case JS_INCLUDE:
					JS::GenerateScriptTagFromText($Text);
					$PathFile = Util::prepare_PATH($path_file);
					$FileList[] = $PathFile;
					$FileList[] = filemtime($PathFile);
					break;
				case JS_INSERT:
					JS::GenerateScriptTagFromFiles($FileList);
					$Text .= CR.file_get_contents(Util::prepare_PATH($path_file)).CR;
					break;
				case JS::GENERATE:
					JS::GenerateScriptTagFromFiles($FileList);
					$key = 'Gen_'.$path_file;
					if (!method_exists($this, $key))
						{ throw new Exception(Messager::Exec(dbOpen(), GetLang(), 'JS_RENDER_NOT_FOUND_GEN_METHOD', Array('path_file' => $path_file))); }
					ob_start();
					try
						{ $this->$key(); }
					catch (Exception $e)
						{ Log::Out(Array('JS::Render(...) get exception', '$e' => $e->getMessage(), '$e->getTrace()' => $e->getTrace())); }
					$Text .= ob_get_contents();
					ob_end_clean();
					break;
				default:
					continue;
				}
			$this->ScriptsList[] = $path_file;
			$type = JS_DONE;
			}
		JS::GenerateScriptTagFromFiles($FileList);
		if ($this->isOutList && count($this->ScriptsList))
			{
			$Text .= 'if ("undefined" === typeof JsScriptsList) { JsScriptsList = []; }'.CR;
			$Text .= 'JsScriptsList.push('.CR.TAB.'"'.implode('",'.CR.TAB.'"', $this->ScriptsList).'");';
			$this->ScriptsList = Array();
			}
		JS::GenerateScriptTagFromText($Text);
		return $this;
		}

	public function find($key)
		{ return isset($this->sj_hash[$key]); }


	}

$JS = new JS();
