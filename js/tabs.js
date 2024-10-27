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
\*========================================================================/

/*
 This is intended to be self contained and should not refer to the
 vBulletin object, pageData or other framework.

 The filters for the toolbars are intimately connected to the tabs and are so included here.
 Not calling it tabfiltertools for brevity.

 This depends on the jquery ui tabs widget
*/
vBulletin.tabtools=function(t,y,F,G,H){function z(a){var b=$(".conversation-toolbar-wrapper",a);return"1"==(b.length?b:a).data("allow-history")}function A(a,b,l,f,n){var d=location.pathname.replace(/\/$/,"").replace(/\/page[0-9]+/,""),c=$(".toolbar-filter-overlay",a),k=I(c,!0);c=$(".pagenav-form .js-pagenum",a).val()||1;var p=$(".toolbar-search-form .js-filter-search",a).val()||"",e=$(a).closest(".canvas-widget"),g=z(a);g=new y.instance(g);e=e.data("widget-default-tab")||e.find(".ui-tabs-nav > li:first-child a").attr("href");
$.isEmptyObject(k)&&$(".conversation-toolbar .js-button-filters",a).each(function(){var h=$(".js-button-filter.js-checked:not(.js-default-checked)",this);h.length&&(k[h.data("filter-name")]=h.data("filter-name"))});l||(a=new RegExp("\\/("+f.join("|")+")/?$"),d=(a=d.match(a))?d.replace(new RegExp(a[0]),"/"+b):d+("/"+b));1<c&&g.isEnabled()&&(d+="/page"+c);l&&b&&e!="#"+b&&(k.tab=b,g.isEnabled()||(d+="?tab="+b));if(!g.isEnabled())return d+(n?"#"+n:"");p&&(k.q=p);b=$.param(k);return d+(b?"?"+b:"")+(n?
"#"+n:"")}function J(a,b,l,f,n,d){var c=$(this);if(c.length){var k=a instanceof y.instance;if(k&&a.isEnabled()){var p=d.create;d.create=function(g,h){var q=B(location.search,["_"]),w=Object.keys(q).length,u=K(c,f),v=a.getState();!(f&&1<w&&"undefined"!=typeof q.tab||!f&&0<w)||v&&!$.isEmptyObject(v.data)||a.setDefaultState({from:"tabs",tab:u},document.title,location.href);a.setStateChange(function(m){m=a.getState();if("tabs"==m.data.from){a.log(m.data,m.title,m.url);var C=l(m.data.tab),x=B(m.url,["tab",
"_"]);if(c.tabs("option","selected")!=C)L.call(c,C),$.isEmptyObject(x)||$(window).trigger("statechange.filter",["tabs",x]);else if($.isEmptyObject(x)){var r;m.data.tab&&(r=f?$(m.data.tab):$('.ui-tabs .ui-tabs-panel[data-url-path="'+m.data.tab+'"]'));r=r&&r.length&&r||$(c.closest(".canvas-widget").data("widget-default-tab"));m=$(".toolbar-filter-overlay .filter-options .js-default-checked",r);m.length?(m.prop("checked",!0).first().data("bypass-filter-display",!0).trigger("change",[!0]).data("bypass-filter-display",
null),$(".filtered-by",r).addClass("h-hide").find(".filter-text-wrapper").empty()):$(".conversation-toolbar .js-default-checked",r).first().trigger("click",[!0])}else $(window).trigger("statechange.filter",["tabs",x])}},"tabs");p.call(c[0],g,h)}}if(b&&k){var e=d.beforeActivate;d.beforeActivate=function(g,h){var q=h.newPanel,w=h.newTab;if(!1===e.call(this,g,h))return!1;h=f?q.attr("id"):w.find(".ui-tabs-anchor").data("url-path");var u=void 0;f||(u=[],c.find(".ui-tabs-nav > li > a, .widget-tabs-panel").each(function(v,
m){v=$(this).data("url-path");-1==$.inArray(v,u)&&u.push(v)}));if(!a.isEnabled())return q=A(q,h,f,u,n),location.href=q,!1;c.data("noPushState")?c.data("noPushState",null):(g={from:"tabs",tab:"#"+h},q=A(q,h,f,u),a.pushState(g,document.title,q))}}c.tabs(d);c.trigger("vb-tabinit")}return c}function D(a,b){return a.offset().top+(a.outerHeight()-parseFloat(a.css("border-bottom-width")))-b.height()}function M(a,b,l,f,n,d){f=$(".conversation-toolbar-wrapper.scrolltofixed-floating",a);var c=null,k=null,p=
null;b&&0<f.length&&(c=new H({element:f,limit:D(b,f)}));l&&(p=new G({context:a,allowHistory:n,onPageChanged:function(e,g){k.updatePageNumber(e);g||k.applyFilters(!1,!0,!1,!0)}}));k=new F({context:a,autoCheck:!1,scrollToTop:b,pagination:p,allowHistory:n,onContentLoad:d});return{$bar:f,$floating:c,pagination:p,filter:k}}function N(a){a.css("border-bottom","").filter(".stream-view.activity-view").find(".list-item-poll form.poll").each(function(b,l){$(this).is(":visible")&&$(".view-less-ctrl",this).hasClass("h-hide-imp")&&
O(this,3)})}function E(a){a&&(P(a),N(a))}var B=t.parseQueryString,P=t.truncatePostContent,O=t.conversation.limitVisiblePollOptionsInAPost,K=t.getSelectedTabHashOrPath,I=t.getSelectedFilters,L=t.selectTabByIndex;ensureFun=t.ensureFun;return{initTabs:function(a,b,l,f,n,d,c,k,p){l.removeClass("ui-state-disabled");J.call(a,b,f,function(e){e=e||d;return l.filter('li:has(a[href*="'+e+'"])').first().index()},k,a.find(".js-module-top-anchor").prop("id"),{active:n,beforeActivate:function(e,g){for(var h;h<
c.length;h++)c[h]&&c[h].hideFilterOverlay()},create:function(e,g){p($(this),g.tab,g.panel)},activate:function(e,g){p($(this),g.newTab,g.newPanel)}})},newTab:function(a,b,l,f,n,d,c,k,p){var e=M(a,b,l,f,p,function(){e.$floating&&e.$floating.updateLimit(D(b,e.$bar));E(d);ensureFun(c)()});n&&(l=e.filter,E(d),ensureFun(k)(),l.lastFilters={filters:l.getSelectedFilters($(".toolbar-filter-overlay",a))});return e},tabAllowHistory:z}}(vBulletin,vBulletin.history,vBulletin.conversation.filter,vBulletin.pagination,
vBulletin.scrollToFixed);
