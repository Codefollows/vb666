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
(function(a){a(()=>{let b=vBAdmin.renderPhrase;a("#head_version_link").html(b("latest_version_available_x",vb_version||b("n_a")));a(".js-cplogout").on("click",c=>{confirm(b("sure_you_want_to_log_out_of_cp"))||c.preventDefault()})})})(jQuery);
