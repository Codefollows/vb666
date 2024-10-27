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
// js.compressed/memberchannel.js
// ***************************
vBulletin.memberChannel=function(d){function n(a,b,e){a=a.filter(b).toggleClass("h-disabled",!e);e?a.attr("href",e):a.removeAttr("href")}function p(a,b){function e(c){var g=c.innerWidth(),h=parseInt(c.css("paddingLeft"),10);c=parseInt(c.css("paddingRight"),10);return Math.floor(g-h-c)}function f(c,g){var h=!!g;c.width(g);c.toggleClass("b-groupgrid__item-inner--expanded",h);g=d(".js-groupgrid-item-icon",l).outerWidth();l=d(".js-groupgrid-item-icon-link",l).outerWidth();h&&l>g&&c.removeClass("b-groupgrid__item-inner--expanded");
var l=c.first();h=e(l)-1;c.find(".js-groupgrid-item-icon-link").width(h).height(h)}if(a.length&&(f(a,""),b)){b=a.first();var k=e(b.closest(".b-groupgrid__item")),m=Math.ceil(b.outerWidth(!0))-e(b);b.closest(".b-groupgrid__item").is(".b-flexgrid__item--lastrow")||(a.each(function(){var c=d(this).closest(".b-groupgrid__item");if(c.is(".b-flexgrid__item--lastrow"))return!0;k=Math.min(k,e(c))}),f(a,k-m-1))}}function q(a,b){a=d(".b-groupgrid__item-inner");b&&b.isResize&&a.css({opacity:"0"});p(a,!1)}function r(a,
b){a=d(".b-groupgrid__item-inner");p(a,!0);b&&b.isResize&&a.removeClass("h-tranparent").animate({opacity:"1"},400)}return{updateToolbarButtonPressState:function(a){function b(f){var k=f.data("js-movable-button-clone-id");return d(".js-movable-toolbar-button-clone").filter(function(){return d(this).data("js-movable-button-clone-id")==k})}if(!a.find(".js-button-filter-display-blogs.js-checked").length){var e=a.find(".js-display-blogs-state").data("display-blogs-state");a.find(".js-button-filter-display-blogs").filter('[data-filter-value="{0}"]'.format(e)).addClass("js-checked")}a.find(".js-button-filters .js-button-filter").removeClass("b-button--pressed").each(function(){b(d(this)).removeClass("b-button--pressed")}).filter(".js-checked").addClass("b-button--pressed").each(function(){b(d(this)).addClass("b-button--pressed")})},
updateToolbarPagination:function(a){var b=d(".conversation-toolbar-wrapper .pagenav-controls .pagenav-form",a),e=d(".arrow",b).removeClass("h-disabled"),f=a.find(".js-membergroup-pagination-info"),k=f.find(".js-prevpage").val(),m=f.find(".js-nextpage").val(),c=f.find(".js-pagenum").val()||1;f=f.find(".js-totalpages").val()||1;var g=a.find(".js-under-toolbar-pagenav .js-pagenav");a=a.find(".js-pagenav").not(g);d(".js-pagenum",b).val(c);d(".pagetotal",b).text(f);n(e,"[rel=prev]",k);n(e,"[rel=next]",
m);0==a.length?g.html(""):g.html(a.clone().html())},initFlexGridAdjustments:function(a){a.on("vbulletin:flexgridstart",".js-flexgrid",q).on("vbulletin:flexgridready",".js-flexgrid",r)}}}(jQuery);
;

