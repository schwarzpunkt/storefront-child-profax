


jQuery(document).ready(function() {
	//var $ = jQuery;

	/**
		Template Libraray for generic backend methods.
		@constructor
	*/
	var PlcTemplate = function() {
		/** @type {PlcTemplate} */ var that = this;                                 // closure
		/** @type {Object.<string, Array.<string>>} */ this.availableLocales = {'de': ['CH'], 'en': ['US'], 'fr': ['FR'], 'ar': ['AE']};
		/** @type {string} */ this.i18n;
	
		/** @type {Array} */ var p;
		// translate document
		that.i18n = 'de-CH';
		if (window.GL_Config && GL_Config.i18n) {
			p = GL_Config.i18n.split('-');
			if (that.availableLocales[p[0]]) {
				that.i18n = GL_Config.i18n;
				if (!that.availableLocales[p[0]].contains(p[1]))
					that.i18n = p[0] + '-' + that.availableLocales[p[0]][0];    // just take the first supported ideom
			}
		}
	
		t5.localizeDB(GL_Config.server_url + '/backend/php/i18n_' + that.i18n + '.json');
		$('[localizetext]').each(function() {
			$(this).text(t5.l($(this).attr('localizetext')));
		});
		$('[localizehtml]').each(function() {
			$(this).html(t5.l($(this).attr('localizehtml')));
		});
		$('[localizealt]').each(function() {
			$(this).attr('alt', t5.l($(this).attr('localizealt')));
		});
		$('[localizetitle]').each(function() {
			$(this).attr('title', t5.l($(this).attr('localizetitle')));
		});
		$('[localizeplaceholder]').each(function() {
			$(this).attr('placeholder', t5.l($(this).attr('localizeplaceholder')));
		});
		$('[localizevalue]').each(function() {
			$(this).attr('value', t5.l($(this).attr('localizevalue')));
		});
	};
	
	
	
	/**
		Request to register, select private or school.
	 */
	PlcTemplate.prototype.registerSelect = function() {
		t5.dialog({
			icon: '',
			title: '',
			message: '<div style="margin:64px 0 16px -96px;text-align:center;font-family:profax; font-size:4em;"><a target="_top" style="color:#08F" href="' + GL_Config.server_url + '/registrationprivate">' + t5.l('Private') + '</a> | <a target="_top" style="color:#08F" href="' + GL_Config.server_url + '/registration">' + t5.l('School') + '</a></div>',
			buttons: [{text: t5.l('Cancel')}]});
	};
	
	
	
	/**
		Request to login.
		@return {boolean} .
	 */
	PlcTemplate.prototype.login = function() {
		/** @type {PlcTemplate} */ var that = this;                                 // closure
		/** @type {string} */ var hmac = CryptoJS.HmacSHA512($('#loginPassword').val() + $('#loginUsername').val(), 'plc');
	/*
		if (!t5.formRequiredCheck('loginform'))
			return false;
	*/
		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: GL_Config.server_url + '/action/login/' + encodeURIComponent($('#loginUsername').val()) + '/' + hmac,
			error: function(jqXHR, textStatus, errorThrown) {
				if (errorThrown == 'Unauthorized') {
					t5.dialog({
						'icon': GL_Config.server_url + '/main/plc/images/icon_alert.svg',
						'title': t5.l('TemplateLoginFailedTitle'),
						'message': t5.l('TemplateLoginFailedMessage')});
				} else {
					window.ch.profax.online.backend.template.ajaxError(jqXHR, textStatus, errorThrown);
				}
			},
			success: function(data, textStatus, jqXHR) {
				/** @type {Object} */ var id;
				/** @type {Element} */ var form;
				/** @type {Element} */ var hiddenField;
		
				id = data.sid.split('=');
				form = document.createElement('form');
				form.setAttribute('method', 'post');
				form.setAttribute('action', GL_Config.server_url);
				form.innerHTML = '<input type="hidden" name="' + id[0] + '" value="' + id[1] + '" />';
				document.body.appendChild(form);
				form.submit();
			}
		});
		return false;
	};


    /**
        Open Window to login by Single Signon.
     */
    PlcTemplate.prototype.ssologin = function(tProvider, tOauthState) {
        /** @type {PlcTemplate} */ var plcTemplate = this;                      // closure
        /** @type {Object} */ var ssoProviders = {
            'microsoft': ['Microsoft™', 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id=b89e53a5-bbef-4120-8f50-86e71470aa73&response_type=code&redirect_uri=' + encodeURIComponent(GL_Config.server_url + '/action/oauth2/microsoft/login') + '&response_mode=query&state=' + tOauthState + '&scope=openid email'],
            'google': ['Google™', 'https://accounts.google.com/o/oauth2/v2/auth?client_id=1009670344521-bc2vhfnbch100f7rl2pjs423268298hn.apps.googleusercontent.com&response_type=code&redirect_uri=' + encodeURIComponent(GL_Config.server_url + '/action/oauth2/google/login') + '&response_mode=query&state=' + tOauthState + '&scope=email']
        };

        t5.dialog({
            title: t5.l('TemplateLoginSSOTitle', ssoProviders[tProvider][0]),
            message: t5.l('TemplateLoginSSOMessage', ssoProviders[tProvider][0]),
            buttons: [{text: t5.l('Cancel')}, {text: t5.l('Continue'), 'default': true, 'callback': function() {
                window.location.href = ssoProviders[tProvider][1];
            }}]
        });
    };
	
	
	
	/**
		Request password-reset.
	 */
	PlcTemplate.prototype.passwordReset = function() {
		t5.dialog({
			'icon': GL_Config.server_url + '/backend/php/images/icon_prompt.svg',
			'title': t5.l('TemplatePasswordResetTitle'),
			'message': t5.l('TemplatePasswordResetMessage') + '<br /><br /><input type="text" id="plcPasswordResetEMail" />',
			'buttons': [
				{'text': t5.l('Cancel')},
				{'text': t5.l('Reset'), 'default': true, 'callback': function(status) {
					$.ajax({
						type: 'GET',
						dataType: 'json',
						url: GL_Config.server_url + '/action/passwordreset/' + encodeURIComponent(status['plcPasswordResetEMail']) + '/' + encodeURIComponent('profaxonline') + '/' + window.btoa(GL_Config.server_url),
						error: function(jqXHR, textStatus, errorThrown) {
							t5.alert({'icon': GL_Config.server_url + '/main/plc/images/icon_alert.svg', title: t5.l('EMailError' + JSON.parse(jqXHR['responseText']).statuscode), message: '', buttons: [{text: t5.l('OK'), 'default': true}]});
						},
						success: function(data, textStatus, jqXHR) {
							t5.alert({'icon': GL_Config.server_url + '/main/plc/images/icon_alert.svg', title: t5.l('TemplatePasswordResetMail'), message: '', buttons: [{text: t5.l('OK'), 'default': true}]});
						}
					});
				}}
			]
		});
	};
	
	
	
	
	/**
		Failed ajax action.
		@param {Object} jqXHR .
		@param {string} textStatus .
		@param {string} errorThrown .
	 */
	PlcTemplate.prototype.ajaxError = function(jqXHR, textStatus, errorThrown) {
		/** @type {PlcTemplate} */ var that = this;                                 // closure
	
		$('#shield').remove();
		switch (errorThrown) {
			case 'Unauthorized':
				t5.alert({
					'icon': GL_Config.server_url + '/main/plc/images/icon_alert.svg',
					'title': t5.l('TemplateInactivityTitle'),
					'message': t5.l('TemplateInactivityMessage'),
					'buttons': [
						{'text': t5.l('OK'), 'default': true, 'callback': function(status) {
							window.onbeforeunload = null;                           // get rid of unload block
							window.location.reload();                               // reload page to show login window
						}
				}]});
				break;
			default:
				t5.alert({'icon': GL_Config.server_url + '/main/plc/images/icon_alert.svg', 'title': t5.l('Error') + ': ' + errorThrown, 'message': textStatus + '<br /><br /><br />' + jqXHR.responseText});
		}
	};
	
	
	
	
	
	
	/**
		Generic Showcase operations.
		@constructor
	*/
	var PlcShowcase = function() {
		/** @type {PlcShowcase} */ var that = this;                                 // closure
		/** @type {number} */ this.itemscount = 0;
		/** @type {number} */ this.item = 0;
	
		$(document).ready(function() {
			if ($('#showcase').length) {
				$(window).resize(function() {
					that.update();
				});
				that.update();
			}
		});
	};
	
	
	/**
		Move items to te left.
	 */
	PlcShowcase.prototype.update = function() {
		this.itemscount = $('#showcase_items_mover .tray').length;
		this.itemsshown = Math.floor(window.innerWidth / (256 + 32)) - 1;
		$('#showcase_items').css('width', this.itemsshown * (256 + 32) + 32);
	
		if (this.itemsshown < this.itemscount) {
			$('.nav').css('color', '');
			this.right();
			this.left();
		} else {
			$('.nav').css('color', 'rgba(255,255,255,0)');
		}
	};
	
	
	/**
		Move items to te left.
	 */
	PlcShowcase.prototype.left = function() {
		this.item++;
		if (this.item > -1) {
			this.item = 0;
			$('#showcase_left').css('opacity', '0');
		}
		$('#showcase_right').css('opacity', '');
		$('#showcase_items_mover').css('transform','translateX(' + (this.item * (256 + 32)) + 'px)');
	};
	
	
	/**
		Move items to te right.
	 */
	PlcShowcase.prototype.right = function() {
		this.item--;
		if ((this.itemscount - this.itemsshown + this.item) < 1) {
			this.item = this.itemsshown - this.itemscount;
			$('#showcase_right').css('opacity', '0');
		}
		$('#showcase_left').css('opacity', '');
		$('#showcase_items_mover').css('transform','translateX(' + (this.item * (256 + 32)) + 'px)');
	};
	
	
	
	/**
		Simple format with basic %s support.
		@param {...string} var_args .
		@return {string} String without format.
	 */
	String.prototype.format = function(var_args) {
		/** @type {Array} */ var args = Array.prototype.slice.call(arguments);
		return this.replace(/([^%]|^)(%s)/g, function(match, p1, p2) {
			return p1 + args.shift().toString();
		}).replace('%%', '%');
	};

/** @type {Object} */ window.ch = { profax: {online: {backend: { template: new PlcTemplate(), licenses: null, teacher: null, reseller: null}}}};
/** @type {PlcShowcase} */ window.ch.profax.online.backend.showcase = new PlcShowcase();
});
