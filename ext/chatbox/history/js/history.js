var History = function() {
	var self = this;
	var args = arguments;
	$(function(){
		self.init.apply(self, args);
	});
};

History.prototype = {
	animSpeed: 'normal',
	noPosts: '<div id="ys-post-1" class="ys-post ys-first ys-admin-post">\n<span class="ys-post-timestamp">13:37</span>\n<span class="ys-post-nickname">Yurivish:<span>\n<span class="ys-post-message">Hey, there aren\'t any posts in this log.</span>\n</div>',

	init: function(options) {
		this.prefsInfo = options.prefsInfo;
		this.log = options.log;
		this.initEvents();
		$('body').ScrollToAnchors({	duration: 800 });
	},


	initEvents: function() {
		var self = this;
		
		this.initLogEvents();


		// Select log
		$('#log').change(function() {
			var logIndex = $(this).find('option[@selected]').attr('rel');
			
			var pars = {
				p: 'yes',
				log: logIndex
			}
			
			self.ajax(function(html) {
				$('#ys-posts').html(html)
				$('#yshout').fadeIn();
				self.initLogEvents();
			}, pars, true, 'index.php');

			
		});

		// Clear the log
		$('#clear-log').click(function() {
			var el = this;
			var pars = {
				reqType: 'clearlog'
			};

			self.ajax(function(json) {
				if (json.error) {
					switch(json.error) {
						case 'admin':
							self.error('You\'re not an admin. Log in through the admin CP to clear the log.');
							el.innerHTML = 'Clear this log';
							return;
							break;
					}
				}

				$('#ys-posts').html(self.noPosts);
				self.initLogEvents();
				el.innerHTML = 'Clear this log'
			}, pars);

			this.innerHTML = 'Clearing...';
			return false;
		});

		// Clear all logs
		$('#clear-logs').click(function() {
			var el = this;
			var pars = {
				reqType: 'clearlogs'
			};

			self.ajax(function(json) {
				if (json.error) {
					switch(json.error) {
						case 'admin':
							el.innerHTML = 'Clear all logs'
							self.error('You\'re not an admin. Log in through the admin CP to clear logs.');
							return;
							break;
					}
				}

				$('#ys-posts').html(self.noPosts);
				self.initLogEvents();
				el.innerHTML = 'Clear all logs'
			}, pars);

			this.innerHTML = 'Clearing...';
			return false;
		});
	},
	
	initLogEvents: function() {
		var self = this;
		
		$('#yshout .ys-post')
			.find('.ys-info-link').toggle(
				function() { self.showInfo.apply(self, [$(this).parent().parent()[0].id, this]); return false; },
				function() { self.hideInfo.apply(self, [$(this).parent().parent()[0].id, this]); return false; })
			.end()
			.find('.ys-ban-link').click(
				function() { self.ban.apply(self, [$(this).parent().parent()[0]]); return false; })
			.end()
			.find('.ys-delete-link').click(
				function() { self.del.apply(self, [$(this).parent().parent()[0]]); return false; });
	},
	
	showInfo: function(id, el) {
		var jEl = $('#' + id + ' .ys-post-info');

		if (jEl.length == 0) return false;
		
		if (this.prefsInfo == 'overlay')
			jEl.css('display', 'block').fadeIn(this.animSpeed);
		else
			jEl.slideDown(this.animSpeed);
		
		el.innerHTML ='Close Info'
		return false;
	},
	
	hideInfo: function(id, el) {
		var jEl = $('#' + id + ' .ys-post-info');

		if (jEl.length == 0) return false;

		if (this.prefsInfo == 'overlay')
			jEl.fadeOut(this.animSpeed);
		else
			jEl.slideUp(this.animSpeed);
			
		el.innerHTML = 'Info';
		return false;
	}, 
	
	ban: function(post) {
		var self = this;
		var link = $('#' + post.id).find('.ys-ban-link')[0];
		
		switch(link.innerHTML) {
			case 'Ban':
				var pIP = $(post).find('.ys-h-ip').html();
				var pNickname = $(post).find('.ys-h-nickname').html();

				var pars = {
					log: self.log,
					reqType: 'ban',
					ip: pIP,
					nickname: pNickname
				};
	
				this.ajax(function(json) {
					if (json.error) {
						switch (json.error) {
							case 'admin':
								self.error('You\'re not an admin. Log in through the admin CP to ban people.');
								break;
						}
						return;
					}
					
					$('#yshout .ys-post[@rel="' + pars.ip + '"]')
						.addClass('ys-banned-post')
						.find('.ys-ban-link')
							.html('Unban');
						
				}, pars);
				
				link.innerHTML = 'Banning...';
				return false;
				break;
			
			case 'Banning...':
				return false;
				break;
			
			case 'Unban':
				var pIP = $(post).find('.ys-h-ip').html();
				var pars = {
					reqType: 'unban',
					ip: pIP
				};
	
				this.ajax(function(json) {
					if (json.error) {
						switch(json.error) {
							case 'admin':
								self.error('You\'re not an admin. Log in through the admin CP to unban people.');
								return;
								break;
						}
					}
					
					$('#yshout .ys-post[@rel="' + pars.ip + '"]')
						.removeClass('ys-banned-post')
						.find('.ys-ban-link')
							.html('Ban');
				
				}, pars);
	
				link.innerHTML = 'Unbanning...';
				return false;
				break;
				
			case 'Unbanning...':
				return false;
				break;
		}
	},
	
	del: function(post) {
		var self = this;

		var link = $('#' + post.id).find('.ys-delete-link')[0];
		if (link.innerHTML == 'Deleting...') return;

		var pUID = $(post).find('.ys-h-uid').html();

		var pars = {
			reqType: 'delete',
			uid: pUID
		};

		self.ajax(function(json) {
			if (json.error) {
				switch(json.error) {
					case 'admin':
						self.error('You\'re not an admin. Log in through the admin CP to ban people.');
						return;
						break;
				}
			}
			
			$(post).slideUp(self.animSpeed);

		}, pars);

		link.innerHTML = 'Deleting...';
		return false;

	},
	
	json: function(parse) {
		var json = eval('(' + parse + ')');
		return json;
	},

	ajax: function(callback, pars, html, page) {
		pars = jQuery.extend({
			reqFor: 'history',
			log: this.log
		}, pars);

		var self = this;
		
		if (page == null) page = '../yshout.php';
		
		$.post(page, pars, function(parse) {
				if (parse)
					if (html)
						callback.apply(self, [parse]);
					else
						callback.apply(self, [self.json(parse)]);
				else
					callback.apply(self);
		});
	},

	error: function(err) {
		alert(err);
	}

};

