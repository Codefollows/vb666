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
vBulletin.precache("following following_pending following_remove showing_x_subscribers showing_x_subscriptions unable_to_contact_server_please_try_again".split(" "),[]);
(function(b){if(!vBulletin.pageHasSelectors([".subscriptions-widget"]))return!1;b(function(){var r=vBulletin.tabtools,m=b(".subscriptions-widget .subscribeTabs");b(".subscriptions-tab, .subscription-list",m);var p={},k,n;b(".ui-tabs-nav > li",m).removeClass("ui-state-disabled");var f=m.find(".ui-tabs-nav > li");var q=f.filter(".ui-tabs-active");var x=q.index();$defaultTabAnchor=q.find("> a");var u=$defaultTabAnchor.data("url-path");allowTabHistory="1"==f.parent().data("allow-history");tabHistory=
new vBulletin.history.instance(allowTabHistory);var v=function(a,c,e,d,g,h,l){return r.newTab(a,c,d,g,h,null,l,b.noop,e)},w=function(a,c){c=b(".subscription-list-header .last-activity .js-arrow",c);a=a.isFilterSelected("mostactive");c.toggleClass("fa-caret-down",a).toggleClass("fa-caret-up",!a)};r.initTabs(m,tabHistory,f,allowTabHistory,x,u,[k],!1,function(a,c,e){var d=b(e);a="#"+d.prop("id");c=u==b(c).data("url-path");a in p||(p[a]=r.tabAllowHistory(d));"#subscriptionsTab"==a?(k||(subscriptionValues=
v(d,void 0,p[a],!0,!1,c,function(){w(k,d)}),k=subscriptionValues.filter),k.applyFilters(!1,!1,!1,!0)):"#subscribersTab"==a&&(n||(subscriberValues=v(d,void 0,p[a],!0,!1,c,function(){w(n,d)}),n=subscriberValues.filter),n.applyFilters(!1,!1,!1,!0))});tabHistory.isEnabled()&&(f=tabHistory.getState(),q=vBulletin.parseQueryString(location.search,["_"]),0!=Object.keys(q).length||f&&!b.isEmptyObject(f.data)||(f=location.pathname.match(/\/(subscriptions|subscribers)\/?$/),f={from:"tabs",tab:f&&f[1]||$defaultTabAnchor.data("url-path")},
tabHistory.setDefaultState(f,document.title,location.href)));actionSubscribeButton=function(){var a=b(this),c=parseInt(a.attr("data-follow-id")),e=a.attr("data-type");if(("follow_members"==e||"follow_contents"==e)&&c){var d="";d=a.hasClass("isSubscribed")?"delete":"add";vBulletin.AJAX({call:"/profile/follow-button",data:{"do":d,follower:c,type:e},success:function(g){if(1==g||"1"==g)if("delete"==d)if(a.attr("data-canusefriends")){var h="b-button--secondary";var l="isSubscribed b-button--special";var t=
"follow"}else{a.remove();return}else h="isSubscribed b-button--special",l="b-button--secondary",t="following";else 2==g&&(h="subscribepending_button b-button--secondary",l="isSubscribed b-button--special",t="following_pending");l+=" b-button--unfollow";a.addClass(h).removeClass(l).find(".js-button__text-primary").text(vBulletin.phrase.get(t))},title_phrase:"following"})}};m.offon("click",".js-subscription__follow",actionSubscribeButton);b(document).offon("click",".subscription-list-header .last-activity .js-sort",
function(){var a=b(".js-arrow",this).hasClass("fa-caret-down")?"leastactive":"mostactive",c=b(this).closest(".tab"),e="subscriptionsTab"==c.attr("id")?k:n;e.updatePageNumber(1);delete e.lastFilters;c.find(".conversation-toolbar-wrapper .toolbar-filter-overlay .filter-options input[name=filter_sort][value="+a+"]").prop("checked",!1).trigger("click")});b(document).offon("click",".subscription-list-header .subscription-name .js-sort",function(){var a=b(".js-arrow",this),c=a.hasClass("fa-caret-up")?!0:
!1,e=b(this).closest(".subscription-list-container").find(".subscription-list");$listitems=e.hasClass("js-subscribers-list")?e.find(".subscription-item .subscription-name .author"):e.find(".subscription-item .subscription-name a");$listitems.sort(function(d,g){if(c){var h=d;d=g;g=h}return b(d).text().toUpperCase().localeCompare(b(g).text().toUpperCase())});b.each($listitems,function(d,g){e.append(b(g).closest(".subscription-item"))});a.toggleClass("fa-caret-up",!c).toggleClass("fa-caret-down",c)})})})(jQuery);
