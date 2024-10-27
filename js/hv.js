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
(function(c){function f(b){c(".humanverify_image",b).each(function(){var a=c(this),d=c(".refresh_imagereg",a);d.off("click").on("click",a,g);c(".imagereg",a).off("click").on("click",a,g);d.removeClass("h-hide")})}function g(b){var a=b.data;c(".progress_imagereg",a).removeClass("h-hide");vBulletin.AJAX({call:"/ajax/api/hv/generateToken",success:function(d){d=d.hash;c("input.hash",a).val(d);c(".imagereg",a).attr("src",pageData.baseurl+"/hv/image?hash="+d);c(".imageregt",a).val("")},api_error:function(){},
complete:function(){c(".progress_imagereg",a).addClass("h-hide")}});return!1}vBulletin.ensureObj("hv");vBulletin.hv.resetOnError=function(b,a,d){var e=vBulletin.ajaxtools;return e.hasError(b,/humanverify_.*_wronganswer/)?(d.after_error=vBulletin.ajaxtools.runBeforeCallback(d.after_error,function(){vBulletin.hv.reset(!0)}),e.showApiError(b,a,d),!1):!0};vBulletin.hv.reset=function(b){var a=c(".humanverify.humanverify_image");if(0<a.length)a.find(".refresh_imagereg").trigger("click"),b&&a.find(".imageregt").trigger("focus");
else if(0<c(".js-humanverify-recaptcha2").length&&"undefined"!=typeof grecaptcha&&"function"==typeof grecaptcha.reset)grecaptcha.reset();else{var d=c(".humanverify.humanverify_question");0<d.length&&vBulletin.AJAX({call:"/ajax/render/humanverify",data:{action:"register",isAjaxTemplateRenderWithData:!0},success:function(e){e=c(e.template);d.replaceWith(e);b&&e.find(".answer").trigger("focus")}})}};vBulletin.hv.show=function(b){var a=b.find(".imagereg");b.removeClass("h-hide");a.height()!=a.attr("height")&&
b.find(".refresh_imagereg").trigger("click")};vBulletin.hv.init=function(b){f(b)};window.recaptcha2callback=function(b){c(".js-humanverify-recaptcha2-response").val(b)};c(function(){f(c(document))})})(jQuery);
