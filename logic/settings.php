<?php

require_once './core/WixInstance.php';

$wix = new WixInstance();

$ad_sizes = array(
	'728x90' => array('css'=>'size1', 'size'=>'728x90'),
	'300x250' => array('css'=>'size2', 'size'=>'300x250'),
	'468x60' => array('css'=>'size3', 'size'=>'468x60'),
	'120x600' => array('css'=>'size4', 'size'=>'120x600'),
	'160x600' => array('css'=>'size5', 'size'=>'160x600'),
	'Pop-up' => array('size'=>'pop-up')
);

$sd_categories = array(
	'Entertaiment', 'News', 'Music', 'Fashion', 'Other'
);



