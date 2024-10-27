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
// js.compressed/colorpicker.min.js
// ***************************
/*
 Color picker
 Author: Stefan Petre www.eyecon.ro

 Dual licensed under the MIT and GPL licenses
*/
(function(c){var h=function(){var h=65,K={eventName:"click",onShow:function(){},onBeforeShow:function(){},onHide:function(){},onChange:function(){},onSubmit:function(){},color:"ff0000",livePreview:!0,flat:!1},n=function(a,b){a=f(a);c(b).data("colorpicker").fields.eq(1).val(a.r).end().eq(2).val(a.g).end().eq(3).val(a.b).end()},r=function(a,b){c(b).data("colorpicker").fields.eq(4).val(a.h).end().eq(5).val(a.s).end().eq(6).val(a.b).end()},p=function(a,b){c(b).data("colorpicker").fields.eq(0).val(k(f(a))).end()},
t=function(a,b){c(b).data("colorpicker").selector.css("backgroundColor","#"+k(f({h:a.h,s:100,b:100})));c(b).data("colorpicker").selectorIndic.css({left:parseInt(150*a.s/100,10),top:parseInt(150*(100-a.b)/100,10)})},u=function(a,b){c(b).data("colorpicker").hue.css("top",parseInt(150-150*a.h/360,10))},w=function(a,b){c(b).data("colorpicker").currentColor.css("backgroundColor","#"+k(f(a)))},v=function(a,b){c(b).data("colorpicker").newColor.css("backgroundColor","#"+k(f(a)))},L=function(a){a=a.charCode||
a.keyCode||-1;if(a>h&&90>=a||32==a)return!1;!0===c(this).parent().parent().data("colorpicker").livePreview&&q.apply(this)},q=function(a){var b=c(this).parent().parent();if(0<this.parentNode.className.indexOf("_hex")){var d=b.data("colorpicker");var e=this.value,g=6-e.length;if(0<g){for(var m=[],h=0;h<g;h++)m.push("0");m.push(e);e=m.join("")}d.color=d=l(x(e))}else 0<this.parentNode.className.indexOf("_hsb")?b.data("colorpicker").color=d=y({h:parseInt(b.data("colorpicker").fields.eq(4).val(),10),s:parseInt(b.data("colorpicker").fields.eq(5).val(),
10),b:parseInt(b.data("colorpicker").fields.eq(6).val(),10)}):(d=b.data("colorpicker"),e=parseInt(b.data("colorpicker").fields.eq(1).val(),10),g=parseInt(b.data("colorpicker").fields.eq(2).val(),10),m=parseInt(b.data("colorpicker").fields.eq(3).val(),10),d.color=d=l({r:Math.min(255,Math.max(0,e)),g:Math.min(255,Math.max(0,g)),b:Math.min(255,Math.max(0,m))}));a&&(n(d,b.get(0)),p(d,b.get(0)),r(d,b.get(0)));t(d,b.get(0));u(d,b.get(0));v(d,b.get(0));b.data("colorpicker").onChange.apply(b,[d,k(f(d)),f(d)])},
M=function(a){c(this).parent().parent().data("colorpicker").fields.parent().removeClass("colorpicker_focus")},N=function(){h=0<this.parentNode.className.indexOf("_hex")?70:65;c(this).parent().parent().data("colorpicker").fields.parent().removeClass("colorpicker_focus");c(this).parent().addClass("colorpicker_focus")},O=function(a){var b=c(this).parent().find("input").focus();a={el:c(this).parent().addClass("colorpicker_slider"),max:0<this.parentNode.className.indexOf("_hsb_h")?360:0<this.parentNode.className.indexOf("_hsb")?
100:255,y:a.pageY,field:b,val:parseInt(b.val(),10),preview:c(this).parent().parent().data("colorpicker").livePreview};c(document).on("mouseup",a,A);c(document).on("mousemove",a,B)},B=function(a){a.data.field.val(Math.max(0,Math.min(a.data.max,parseInt(a.data.val+a.pageY-a.data.y,10))));a.data.preview&&q.apply(a.data.field.get(0),[!0]);return!1},A=function(a){q.apply(a.data.field.get(0),[!0]);a.data.el.removeClass("colorpicker_slider").find("input").focus();c(document).off("mouseup",A);c(document).off("mousemove",
B);return!1},P=function(a){a={cal:c(this).parent(),y:c(this).offset().top};a.preview=a.cal.data("colorpicker").livePreview;c(document).on("mouseup",a,C);c(document).on("mousemove",a,D)},D=function(a){q.apply(a.data.cal.data("colorpicker").fields.eq(4).val(parseInt(360*(150-Math.max(0,Math.min(150,a.pageY-a.data.y)))/150,10)).get(0),[a.data.preview]);return!1},C=function(a){n(a.data.cal.data("colorpicker").color,a.data.cal.get(0));p(a.data.cal.data("colorpicker").color,a.data.cal.get(0));c(document).off("mouseup",
C);c(document).off("mousemove",D);return!1},Q=function(a){var b={cal:c(this).parent(),pos:c(this).offset()};b.preview=b.cal.data("colorpicker").livePreview;c(document).on("mouseup",b,E);c(document).on("mousemove",b,z);a.data=b;z(a)},F=-1,G=-1,z=function(a){if(F!=a.pageX||G!=a.pageY)return F=a.pageX,G=a.pageY,q.apply(a.data.cal.data("colorpicker").fields.eq(6).val(parseInt(100*(150-Math.max(0,Math.min(150,a.pageY-a.data.pos.top)))/150,10)).end().eq(5).val(parseInt(100*Math.max(0,Math.min(150,a.pageX-
a.data.pos.left))/150,10)).get(0),[a.data.preview]),!1},E=function(a){n(a.data.cal.data("colorpicker").color,a.data.cal.get(0));p(a.data.cal.data("colorpicker").color,a.data.cal.get(0));c(document).off("mouseup",E);c(document).off("mousemove",z);return!1},R=function(a){c(this).addClass("colorpicker_focus")},S=function(a){c(this).removeClass("colorpicker_focus")},T=function(a){c(this).addClass("colorpicker_focus")},U=function(a){c(this).removeClass("colorpicker_focus")},V=function(a){a=c(this).parent();
var b=a.data("colorpicker").color;a.data("colorpicker").origColor=b;w(b,a.get(0));a.data("colorpicker").onSubmit(b,k(f(b)),f(b),a.data("colorpicker").el);c(a.data("colorpicker").el).ColorPickerHide()},I=function(a){var b=c("#"+c(this).data("colorpickerId"));b.data("colorpicker").onBeforeShow.apply(this,[b.get(0)]);var d=c(this).offset(),e="CSS1Compat"==document.compatMode;a=window.pageXOffset||(e?document.documentElement.scrollLeft:document.body.scrollLeft);var g=window.pageYOffset||(e?document.documentElement.scrollTop:
document.body.scrollTop);var m=window.innerWidth||(e?document.documentElement.clientWidth:document.body.clientWidth);var f=d.top+this.offsetHeight;d=d.left;f+176>g+(window.innerHeight||(e?document.documentElement.clientHeight:document.body.clientHeight))&&(f-=this.offsetHeight+176);d+356>a+m&&(d-=356);b.css({left:d+"px",top:f+"px"});0!=b.data("colorpicker").onShow.apply(this,[b.get(0)])&&b.show();c(document).on("mousedown",{cal:b},H);return!1},H=function(a){W(a.data.cal.get(0),a.target,a.data.cal.get(0))||
(0!=a.data.cal.data("colorpicker").onHide.apply(this,[a.data.cal.get(0)])&&a.data.cal.hide(),c(document).off("mousedown",H))},W=function(a,b,d){if(a==b)return!0;if(a.contains)return a.contains(b);if(a.compareDocumentPosition)return!!(a.compareDocumentPosition(b)&16);for(b=b.parentNode;b&&b!=d;){if(b==a)return!0;b=b.parentNode}return!1},y=function(a){return{h:Math.min(360,Math.max(0,a.h)),s:Math.min(100,Math.max(0,a.s)),b:Math.min(100,Math.max(0,a.b))}},x=function(a){a=parseInt(-1<a.indexOf("#")?a.substring(1):
a,16);return{r:a>>16,g:(a&65280)>>8,b:a&255}},l=function(a){var b={h:0,s:0,b:0},d=Math.max(a.r,a.g,a.b),c=d-Math.min(a.r,a.g,a.b);b.b=d;b.s=0!=d?255*c/d:0;b.h=0!=b.s?a.r==d?(a.g-a.b)/c:a.g==d?2+(a.b-a.r)/c:4+(a.r-a.g)/c:-1;b.h*=60;0>b.h&&(b.h+=360);b.s*=100/255;b.b*=100/255;return b},f=function(a){var b,d;var c=Math.round(a.h);var g=Math.round(255*a.s/100);a=Math.round(255*a.b/100);if(0==g)c=b=d=a;else{g=(255-g)*a/255;var f=c%60*(a-g)/60;360==c&&(c=0);60>c?(c=a,d=g,b=g+f):120>c?(b=a,d=g,c=a-f):180>
c?(b=a,c=g,d=g+f):240>c?(d=a,c=g,b=a-f):300>c?(d=a,b=g,c=g+f):360>c?(c=a,b=g,d=a-f):d=b=c=0}return{r:Math.round(c),g:Math.round(b),b:Math.round(d)}},k=function(a){var b=[a.r.toString(16),a.g.toString(16),a.b.toString(16)];c.each(b,function(a,c){1==c.length&&(b[a]="0"+c)});return b.join("")},J=function(){var a=c(this).parent(),b=a.data("colorpicker").origColor;a.data("colorpicker").color=b;n(b,a.get(0));p(b,a.get(0));r(b,a.get(0));t(b,a.get(0));u(b,a.get(0));v(b,a.get(0));a.data("colorpicker").onChange.apply(a,
[b,k(f(b)),f(b)])};return{init:function(a){a=c.extend({},K,a||{});if("string"==typeof a.color)if(-1!=a.color.indexOf("rgb(")){var b=a.color.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);a.color=l({r:parseInt(b[1]),g:parseInt(b[2]),b:parseInt(b[3])})}else a.color=l(x(a.color));else if(void 0!=a.color.r&&void 0!=a.color.g&&void 0!=a.color.b)a.color=l(a.color);else if(void 0!=a.color.h&&void 0!=a.color.s&&void 0!=a.color.b)a.color=y(a.color);else return this;return this.each(function(){if(!c(this).data("colorpickerId")){var b=
c.extend({},a);b.origColor=a.color;var e="collorpicker_"+parseInt(1E3*Math.random());c(this).data("colorpickerId",e);e=c('<div class="colorpicker"><div class="colorpicker_color"><div><div></div></div></div><div class="colorpicker_hue"><div></div></div><div class="colorpicker_new_color"></div><div class="colorpicker_current_color"></div><div class="colorpicker_hex"><input type="text" maxlength="6" size="6" /></div><div class="colorpicker_rgb_r colorpicker_field"><input type="text" maxlength="3" size="3" /><span></span></div><div class="colorpicker_rgb_g colorpicker_field"><input type="text" maxlength="3" size="3" /><span></span></div><div class="colorpicker_rgb_b colorpicker_field"><input type="text" maxlength="3" size="3" /><span></span></div><div class="colorpicker_hsb_h colorpicker_field"><input type="text" maxlength="3" size="3" /><span></span></div><div class="colorpicker_hsb_s colorpicker_field"><input type="text" maxlength="3" size="3" /><span></span></div><div class="colorpicker_hsb_b colorpicker_field"><input type="text" maxlength="3" size="3" /><span></span></div><div class="colorpicker_cancel"></div><div class="colorpicker_submit"></div></div>').attr("id",
e);b.flat?e.appendTo(this).show():e.appendTo(document.body);b.fields=e.find("input").on("keyup",L).on("change",q).on("blur",M).on("focus",N);e.find("span").on("mousedown",O).end().find(">div.colorpicker_current_color").on("click",J);b.selector=e.find("div.colorpicker_color").on("mousedown",Q);b.selectorIndic=b.selector.find("div div");b.el=this;b.hue=e.find("div.colorpicker_hue div");e.find("div.colorpicker_hue").on("mousedown",P);b.newColor=e.find("div.colorpicker_new_color");b.currentColor=e.find("div.colorpicker_current_color");
e.data("colorpicker",b);e.find("div.colorpicker_submit").on("mouseenter",R).on("mouseleave",S).on("click",V);e.find("div.colorpicker_cancel").on("mouseenter",T).on("mouseleave",U).on("click",function(){var a=c(this).parent();J.apply(this);c(a.data("colorpicker").el).ColorPickerHide()});n(b.color,e.get(0));r(b.color,e.get(0));p(b.color,e.get(0));u(b.color,e.get(0));t(b.color,e.get(0));w(b.color,e.get(0));v(b.color,e.get(0));if(b.flat)e.css({position:"relative",display:"block"});else c(this).on(b.eventName,
I)}})},showPicker:function(){return this.each(function(){c(this).data("colorpickerId")&&I.apply(this)})},hidePicker:function(){return this.each(function(){c(this).data("colorpickerId")&&c("#"+c(this).data("colorpickerId")).hide()})},setColor:function(a){if("string"==typeof a)if(-1!=a.indexOf("rgb(")){var b=a.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);a=l({r:parseInt(b[1]),g:parseInt(b[2]),b:parseInt(b[3])})}else a=l(x(a));else if(void 0!=a.r&&void 0!=a.g&&void 0!=a.b)a=l(a);else if(void 0!=a.h&&void 0!=
a.s&&void 0!=a.b)a=y(a);else return this;return this.each(function(){if(c(this).data("colorpickerId")){var b=c("#"+c(this).data("colorpickerId"));b.data("colorpicker").color=a;b.data("colorpicker").origColor=a;n(a,b.get(0));r(a,b.get(0));p(a,b.get(0));u(a,b.get(0));t(a,b.get(0));w(a,b.get(0));v(a,b.get(0))}})},getColor:function(){if(c(this).data("colorpickerId")){var a=c("#"+c(this).data("colorpickerId")).data("colorpicker").color,b=f(a);return{rgb:b,hex:k(b),hsb:a}}}}}();c.fn.extend({ColorPicker:h.init,
ColorPickerHide:h.hidePicker,ColorPickerShow:h.showPicker,ColorPickerSetColor:h.setColor,ColorPickerGetColor:h.getColor})})(jQuery);
;

