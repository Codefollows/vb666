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
vBulletin.precache(["no_preview_text_available","working_ellipsis"],["threadpreview"]);
vBulletin.qtip=function(a){function k(b,d,l){var g=["left","right"];vBulletin.isRtl()&&g.reverse();d={style:{classes:"qtip-shadow qtip-rounded "+d},position:{my:"top "+g[0],at:"bottom "+g[1],viewport:a(window)}};a.extend(d,l);b.qtip(d)}function m(b){k(a(".js-tooltip[title]",b))}(function(){function b(l,g){var e=g.elements.tooltip;window.setTimeout(function(){var c=parseInt(e.css("left"),10),f=a(window).scrollLeft();if(c<f)e.css("left",f+1);else{var h=e.outerWidth(),n=a(window).width();h=c+h;f+=n;
h>f&&e.css("left",c-(h-f)-1)}},0)}var d=a.fn.qtip;a.fn.qtip=function(){"object"==typeof arguments[0]&&("undefined"==typeof arguments[0].events&&(arguments[0].events={}),arguments[0].events.move="function"==typeof arguments[0].events.move?vBulletin.ajaxtools.runBeforeCallback(arguments[0].events.move,b):b);return d.apply(this,arguments)}})();m(a(document));a(document).on("vb-instrument",function(b){m(a(b.target))});(0<a(".channel-content-widget").length&&a(".channel-content-widget").eq(0).attr("data-canviewtopiccontent")||
0<a(".search-results-widget").length)&&a(document).offon("mouseover",".topic-list-container .topic-title, .conversation-list .post-title, .conversation-list .b-post__title",function(){if(0<vBulletin.options.get("threadpreview")){var b=a(this);if("1"!=b.data("vb-qtip-preview-initialized")){b.data("vb-qtip-preview-initialized","1");var d=parseInt(b.closest(".topic-item").attr("data-node-id")||b.closest(".js-post").attr("data-node-id"));vBulletin.isRtl();k(b,"qtip-topicpreview",{content:{text:function(l,
g){var e=function(c){var f=c?c:vBulletin.phrase.get("no_preview_text_available");g.set("content.text",f);return c};return d?(vBulletin.AJAX({call:"/ajax/fetch-node-preview",data:{nodeid:d},success:function(c){e(a.trim(c))},api_error:function(){e()},error:function(c,f,h){e(f+": "+h)}}),vBulletin.phrase.get("working_ellipsis")):e()}},show:{delay:500},hide:{event:"mouseleave click"}});b.trigger("mouseover")}}else a(document).off("mouseover",".topic-list-container .topic-title, .conversation-list .post-title, .conversation-list .b-post__title")});
return{apply:k}}(jQuery);
