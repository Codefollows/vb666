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
vBulletin.precache(["blog_subscribers_list","blog_subscribers","unable_to_contact_server_please_try_again"],[]);
(function(c){if(!vBulletin.pageHasSelectors([".summary-widget",".blogadmin-widget"]))return!1;var q=function(){c("#blogSubscribersSeeAll").off("click").on("click",function(e){l(c(this).attr("data-node-id"));e.stopPropagation();return!1})},b,l=function(e,g,h){b=c("#blogSubscribersAll").dialog({title:vBulletin.phrase.get("blog_subscribers_list"),autoOpen:!1,modal:!0,resizable:!1,closeOnEscape:!1,showCloseButton:!1,width:450,dialogClass:"dialog-container dialog-box blog-subscribers-dialog"});vBulletin.pagination({context:b,
onPageChanged:function(a,k){l(e,a)}});b.off("click",".blog-subscribers-close").on("click",".blog-subscribers-close",function(){b.dialog("close")});b.off("click",".action_button").on("click",".action_button",function(){if(!c(this).hasClass("subscribepending_button")){var a=c(this),k=parseInt(a.attr("data-userid"),10),d="";a.hasClass("subscribe_button")?d="add":a.hasClass("unsubscribe_button")&&(d="delete");"number"==typeof k&&d&&vBulletin.AJAX({call:"/profile/follow-button",data:{"do":d,follower:k,
type:"follow_members"},success:function(f){if(1==f||2==f){if("add"==d){var m="subscribe_button b-button b-button--special";var n=(1==f?"subscribed":"subscribepending")+"_button b-button b-button--secondary";var p=1==f?"following":"following_pending";a.attr("disabled","disabled")}else"delete"==d&&(m="subscribed_button unsubscribe_button b-button b-button--special",n="subscribe_button b-button b-button--secondary",p="follow");a.removeClass(m).addClass(n).text(vBulletin.phrase.get(p))}},title_phrase:"profile_guser",
error_phrase:"unable_to_contact_server_please_try_again"})}});g||(g=1);h||(h=10);vBulletin.AJAX({call:"/ajax/render/subscribers_list",data:{nodeid:e,page:g,perpage:h},success:function(a){b.dialog("close");c(".blog-subscribers-content",b).html(a);b.dialog("open")},title_phrase:"blog_subscribers",error_phrase:"unable_to_contact_server_please_try_again"})};c(document).ready(function(){q()})})(jQuery);
