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
(function(c,f){function g(a){a.preventDefault();var d=c(a.currentTarget).closest(".js-login-form-main");a=d.closest(".js-login-form-main-container");var m=c(".js-error-box",a),h=c(".js-login-message-box",a),n=c(".js-login-button",a);h.height(d.height());var k=b=>{n.prop("disabled",!b);h.toggleClass("h-hide",b);d.toggleClass("h-hide",!b)},l=b=>{m.html(b).toggleClass("h-hide",!b);d.find(".js-login-username, .js-login-password").toggleClass("badlogin",!!b)};k(!1);l("");f.ajaxtools.ajaxSilent({call:"/auth/ajax-login",
data:d.serializeArray(),success:()=>location.reload(),api_error:b=>{d.find(".js-login-password").val("");l(f.phrase.get(b[0]));k(!0)},error:()=>{location.href=pageData.baseurl}})}var e=c(document);e.on("click",".js-login-button",g);e.on("keydown",".js-login-username, .js-login-password",a=>{13==a.keyCode&&g(a)});e.on("focus",".js-login-username, .js-login-password",function(a){c(this).removeClass("badlogin")});e.on("vb-login",()=>location.reload())})(jQuery,vBulletin);
