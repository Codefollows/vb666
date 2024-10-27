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
// js.compressed/signup.js
// ***************************
vBulletin.precache("close email_addresses_must_match error invalid_email_address moderateuser paid_subscription_required paid_subscriptions please_enter_a_username please_enter_your_email_address please_enter_your_parent_or_guardians_email_address please_select_a_day please_select_a_month please_select_a_year register_not_agreed registeremail registration_complete registration_coppa_fail registration_gregister registration_start_failed site_terms_and_rules_title terms_of_service_page_text".split(" "),
["checkcoppa","regrequirepaidsub","reqbirthday","usecoppa","webmasteremail"]);
(function(a){if(!vBulletin.pageHasSelectors([".registration-widget"]))return!1;a(function(){function z(b,h,d,g,e){e=e||!1;0!=b&&0!=h&&0!=d&&(g&&a(".birth-date-wrapper").addClass("h-hide"),vBulletin.AJAX({call:"/registration/iscoppa",data:{month:b,day:h,year:d},success:function(c){if(c&&"undefined"!=typeof c.needcoppa){v=0!=c.needcoppa;var f=a(".birth-date-wrapper"),l=a(".signup-content");if(2==c.needcoppa){g||(vBulletin.error("error","registration_coppa_fail"),f.addClass("h-hide"));l.addClass("h-hide");
a(".coppafail_notice").removeClass("h-hide");return}var m=a(".js-registration__paidsubscription"),k=1==c.needcoppa||!e;c=1==c.needcoppa;e&&g&&!k&&(a("#regMonth").val(0),a("#regDay").val(0),a("#regYear").val(0));f.toggleClass("h-hide",k);l.removeClass("h-hide");m.toggleClass("h-hide-imp",c);m.find("select,input").prop("disabled",c);k?(a("#regContent").removeClass("h-hide"),a("#frmRegister .js-button-group").removeClass("h-hide")):a(".registration-widget select").selectBox()}a(".coppa")[v?"removeClass":
"addClass"]("h-hide-imp")},error_phrase:"registration_start_failed"}))}function q(b,h){return b?(vBulletin.warning("error",b,function(){h.trigger("focus")}),!1):!0}function D(){if(w){var b={"#regMonth":"please_select_a_month","#regDay":"please_select_a_day","#regYear":"please_select_a_year"};for(var h in b)if(0==a(h).val())return q(b[h],a(h).next(".selectBox"))}h=function(e,c,f,l,m,k){var p=a(e);if(p.length){e=p.val();if(""==a.trim(e))return q(c,p);if(f&&!f(e))return q(l,p);if(m&&(c=a(m),c.length&&
e!=c.val()))return q(k,c)}return!0};b=[["#regDataUsername","please_enter_a_username"],["#regDataEmail","please_enter_your_email_address",isValidEmailAddress,"invalid_email_address","#regDataEmailConfirm","email_addresses_must_match"]];v&&b.push(["#parentGuardianEmail","please_enter_your_parent_or_guardians_email_address",isValidEmailAddress,"invalid_email_address"]);for(var d=0;d<b.length;d++)if(!h.apply(window,b[d]))return!1;if(!vBulletin.checkPassword(a("#regDataPassword"),a("#regDataConfirmpassword")))return!1;
b="";if(!a("#cbApproveTerms").is(":checked")){b="register_not_agreed";var g=a("#cbApproveTerms")}A&&!n&&(b="paid_subscription_required",g=a(".cost"));n&&!t&&(b="please_select_a_payment_method",g=a("input.paymentapi").first());return q(b,g)}function E(){var b=this;console.log("Paid Subscriptions Data: subscriptionid: "+n+"; subscriptionsubid: "+x+"; paymentapiclass: "+t+"; currency: "+u);if(!D())return!1;a(".js-button-group .js-button",b).prop("disabled",!0);var h=a(b).serializeArray();vBulletin.AJAX({call:"/registration/registration",
data:h,success:function(d){if(d.usecoppa)location.replace(d.urlPath);else{d.newtoken&&"guest"!=d.newtoken&&vBulletin.doReplaceSecurityToken(d.newtoken);var g=function(){vBulletin.alert("registration_gregister",d.msg,null,function(){d.urlPath?location.replace(d.urlPath):(a(".signup-success").removeClass("h-hide"),a(".signup-content").addClass("h-hide"))})};vBulletin.ensureObj("paypal2");vBulletin.paypal2.regfinal=g;if(n){var e=openConfirmDialog({title:vBulletin.phrase.get("paid_subscriptions"),message:vBulletin.phrase.get("loading")+
"...",width:500,dialogClass:"paidsubscription-dialog loading",buttonLabel:{yesLabel:vBulletin.phrase.get("order"),noLabel:vBulletin.phrase.get("cancel")},onClickYesWithAjax:!0,onClickYes:function(){a(this).closest(".paidsubscription-dialog").find("form").submit()},onClickNo:function(){A?vBulletin.warning("error","paid_subscription_required",function(){e.dialog("open")}):g()}});vBulletin.AJAX({call:"/ajax/api/paidsubscription/placeorder",data:{subscriptionid:n,subscriptionsubid:x,paymentapiclass:t,
currency:u,context:"registration"},complete:function(){a("body").css("cursor","auto")},success:function(c){let f=a(".dialog-content .message",e);a(".paidsubscription-dialog").removeClass("loading");f.html(c);f.trigger("vb-instrument");a('.dialog-content .message input[type="submit"], .dialog-content .message .js-subscription__cancel',e).hide();e.dialog("option","position",{of:window})},error_message:"error_payment_form"})}else g()}},api_error:vBulletin.hv.resetOnError,error_phrase:"registration_start_failed",
complete:function(){a(".js-button-group .js-button",b).prop("disabled",!1)}})}var w=parseInt(window.vBulletin.options.get("usecoppa")),v=!1,y=!1,F=parseInt(window.vBulletin.options.get("reqbirthday")),A=parseInt(window.vBulletin.options.get("regrequirepaidsub")),n,x,t,u;(function(){a("#frmRegister").trigger("reset");setTimeout(vBulletin.hv.reset,0);if(w||F){var b=vBulletin.storagetools.getCookie("coppaage"),h=!1;if(w&&b&&parseInt(vBulletin.options.get("checkcoppa"))){if(b=b.split("-",3),3==b.length){var d=
parseInt(b[0]);var g=parseInt(b[1]);var e=parseInt(b[2]);0!=d&&0!=g&&0!=e&&(h=!0,a("#regMonth").val(d),a("#regDay").val(g),a("#regYear").val(e));z(d,g,e,h,!0)}}else a(".signup-content").removeClass("h-hide");a("#regMonth, #regDay, #regYear").off("change").on("change",function(){d=a("#regMonth").val();g=a("#regDay").val();e=a("#regYear").val();z(d,g,e,h)})}else a(".signup-content").removeClass("h-hide");a(".registration-widget select").selectBox();a("#regDataUsername").off("keydown blur").on("keydown",
function(f){13==f.keyCode&&a(this).triggerHandler("blur");return!0}).on("blur",function(){if(""==a.trim(this.value))return!0;y=!0;var f=this;vBulletin.AJAX({call:"/registration/checkusername",data:{username:a.trim(f.value)},success:function(l){},complete:function(){y=!1},after_error:function(){f.value="";f.select();f.focus()}})});var c=a(".paidsubscription_row");0<c.length&&a("select.cost",c).change(function(){var f=a(this).closest(".newsubscription_row"),l=a(this).find("option:selected").first(),
m=a(this).closest(".subscriptions_list"),k=a(".order_confirm",c);a(".payment-form",c);var p=f.data("allowedapis"),G=l.data("recurring"),B=0,C;n=f.data("id");x=l.data("subid");u=l.data("currency");a('<tr class="confirm_data"><td>'+l.data("subtitle")+"</td><td>"+l.data("duration")+"</td><td>"+l.data("value")+"</td></tr>").appendTo(a(".order_confirm_table",k));m.addClass("h-hide");k.off("click",".remove_subscription").on("click",".remove_subscription",function(){a(".confirm_data",k).remove();a("input.paymentapi",
k).closest("label").removeClass("h-hide");a(".subscriptions-order",k).prop("disabled",!1);a("select.cost",m).selectBox("value","");k.addClass("h-hide");m.removeClass("h-hide");n=0;return!1});k.off("click","input.paymentapi").on("click","input.paymentapi",function(){t=a("input.paymentapi:checked",k).val()});a("input.paymentapi",k).each(function(){var r=a(this),H=r.data("currency").split(","),I=r.data("recurring");-1==a.inArray(u,H)||-1==a.inArray(r.val(),p)||G&&!I?r.closest("label").addClass("h-hide"):
(B++,C=r)});1==B&&C.click();k.removeClass("h-hide")})})();a("#frmRegister").off("submit.usersignup").on("submit.usersignup",function(){var b=this;setTimeout(function(){y||E.apply(b)},10);return!1});a("#regBtnReset").off("click").on("click",function(b){vBulletin.hv.reset();setTimeout(function(){a(".registration-widget select").selectBox("refresh");a("#regMonth").next(".selectBox").trigger("focus")},50)})})})(jQuery);
;

