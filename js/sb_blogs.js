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
vBulletin.precache(["create_a_blog","error_creating_user_blog_channel","error_fetching_user_blog_channels","select_a_blog"],[]);
(function(a){function l(n){var h=a(this).closest(".canvas-widget").data("blog-channel-id");vBulletin.AJAX({call:"/ajax/api/user/getGitCanStart",data:{parentNodeId:h},success:function(d){if(a.isArray(d)){var k=d.length;if(1<k){var e=a("#user-blogs-dialog"),f=a("select.custom-dropdown",e);a.each(d,function(m,b){a("<option />").val(b.nodeid).html(b.title).appendTo(f)});e.dialog({title:vBulletin.phrase.get("select_a_blog"),autoOpen:!1,modal:!0,resizable:!1,closeOnEscape:!1,showCloseButton:!1,width:500,
dialogClass:"dialog-container create-blog-dialog-container dialog-box",open:function(){f.removeClass("h-hide").selectBox()},close:function(){f.selectBox("destroy").find("option").remove()},create:function(){a(".btnContinue",this).on("click",function(){location.href="{0}/new-content/{1}".format(pageData.baseurl,a("select.custom-dropdown",e).val())});a(".btnCancel",this).on("click",function(){e.dialog("close")})}}).dialog("open")}else 1==k?location.href="{0}/new-content/{1}".format(pageData.baseurl,
d[0].nodeid):vBulletin.AJAX({call:"/ajax/api/blog/canCreateBlog",data:{parentid:h},success:function(m){var b=a("#create-blog-dialog").dialog({title:vBulletin.phrase.get("create_a_blog"),autoOpen:!1,modal:!0,resizable:!1,closeOnEscape:!1,showCloseButton:!1,width:500,dialogClass:"dialog-container create-blog-dialog-container dialog-box",create:function(){vBulletin.ajaxForm.apply(a("form",this),[{success:function(c,g,p,q){a.isPlainObject(c)&&0<Number(c.nodeid)?location.href="{0}/new-content/{1}".format(pageData.baseurl,
c.nodeid):vBulletin.error("error","error_creating_user_blog_channel")},error_phrase:"error_creating_user_blog_channel"}]);a(".btnCancel",this).on("click",function(){b.dialog("close")});a(".blog-adv-settings",this).on("click",function(){var c=a.trim(a(".blog-title",b).val()),g=a.trim(a(".blog-desc",b).val());return c||g?(location.href="{0}?blogtitle={1}&blogdesc={2}".format(this.href,encodeURIComponent(c),encodeURIComponent(g)),!1):!0})},open:function(){a("form",this).trigger("reset")}}).dialog("open")},
title_phrase:"create_a_blog"})}else vBulletin.error("error","error_fetching_user_blog_channels")},error_phrase:"error_fetching_user_blog_channels"})}if(!vBulletin.pageHasSelectors([".bloghome-widget"]))return!1;a(function(){a(".bloghome-widget").offon("click",".conversation-toolbar .new-conversation-btn",l);vBulletin.memberChannel.initFlexGridAdjustments(a(".bloghome-widget"))})})(jQuery);
