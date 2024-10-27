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
(function(b){function f(a){if("1"!=a.data("vb-tabbedcontainer-tabs-initialized")){var d=a.data("default-tab")||0;a.tabs({create:function(c,e){a.find(".js-tabs-loading-placeholder").remove();a.find(".js-show-on-tabs-create").removeClass("h-hide");a.tabs("option","active",d)},activate:function(c,e){e.newPanel.find(".js-parent-tab-render-listener").trigger("parent-tab-render")}});a.trigger("vb-tabinit");a.attr("data-vb-tabbedcontainer-tabs-initialized","1")}}b(function(){var a=b(".js-tabbedcontainer-widget-tab-wrapper");
a.length&&b.each(a,function(d,c){f(b(c))})})})(jQuery);
