<?php

require_once './core/db.php';

class WixInstance {

	const PERMISSIONS_OWNER = 'OWNER';

	var $json = null;
	var $tag = null;
	var $db = null;
	var $_compId = null;

	function __construct() {
		$this->json = self::isWixRequest();
		//error_log($this->json);
		$this->initInstanceFromDB();
		$this->db = DB();
		$this->_compId = $_REQUEST['compId'];
		if(!isset($_REQUEST['compId'])) {
			throw new Exception("parameter compId is mandatory!");
		}
	}

	public function getJSON() {
		return $this->json;
	}

	public function getInstanceId() {
		return $this->json['instanceId'];
	}

	public function getComponentId() {
		return $this->_compId;
	}

	public function getSignDate() {
		return $this->json['signDate'];
	}

	public function getUid() {
		return $this->json['uid'];
	}

	public function getPermissions() {
		return $this->json['permissions'];
	}

	public function initInstanceFromDB() {

	}

	public function setParams($name, $url, $size, $category) {
		if ($this->getPermissions() == self::PERMISSIONS_OWNER) {

		} else {
			throw new Exception('Access denied');
		}
	}

	public static function &isWixRequest() {

		list( $code, $data ) = explode( '.', $_REQUEST[ 'instance' ] );

		if ( base64_decode( strtr( $code, "-_", "+/" ) ) != hash_hmac( "sha256", $data, WIX_APP_SECRET, TRUE ) ) {
			throw new Exception('Wrong instance data');
		}

		if ( ( $json = json_decode( base64_decode( $data ), true ) ) === null ) {
			throw new Exception('Can\'t load instance json');
		}
		return $json;
	}

	public function getScriptTag() {
		$path = QADABRA_TAGS . '/' . md5($this->getComponentId());
		if (file_exists($path)) {
			$res = file_get_contents($path);
		} else {
			$res =  "Empty Ad";
		}

		return $res;
	}

} 