// ***************************
// js.compressed/vbColorPicker.js
// ***************************
var vBulletin_ColorPicker=function(g,a){$("<link>").appendTo("head").attr({rel:"stylesheet",type:"text/css",href:pageData.baseurl+"/js/colorpicker/css/colorpicker.css"});(function(){$(g).each(function(){var b=$(this),c=$('<span class="'+(a.triggerClass?a.triggerClass:"colorPickerTrigger")+'"></span>').insertBefore(b);c.css("backgroundColor",b.val());var f=c.css("backgroundColor");b.off("keyup").on("keyup",function(){/^[0-9a-f]{3}(?:[0-9a-f]{3})?$/i.test(b.val())&&b.val("#"+b.val());c.css("backgroundColor",
b.val());c.ColorPickerSetColor(c.css("backgroundColor"));if(a.onChange){var d=c.ColorPickerGetColor();a.onChange.call(b,"#"+d.hex)}});f={color:a.color?a.color:f,onChange:function(d,e,h){b.val("#"+e);c.css("backgroundColor","#"+e);a.onChange&&a.onChange.call(b,"#"+e)},onSubmit:function(d,e,h,k){$(k).val(e);b.val("#"+e);c.css("backgroundColor","#"+e);a.onSubmit&&"function"==typeof a.onSubmit&&a.onSubmit.call(b,"#"+e)}};a.fadeIn&&(f.onShow=function(d){$(d).fadeIn(a.fadeSpeed?a.fadeSpeed:500);a.onShow&&
a.onShow.call(d);return!1});a.fadeOut&&(f.onHide=function(d){$(d).fadeIn(a.fadeSpeed?a.fadeSpeed:500);a.onHide&&a.onHide.call(d);return!1});!a.fadeIn&&a.onShow&&(f.onShow=a.onShow);!a.fadeOut&&a.onHide&&(f.onHide=a.onHide);c.ColorPicker(f)})})()};
;

