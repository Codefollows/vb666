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
vBulletin.precache("paymentapi payment_api_error paymentapi_paymentsubmitted paypal2_paymentsuccess paypal2_paymentsuccess_reg paypal2_waitingforwebhook".split(" "),[]);vBulletin.ensureObj("paypal2");
((l,h,e)=>{function u(a,q,n){function p(b,m,v,w){b={style:{tagline:!1},onError(d){g()}};let t=r;f.data("recurring")?(b.createSubscription=function(d,k){return f.data("subscriptionid")},b.onApprove=async function(d){await m.AJAX({call:"/ajax/api/paidsubscription/completePaypalSubscription",data:{order_id:d.orderID,subscription_id:d.subscriptionID},success(k,x,y){k.success&&(m.dialogtools.alert("paymentapi_paymentsubmitted","paypal2_waitingforwebhook"),t())}})}):(b.createOrder=async function(d,k){d=
await m.AJAX({call:"/ajax/api/paidsubscription/preparePayPalOrder",data:c.serializeArray()});if(d.id)return d.id},b.onApprove=async function(d){await m.AJAX({call:"/ajax/api/paidsubscription/completePayPalOrder",data:{order_id:d.orderID},success(k,x,y){console.log(k);k.success&&(m.dialogtools.alert("paymentapi_paymentsubmitted",isRegistration?"paypal2_paymentsuccess_reg":"paypal2_paymentsuccess"),t())}})});v.Buttons(b).render(w)}const f=l(n),c=f.closest("form"),g=()=>{h.dialogtools.alert("paymentapi",
"payment_api_error","error")};isRegistration="registration"===f.data("context");let r;if(isRegistration){let b=c.closest("#confirm-dialog");(b&&b.find("#btnConfirmDialogYes")).hide();r=()=>{f.hide();b.dialog("close");h.ensureMethod(e,"regfinal")()}}else c.find(":submit:enabled").hide(),r=()=>{f.hide()};h.ready("paypal2").then(()=>z(h,a,q)).then(b=>p(jQuery,h,window[b],n)).catch(b=>{console.log(b);g()})}function z(a,q,n){return new Promise((p,f)=>{let c="paypal2_"+q.toUpperCase()+(n?"_recurring":"_onetime");
a.ensureObj("urls",{},e);a.ensureObj("urls_loaded",{},e);if(e.urls.hasOwnProperty(c))if(e.urls_loaded.hasOwnProperty(c))p(c);else{e.urls_loaded[c]=!0;let g=document.createElement("script");g.onload=()=>p(c);g.onerror=()=>f("Failed to load PayPal JS for "+c+" :"+e.urls[c]);g.setAttribute("src",e.urls[c]);g.setAttribute("data-namespace",c);document.head.appendChild(g)}else f("Failed to find JS URL cache for "+c)})}l(document).on("vb-instrument",a=>{a=l(a.target).find(".js-paypal2-btn-container");0<
a.length&&!a.data("paypal2-initialized")&&(a.data("paypal2-initialized",!0),u(a.data("currency"),a.data("recurring"),"#"+a.attr("id")))});l(()=>{let a=l(".js-paypal2-urls");0<a.length&&(e.urls=a.data("paypalUrls"),h.ready("paypal2").resolve())})})(jQuery,vBulletin,vBulletin.paypal2);