// ***************************
// js.compressed/paidsubscription.js
// ***************************
vBulletin.precache("paymentapi payment_api_error paymentapi_paymentsubmitted paypal2_paymentsuccess paypal2_paymentsuccess_reg paypal2_waitingforwebhook".split(" "),[]);vBulletin.ensureObj("paypal2");
((l,h,e)=>{function u(a,q,n){function p(b,m,v,w){b={style:{tagline:!1},onError(d){g()}};let t=r;f.data("recurring")?(b.createSubscription=function(d,k){return f.data("subscriptionid")},b.onApprove=async function(d){await m.AJAX({call:"/ajax/api/paidsubscription/completePaypalSubscription",data:{order_id:d.orderID,subscription_id:d.subscriptionID},success(k,x,y){k.success&&(m.dialogtools.alert("paymentapi_paymentsubmitted","paypal2_waitingforwebhook"),t())}})}):(b.createOrder=async function(d,k){d=
await m.AJAX({call:"/ajax/api/paidsubscription/preparePayPalOrder",data:c.serializeArray()});if(d.id)return d.id},b.onApprove=async function(d){await m.AJAX({call:"/ajax/api/paidsubscription/completePayPalOrder",data:{order_id:d.orderID},success(k,x,y){console.log(k);k.success&&(m.dialogtools.alert("paymentapi_paymentsubmitted",isRegistration?"paypal2_paymentsuccess_reg":"paypal2_paymentsuccess"),t())}})});v.Buttons(b).render(w)}const f=l(n),c=f.closest("form"),g=()=>{h.dialogtools.alert("paymentapi",
"payment_api_error","error")};isRegistration="registration"===f.data("context");let r;if(isRegistration){let b=c.closest("#confirm-dialog");(b&&b.find("#btnConfirmDialogYes")).hide();r=()=>{f.hide();b.dialog("close");h.ensureMethod(e,"regfinal")()}}else c.find(":submit:enabled").hide(),r=()=>{f.hide()};h.ready("paypal2").then(()=>z(h,a,q)).then(b=>p(jQuery,h,window[b],n)).catch(b=>{console.log(b);g()})}function z(a,q,n){return new Promise((p,f)=>{let c="paypal2_"+q.toUpperCase()+(n?"_recurring":"_onetime");
a.ensureObj("urls",{},e);a.ensureObj("urls_loaded",{},e);if(e.urls.hasOwnProperty(c))if(e.urls_loaded.hasOwnProperty(c))p(c);else{e.urls_loaded[c]=!0;let g=document.createElement("script");g.onload=()=>p(c);g.onerror=()=>f("Failed to load PayPal JS for "+c+" :"+e.urls[c]);g.setAttribute("src",e.urls[c]);g.setAttribute("data-namespace",c);document.head.appendChild(g)}else f("Failed to find JS URL cache for "+c)})}l(document).on("vb-instrument",a=>{a=l(a.target).find(".js-paypal2-btn-container");0<
a.length&&!a.data("paypal2-initialized")&&(a.data("paypal2-initialized",!0),u(a.data("currency"),a.data("recurring"),"#"+a.attr("id")))});l(()=>{let a=l(".js-paypal2-urls");0<a.length&&(e.urls=a.data("paypalUrls"),h.ready("paypal2").resolve())})})(jQuery,vBulletin,vBulletin.paypal2);
;

