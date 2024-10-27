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
// js.compressed/jquery/jquery.condense.custom.min.js
// ***************************
/*
 Condense 0.1 - Condense and expand text heavy elements

 (c) 2008 Joseph Sillitoe
 Dual licensed under the MIT License (MIT-LICENSE) and GPL License,version 2 (GPL-LICENSE).

 Modified for vBulletin:
  - Added callbacks when initializing condense plugin is done (onInit),
    when condensing is done (onCondense),
    when expanding is done (onExpand) and
    when condensing or expanding is done (onToggle).
  - Added patch for infinite loop when condense length cutoff occurs in consecutive spaces (https://github.com/jsillitoe/jquery-condense-plugin/issues/5)
*/
(function(d){function m(a,b){if(d.trim(a.text()).length<=b.condensedLength+b.minTrail)return f("element too short: skipping."),!1;var e=d.trim(a.html()),c=d.trim(a.text());a=a.clone();var g=0;do{a:{var k=e;var l=b.delim,h=b.condensedLength+g;do{h=k.indexOf(l,h);if(0>h){f("No delimiter found.");k=k.length;break a}for(g=!0;n(k,h);)h++,g=!1}while(!g);f("Delimiter found in html at: "+h);k=h}a.html(e.substring(0,k+1));l=a.text().length;h=a.html().length;g=a.html().length-l;f("condensing... [html-length:"+
h+" text-length:"+l+" delta: "+g+" break-point: "+k+"]")}while(g&&a.text().length<b.condensedLength);if(c.length-l<b.minTrail)return f("not enough trailing text: skipping."),!1;f("clone condensed. [text-length:"+l+"]");return a}function n(a,b){return a.indexOf(">",b)<a.indexOf("<",b)}function p(a,b){f("Condense Trigger: "+a.html());var e=a.parent(),c=e.next();c.show();var g=c.width(),d=c.height();c.hide();var l=e.width(),h=e.height();e.animate({height:d,width:g,opacity:1},b.lessSpeed,b.easing,function(){e.height(h).width(l).hide();
c.show();"function"==typeof b.onCondense&&b.onCondense.apply(a,[c]);"function"==typeof b.onToggle&&b.onToggle.apply(a,[e,c,!0])})}function q(a,b){f("Expand Trigger: "+a.html());var e=a.parent(),c=e.prev();c.show();var d=c.width(),k=c.height();c.width(e.width()+"px").height(e.height()+"px");e.hide();c.animate({height:k,width:d,opacity:1},b.moreSpeed,b.easing,function(){"function"==typeof b.onExpand&&b.onExpand.apply(a,[c]);"function"==typeof b.onToggle&&b.onToggle.apply(a,[c,e,!1])});e.attr("id")&&
(d=e.attr("id"),e.attr("id","condensed_"+d),c.attr("id",d))}function f(a){window.console&&window.console.log&&window.console.log(a)}d.fn.condense=function(a){d.metadata?f("metadata plugin detected"):f("metadata plugin not present");var b=d.extend({},d.fn.condense.defaults,a);return this.each(function(){$this=d(this);var a=d.metadata?d.extend({},b,$this.metadata()):b;f("Condensing ["+$this.text().length+"]: "+$this.text());var c=m($this,a);if(c){$this.attr("id")?$this.attr("id","condensed_"+$this.attr("id")):
!1;var g=" <span class='condense_control condense_control_less' style='cursor:pointer;'>"+a.lessText+"</span>";c.append(a.ellipsis+(" <span class='condense_control condense_control_more' style='cursor:pointer;'>"+a.moreText+"</span>"));$this.after(c).hide().append(g);d(".condense_control_more",c).click(function(){f("moreControl clicked.");q(d(this),a)});d(".condense_control_less",$this).click(function(){f("lessControl clicked.");p(d(this),a)})}"function"==typeof a.onInit&&a.onInit.apply(this,[c])})};
d.fn.condense.defaults={condensedLength:200,minTrail:20,delim:" ",moreText:"[more]",lessText:"[less]",ellipsis:" ( ... )",moreSpeed:"normal",lessSpeed:"normal",easing:"linear",onInit:null,onCondense:null,onExpand:null,onToggle:null}})(jQuery);
;

// ***************************
// js.compressed/notice.js
// ***************************
vBulletin.precache(["login","cancel"],["noticepreviewlength"]);((a,b,e)=>{a(()=>{let c=a(".js-notice-text"),d=e.get("noticepreviewlength");c.removeClass("h-hide-imp");d&&c.condense({condensedLength:d,minTrail:20,delim:" ",moreText:b.get("see-more"),lessText:b.get("see-less"),ellipsis:"...",moreSpeed:"fast",lessSpeed:"fast",easing:"linear"})})})(jQuery,vBulletin.phrase,vBulletin.options);
;

