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
(function(b){b(function(){var a=b("#reset-password-form");a.submit(function(c){c=a.find(':input[name="new-password"]');var d=a.find(':input[name="new-password-confirm"]');return vBulletin.checkPassword(c,d)?!0:!1})})})(jQuery);
