Array.prototype.inArray = function (value) {
	for (var i = 0; i < this.length; i++)
		if (this[i] === value)
			return true;

	return false;
};

var AdminCP = function() {
	var self = this;
	var args = arguments;
	$(function(){
		self.init.apply(self, args);
	});
};

AdminCP.prototype = {
	z: 5,
	animSpeed: 'normal',
	curSection: 'login',
	curPrefPane: 'administration',
	curAboutPane: 'about',

	init: function(options) {
		this.initializing = true;
		this.loginForm();
		this.initEvents();
		if (this.loaded()) this.afterLogin();
		else {
			$('#login-password')[0].focus();
		}

		this.initializing = false;
	},

	loginForm: function() {
		$('#login-loading').fadeTo(1, 0);
	},

	initEvents: function() {
		var self = this;

		$('#login-form').submit(function() { self.login(); return false; });
		$('#n-prefs').click(function() { self.show('preferences'); return false; });
		$('#n-bans').click(function() { self.show('bans'); return false; });
		$('#n-about').click(function() { self.show('about'); return false; });
	},

	afterLogin: function() {
		var self = this;

		// Login and logout
		$('#login-password')[0].blur();
		$('.logout').click(function() { self.logout(); return false; });

		// Show the nav
		if (this.initializing)
			$('#nav ul').css('display', 'block');
		else
			$('#nav ul').slideDown();

		// Some css for betterlookingness
		$('#preferences-form fieldset:odd').addClass('odd');
		$('#preferences-form fieldset:even').addClass('even');

		$('#bans-list li:odd').addClass('odd');
		$('#bans-list li:even').addClass('even');

		// Hide the loading thingie
		$('.sn-loading').fadeTo(1, 0);

		// Events after load
		this.initEventsAfter();

		// If they want to go directly to a section
		var anchor = this.getAnchor();

		if (anchor.length > 0 && ['preferences', 'bans', 'about'].inArray(anchor))
			self.show(anchor);
		else
			self.show('preferences');
	},

	initEventsAfter: function() {
		var self = this;

		// Navigation
		$('#sn-administration').click(function() { self.showPrefPane('administration');	return false;	});
		$('#sn-display').click(function() { self.showPrefPane('display'); return false; });
		$('#sn-about').click(function() { self.showAboutPane('about'); return false; });
		$('#sn-contact').click(function() { self.showAboutPane('contact'); return false; });
		$('#sn-resetall').click(function() { self.resetPrefs(); return false; });
		$('#sn-unbanall').click(function() { self.unbanAll(); return false; });

		// Bans
		$('.unban-link').click(function() {
			self.unban($(this).parent().find('.ip').html(), $(this).parent());
			return false;
		});

		// Preferences
		$('#preferences-form input').keypress(function(e) {
			var key = window.event ? e.keyCode : e.which;
			if (key == 13 || key == 3) {
				self.changePref.apply(self, [$(this).attr('rel'), this.value]);
				return false;
			}
		}).focus(function() {
			this.name = this.value;
		}).blur(function() {
			if (this.name != this.value)
				self.changePref.apply(self, [$(this).attr('rel'), this.value]);
		});

		$('#preferences-form select').change(function() {
			self.changePref.apply(self, [$(this).attr('rel'), $(this).find('option:selected').attr('rel')]);
		});
	},

	changePref: function(pref, value) {
		this.loading();
		var pars = {
			mode: 'setpreference',
			preference: pref,
			'value': value
		};
		this.ajax(function(json) {
			if (!json.error)
				this.done();
			else
				alert(json.error);
		}, pars);
	},

	resetPrefs: function() {
		this.loading();

		var pars = {
			mode: 'resetpreferences'
		}

		this.ajax(function(json) {
			this.done();
			if (json.prefs)
				for(pref in json.prefs) {
					var value = json.prefs[pref];
					var el = $('#preferences-form input[@rel=' + pref + '], select[@rel=' + pref + ']')[0];
	
					if (el.type == 'text')
						el.value = value;
					else  {
						if (value == true) value = 'true';
						if (value == false) value = 'false';
	
						$('#preferences-form select[@rel=' + pref + ']')
							.find('option')
							.removeAttr('selected')
							.end()
							.find('option[@rel=' + value + ']')
							.attr('selected', 'yeah');
	
					}
				}
			}, pars);

	},

	invalidPassword: function() {
		// Shake the login form
		$('#login-form')
			.animate({ marginLeft: -145 }, 100)
			.animate({ marginLeft: -155 }, 100)
			.animate({ marginLeft: -145 }, 100)
			.animate({ marginLeft: -155 }, 100)
			.animate({ marginLeft: -150 }, 50);

		$('#login-password').val('').focus();
	},

	login: function() {
		if (this.loaded()) {
			alert('Something _really_ weird has happened. Refresh and pretend nothing ever happened.');
			return;
		}

		var self = this;
		var pars = {
			mode: 'login',
			password: $('#login-password').val()
		};

		this.loginLoading();

		this.ajax(function() {
			this.ajax(function(json) {
				self.loginDone();
				if (json.error) {
					self.invalidPassword();
					return;
				}

				$('#content').append(json.html);
				self.afterLogin.apply(self);
			}, pars);
		}, pars);

	},

	logout: function() {
		var self = this;
		var pars = {
			mode: 'logout'
		};

		this.loading();

		this.ajax(function() {
			$('#login-password').val('');
			$('#nav ul').slideUp();
			self.show('login', function() {
				$('#login-password')[0].focus();
				$('.section').not('#login').remove();
				self.done();
			});
		}, pars);
	},

	show: function(section, callback) {
//		var sections = ['login', 'preferences', 'bans', 'about'];
//		if (!sections.inArray(section)) section = 'preferences';
		
		if ($.browser.msie) {
			if (section == 'preferences')
				$('#preferences select').css('display', 'block');
			else
				$('#preferences select').css('display', 'none');
		}
		
		if (section == this.curSection) return;
		this.curSection = section;
		
		$('#' + section)[0].style.zIndex = ++this.z;
		if (this.initializing)
			$('#' + section).css('display', 'block');
		else
			$('#' + section).fadeIn(this.animSpeed, callback);
	},

	showPrefPane: function(pane) {
		var self = this;

		if (pane == this.curPrefPane) return;
		this.curPrefPane = pane;
		$('#preferences .cp-pane').css('display', 'none');
		$('#cp-pane-' + pane).css('display', 'block').fadeIn(this.animSpeed, function() {
			if (self.curPrefPane == pane)
				$('#preferences .cp-pane').not('#cp-pane-' + pane).css('display', 'none');
			else
				$('#cp-pane-' + pane).css('display', 'none');

		});
	},

	showAboutPane: function(pane) {
		var self = this;

		if (pane == this.curAboutPane) return;
		this.curAboutPane = pane;
		$('#about .cp-pane').css('display', 'none');
		$('#cp-pane-' + pane).css('display', 'block').fadeIn(this.animSpeed, function() {
			if (self.curAboutPane == pane)
				$('#about .cp-pane').not('#cp-pane-' + pane).css('display', 'none');
			else
				$('#cp-pane-' + pane).css('display', 'none');

		});
	},

	ajax: function(callback, pars, html) {
		var self = this;

		$.post('ajax.php', pars, function(parse) {
		//	alert(parse);
				if (parse)
					if (html)
						callback.apply(self, [parse]);
					else
						callback.apply(self, [self.json(parse)]);
				else
					callback.apply(self);
		});
	},

	json: function(parse) {
		var json = eval('(' + parse + ')');
		return json;
	},

	loaded: function() {
		return ($('#cp-loaded').length == 1);
	},

	loading: function() {
		$('#' + this.curSection + ' .sn-loading').fadeTo(this.animSpeed, 1);
	},

	done: function() {
		$('#' + this.curSection + ' .sn-loading').fadeTo(this.animSpeed, 0);
	},

	loginLoading: function() {
		$('#login-password').animate({
			width: 134
		});

		$('#login-loading').fadeTo(this.animSpeed, 1);

	},

	loginDone: function() {
		$('#login-password').animate({
			width: 157
		});
		$('#login-loading').fadeTo(this.animSpeed, 0);
	},

	getAnchor: function() {
	  var href = window.location.href;
	  if (href.indexOf('#') > -1 )
	   	return href.substr(href.indexOf('#') + 1).toLowerCase();
	  return '';
	},

	unban: function(ip, el) {
		var self = this;

		this.loading();
		var pars = {
			mode: 'unban',
			'ip': ip
		};

		this.ajax(function(json) {
			if (!json.error) {
				$(el).fadeOut(function() {
					$(this).remove();
					$('#bans-list li:odd').removeClass('even').addClass('odd');
					$('#bans-list li:even').removeClass('odd').addClass('even');
				}, this.animSpeed);
			}
			self.done();
		}, pars);
	},

	unbanAll: function() {
		this.loading();

		var pars = {
			mode: 'unbanall'
		}

		this.ajax(function(json) {
			this.done();
			$('#bans-list').fadeOut(this.animSpeed, function() {
				$('#bans-list').children().remove();
				$('#bans-list').fadeIn();
			});
		}, pars);
	}

};

var cp = new AdminCP();