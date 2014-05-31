<?php

$actions = array(
	'login' => 'http://qadabraapi-env.elasticbeanstalk.com/LoginController',
	'register' => 'http://qadabraapi-env.elasticbeanstalk.com/RegisterController',
	'create_ad' => 'http://qadabraapi-env.elasticbeanstalk.com/PlacementController'
);


if(!isset($action) || !in_array($action, array_keys($actions)) || !isset($data)) {
	echo json_encode(array('response'=>'error'));
	die;
}

if ($action == 'create_ad') {
	$id = time();
	$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
	$id .= $randomString;
	$data_obj = json_decode($data, 1);

	$name = "Wix_{$data_obj['size']}_{$id}";

	$data_obj['name'] = $name;
	$data = json_encode($data_obj);
}
//echo $data; die;
$res = Util::sendPOST($actions[$action], $data, '', 1);

switch ($action) {
	case 'login':
		//print_r($res); die;
		$cookies = $res['cookies'];
		//echo $cookies; die;
		break;
	case 'register':
		break;
	case 'create_ad':
		$res_obj = json_decode($res['body'], 1);
		if (isset($res_obj['response']) && !empty($res_obj['response'])) {
			$script_tag = htmlspecialchars_decode($res_obj['response']);

			$path = QADABRA_TAGS . '/' . md5($comp_id);
			file_put_contents($path, $script_tag);

			break;
		}

}

echo $res['body'];

