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
(function(h){vBulletin.ensureObj("history");var d=window.History,k=d&&!!d.enabled;d.options.transformHash=!1;vBulletin.history.instance=function(l){var g=!0,e=!!l&&k;this.isEnabled=function(){return e};this.setDefaultState=function(b,a,c){if(e){g=!1;var f=vBulletin.parseUrl(c);try{c=decodeURI(f.pathname)+decodeURIComponent(f.search)+decodeURIComponent(f.hash),d.replaceState(b,a,c),g=!0}catch(m){console.error("Unable to parse and decode URL "+c)}}};this.setStateChange=function(b,a){e&&d.Adapter.bind(window,
"statechange"+(a?"."+a:""),function(c){g&&b.apply(this,h.makeArray(arguments))})};this.pushState=function(b,a,c){if(e){g=!1;var f=vBulletin.parseUrl(c);try{c=decodeURI(f.pathname)+decodeURIComponent(f.search)+decodeURIComponent(f.hash),d.pushState(b,a,c),g=!0}catch(m){console.error("Unable to parse and decode URL "+c)}}};this.getState=function(){if(e)return d.getState()};this.log=function(){e&&d.log.call(window,arguments)};e&&(h(window).data("hashchange.history")||d.Adapter.bind(window,"hashchange.history",
function(b){var a=location.hash;g&&a&&((b=a.match(/#post(\d+)/))&&Number(b[1])+""===b[1]&&1<Number(b[1])&&0==h(a).length&&h(".conversation-content-widget").length?(a=vBulletin.parseUrl(location.href),location.replace([a.pathname,a.search,a.search?"&":"?","p=",b[1],b[0]].join(""))):(setTimeout(function(){history.back()},0),(b=vBulletin.scrollToAnchor(a))&&window.scrollTo(0,0)))}),h(window).data("hashchange.history",!0));return this};vBulletin.ready("history").resolve()})(jQuery);
