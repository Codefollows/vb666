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
// ***************************
// js.compressed/password.js
// ***************************
vBulletin.precache("error password_needs_numbers password_needs_special_chars password_needs_uppercase password_too_short passwords_must_match".split(" "),["passwordminlength","passwordrequirenumbers","passwordrequirespecialchars","passwordrequireuppercase"]);
$.extend(vBulletin,function(f){function c(a,b){vBulletin.error("error",a,function(){b.trigger("focus")})}return{checkPassword:function(a,b){var d=a.val();if(d.length<vBulletin.options.get("passwordminlength"))return c("password_too_short",a),!1;if(vBulletin.options.get("passwordrequireuppercase")&&!d.match(/[A-Z]/))return c("password_needs_uppercase",a),!1;if(vBulletin.options.get("passwordrequirenumbers")&&!d.match(/[0-9]/))return c("password_needs_numbers",a),!1;if(vBulletin.options.get("passwordrequirespecialchars")){var e=
vBulletin.regexEscape(" !\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~");if(!d.match(new RegExp("["+e+"]")))return c("password_needs_special_chars",a),!1}return b&&b.val&&d!=b.val()?(c("passwords_must_match",b),!1):!0}}}(jQuery));
;

// ***************************
// js.compressed/reset-password.js
// ***************************
(function(b){b(function(){var a=b("#reset-password-form");a.submit(function(c){c=a.find(':input[name="new-password"]');var d=a.find(':input[name="new-password-confirm"]');return vBulletin.checkPassword(c,d)?!0:!1})})})(jQuery);
;

