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
(function(a){a(function(){a(".js-nojs-warning").remove();var b=a(".js-api-form");a(".js-api-form-submit").on("click",function(){var f=a(':input[name="api[class]"]',b).val()||a(':input[name="api[class]"]',b).attr("placeholder"),k=a(':input[name="api[method]"]',b).val()||a(':input[name="api[method]"]',b).attr("placeholder"),d=a(':input[name="parameters"]',b).val()||a(':input[name="parameters"]',b).attr("placeholder");d=JSON.parse(d)||{};var e=b.attr("action"),l=a(':input[name="bogus_securitytoken"]',
b).is(":checked");d.securitytoken=a(':input[name="securitytoken"]',b).val();l&&(d.securitytoken="hammertime");e+=f+"/"+k;f="Attempting to make ajax call with...  url: "+e+"  data: "+JSON.stringify(d,null,4);console.log(f);var g=a(':input[name="output"]'),h=a(".js-robot-helper");g.val(f);h.text("Waiting");vBulletin.AJAX({url:e,data:d,success:function(c){console.log(e+" success!");console.log(JSON.stringify(c));g.val(JSON.stringify(c,null,4));h.text("Done")},api_error:function(c){console.log(e+" API error!");
console.log(JSON.stringify(c));g.val(JSON.stringify(c));h.text("Error")},error:function(c,m,n){console.log(e+" error!");console.dir(c);g.val(c.responseText+"\n\njqXHR: "+JSON.stringify(c)+"\nText Status: "+JSON.stringify(m)+"\nError: "+JSON.stringify(n));h.text("Error")}})});a(".js-api-form-addparam").on("click",function(){})})})(jQuery);
