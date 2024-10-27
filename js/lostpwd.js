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
vBulletin.precache("lostpw_email_sent forgot_password_title invalid_email_address please_enter_your_email_address please_enter_a_username requiredfieldmissing activation_code activate_your_account".split(" "),[]);
(function(c){function f(){location.href=pageData.baseurl}function g(a,b,d){a="";b=c(".email",b);d=b.val();""==c.trim(d)?a="please_enter_your_email_address":isValidEmailAddress(d)||(a="invalid_email_address");a&&vBulletin.error("error",a,b);return!a}function h(){vBulletin.ajaxForm.apply(c("#frmActivateuser"),[{beforeSubmit:function(a,b,d){a="";d=c(".activateid",b);if(""==c.trim(d.val())){a=["requiredfieldmissing",vBulletin.phrase.get("activation_code")];var e=d}b=c(".username",b);""==c.trim(b.val())&&
(a="please_enter_a_username",e=b);a&&vBulletin.warning("error",a,e);return!a},success:function(a,b,d,e){vBulletin.alert("activate_your_account",a.msg,null,f)}}])}function k(){vBulletin.ajaxForm.apply(c("#frmActivateemail"),[{beforeSubmit:g,success:function(a,b,d,e){vBulletin.alert("email_activation_codes",a.msg,null,f)}}])}function l(){vBulletin.hv.reset();vBulletin.ajaxForm.apply(c("#frmLostpw"),[{beforeSubmit:g,success:function(a){vBulletin.alert("forgot_password_title","lostpw_email_sent",null,
f)},api_error:vBulletin.hv.resetOnError}])}c(function(){l();h();k()})})(jQuery);