// ***************************
// js.compressed/sb_blogs.js
// ***************************
vBulletin.precache(["create_a_blog","error_creating_user_blog_channel","error_fetching_user_blog_channels","select_a_blog"],[]);
(function(a){function l(n){var h=a(this).closest(".canvas-widget").data("blog-channel-id");vBulletin.AJAX({call:"/ajax/api/user/getGitCanStart",data:{parentNodeId:h},success:function(d){if(a.isArray(d)){var k=d.length;if(1<k){var e=a("#user-blogs-dialog"),f=a("select.custom-dropdown",e);a.each(d,function(m,b){a("<option />").val(b.nodeid).html(b.title).appendTo(f)});e.dialog({title:vBulletin.phrase.get("select_a_blog"),autoOpen:!1,modal:!0,resizable:!1,closeOnEscape:!1,showCloseButton:!1,width:500,
dialogClass:"dialog-container create-blog-dialog-container dialog-box",open:function(){f.removeClass("h-hide").selectBox()},close:function(){f.selectBox("destroy").find("option").remove()},create:function(){a(".btnContinue",this).on("click",function(){location.href="{0}/new-content/{1}".format(pageData.baseurl,a("select.custom-dropdown",e).val())});a(".btnCancel",this).on("click",function(){e.dialog("close")})}}).dialog("open")}else 1==k?location.href="{0}/new-content/{1}".format(pageData.baseurl,
d[0].nodeid):vBulletin.AJAX({call:"/ajax/api/blog/canCreateBlog",data:{parentid:h},success:function(m){var b=a("#create-blog-dialog").dialog({title:vBulletin.phrase.get("create_a_blog"),autoOpen:!1,modal:!0,resizable:!1,closeOnEscape:!1,showCloseButton:!1,width:500,dialogClass:"dialog-container create-blog-dialog-container dialog-box",create:function(){vBulletin.ajaxForm.apply(a("form",this),[{success:function(c,g,p,q){a.isPlainObject(c)&&0<Number(c.nodeid)?location.href="{0}/new-content/{1}".format(pageData.baseurl,
c.nodeid):vBulletin.error("error","error_creating_user_blog_channel")},error_phrase:"error_creating_user_blog_channel"}]);a(".btnCancel",this).on("click",function(){b.dialog("close")});a(".blog-adv-settings",this).on("click",function(){var c=a.trim(a(".blog-title",b).val()),g=a.trim(a(".blog-desc",b).val());return c||g?(location.href="{0}?blogtitle={1}&blogdesc={2}".format(this.href,encodeURIComponent(c),encodeURIComponent(g)),!1):!0})},open:function(){a("form",this).trigger("reset")}}).dialog("open")},
title_phrase:"create_a_blog"})}else vBulletin.error("error","error_fetching_user_blog_channels")},error_phrase:"error_fetching_user_blog_channels"})}if(!vBulletin.pageHasSelectors([".bloghome-widget"]))return!1;a(function(){a(".bloghome-widget").offon("click",".conversation-toolbar .new-conversation-btn",l);vBulletin.memberChannel.initFlexGridAdjustments(a(".bloghome-widget"))})})(jQuery);
;

// ***************************
// js.compressed/blog_summary.js
// ***************************
vBulletin.precache(["blog_subscribers_list","blog_subscribers","unable_to_contact_server_please_try_again"],[]);
(function(c){if(!vBulletin.pageHasSelectors([".summary-widget",".blogadmin-widget"]))return!1;var q=function(){c("#blogSubscribersSeeAll").off("click").on("click",function(e){l(c(this).attr("data-node-id"));e.stopPropagation();return!1})},b,l=function(e,g,h){b=c("#blogSubscribersAll").dialog({title:vBulletin.phrase.get("blog_subscribers_list"),autoOpen:!1,modal:!0,resizable:!1,closeOnEscape:!1,showCloseButton:!1,width:450,dialogClass:"dialog-container dialog-box blog-subscribers-dialog"});vBulletin.pagination({context:b,
onPageChanged:function(a,k){l(e,a)}});b.off("click",".blog-subscribers-close").on("click",".blog-subscribers-close",function(){b.dialog("close")});b.off("click",".action_button").on("click",".action_button",function(){if(!c(this).hasClass("subscribepending_button")){var a=c(this),k=parseInt(a.attr("data-userid"),10),d="";a.hasClass("subscribe_button")?d="add":a.hasClass("unsubscribe_button")&&(d="delete");"number"==typeof k&&d&&vBulletin.AJAX({call:"/profile/follow-button",data:{"do":d,follower:k,
type:"follow_members"},success:function(f){if(1==f||2==f){if("add"==d){var m="subscribe_button b-button b-button--special";var n=(1==f?"subscribed":"subscribepending")+"_button b-button b-button--secondary";var p=1==f?"following":"following_pending";a.attr("disabled","disabled")}else"delete"==d&&(m="subscribed_button unsubscribe_button b-button b-button--special",n="subscribe_button b-button b-button--secondary",p="follow");a.removeClass(m).addClass(n).text(vBulletin.phrase.get(p))}},title_phrase:"profile_guser",
error_phrase:"unable_to_contact_server_please_try_again"})}});g||(g=1);h||(h=10);vBulletin.AJAX({call:"/ajax/render/subscribers_list",data:{nodeid:e,page:g,perpage:h},success:function(a){b.dialog("close");c(".blog-subscribers-content",b).html(a);b.dialog("open")},title_phrase:"blog_subscribers",error_phrase:"unable_to_contact_server_please_try_again"})};c(document).ready(function(){q()})})(jQuery);
;

