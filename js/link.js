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
vBulletin.precache(["attach_link","please_enter_link_url"],[]);(function(a){a(function(){vBulletin.conversation.bindEditFormEventHandlers("link");a(".b-content-entry .b-content-entry-panel__content--link").each(function(){a("body").trigger("link_editform_onload",[a(this)])})})})(jQuery);
