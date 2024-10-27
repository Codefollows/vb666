/*
 =======================================================================*\
|| ###################################################################### ||
|| # vBulletin 6.0.6
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2024 MH Sub I, LLC dba vBulletin. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/
(function(a){a(()=>{let b=a("#mod_security_test_result");if(0<b.length){let c=a("<img></img>");c.on("error",d=>{b.html(vBAdmin.renderPhrase("yes"))});c.get(0).src="core/clear.gif?test=%u0067"}})})(jQuery);
