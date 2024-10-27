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
vBulletin.precache(["error","pmchat_close_button_label","pmchat_idle_disconnected_message","pmchat_idle_disconnected_title","pmchat_reconnect_button_label"],["pmchat_enabled","pmchat_header_polling_interval","pmchat_chat_polling_idle_timeout","pmchat_chat_polling_min_interval","pmchat_chat_polling_max_interval"]);
(function(a){function ea(g){g=g.hasOwnProperty("messageid")?"?messageid="+encodeURIComponent(g.messageid):"?aboutNodeid="+encodeURIComponent(g.aboutNodeid)+"&toUserid="+encodeURIComponent(g.toUserid);return""==g?"#":la+g}function fa(g){var v;g=g.hasOwnProperty("url")?g.url:ea(g);"#"!=g&&((v=window.open(g,"_blank","width=600,height=700,resizable=yes,scrollbars=yes,status=yes"))&&!v.closed&&"undefined"!=typeof v.closed||window.open(g+queryString))}function ma(){function g(h){var f=h[Object.keys(h)[0]];
f=f&&f.orderListForJs||[];var t=f.length,z=!1,w={};if(0<t)J.find(".js-pmchat__submenu__ephemeral").remove();else return!1;for(var C=0;C<t;C++){var u=f[C];if(h.hasOwnProperty(u)){var p=h[u];var m=q.clone(!0);var U=m.find(".js-pmchat__submenu__link"),D="js-pmchat__messagefinder__"+u;m.removeClass("h-hide-imp");U.attr("data-starter",u).prop("title",p.previewtext).attr("href",ea({messageid:u}));m.find(".js-pmchat__submenu__title").html(p.title);0==p.msgread&&m.addClass("b-comp-menu-dropdown__content-item--unread");
m.addClass(D);m.insertBefore(P);(m=0<L&&(!x.hasOwnProperty(u)||x[u].publishdate!=p.publishdate||x[u].userid!=p.userid)&&0==p.msgread&&p.userid!=pageData.userid)&&(z=!0);w[u]={publishdate:p.publishdate,userid:p.userid,title:p.title,previewtext:p.previewtext,findmehelper:D,isNew:m}}}x=w;L=E;z&&v()}function v(){console.log("New Messages found in polling! Notifying user");if(A.find(".js-comp-menu-dropdown").hasClass("b-comp-menu-dropdown--open")){var h=[];for(f in x)x.hasOwnProperty(f)&&x[f].isNew&&h.push("."+
x[f].findmehelper);if(0<h.length){var f=h.join(", ");var t=a(f),z=setInterval(function(){t.toggleClass("b-comp-menu-dropdown__content-item--unread-alert")},100);setTimeout(function(){clearInterval(z);t.removeClass("b-comp-menu-dropdown__content-item--unread-alert")},3E3)}}else A.find(".js-comp-menu-dropdown").addClass("b-comp-menu-dropdown--alert")}function V(){r+=60;r>=F&&K()}function K(){clearTimeout(W);r>=F?console.log("Header polling stopped due to idle timeout. (idle time: "+r+"s)"):W=setTimeout(X,
1E3*k)}function X(){K();console.log("Polled for header data!"+Date.now());M()}function Q(h,f,t){h.length&&(h.text()!=f&&(h.text(f),h.toggleClass("h-hide-imp",0>=f)),t&&h.parents().eq(1).toggleClass("h-hide-imp",0>=f))}function M(h){G?"function"==typeof h&&N&&N.always(h):(G=!0,O.removeClass("h-hide-imp"),vBulletin.loadingIndicator.suppressNextAjaxIndicator(),N=vBulletin.AJAX({call:"/chat/loadheaderdata",success:function(f){var t=f.headerCounts;Q(R,t.messages);Q(Y,t.totalcount);aa.each(function(){var z=
a(this),w=z.data("folderid")||0;Q(z,t.details&&t.details[w]&&t.details[w].count||0,!0)});a.isEmptyObject(f.messages)||g(f.messages)},api_error:vBulletin.ajaxtools.logApiError,error:vBulletin.ajaxtools.logAjaxError,complete:[function(){G=!1;O.addClass("h-hide-imp");E=Date.now()},h]}))}var A=a(".js-pmchat__dropdown"),R=A.find(".js-pmchat__messages-count"),Y=a(".notifications-count").not(".js-pmchat__messages-count").not("[data-folderid]"),aa=a(".notifications-count[data-folderid]").not(".js-pmchat__messages-count"),
J=A.find(".js-pmchat__dropdown-submenu"),q=J.find(".js-pmchat__dropdown__single-pm-template").removeClass("js-pmchat__dropdown__single-pm-template"),P=J.find(".js-pmchat__insert-marker"),O=a(".js-pmchat__dropdown-submenu").find(".js-pmchat__submenu__loading-icon");A.find(".js-pmchat__header-data");var k=vBulletin.options.get("pmchat_header_polling_interval")||30,r=0,F=vBulletin.options.get("pmchat_chat_polling_idle_timeout")||300,E=0,x={},L=0,G=!1,N=null,ba=a.debounce(100,function(){"function"==typeof vBulletin.CompMenuDropdown.updateMenuFormat&&
vBulletin.CompMenuDropdown.updateMenuFormat()});J.off("click").on("click",".js-pmchat__submenu__link",function(){var h={},f=a(this).attr("href");"#"!=f?h.url=f:h.messageid=a(this).data("starter");fa(h);a(this).parent().removeClass("b-comp-menu-dropdown__content-item--unread");return!1});a(".js-pmchat-reload-header").on("click",function(h){A.find(".js-comp-menu-dropdown").removeClass("b-comp-menu-dropdown--alert");r=0;Date.now()-E<1E3*k?console.log("PM Load aborted: less than "+k+" seconds since last load."):
(K(),M(ba))});(function(){console.log("Idle detection enabled");setInterval(V,6E4);a(document).on("click keypress scroll",function(h){r=0})})();var W=setTimeout(X,1E3*k)}function na(){function g(){var d=a(".js-pmchat__insert-marker").offset().top,c=a(".js-pm-content-entry-container"),e=c.outerHeight();c="fixed"==c.css("position");var b=a(window).outerHeight();(!c||d>b-e)&&vBulletin.animateScrollTop(d)}function v(d){if(f)console.log("Nothing to load, awaiting first message.");else if(B.messagesLoading)console.log("loadNewMessages() rejected: Still awaiting messages from a previous call.");
else{console.log("loadNewMessages() executing.");var c=[];O.find(".js-pmchat__post-wrapper").each(function(e,b){e=parseInt(a(b).data("publishdate")||0,10);var l=parseInt(a(b).data("nodeid")||0,10);e>G&&(G=e,N=l);0<l&&c.push(l);G>e&&a(b).removeClass("js-pmchat__post-wrapper")});B.messagesLoading=!0;vBulletin.loadingIndicator.suppressNextAjaxIndicator();vBulletin.AJAX({call:"/chat/loadnewmessages",data:{parentid:F,newreplyid:d,lastpublishdate:G,lastnodeid:N,loadednodes:c},success:function(e){S=(S+1)%
10;e.html&&""!==e.html?(T[S]=1,D=Date.now(),console.log("loadNewMessages(): Inserting new messages."),a(e.html).insertBefore(ba),g()):(T[S]=-1,console.log("loadNewMessages(): No new messages."))},error:function(e){console.log("loadNewMessages(): Error loading new PMs. Ajax result:");console.log(e)},complete:function(e){window.vBulletin.loadingIndicator.hide();B.messagesLoading=!1;B.queued&&(B.queued=!1,window.vBulletin.loadingIndicator.show(),setTimeout(v,0));V()}})}}function V(){clearTimeout(z);
var d=Date.now(),c=d-D,e=d-t,b=0<T[S],l=T.reduce(function(n,y){return n+y})/10;0<D?b?(m>C&&(m=C),--m,D>w&&(w=D)):m-=1*l:m+=1;m<u?m=u:m>p&&(m=p);t=d;Z&&(Z=!1,w=d);b=(d-w)/1E3;console.log("Idle time: "+b+"s. (timeout: "+U+"s)");b>=U?(console.log("Polling for messages stopped due to idle timeout. Idle start time: "+w+" time now: "+d),X(),w=d):(z=setTimeout(K,1E3*m),console.log("Polling for messages complete. Next poll in "+m+"seconds. Time since last poll: "+e/1E3+"s, since last hit: "+c/1E3+"s."))}
function K(){V();v()}function X(){if(null==h){var d={title:vBulletin.phrase.get("pmchat_idle_disconnected_title"),message:vBulletin.phrase.get("pmchat_idle_disconnected_message"),width:"50%",buttonLabel:{yesLabel:vBulletin.phrase.get("pmchat_reconnect_button_label"),noLabel:vBulletin.phrase.get("pmchat_close_button_label")},onClickYes:function(){w=Date.now();m=C;K()},onClickNo:function(){window.close()}};h=openConfirmDialog(d)}else h.dialog("open")}function Q(d){d?(oa.remove(),ha.remove(),k.attr("data-message-type",
"pm-reply"),History.replaceState({},L,pageData.baseurl_pmchat+"?messageid="+d),A(d)):console.log("Missing nodeid for loadParticipants()")}function M(){pa.trigger("event-js-element-resize")}function A(d,c){vBulletin.AJAX({call:"/chat/loadparticipants",data:{nodeid:d},success:function(e){let b=a(".js-pmchat__participants-insert-marker"),l=a(".js-pmchat__participant");e.participants_html&&""!=e.participants_html&&b.length&&(l.remove(),b.prepend(e.participants_html),ca.trigger("event-js-content-change"));
P.hasClass("h-hide")&&(P.removeClass("h-hide"),ia.trigger("click"),da.removeClass("h-hide"));e.phrase&&""!=e.phrase&&(a(".js-participants-count-phrase").text(e.phrase),a(".js-participants-count-phrase--title").prop("title",e.phrase));e.title&&""!=e.title&&a(document).find("title").html(e.title);if(c&&c.success&&"function"==typeof c.success)return c.success.apply(this,[e])},complete:function(e,b,l){if(c&&c.complete&&"function"==typeof c.complete)return c.complete.apply(this,[e,b,l])},error:function(e,
b,l){console.log("/ajax/chat/loadparticipants failed!");console.log("----------------");console.log("jqXHR:");console.dir(e);console.log("text status:");console.dir(b);console.log("error thrown:");console.dir(l);console.log("----------------")}})}function R(d){var c=a(this).attr("href");c.startsWith("#")||(d.preventDefault(),(d=window.open(c,"_blank"))&&!d.closed&&"undefined"!=typeof d.closed||window.open(c))}function Y(d){da.animate({height:"toggle"},{duration:"fast",start:function(){ca.trigger("event-js-element-resize-start")},
progress:function(c,e,b){M()},complete:function(){M()}});d.preventDefault();ja.length&&ja.toggleClass("fa-caret-down fa-caret-up")}function aa(){var d=function(n){var y=0<ca.height();if(n.data&&n.data.command)switch(n.data.command){case "open":H.removeClass("h-hide");break;case "close":H.addClass("h-hide");break;default:y?H.toggleClass("h-hide"):H.removeClass("h-hide")}else H.toggleClass("h-hide");y?M():Y(n);n.preventDefault()},c={},e=a(".js-participants-add-recipients-input"),b=new vBulletin_Autocomplete(e,
{apiClass:"user",afterAdd:function(n,y,I){"undefined"!=typeof I&&"undefined"!=typeof I.id&&I.id&&(c[y]=I.id)},delimiter:" ; "}),l=function(n){b.clearElements();n.data?n.data.command="close":n.data={command:"close"};d(n)};console.log({addRecipientsAutoComplete:b});ka.off("click").on("click",{command:"toggle"},d);H.find(".js-add-recipients-cancel").off("click").on("click",{command:"close"},l);H.find(".js-add-recipients-submit").off("click").on("click",function(n){var y=b.getInputField(),I=y.val();I&&
(y.val(""),b.addElement(I,I));y=b.getValues();vBulletin.AJAX({call:"/ajax/api/content_privatemessage/addPMRecipientsByUsernames",data:{pmid:F,usernames:y,usernamesToIds:c},success:function(ra){A(F,{success:function(){l(n);B.messagesLoading?B.queued=!0:v()},complete:function(){window.vBulletin.loadingIndicator.hide()}})}})})}function J(d){function c(){if("fixed"==b.css("position")){l.removeClass("h-hide");var n=b.outerHeight()-30;l.css("height",n+"px")}else l.addClass("h-hide")}var e=d+"-replacement",
b=a("."+d),l=a("."+e);0==l.length&&(l=a("<div />").addClass(e).insertAfter(b));a(c);a(document).off("click",c).on("click",c);vBulletin.Responsive.Debounce.registerCallback(c);CKEDITOR.on("instanceReady",function(n){c();n.editor.on("focus",function(){setTimeout(function(){c()},0)})});b.on("event-js-element-resize",c)}var q=a(".js-pmchat__container"),P=a(".js-pmchat__participants"),O=q.find(".js-pmchat__thread-container"),k=q.find("form"),r=q.find(".js-pmchat__data");q=parseInt(r.data("pmchannelid"),
10);var F=parseInt(r.data("parentid"),10),E=parseInt(r.data("pm_messageid"),10),x=parseInt(r.data("to_userid"),10),L=r.data("pm_title"),G=0,N=0,ba=a(".js-pmchat__insert-marker"),W=O.find(".js-pmchat__thread-placeholder"),h,f=!1;q!=F&&0<E||(f=!0);var t=0,z,w=Date.now(),C,u=vBulletin.options.get("pmchat_chat_polling_min_interval")||1,p=vBulletin.options.get("pmchat_chat_polling_max_interval")||30;p<u&&(q=p,p=u,u=q);var m=C=(10*u+1*p)/11;var U=vBulletin.options.get("pmchat_chat_polling_idle_timeout")||
300,D=0,S=9,T=[];for(q=0;10>q;++q)T[q]=0;var B={messagesLoading:!1,queued:!1},Z=!1,pa=a(".js-pmchat__header"),ia=a(".js-participants-collapser-ui"),ja=a(".js-participants-toggle-arrow"),da=a(".js-participants-collapsible"),ca=da.find(".js-vbscroller-wrapper"),ka=a(".js-participants-show-add-recipients-ui"),H=a(".js-participants-add-recipients-wrapper"),oa=k.find(".js-wrapper-contententry_title"),ha=k.find(".js-wrapper-contententry__msgRecipients");new vBulletin_Autocomplete(a(".privatemessage_author"),
{apiClass:"user",delimiter:" ; "});a(".js-pm-content-entry-container .js-pmchat-submit").off("click").on("click",function(d){function c(b,l,n){a("<input>").attr({type:"hidden",name:l,value:n}).appendTo(b)}var e=a(this);e.prop("disabled",!0);w=Date.now();f?(0==k.find('input[name="msgRecipients"]').length&&x?(console.log({msg:"sentto input added with prefill:",to_userid:x}),c(k,"sentto[]",x)):console.log({msg:'User editable input[name="msgRecipients"] was found. Prefilled sentto skipped.'}),0==k.find('input[name="title"]').length?
(console.log({msg:"title input added with prefill:",pm_title:L}),c(k,"title",L)):console.log({msg:'User editable input[name="title"] was found. Prefilled title skipped.'})):(d=k.find('input[name="respondto"]'),0<d.length?d.val(E):c(k,"respondto",E),0==k.find('input[name="msgtype"]').length&&c(k,"msgtype","message"));f&&W.addClass("h-hide");vBulletin.loadingIndicator.show();vBulletin.AJAX({url:k.attr("action"),data:k.serialize(),success:function(b){b.nodeId&&(b=parseInt(b.nodeId,10),f&&(k.find('input[name="parentid"]').val(b),
r.data("parentid",b),r.data("pm_messageid",b),r.attr("data-parentid",b),r.attr("data-pm_messageid",b),F=E=b,f=!1,Q(b)),console.log("==================NEW MESSAGE POSTED, LOADING NEW MESSAGES!"),B.messagesLoading?B.queued=!0:v(b),vBulletin.contentEntryBox.resetForm(k,!1,function(){var l=k.attr("ck-editorid")||a(".js-editor",k).attr("id");l=vBulletin.ckeditor.getEditor(l);vBulletin.hv.reset();l.focus()}))},api_error:vBulletin.hv.resetOnError,complete:function(b){vBulletin.loadingIndicator.hide();e.prop("disabled",
!1)}});return!1});q="a:not('.js-pmchat-ignoreanchor')";var qa=`.js-namecard-html ${q}`;vBulletin.options.get("usenamecard")&&(q+=":not([data-vbnamecard])",a("body").on("click",qa,R));O.on("click",q,R);P.on("click",q,R);a("body").on("click","a.js-pmchat-ignoreanchor",function(d){d.preventDefault()});ia.off("click").on("click",Y);0<ka.length&&0<H.length&&aa();a(".js-pm-content-entry-container .js-button").enable();g();K();(function(){a(document).on("click keypress scroll",function(c){Z=!0});var d=k.attr("ck-editorid")||
a(".js-editor",k).attr("id");a("#"+d).on("afterInit",function(){var c=vBulletin.ckeditor.getEditor(d);c.on("contentDom",function(){function e(){Z=!0}var b=c.editable();null!=b&&(b.attachListener(b,"keypress",e),b.attachListener(b,"click",e),b.attachListener(b,"focus",e))})})})();window.setTimeout(function(){var d=k.attr("ck-editorid")||a(".js-editor",k).attr("id");a("#"+d).on("afterInit",function(){var c=vBulletin.ckeditor.getEditor(d);if(c){console.log("Setting predefined starter PM text: "+r.data("pm_textprefill"));
var e=r.data("pm_textprefill");e&&(e+="&nbsp;");c.setData(e,function(){vBulletin.ckeditor.fixTableFunctionality.call(vBulletin.ckeditor,{},c);var b=ha.find(".autocompleteHelper");0<b.length?b.trigger("focus"):c.focus();e&&(b=c.createRange(),b.moveToPosition(b.root,CKEDITOR.POSITION_BEFORE_END),c.getSelection().selectRanges([b]));g()})}else console.log("Editor not found, cannot set predefined starter PM text")})},0);J("js-pm-content-entry-container");J("js-pmchat__header");(function(){var d=a("#debug-information");
if(1==d.length){function c(){a("body").is(".l-xsmall")?d.appendTo("body"):d.insertBefore(".js-pm-content-entry-container")}c();vBulletin.Responsive.Debounce.registerCallback(c)}})()}var la=pageData.baseurl_pmchat;(function(){a(function(){a("body").off("click",".js-pmchat-link").on("click",".js-pmchat-link",function(v){v.preventDefault();v={url:a(this).attr("href")};fa(v);return!1});var g=a(".js-pmchat__dropdown");0<g.length?g.attr("data-initialized")?console.log("PM Dropdown already initialized. Skipping re-init."):
(console.log("Initializing PM Dropdown!"),g.attr("data-initialized",!0),ma()):console.log("PM Dropdown not detected, skipping init.");g=a(".js-pmchat__container");0<g.length?g.attr("data-initialized")?console.log("PM Chat window already initialized. Skipping re-init."):(console.log("Initializing PM Chat window!"),g.attr("data-initialized",!0),na()):console.log("PM Chat window not detected, skipping init.")})})()})(jQuery);
