
/*

 Wix.getSiteInfo( function(siteInfo) {
 // do something with the siteInfo
 });

* */

function QadabraAPI() {
	this.compId = Wix.Utils.getOrigCompId();
	this.instanceId = Wix.Utils.getInstanceId();
}

(function ($)
{
	QadabraAPI.prototype.m = {};

	QadabraAPI.prototype.login = function (username, password, cb) {
		$.post('/qadabra_api',
			{
				action: 'login',
				comp_id: this.compId,
				data: $.toJSON({username:username, password:password})
			},
			function(data) {
				var res = $.parseJSON(data)
				cb(res['response'])
			}
		)
	}

	QadabraAPI.prototype.register = function (fullname, username, password, cb) {
		$.post('/qadabra_api',
			{
				action: 'register',
				comp_id: this.compId,
				data: $.toJSON({username:username, password:password, fullname:fullname})
			},
			function(data) {
				var res = $.parseJSON(data)
				cb(res['response'])
			}
		)
	}

	QadabraAPI.prototype.create_ad = function (url, size, category, cb) {
		$.post('/qadabra_api',
			{
				action: 'create_ad',
				comp_id: this.compId,
				data: $.toJSON({url:url, size:size, category:category})
			},
			function(data) {
				var res = $.parseJSON(data)
				cb(res['response'])
			}
		)
	}
})(jQuery)