// ***************************
// js.compressed/profile_customization.js
// ***************************
vBulletin.precache("error_x error_saving_customizations profile_style_customizations saving style_applied_as_site_default kilobytes set_to_default profile_theme_reset_confirmation reverting".split(" "),[]);
var cssMappings={profcustom_navbar_background_active:[".profileTabs .widget-tabs-nav li.ui-tabs-active"],profcustom_navbar_border_active:[".profileTabs .widget-tabs-nav li.ui-tabs-active a"],profcustom_navbar_text_color_active:[".profileTabs .widget-tabs-nav li.ui-tabs-active a"],profcustom_navbar_background:[".profileTabs .widget-tabs-nav li.ui-state-default:not(.ui-state-active)"],profcustom_navbar_border:[".profileTabs .widget-tabs-nav li.ui-state-default:not(.ui-state-active) a"],profcustom_navbar_text_color:[".profileTabs .widget-tabs-nav li.ui-state-default:not(.ui-state-active) a"],
toolbar_background:[".profileTabs .conversation-toolbar-wrapper .conversation-toolbar",".forum-list-container .forum-list-header"],profcustom_navbar_toolbar_text_color:[".profileTabs .conversation-toolbar-wrapper .conversation-toolbar",".forum-list-container .forum-list-header"],profcustom_navbarbutton_background:[".profileTabs .conversation-toolbar-wrapper .conversation-toolbar .button.primary"],profcustom_navbarbutton_border:[".profileTabs .conversation-toolbar-wrapper .conversation-toolbar .button.primary"],
profcustom_navbarbutton_color:[".profileTabs .conversation-toolbar-wrapper .conversation-toolbar .button.primary"],profile_button_secondary_background:[".profileTabs .conversation-toolbar-wrapper .conversation-toolbar .button.secondary"],profcustom_navbarbuttonsecondary_border:[".profileTabs .conversation-toolbar-wrapper .conversation-toolbar .button.secondary"],profcustom_navbarbuttonsecondary_color:[".profileTabs .conversation-toolbar-wrapper .conversation-toolbar .button.secondary"],profile_content_background:["#profileTabs",
"#profileTabs .conversation-list.stream-view .list-item"],profile_content_border:[".profileTabs .tab .list-container","#profileTabs .conversation-list.stream-view"],profile_content_divider_border:[".profileTabs .post-footer-wrapper .post-footer .divider"],profile_section_background:[".profileTabs .section .section-header"],profile_section_border:[".profileTabs .section .section-header"],profile_section_text_color:[".profileTabs .section .section-header"],profile_section_font:[".profileTabs .section .section-header"],
profile_content_primarytext:["#profileTabs.profileTabs","#profileTabs.profileTabs .conversation-list.stream-view .list-item","#profileTabs.profileTabs .widget-content","#profileTabs.profileTabs .post-content"],profile_content_secondarytext:[".profile-widget .post-footer-wrapper .post-footer ul li",".profile-widget .conversation-list.stream-view .list-item-header .info .subscribed",".canvas-layout-container .canvas-widget .widget-content .profileTabs .post-date"],profile_content_linktext:[".profile-widget .widget-tabs.ui-tabs .ui-widget-content a",
".profile-widget .widget-tabs.ui-tabs .ui-widget-content a:active",".profile-widget  .widget-tabs.ui-tabs .ui-widget-content a:visited"],profile_content_font:["#profileTabs"],side_nav_background:[".profile_sidebar_content"],form_dropdown_border:[".profile_sidebar_content"],side_nav_avatar_border:[".profile-sidebar-widget .profileContainer .profile-photo-wrapper .profile-photo"],side_nav_divider_border:[".profile-sidebar-widget .profile-menulist .profile-menulist-item"],profile_userpanel_textcolor:[".profile_sidebar_content",
".profile_sidebar_content .profile-menulist-item a label"],profile_userpanel_linkcolor:[".profile_sidebar_content .profile-menulist-item a .subscriptions-count, .profile_sidebar_content .profile-menulist-item a .subscriptions-count:hover, .profile_sidebar_content .profile-menulist-item a .subscriptions-count:visited"],profile_userpanel_font:[".profile_sidebar_content"],profilesidebar_button_background:[".profile_sidebar_content .button.primary"],profilesidebar_button_border:[".profile_sidebar_content .button.primary"],
profilesidebar_button_text_color:[".profile_sidebar_content .button.primary"],button_primary_text_color:[".profileTabs .button.primary",".profileTabs .button.primary:hover"],button_primary_border:[".profileTabs .button.primary",".profileTabs .button.primary:hover"],profile_button_primary_background:[".profileTabs .button.primary",".profileTabs .button.primary:hover"]},colorTypes={profcustom_navbar_background_active:3,profcustom_navbar_background:3,toolbar_background:3,profile_content_background:3,
profile_section_background:3,side_nav_background:3,profilesidebar_button_background:3,module_tab_border_active:2,module_tab_border:2,button_secondary_border:2,profile_content_border:2,profile_content_divider_border:2,profile_section_border:2,form_dropdown_border:2,side_nav_avatar_border:2,side_nav_divider_border:2,profilesidebar_button_border:2,profcustom_navbar_text_color_active:1,profcustom_navbar_text_color:1,button_secondary_text_color:1,profcustom_navbar_toolbar_text_color:1,profile_section_color:1,
profile_section_text_color:1,profile_content_primarytext:1,profile_content_secondarytext:1,profile_content_linktext:1,profile_userpanel_textcolor:1,profile_userpanel_linkcolor:1,profilesidebar_button_text_color:1,button_primary_text_color:1,button_primary_border:2,profile_button_primary_background:3,profcustom_navbarbutton_color:1,profcustom_navbarbutton_border:2,profcustom_navbarbutton_background:3,profcustom_navbarbuttonsecondary_color:1,profcustom_navbarbuttonsecondary_border:2,profile_button_secondary_background:3},
newSettings={},revertChanges=function(a){$.each(a,function(c,d){targetEl=$("[name="+c+"]");$.each(d,function(b,e){if(e&&""!=e)switch(b){case "color":targetEl.val(e);targetEl.trigger("keyup");break;case "image":setBackgroundImage("",e,c);break;case "family":1<targetEl.length?$.each(targetEl,function(f,g){"family"==$(g).attr("data-type")&&($(g).val(e),$(g).trigger("change"))}):(targetEl.val(e),targetEl.trigger("change"));break;case "size":1<targetEl.length?$.each(targetEl,function(f,g){"size"==$(g).attr("data-type")&&
($(g).val(e),$(g).trigger("change"))}):(targetEl.val(e),targetEl.trigger("change"));break;case "repeat":targetEl=$("[name=repeat_type]"),1<targetEl.length?$.each(targetEl,function(f,g){$(g).attr("data")==c&&($(g).val(e),$(g).trigger("change"))}):(targetEl.val(e),targetEl.trigger("change"))}})})},uploadFromUrl=function(a){var c=$(a.target).closest(".frmBgImageUrl");if(c.length){var d=c.find(".profCustomBgImageUrl");d&&d.val()?(c.find(".js-upload-progress").removeClass("h-hide"),vBulletin.AJAX({call:"/uploader/url",
data:{urlupload:d.val()},success:function(b){b.imageUrl?setBackgroundImage(a,b.imageUrl):vBulletin.error("profile_style_customizations","upload_file_failed")},complete:function(){c.find(".js-upload-progress").addClass("h-hide")},title_phrase:"profile_style_customizations"})):vBulletin.error("profile_style_customizations","upload_file_failed")}},setBgRepeat=function(a){ident=$(a.target).attr("data");repeat=a.target.value;if("undefined"!=typeof cssMappings[ident])for(selectors=cssMappings[ident],addNewSetting(ident,
"repeat",repeat),"undefined"==typeof newSettings[ident].image&&(newSettings[ident].image=$(selectors[0]).css("background-image")),i=0;i<selectors.length;i++)$(selectors[i]).css("background-repeat",repeat)},clearBgImage=function(a){ident=$(a.target).attr("data");if("undefined"!=typeof cssMappings[ident])for(selectors=cssMappings[ident],addNewSetting(ident,"image","none"),i=0;i<selectors.length;i++)$(selectors[i]).css("background-image","none")},clearBgColor=function(a){var c=$(a.target);a=c.attr("data");
c=c.is(":checked");if("undefined"!=typeof cssMappings[a])for(selectors=cssMappings[a],c?(addNewSetting(a,"color","transparent"),val="transparent"):(val="none",c=$('input.colorPicker[name="'+a+'"]').val(),"string"==typeof c&&0===c.indexOf("#")&&(val=c),addNewSetting(a,"color",val)),i=0;i<selectors.length;i++)$(selectors[i]).css("background-color",val)},setBackgroundImage=function(a,c,d){"undefined"!==typeof d?a=d:(a=$(a.target).attr("data"),d=-1==c.indexOf("?")?"?":"&",c='url("'+c+d+"random="+Math.random()+
'")');if("undefined"!=typeof cssMappings[a])for(selectors=cssMappings[a],addNewSetting(a,"image",c),i=0;i<selectors.length;i++)$(selectors[i]).css("background-image",c)};function addNewSetting(a,c,d){newSettings[a]||(newSettings[a]={});newSettings[a][c]=d}
function toggleBgType(a){imgRadio=$(a.target).closest(".profCustomBackgroundEdit").find(".profCustomBgTypeColor");"color"==a.target.value?($(a.target).closest(".profCustomBackgroundEdit").find(".profCustomBgImage").addClass("h-hide"),$(a.target).closest(".profCustomBackgroundEdit").find(".profCustomBgColor").removeClass("h-hide")):($(a.target).closest(".profCustomBackgroundEdit").find(".profCustomBgColor").addClass("h-hide"),$(a.target).closest(".profCustomBackgroundEdit").find(".profCustomBgImage").removeClass("h-hide"))}
function toggleImgSource(a){"file"==a.target.value?($(a.target).closest(".profCustomBackgroundEdit").find(".frmBgImageUrl").addClass("h-hide"),$(a.target).closest(".profCustomBackgroundEdit").find(".frmBgImageFile, .ProfCustomBgRepeat").removeClass("h-hide")):"url"==a.target.value?($(a.target).closest(".profCustomBackgroundEdit").find(".frmBgImageFile").addClass("h-hide"),$(a.target).closest(".profCustomBackgroundEdit").find(".frmBgImageUrl, .ProfCustomBgRepeat").removeClass("h-hide")):$(a.target).closest(".profCustomBackgroundEdit").find(".frmBgImageFile, .frmBgImageUrl, .ProfCustomBgRepeat").addClass("h-hide")}
function updateColorFromComponent(a){addNewSetting($(this).attr("data"),"color",a);$(this).parent().find(".rdProfCustomBgColorClear").prop("checked",!1);if("undefined"!=typeof cssMappings[$(this).attr("data")])for(selector in selectors=cssMappings[$(this).attr("data")],selectors)switch(colorTypes[$(this).attr("data")]){case 1:$(selectors[selector]).css("color",a);break;case 2:$(selectors[selector]).css("border-color",a);break;case 3:$(selectors[selector]).css("background-color",a),$(selectors[selector]).css("background-image",
"none")}}function setFontFamily(a){fontname=$(a.target).find(">option:selected").text();ident=$(a.target).attr("data");addNewSetting(ident,"family",fontname);$(a.target).closest(".fontselectorWrapper").find(".fontDisplay").css("font-family",fontname).html(fontname);if("undefined"!=typeof cssMappings[ident])for(selector in selectors=cssMappings[ident],selectors)$(selectors[selector]).css("font-family",fontname)}
function setFontSize(a){ident=$(a.target).attr("data");$(a.target).closest(".fontselectorWrapper").find(".fontDisplay").css("font-size",a.target.value);addNewSetting(ident,"size",a.target.value);if("undefined"!=typeof cssMappings[ident])for(selector in selectors=cssMappings[ident],selectors)$(selectors[selector]).css("font-size",a.target.value)}function rgb2hex(a){a=a.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);return"#"+hex(a[1])+hex(a[2])+hex(a[3])}
function rgba2hex(a){a=a.match(/^rgba\((\d+),\s*(\d+),\s*(\d+),\s*(\d+)\)$/);var c={};if(0==a[4])c.transparent=!0;else if(1==a[4])c.transparent=!1;else return!1;c.hex="#"+hex(a[1])+hex(a[2])+hex(a[3]);return c}function hex(a){var c="0123456789abcdef".split("");return isNaN(a)?"00":c[(a-a%16)/16]+c[a%16]}
function setCurrentBgValues(a){var c=a.attr("data");if("undefined"!=typeof cssMappings[c]){var d=$(cssMappings[c][0]),b={};let e="";b.color=d.css("background-color");b.image=d.css("background-image");b.repeat=d.css("background-repeat");if("string"==typeof b.image&&"none"!=b.image.substring(0,4))c=b.image.lastIndexOf("/")+1,e=b.image.substr(c,b.image.length-c-2),a.find(".fileText").val(e),""!=b.repeat&&a.find(".ProfCustomBgRepeatType").val(b.repeat);else if("transparent"==b.color)a.find(".rdProfCustomBgColorClear").prop("checked",
!0);else if("string"==typeof b.color){b=b.color;if(0===b.indexOf("rgba")){if(d=rgba2hex(b))b=d.hex,a.find(".rdProfCustomBgColorClear").prop("checked",d.transparent)}else 0===b.indexOf("rgb")&&(b=rgb2hex(b));a.find('input[name="'+c+'"].colorPicker').val(b)}""!=e?a.find(".rdProfCustomBgTypeImage").trigger("click"):a.find(".rdProfCustomBgTypeColor").trigger("click")}}
(function(a){if(!vBulletin.pageHasSelectors([".profileEditContent"]))return!1;a(function(){var c=0;a(".profile_custom_edit .profileTabs").tabs({activate:function(d,b){a().add(b.oldTab).add(b.newTab).find(".b-icon").toggleClass("b-icon--active")}});a(".profCustomBackgroundEdit").each(function(d){c++;d=a(".profCustomBgTemplate").clone();let b=a(this);a(d).removeClass("h-hide profCustomBgTemplate");a(d).css("display","block");b.append(d);b.find(".colorPicker").attr("data",b.attr("data"));b.find(".colorPicker").attr("name",
b.attr("data"));b.find(".colorPicker").removeClass("template");b.find(".rdProfCustomBgTypeColor, .rdProfCustomBgTypeImage").off("click").on("click",toggleBgType);b.find(".rdProfCustomBgTypeColor, .rdProfCustomBgTypeImage").prop("name","profCustomBgType"+c);b.find(".rdProfCustomFile, .rdProfCustomUrl, .rdProfCustomBgImageNone").off("click").on("click",toggleImgSource);b.find(".rdProfCustomFile, .rdProfCustomUrl, .rdProfCustomBgImageNone").attr("name","profCustomBgSrc"+c);b.find(".profCustomBgImageFile, .profCustomBgImageUrl, .rdProfCustomBgColorClear, .rdProfCustomBgImageNone, .ProfCustomBgRepeatType, .profCustomUploadUrl").attr("data",
a(this).attr("data"));setCurrentBgValues(b);a(this).find(".profCustomBgImageFile").fileupload({dropZone:"vb-self",url:vBulletin.getAjaxBaseurl()+"/uploader/upload-file",type:"POST",dataType:"json",add:function(e,f){b.find(".js-upload-progress").removeClass("h-hide");f.submit()},done:function(e,f){f?f.result.errors?"undefined"==typeof f.result.errors[0]?vBulletin.error("profile_style_customizations",f.result.errors):vBulletin.error("profile_style_customizations",f.result.errors[0][0]):f.result.imageUrl?
(b.find(".profile-img-option-container .profile-img-option-field input.fileText").val(f.result.filename),setBackgroundImage(e,f.result.imageUrl)):vBulletin.error("profile_style_customizations","unable_to_upload_file"):vBulletin.error("profile_style_customizations","invalid_server_response_please_try_again")},fail:function(e,f){e="error_uploading_image";var g="error";if(f&&0<f.files.length)switch(f.files[0].error){case "acceptFileTypes":e="invalid_image_allowed_filetypes_are",g="warning"}vBulletin.alert("upload",
e,g,function(){$editProfilePhotoDlg.find(".fileText").val("");$editProfilePhotoDlg.find(".browse-option").trigger("focus")})},always:function(){b.find(".js-upload-progress").addClass("h-hide")}})});a("body").is(".view-mode")&&(vBulletin_ColorPicker(".colorPicker",{onChange:updateColorFromComponent}),a(".selectCustomProfFontfamily").off("change").on("change",setFontFamily),a(".selectCustomProfFontsize").off("change").on("change",setFontSize),a(".rdProfCustomBgImageNone").off("click").on("click",clearBgImage),
a(".rdProfCustomBgColorClear").off("click").on("click",clearBgColor),a(".ProfCustomBgRepeatType").off("change").on("change",setBgRepeat),a(".profCustomUploadUrl").off("click").on("click",uploadFromUrl));a(".profCustomSave").off("click").on("click",function(){if(a.isEmptyObject(newSettings))vBulletin.error("profile_customization","there_are_no_changes_to_save");else{var d=newSettings;newSettings={};vBulletin.AJAX({call:"/profile/save-stylevar",data:{stylevars:d,userid:pageData.userid},success:function(b){vBulletin.alert("profile_customization",
"usercss_saved")},title_phrase:"profile_style_customizations",error_phrase:"error_saving_customizations"})}});a(".profCustomRevert").off("click").on("click",function(){var d=[],b=0;a.isEmptyObject(newSettings)?vBulletin.error("profile_customization","there_are_no_changes_to_revert"):(a.each(newSettings,function(e,f){d[b]=e;b++}),vBulletin.AJAX({call:"/profile/revert-stylevars",data:{stylevars:d,userid:pageData.userid},success:function(e){revertChanges(e)},title_phrase:"profile_style_customizations",
error_phrase:"error_saving_customizations"}),newSettings={})});a(".profCustomDefault").off("click").on("click",function(){var d=[],b=0;a.isEmptyObject(newSettings)||a.each(newSettings,function(e,f){d[b]=e;b++});openConfirmDialog({title:vBulletin.phrase.get("set_to_default"),message:vBulletin.phrase.get("profile_theme_reset_confirmation"),iconType:"warning",onClickYes:function(){vBulletin.AJAX({call:"/profile/reset-default",data:{stylevars:d,userid:pageData.userid},success:function(e){window.location.reload()},
title_phrase:"profile_style_customizations",error_phrase:"error_saving_customizations"});newSettings={}}})});a(".profCustomApplyAll").off("click").on("click",function(){vBulletin.AJAX({call:"/profile/save-default",data:{stylevars:newSettings,userid:pageData.userid},success:function(d){vBulletin.alert("profile_customization","style_applied_as_site_default")},title_phrase:"profile_style_customizations",error_phrase:"error_saving_customizations"})});a(".profCustomCancel").off("click").on("click",function(){a(".profile_custom_edit").addClass("h-hide")})})})(jQuery);
;

