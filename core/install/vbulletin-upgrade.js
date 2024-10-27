/*!=======================================================================*\
|| ###################################################################### ||
|| # vBulletin 6.0.6
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2024 MH Sub I, LLC dba vBulletin. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/
(($) =>
{
	//helper functions
	function conditionalSet($item, value, setter='val')
	{
		let dataname = 'default-' + setter,
			defval = $item.data(dataname);

		if(!defval)
		{
			defval = $item[setter]();
			$item.data(dataname, defval);
		}

		$item[setter](value ? value : defval);
	}

	function vB_Upgrade()
	{
		var eventdata = {upgrade:this};

		YAHOO.util.Event.on("querystatus", "click", this.querystatus, this, true);

		$('#beginupgrade').on('click', eventdata, this.beginupgrade);

		$('#promptform').on('submit', eventdata, this.promptsubmit);
		$('#promptcancel').on('click', eventdata, this.promptcancel);

		YAHOO.util.Event.on("confirmform", "submit", this.confirmsubmit, this, true);
		YAHOO.util.Event.on("confirmok", "click", this.confirmsubmit, this, true);
		YAHOO.util.Event.on("confirmcancel", "click", this.confirmsubmit, this, true);

		$('#customerid').on('focus', 	() => $('#customerid_error').addClass('hidden'));

		$('#showdetails').on('click', this.setdetails.bind(this, true));
		$('#hidedetails').on('click', this.setdetails.bind(this, false));

		let $optionsbutton = $('#options');
		$optionsbutton.on('click', () => $('#optionsbox').toggleClass('hidden'))
		$optionsbutton.toggleClass('hidden', (SCRIPTINFO['version'] == 'install'));

		this.$confirmbox = $('#confirm');
		this.$promptbox = $('#prompt');

		//there is probably a better way to handle the default values than pulling whatever
		//was rendered in the initial html but we won't poke that right now.

		// start the processing..
		this.upgradelog = $('#mainmessage');
		this.upgradelog.append($('<ul>').addClass('js-messagelist list_no_decoration'));

		this.replacelastmessage = 0;
		this.stepsdone = 0;
		this.prevstep = '';
		this.promptinfo = {};

		this.confirm_callback = null;
		this.ajax_query_req = null;
		this.timer = null;
		this.target = SETUPTYPE == 'install' ? 'install.php' : 'upgrade.php';
		this.percentout = true;
	}

	vB_Upgrade.prototype = {
		setdetails(showdetail)
		{
			$('#detailbox').toggleClass('hidden', !showdetail);
			$('#showdetails').toggleClass('hidden', showdetail);
			$('#hidedetails').toggleClass('hidden', !showdetail);
		},

		log_message (text, classname, replace)
		{
			var $loglist = this.upgradelog.find('.js-messagelist'),
				$li = $('<li>').html(text).addClass(classname);

			if (replace == 1 && this.replacelastmessage == 1)
			{
				$loglist.find('li').last().replaceWith($li);
			}
			else
			{
				$loglist.append($li);
			}

			this.replacelastmessage = replace;
			this.upgradelog.scrollTop(this.upgradelog.prop('scrollHeight'));
		},

		show_confirm(text, confirm_callback, hidecancel, oktext, canceltext, width, height, title, advanced, reset)
		{
			var $cancelbutton = $('#confirmcancel');

			//set some form text if we have it.
			conditionalSet($('#confirmtitle'), title, 'html');

			conditionalSet($('#confirmok'), oktext);
			conditionalSet($cancelbutton, canceltext);

			//toggle some classes.
			$('#confirmreset').toggleClass('hidden', !reset);
			$cancelbutton.toggleClass('hidden', !!hidecancel);
			this.$confirmbox.toggleClass('advancedconfirm', !!advanced);

			//set some styles -- we should probably not have the server controlling pixel widths on display.
			//We need to do some calcuations on the width and can't read that directly so... fake it.
			//We cannot adjust the width directly or things will break.
			this.$confirmbox.css('--dialog-width', width ? width : '');
			this.$confirmbox.css('height', height ? height : '');

			$('#confirmmessage').html(text);

			this.showprogress(false);
			this.confirm_callback = confirm_callback;

			this.show_dialog(this.$confirmbox, true);

			//need to do this after we show the box.  Focus doesn't necesarily work on non visible elements.
			this.$confirmbox.find('input[type=text]').first().trigger('focus');
		},

		//As far as I can tell this is not used.  It's only triggered by upgrade
		//scripts returning a "prompt" result without the confirmf lag set.  Which nothing I can find actually does.
		//It shows a single text item and returns it without any complicated call backs.  May be useful.
		//Probably ought to be combined with the confirm function.
		show_prompt(text, cancel, title, reset)
		{
			var $cancelbutton = $('#promptcancel');
			$cancelbutton.val(cancel ? cancel : '');
			$cancelbutton.toggleClass('hidden', !cancel);

			//set some form text if we have it.
			conditionalSet($('#prompttitle'), title, 'html');

			//toggle some classes.
			$('#promptreset').toggleClass('hidden', !reset);

			$('#promptmessage').html(text);

			this.showprogress(false);

			var $response = $('#promptresponse');
			$response.val('');
			this.show_dialog(this.$promptbox, true);
			$response.trigger('focus');
		},

		promptcancel(e)
		{
			e.data.upgrade.handleprompt("");
		},

		promptsubmit(e)
		{
			e.preventDefault();
			var $responsefield = $('#promptresponse');
			if ($responsefield.val() == "")
			{
				$responsefield.trigger('focus');
				return;
			}

			e.data.upgrade.handleprompt($responsefield.val());
		},

		handleprompt(response)
		{
			this.show_dialog(this.$promptbox, false);
			this.process_step_after_prompt(this.promptinfo, response, SCRIPTINFO['only']);
		},

		show_dialog($element, show)
		{
			$('#vb_overlay_background').toggleClass('hidden', !show);
			$element.toggleClass('hidden', !show);

			//we really only need this active active when a dialog is showing
			//(we'll check which one in the handler) and keypress generates a number
			//of events.
			if(show)
			{
				YAHOO.util.Event.on(window, "keydown", this.cancelform, this, true);
			}
			else
			{
				YAHOO.util.Event.removeListener(window, "keydown", this.cancelform);
			}
		},

		//return json object with the basic data from the response XML
		get_response_data(xmldom)
		{
			let defaults = {
				'status': '',
				'longversion': '',
				'version': '',
				'nextstep': '',
				'startat': 0,
				'max': false,
				'empty_script': false,
			};

			let data = this.extract_data_from_xml($(xmldom), defaults);
			for(let key of ['empty_script'])
			{
				data[key] = this.xml_field_to_bool(data[key]);
			}
			return data;
		},

		//return json object with the various "prompt" values from the
		//xml object.  Note that these often won't be present and serveral
		//are mutually exclusive
		get_prompt_data(xmldom)
		{
			let defaults = {
				'prompt': null,
				'html': null,
				'upgradecomplete': null,
				'confirm': false,
				'reset': false,
				'title': null,
				'hidecancel': false,
				'ok': null,
				'cancel': null,
				'width': null,
				'height': null,
			}

			let data = this.extract_data_from_xml($(xmldom), defaults);
			for(let key of ['confirm', 'reset', 'hidecancel'])
			{
				data[key] = this.xml_field_to_bool(data[key]);
			}
			return data;
		},

		extract_data_from_xml($xml, defaults)
		{
			let data = defaults;
			for (let key of Object.keys(defaults))
			{
				//this will technically grab any decendants of the DOM root matching the tag name
				//and take the first of the list. In practice we expect tags to be direct children
				//and unique.
				let $node = $xml.find(key);
				if($node.length)
				{
					data[key] = $node.first().text();
				}
			}
			return data;
		},

		xml_field_to_bool(val)
		{
			return !!val && val !== '0' && val !=='false';
		},

		set_status(text)
		{
			//these appear to always be set together so we'll hide the details.
			$('#statusmessage').html(text);
			$('#progressmessage').html(text);
		},

		abort_upgrade()
		{
			this.set_status(ABORTMSG);
		},

		beginupgrade(e)
		{
			$('#beginsection').addClass('hidden');
			$('#progresssection').removeClass('hidden');
			e.data.upgrade.process_step(SCRIPTINFO['version'], SCRIPTINFO['step'], SCRIPTINFO['startat'], true, false, true, SCRIPTINFO['only']);
		},

		process_step_after_prompt(promptinfo, response, only, htmldata)
		{
			this.process_stepex(promptinfo.version, promptinfo.nextstep, {startat: promptinfo.startat}, false, response, null, only, htmldata);
		},

		process_step(version, step, startat, checktable, response, firstrun, only, $extrafieldscontainer)
		{
			this.process_stepex(version, step, {startat}, checktable, response, firstrun, only, $extrafieldscontainer);
		},

		process_step_response_prompt(promptdata, version)
		{
			let {hidecancel, ok, cancel, width, height, title, reset} = promptdata;

			if (!!promptdata.prompt && !promptdata.confirm)
			{
				this.show_prompt(promptdata.prompt, cancel, title, reset);
				return true;
			}

			let callback, text, advanced;
			if (!!promptdata.prompt)
			{
				text = promptdata.prompt;
				callback = this.confirm;
				advanced = false;
			}
			else if (!!promptdata.html)
			{
				text = promptdata.html;
				callback = this.confirmhtml;
				advanced = true;
			}
			//we can almost certainly get away checking for the prompt text like the others, but preserving the prior behavior
			else if (version == "done")
			{
				text = promptdata.upgradecomplete;
				callback = this.exitupgrade;
				advanced = false;
			}
			else
			{
				return false;
			}

			this.show_confirm(text, callback.bind(this), hidecancel, ok, cancel, width, height, title, advanced, reset);
			return true;
		},

		showprogress(show)
		{
			$('upgradeprogress').toggleClass('hidden', !show);
		}
	};

	vB_Upgrade.prototype.querystatus = function(step)
	{
		YAHOO.util.Dom.get("querystatus").disabled = true;
		var postdata = "ajax=1&status=1&";

		var callback =
		{
			failure : vBulletin_AJAX_Error_Handler,
			timeout : 0,
			success : this.process_querystatus,
			scope   : this,
			argument: {"step" : step}
		}
		if (YAHOO.util.Connect.isCallInProgress(this.ajax_query_req))
		{
			YAHOO.util.Connect.abort(this.ajax_query_req);
		}
		this.ajax_query_req = YAHOO.util.Connect.asyncRequest("POST", this.target, callback, postdata);
	}

	vB_Upgrade.prototype.process_querystatus = function(ajax)
	{
		if (ajax.responseXML)
		{
			YAHOO.util.Dom.get("querystatus").disabled = false;
			var processes = ajax.responseXML.getElementsByTagName("process");
			if (processes.length)
			{
				this.setdetails(true);

				var output = '';
				for (var i = 0; i < processes.length; i++)
				{
					this.log_message(ajax.responseXML.getElementsByTagName("query_status")[0].firstChild.nodeValue, "querystatus_header");
					this.log_message(processes[i].firstChild.nodeValue, "querystatus_message");
				}
			}
			else
			{
				var noprocesses = ajax.responseXML.getElementsByTagName("noprocess");
				if (noprocesses.length)
				{
					alert(noprocesses[0].firstChild.nodeValue);
				}
			}
		}
	}

	vB_Upgrade.prototype.process_stepex = function(version, step, stepdata, checktable, response, firstrun, only, $extrafieldscontainer)
	{
		this.showprogress(true);

		//options box isn't a form so we can't use it directly but the jquery input selector
		//should do what we want, though the returned format is inconvenient.
		//Convert to [[name, value], ...] instead of {name: value} to account for potential
		//"arrays" in the form data.
		let data = [
			['ajax', 1],
			['version', version],
		 	['checktable', checktable],
			['firstrun', !!firstrun],
			['step', step],
			['only', (only == 1)],
		];

		let $inputs = $(':input', '#optionsbox');
		if($extrafieldscontainer && $extrafieldscontainer.length > 0)
		{
			$inputs = $inputs.add(':input', $extrafieldscontainer);
			data.push(['htmlsubmit', 1]);
		}

		let fielddata = $inputs.serializeArray().map((i) => [i.name, i.value]);
		data.push(... fielddata);
		data.push(... Object.entries(stepdata));

		//not sure why we we don't send a response of type boolean.  Perhaps trying to avoid a false value
		//getting sent?  The caller should handle that.
		if (typeof(response) != "undefined" && typeof(response) != "boolean" && response != null)
		{
			data.push(['response', response]);
		}

		let postdata = new URLSearchParams(data).toString();

		var callback =
		{
			failure : this.ajax_failure,
			timeout : 0,
			success : this.process_step_result,
			scope   : this,
			argument: {
				"step"    : step,
				"version" : version,
				"startat" : stepdata.startat
			}
		}

		//the query string params don't do anything, but they show up in various reporting
		//where the post doesn't -- which makes it much easier to figure out what's happening
		var url = this.target + '?' + (new URLSearchParams({version, step}));
		YAHOO.util.Connect.asyncRequest("POST", url, callback, postdata);
		// Start process timer.
		var thisC = this;
		this.timer = setTimeout(function(){ thisC.show_query_status(version); }, 20000);
	}

	vB_Upgrade.prototype.cancel_query_status = function()
	{
		YAHOO.util.Dom.addClass("querystatus", "hidden");
		YAHOO.util.Dom.get("querystatus").disabled = false;
		clearTimeout(this.timer);
		if (YAHOO.util.Connect.isCallInProgress(this.ajax_query_req))
		{
			YAHOO.util.Connect.abort(this.ajax_query_req);
		}
	}

	vB_Upgrade.prototype.ajax_failure = function(ajax)
	{
		vBulletin_AJAX_Error_Handler(ajax);

		//in some cases PHP will return 500 on fatal errors and sometimes it doesn't.
		//let's call this here in case we have a message worth showing.  Otherwise
		//the system just hangs.
		this.process_bad_response(ajax.responseText, ajax.argument.step, ajax.argument.version);
		this.cancel_query_status();
	}

	vB_Upgrade.prototype.show_query_status = function(version)
	{
		if (version.match(/^\d/) || version == "dev")
		{
			YAHOO.util.Dom.removeClass("querystatus", "hidden");
		}
	}

	vB_Upgrade.prototype.process_step_result = function(ajax)
	{
		if (ajax.responseXML)
		{
			this.cancel_query_status();

			var errors = ajax.responseXML.getElementsByTagName("error");
			var errors_html = ajax.responseXML.getElementsByTagName("error_html");
			var errorlist = null;

			var scriptXStepYMessage = '<br /><br />' + construct_phrase(SCRIPT_X_STEP_Y, vBAdmin.htmlspecialchars(ajax.argument.version), vBAdmin.htmlspecialchars(ajax.argument.step));

			if (errors.length)
			{
				errorlist = errors;
			}
			if (errors_html.length)
			{
				errorlist = errors_html;
			}

			if (errorlist)
			{
				for (var i = 0; i < errorlist.length; i++)
				{
					this.log_message(errorlist[i].firstChild.nodeValue);
					this.show_confirm(errorlist[i].firstChild.nodeValue + scriptXStepYMessage, null, true);
				}
				this.abort_upgrade();
				return;
			}

			//do we have a status?
			var data = this.get_response_data(ajax.responseXML);
			if (data.status == '')
			{
				var errors = ajax.responseXML.getElementsByTagName("fatal_error")
				if (errors.length)
				{
					this.process_error_response(errors[0], ajax.argument.step, ajax.argument.version);
				}
				else
				{
					// If we have no status or errors then assume we received unexpected text!
					this.process_bad_response(ajax.responseText, ajax.argument.step, ajax.argument.version);
				}
				return;
			}

			this.set_status(data.status);

			var {version,	nextstep, startat} = data;

			this.promptinfo = {
				"version"    : version,
				"nextstep"   : nextstep,
				"startat"    : startat,
			};

			//this stuff can be a little hard to keep straight.  The version/start/etc variable we pull from the XML
			//refer to the *next* step that we should run according to the response. this.prevstep refers to the last
			//"next step" we saved, which could very well refer to the step that produced this response
			//(but might not if we are looping through batches).
			var firstbatch = (ajax.argument.startat == 0);
			var stepinfo = version + "-" + nextstep;

			var prompt = ajax.responseXML.getElementsByTagName("prompt");
			var html = ajax.responseXML.getElementsByTagName("html");

			//note that the "upgradecomplete" message triggers a prompt but it's not considered a prompt
			//for processing purposes here like the prompt/html values.
			//It's possible that it should be treated as such but is a "don't care"
			let promptdata = this.get_prompt_data(ajax.responseXML),
					isPrompt = (!!promptdata.prompt || !!promptdata.html);

			if (startat == 0 && !isPrompt && (!this.prevstep || this.prevstep != stepinfo))
			{
				this.prevstep = stepinfo;
				this.stepsdone++;

				var percent = Math.floor((this.stepsdone / TOTALSTEPS) * 100),
					percentstr = percent + '%',
					$progressbar = $('#progressbar'),
					$percentout = $('#percentageout');

				$progressbar.css('width', percentstr);

				if (percent > 0)
				{
					//show the percentage outside of the bar until there is enough space to put it in
					if (this.percentout)
					{
						$percentout.html(percentstr);
						if($progressbar.width() >= $percentout.width())
						{
							this.percentout = false;
							$percentout.html('');
							$progressbar.html('<span>' + percentstr + '</span>');
						}

					}
					else
					{
							$progressbar.html('<span>' + percentstr + '</span>');
					}
				}
			}

			var upgradenotice = ajax.responseXML.getElementsByTagName("upgradenotice");
			if (upgradenotice.length && !isPrompt)
			{
				this.log_message(upgradenotice[0].firstChild.nodeValue);
			}

			var messages = ajax.responseXML.getElementsByTagName("message");
			if (messages.length)
			{
				for (var i = 0; i < messages.length; i++)
				{
					if (messages[i].firstChild)
					{
						var message = messages[i].firstChild.nodeValue;
					}
					else
					{
						var message = vBAdmin.renderPhrase('no_message');
					}

					if (firstbatch && i == 0)
					{
						message = vBAdmin.renderPhrase('version_x_step_y_z', ajax.argument.version, ajax.argument.step, message);
					}
					this.log_message(message, null, YAHOO.util.Dom.getAttribute(messages[i], "replace"));
				}
			}
			else if (!isPrompt && !data.empty_script)
			{
				this.log_message(vBAdmin.renderPhrase('version_x_step_y_z', ajax.argument.version, ajax.argument.step, vBAdmin.renderPhrase('no_message')));
			}

			var apperror = ajax.responseXML.getElementsByTagName("apperror");
			if (apperror.length)
			{
				switch (YAHOO.util.Dom.getAttribute(apperror[0], "type"))
				{
					case "APP_CREATE_TABLE_EXISTS":
						this.show_confirm(apperror[0].firstChild.nodeValue + scriptXStepYMessage, this.confirmtable);
						return;
						break;

					case "PHP_TRIGGER_ERROR":
					case "MYSQL_HALT":
						this.log_message(apperror[0].firstChild.nodeValue, "noindent");
						this.show_confirm(apperror[0].firstChild.nodeValue + scriptXStepYMessage, null, true);
						this.abort_upgrade();
						return;
						break;
				}
			}

			this.process_warnings(ajax.responseXML);

			//this returns true if the response resulted in a prompt
			//if we don't prompt, move to the next step.
			if(!this.process_step_response_prompt(promptdata, version))
			{
				let {startat, max} = data;
				this.process_stepex(version, nextstep, {startat, max}, true, null, null, SCRIPTINFO['only']);
			}
		}
		else if (ajax.responseText)
		{
			this.process_bad_response(ajax.responseText, ajax.argument.step, ajax.argument.version);
		}
	}


	vB_Upgrade.prototype.process_warnings = function(xml)
	{
		var warnings = xml.getElementsByTagName('warnings');
		if (warnings.length == 0)
		{
			return;
		}

		var warninglist = warnings[0].getElementsByTagName('warning');
		for (var i = 0; i < warninglist.length; i++)
		{
			var description = warninglist[i].getElementsByTagName('description');
			if (description.length)
			{
				this.log_message(description[0].textContent);
			}
		}
	}


	vB_Upgrade.prototype.process_error_response = function(errors, step, version)
	{
		var descriptions = errors.getElementsByTagName('description'),
			safeVersion = vBAdmin.htmlspecialchars(version)
			safeStep = vBAdmin.htmlspecialchars(step);

		var message = "<p>" + FATAL_ERROR_OCCURRED + "</p>";
		if (descriptions.length == 1)
		{
			message += descriptions[0].textContent;
		}
		else
		{
			message += "<ul>";
			for (var i = 0; i < descriptions.length; i++)
			{
				message = message + "<li>" + descriptions[i].textContent + "</li>";
			}
			message = "</ul>";
		}

		var scriptXStepYMessage = '<br /><br />' + construct_phrase(SCRIPT_X_STEP_Y, safeVersion, safeStep);

		this.log_message(vBAdmin.renderPhrase('version_x_step_y_z', safeVersion, safeStep, FATAL_ERROR_OCCURRED));
		this.show_confirm(message + scriptXStepYMessage, null, true, null, null, null, null, version + ' Step #' + step);
		this.abort_upgrade();
	}

	vB_Upgrade.prototype.process_bad_response = function(text, step, version)
	{
		var safeVersion = vBAdmin.htmlspecialchars(version),
			safeStep = vBAdmin.htmlspecialchars(step),
			safeText = vBAdmin.htmlspecialchars(text),
			scriptXStepYMessage = construct_phrase(SCRIPT_X_STEP_Y, safeVersion, safeStep),
			newtext = text.replace(/^<\?xml version="[^"]+" encoding="[^"]+"\?>\n/, '');

		if (newtext.length == 0)
		{
			this.show_confirm(SERVER_NO_RESPONSE + '<br /><br />' + scriptXStepYMessage, null, true);
			this.log_message(vBAdmin.renderPhrase('version_x_step_y_z', safeVersion, safeStep, SERVER_NO_RESPONSE));
			this.abort_upgrade();
			return;
		}

		if (text.match(/fatal error.*maximum execution time/i))
		{
			//  Only the template merge allows skip at present
			if (version == "final" && step == 24)
			{
				// Tell step that it timed out so that it will output the proper "time out" phrase and continue
				// on with the next step. That could be done right here without calling the script again but this
				// way allows us to continue with the flow of the step process without sticking in branches
				this.process_step(version, step, 0, false, "timeout", null, SCRIPTINFO['only']);
				return;
			}
		}

		var message = vBAdmin.renderPhrase('unexpected_text', safeText);
		this.log_message(vBAdmin.renderPhrase('version_x_step_y_z', safeVersion, safeStep, message));

		this.show_confirm(message + scriptXStepYMessage, null, true);
		this.abort_upgrade();
	}

	vB_Upgrade.prototype.confirmhtml = function(e, confirm)
	{
		let response = confirm ? 'yes' : 'no',
			continueok = true;

		if (response == 'yes')
		{
			$('#confirmform input[vbrequire=1]').each((i, e) =>
			{
				let $input = $(e),
					val = $input.val(),
					name = $input.attr('name'),
					$error = $('#' +  $.escapeSelector(name) + '_error');


				$error.toggleClass('hidden', !!val);
				continueok = continueok && !!val;

				console.log($input, !val, name, $error, continueok);
			});

			if (!continueok)
			{
				return false;
			}
		}

		this.process_step_after_prompt(this.promptinfo, response, SCRIPTINFO['only'], this.$confirmbox);

		return true;
	}

	vB_Upgrade.prototype.confirm = function(e, confirm)
	{
		var response = confirm ? 'yes' : 'no';
		this.process_step_after_prompt(this.promptinfo, response, SCRIPTINFO['only']);
		return true;
	}

	vB_Upgrade.prototype.confirmtable = function(e, confirm)
	{
		if (confirm)
		{
			this.process_step_after_prompt(this.promptinfo, null, SCRIPTINFO['only']);
		}
		else
		{
			this.abort_upgrade();
		}

		return true;
	}


	vB_Upgrade.prototype.confirmsubmit = function(e)
	{
		YAHOO.util.Event.stopEvent(e);
		var target = YAHOO.util.Event.getTarget(e);
		var closebox = true;
		if (this.confirm_callback)
		{
			var result = this.confirm_callback.call(this, e, target.id == "confirmok" ? true : false);
			if (!result)
			{
				closebox = false;
			}
		}
		if (closebox)
		{
			this.show_dialog(this.$confirmbox, false);
		}
	}

	vB_Upgrade.prototype.exitupgrade = function(e, confirm)
	{
		var admincpurl = '../../admincp';
		if (confirm)
		{
			vBAdmin.vBRedirect(admincpurl);
		}
		else
		{
			$('#admincp').removeClass('hidden').off('click').on('click', () => vBAdmin.vBRedirect(admincpurl));
		}

		return true;
	}


	vB_Upgrade.prototype.cancelform = function(e)
	{
		if (e.keyCode == 27)
		{
			//this should probably check that we actually have a cancel button.  Otherwise the
			//prompt may not be expecting "cancel" behavior.
			YAHOO.util.Event.stopEvent(e);
			if (!this.$confirmbox.is('.hidden'))
			{
				this.show_dialog(this.$confirmbox, false);
				this.confirm_callback.call(this, e, false);
			}

			if (!this.$promptbox.is('.hidden'))
			{
				this.handleprompt("");
			}
		}
	}

	$(() =>
	{
		var upgradeobj = new vB_Upgrade();
	});
})(jQuery);


/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 26385 $
|| #######################################################################
\*=========================================================================*/
