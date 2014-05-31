<!DOCTYPE HTML>
<html>
<head>
    <title>Settings Endpoint</title>
	<link rel="stylesheet" href="/js/wix-ui-lib/ui-lib.min.css"/>
	<link rel="stylesheet" href="/css/login.css"/>
	<link rel="stylesheet" href="/css/ad_size.css"/>
    <script type="text/javascript" src="//sslstatic.wix.com/services/js-sdk/1.24.0/js/Wix.js"></script>
	<?php
	$JS->add(JS::JS_JQUERY_PLUGIN_JSON())
		->add(JS::JS_JQUERY_COOKIE())
		->add(JS::JS_QADABRA())
		->add(JS::JS_WIX_UI_LIB())
		->Render(JS_INCLUDE);
	?>
	<script type="text/javascript">
		var qdbr_api = new QadabraAPI()
		function login() {
			qdbr_api.login($('#login').val(), $('#pwd').val(), function(res){
				if (res=='ok') {
					$('#login_panel').hide(); //css({'display':'none'});
					$('#golive_panel').show(); //css({'display':'inherit'});
				} else {
					alert("Can't login");
				}
			})
		}

		function createAd() {
			Wix.getSiteInfo( function(siteInfo) {
				var siteUrl = siteInfo['url'];
				qdbr_api.create_ad(siteUrl, Wix.UI.get('ad_size').value, Wix.UI.get('ad_category').value, function(res){
					//console.log([Wix.Utils.getOrigCompId()]);
					Wix.Settings.refreshAppByCompIds([Wix.Utils.getOrigCompId()]);
				})
			});
		}

		function golive() {
			//var modal = Wix.UI.create({ctrl: 'Popup',
			//	options: {modal:true, buttonSet: 'okCancel', fixed:true}});
			//modal.getCtrl().open();
			createAd();
		}

		function show_login() {
			$('div.loggedOut').addClass('hidden');
			$('div.loggedIn').removeClass('hidden');
		}

		function hide_login() {
			$('div.loggedOut').addClass('hidden');
			$('div.loggedIn').removeClass('hidden');
		}

		$(document).ready(function () {

			Wix.UI.initialize({
				ad_category: 0,
				ad_size: 0
			});

			/**
			 Validate email adress
			 */
//			$('#email').getCtrl().setValidationFunction(function (value) {
//				return (/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(value));
//			});
		});

	</script>
	<style>
		a {color: #6bb445}
		a:hover {color: #6bb445}
	</style>
</head>
<body>


<header class="box">
	<div class="logo">
		<img width="86" src="/pic/qadabra_logo.png" alt="logo"/>
	</div>
	<div class="loggedOut">
		<p>
			Create result, update your settings and payment information in Qadabra Platform
		</p>

		<div class="login-panel">
			<p class="create-account"><strong><a href="javascript:void(0)" onclick="show_login()" target="_blank">Log in</a></strong></p>
			<button onclick="golive()" class="submit uilib-btn connect btn-green">Go live</button>
		</div>
	</div>
	<div class="loggedIn hidden">
		<p>You are now connected to <strong>John Doe (john@doe.com)</strong> account<br/>
			<a class="disconnect-account">Disconnect account</a></p>

		<div class="premium-panel">
			<p class="premium-features ">Premium features</p>
			<button class="submit uilib-btn upgrade btn-green">Upgrade</button>
		</div>
	</div>
</header>



<ul id="login_panel" style="display: none">
	<li>
		Login <input id="login" type="text">
	</li>
	<li>
		Password <input id="pwd" type="password">
	</li>
	<li>
		<button onclick="login()">Login</button>
	</li>
</ul>


<p style="display: none" id="golive_panel">
	<button onclick="golive()">Go live</button>
</p>


<div class="accordion" wix-ctrl="Accordion">
	<div wix-scroll="{height:446}">
		<div class="acc-pane">
			<h3>Choose you add size: </h3>
			<div class="acc-content">
				<div wix-model="ad_size" wix-ctrl="Radio">

					<?php $i=0; ?>
					<?php foreach($ad_sizes as $label=>$value) { ?>
						<?php $i++; ?>
						<?php if (($i%3)==1) { ?>
							<div class="table">
						<?php } ?>
						<div data-radio-value="<?php echo $value['size'];?>" class="table-cell"><?php echo $label;?>
							<?php if (isset($value['css'])) { ?>
								<div class="<?php echo $value['css'];?> form-size">
									<div class="b-box"></div>
								</div>
							<?php } ?>
						</div>
						<?php if (($i%3)==0) { ?>
							</div>
						<?php } ?>
					<?php } ?>

				</div>
			</div>
		</div>
		<div class="acc-pane">
			<h3>Categorize your site: </h3>
			<div class="acc-content">
				<div wix-model="ad_category" wix-ctrl="Radio">
					<?php foreach($sd_categories as $category) { ?>
						<div data-radio-value="<?php echo $category;?>"><?php echo $category;?></div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

</body>
</html>