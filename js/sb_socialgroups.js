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
vBulletin.precache(["contenttype_vbforum_socialgroup","error","invalid_page_specified","social_groups_gsocialgroups"],[]);
(function(c){function v(a,b){w||(w=vBulletin.conversation.filter({context:b,autoCheck:!1,scrollToTop:a,pagination:!1,allowHistory:!1,tabParamAsQueryString:!0,beforeApplyFilters:function(){t({},!1);return!1}}))}function A(){if(c(".socialgroup-widget").hasClass("socialgroup-home-widget")){var a=vBulletin.tabtools,b=c(".activity-stream-widget"),e,f,g=b.find(".widget-tabs-nav .ui-tabs-nav > li"),h=g.filter(".ui-tabs-active"),u=h.index(),x="1"==g.parent().data("allow-history"),B=new vBulletin.history.instance(x);
-1==u&&(u=0,h=g.first());q=h.find("> a").attr("href");a.initTabs(b,B,g,x,u,q,[r],!0,function(l,n,m){l=c(m);m="#"+l.prop("id");n=q==m;m in p||(p[m]=a.tabAllowHistory(l));if("#activity-stream-tab"==m){if(r)n=e.filter,e.pagination&&e.pagination.setOption("context",l),n.setOption("context",l),"undefined"!=typeof n.lastFilters&&0<c(".conversation-empty:not(.h-hide)",l).length&&delete n.lastFilters;else{m=p[m];var C=c(".conversation-list",l);e=a.newTab(l,b,!1,!0,n,C,null,c.noop,m);r=e.filter;n&&c(this).data("noPushState",
!0)}r.applyFilters(!1,!0)}else if("#groups-tab"==m&&(v(b,c("#groups-tab")),r&&r.toggleNewConversations(!1),k||(k=vBulletin.history.instance(p[m]),n&&y(!0),z()),l=c(".conversation-empty",l),0<l.length)){if(n&&!f)return f=!0,!1;l.addClass("h-hide");t({},!c(this).data("noPushState"))}});vBulletin.conversation.bindEditFormEventHandlers("all")}}function y(a){if(k.isEnabled()){var b=k.getState();if(!b||c.isEmptyObject(b.data)){b={from:"filter_groups",page:Number(c(".pagenav-form .defaultpage",d).val())||
1,filters:{filter_groups:c(".js-button-filter.js-checked",d).data("filter-value")}};a&&(b.tab="#groups-tab");var e=vBulletin.parseQueryString(location.search);a&&"undefined"==typeof e.tab&&q&&"#groups-tab"!=q?(e.tab="groups-tab",a=location.pathname+"?"+c.param(e)):a=location.href;k.setDefaultState(b,document.title,a)}}}function z(){k.isEnabled()&&k.setStateChange(function(a,b,e){a=k.getState();if("filter_groups"==a.data.from||"pagination"==b){k.log(a.data,a.title,a.url);var f=c(".socialgroup-widget"),
g=(f=f.hasClass("ui-tabs")&&f)&&f.tabs("option","selected");g=f&&f.find(".ui-tabs-nav > li").eq(g).find("a").attr("href");!1!==g&&g!=a.data.tab?(b=f.find(".ui-tabs-nav > li").filter('li:has(a[href*="{0}"])'.format(a.data.tab)).index(),vBulletin.selectTabByIndex.call(f,b)):t({page:a.data.page,my:"pagination"==b&&e?e.filter_groups||"show_all":a.data.filters.filter_groups,display_groups:"pagination"==b&&e?e.display_groups||"display_grid":a.data.filters.display_groups},!1)}},"filter")}function D(a){vBulletin.AJAX({call:"/ajax/api/content_channel/canAddChannel",
data:{nodeid:pageData.channelid},success:function(b){!0===b.can?location.href=pageData.baseurl+"/sgadmin/create/settings":0<b.exceeded&&vBulletin.warning("social_groups_gsocialgroups","you_can_only_create_x_groups_delete")},title_phrase:"social_groups_gsocialgroups"})}function E(a){a=c(this);var b={page:1};a.is(".js-button-filter-my")?(b.my=a.data("filter-value"),b.display_groups=c(".js-button-filter-display-groups.js-checked",d).data("filter-value")):(b.my=c(".js-button-filter-my.js-checked",d).data("filter-value"),
b.display_groups=a.data("filter-value"));t(b,!0)}function t(a,b){var e=c(".conversation-list",d),f=c(".sg-groups-list",e),g=c(".js-membergroup-pagination-info",f);a.sgparent=a.sgparent||c(".js-category",g).val();a.page=a.page||c(".js-pagenum",g).val();a.perpage=a.perpage||c(".js-perpage",g).val();a.my=a.my||d.find(".js-button-filters .js-button-filter-my.js-checked").data("filter-value");a.display_groups=a.display_groups||d.find(".js-button-filters .js-button-filter-display-groups.js-checked").data("filter-value");
a.routeInfo=a.routeInfo||f.find(".sg-groups-list-container").data("route");a.sort_field=a.sort_field||d.find('.toolbar-filter-overlay input[name="filter_sort"]:checked').val()||"title";a.sort_order=a.sort_order||d.find('.toolbar-filter-overlay input[name="filter_order"]:checked').val()||"asc";c(".js-button-filters .js-button-filter-my",d).removeClass("js-checked").filter('[data-filter-value="{0}"]'.format(a.my)).addClass("js-checked");c(".js-button-filters .js-button-filter-display-groups",d).removeClass("js-checked").filter('[data-filter-value="{0}"]'.format(a.display_groups)).addClass("js-checked");
a.isAjaxTemplateRenderWithData=!0;vBulletin.AJAX({call:"/ajax/render/socialgroup_nodes",data:a,success:function(h){h=c(h.template);e.replaceWith(h);vBulletin.memberChannel.updateToolbarPagination(d);vBulletin.memberChannel.updateToolbarButtonPressState(d);vBulletin.initFlexGridFixLastRowAll(!0);vBulletin.initFrameImagesWithColor();h.trigger("vb-instrument")},title_phrase:"contenttype_vbforum_socialgroup",error_phrase:"unable_to_contact_server_please_try_again"});if(b)if(b=location.pathname,q&&"#groups-tab"!=
q&&(b+="?tab=groups-tab"),b=vBulletin.makePaginatedUrl(b,a.page),b=vBulletin.makeFilterUrl(b,"filter_groups",a.my,d),b=vBulletin.makeFilterUrl(b,"display_groups",a.display_groups,d),k&&k.isEnabled())k.getState(),a={from:"filter_groups",page:a.page,tab:d.hasClass("ui-tabs-panel")?"#"+d.attr("id"):void 0,filters:{filter_groups:a.my,display_groups:a.display_groups}},k.pushState(a,document.title,b);else if(p["#groups-tab"])return b=vBulletin.makeFilterUrl(b,"filter_groups",filterValue,d,c(".activity-stream-widget .js-module-top-anchor").attr("id")),
location.href=b,!1}if(!vBulletin.pageHasSelectors([".socialgroup-home-widget",".socialgroup-category-list-widget"]))return!1;var k,p={},r,w,d,q;c(function(){d=c(".groups-tab",a);A();a=c(".socialgroup-category-list-widget");a.length&&v(a,a);p["#groups-tab"]=p["#groups-tab"]||"1"==c(".conversation-toolbar-wrapper",d).data("allow-history");c(document).off("click",".add-sg").on("click",".add-sg",D);d.off("click",".js-button-filter").on("click",".js-button-filter",E);groupsPagination=new vBulletin.pagination({context:d,
allowHistory:p["#groups-tab"],onPageChanged:function(b,e,f,g){if(!e){var h="undefined"!=typeof g?vBulletin.parseQueryString(h):null;b={my:h&&"undefined"==typeof h.filter_groups?"show_all":c(".js-button-filter-my.js-checked",d).data("filter-value"),display_groups:h&&"undefined"==typeof h.display_groups?"display_grid":c(".js-button-filter-display-groups.js-checked",d).data("filter-value"),page:b};t(b,e)}}});var a=c(".socialgroup-widget");a.hasClass("ui-tabs")||(k=vBulletin.history.instance(p["#groups-tab"]),
y(!1),z());vBulletin.memberChannel.updateToolbarButtonPressState(d);vBulletin.memberChannel.initFlexGridAdjustments(c(".socialgroup-widget"))})})(jQuery);
