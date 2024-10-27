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
function construct_phrase(a,...h){for(var e=1;e<=h.length;e++)a=a.replace(new RegExp("%"+e+"\\$s","gi"),h[e-1]);return a}function isFullScreen(a){return/\bCodeMirror-fullscreen\b/.test(a.getWrapperElement().className)}function winHeight(){return window.innerHeight||(document.documentElement||document.body).clientHeight}
function setFullScreen(a,h){var e=a.getWrapperElement();h?(e.className+=" CodeMirror-fullscreen",e.style.height=winHeight()+"px",document.documentElement.style.overflow="hidden"):(e.className=e.className.replace(" CodeMirror-fullscreen",""),e.style.height="",document.documentElement.style.overflow="");updateCodeMirrorWidth();h=$(e).find("textarea");window.scrollTo(0,h.offset().top);h.focus();a.refresh()}
function updateCodeMirrorWidth(){var a=$(".CodeMirror-wrap"),h=a.closest("#ctrl_template"),e=0<$(".CodeMirror-fullscreen").length;1>a.length||1>h.length||(e?a.css("width",""):(e=$("<div />").text(Array(5E3).join("a ")).appendTo(h),a.hide(),a.width(h.innerWidth()),a.show(),e.remove()))}
function setUpCodeMirror(a){CodeMirror.on(window,"resize",function(){updateCodeMirrorWidth();var g=document.body.getElementsByClassName("CodeMirror-fullscreen")[0];g&&(g.CodeMirror.getWrapperElement().style.height=winHeight()+"px")});var h=CodeMirror.newFoldFunction(CodeMirror.tagRangeFinder),e=$("#"+a.textarea_id),q=$('<div class="smallfont sizetools"><a class="fullscreen" href="#"></div>'+a.phrase_fullscreen+"</a>");e.after(q);var m=CodeMirror.fromTextArea(e.get(0),{mode:a.mode,lineNumbers:!0,matchBrackets:!0,
autoCloseBrackets:!0,autoCloseTags:!0,tabMode:"indent",indentUnit:4,tabSize:4,electricChars:!1,smartIndent:!0,indentWithTabs:!0,autoClearEmptyLines:!0,lineWrapping:!0,highlightSelectionMatches:!0,styleActiveLine:!0,extraKeys:{"Ctrl-Q":function(g){h(g,g.getCursor().line)},"Ctrl-Space":"autocomplete",F11:function(g){setFullScreen(g,!isFullScreen(g))},Esc:function(g){isFullScreen(g)&&setFullScreen(g,!1)}}});q.on("click",()=>{setFullScreen(m,!0);return!1});(a=e.attr("dir"))&&$(m.display.wrapper).attr("dir",
a);updateCodeMirrorWidth();m.on("gutterClick",h);var p=e.parents("form");p.find("input.searchstring").val(p.find('input[name="searchstring"]').val());p.find("input.findbutton").on("click",function(){var g=m.getSearchCursor(p.find("input.searchstring").val(),m.getCursor(),!0);g.find();g.atOccurrence&&(m.setCursor(g.to()),m.setSelection(g.from(),g.to()))})}
$.extend(window,(a=>{function h(b,c=!1){b=new URL(b,document.baseURI);if(c)return!!window.open(b);location=b;return!0}function e(b,...c){var d=a(".js-phrase-data").data(b);return d?c.length?construct_phrase(d,...c):d:b}function q(){function b(){return!a(".js-adminmessage-dismissmultiple:not(:checked)").length}function c(){var k=!a(".js-adminmessage-dismissmultiple:checked").length;a(".js-adminmessage-dismissmultiple-submit").prop("disabled",k)}function d(){a(".js-adminmessage-dismissmultiple").prop("checked",
!b());c()}function f(){a(".js-adminmessage-dismissmultiple-toggle").prop("checked",b());c()}a(".js-adminmessage-dismissmultiple-toggle").off("change",d).on("change",d);a(".js-adminmessage-dismissmultiple").off("change",f).on("change",f)}function m(){a(".js-autocheck-master").each(function(){var b=a(this);b.on("focus",function(){a(document).find(b.data("on")).prop("checked",!0)})});a(".js-checkbox-container").on("click",".js-checkbox-master",b=>{let c=a(b.currentTarget);childclass=c.data("child")||
"js-checkbox-child";filter=c.prop("checked")?":not(:checked)":":checked";a(b.delegateTarget).find("."+childclass+filter).trigger("click")});a(".js-link").on("click",b=>{h(a(b.currentTarget).data("href"))});a(".js-link-newwindow").on("click",b=>{window.open(a(b.currentTarget).data("href"))});a(".js-link-popup").on("click",b=>{b=a(b.currentTarget);let c=parseInt(b.data("height")||600),d=parseFloat(b.data("width")||.9);0<d&&1>=d&&(d*=screen.width);window.open(b.data("href"),"popup","resizable=yes,scrollbars=yes,width="+
d+",height="+c)})}function p(){a(".js-image-upload-update-preview").on("change vb-init",function(b){b=a(this).attr("name");var c=a('.js-channel-icon-preview-wrapper[data-for="'+b+'"]'),d=a('img[data-for="'+b+'"]'),f=a('.js-image-upload-remove[data-for="'+b+'"]'),k=a('.js-image-upload-revert[data-for="'+b+'"]');"undefined"!=typeof FileReader&&(b=new FileReader,b.onload=function(l){d.attr("src",l.target.result);c.toggleClass("hide",!1);f.prop("disabled",!1);k.length&&k.prop("disabled",!1)},0<this.files.length&&
b.readAsDataURL(this.files[0]))});a(".js-image-upload-update-preview").trigger("vb-init");a(".js-image-upload-remove").on("click",function(){var b=a(this),c=b.data("for"),d=c&&a('.js-image-upload-revert[data-for="'+c+'"]'),f=c&&a('input[name="'+c+'"]'),k=c&&a('.js-image-upload-filedataid[data-for="'+c+'"]'),l=a('.js-channel-icon-preview-wrapper[data-for="'+c+'"]');c=c&&a('img[data-for="'+c+'"]');f.val("");k.val("0");c.attr("src","");l.toggleClass("hide",!0);d.length&&d.prop("disabled",!1);b.prop("disabled",
!0)});a(".js-image-upload-revert").on("click",function(){var b=a(this),c=b.data("for"),d=c&&a('.js-image-upload-remove[data-for="'+c+'"]'),f=c&&a('input[name="'+c+'"]'),k=c&&a('.js-image-upload-filedataid[data-for="'+c+'"]'),l=a('.js-channel-icon-preview-wrapper[data-for="'+c+'"]');c=c&&a('img[data-for="'+c+'"]');f.val("");k.val(k.data("orig-value"));c.attr("src",c.data("orig-src"));l.toggleClass("hide",!1);b.prop("disabled",!0);d.prop("disabled",!1)})}function g(){a("[autofocus]:not(:focus)").eq(0).trigger("focus");
a(".js-page-redirect").each((b,c)=>{let d=a(c);b=d.data("timeout");setTimeout(()=>{d.is("a")?location=d.prop("href"):d.is("form")&&c.submit()},1E3*b);return!1});a("a.js-helplink").on("click",b=>{var c=document.URL,d="";-1!=c.search("admincp")?d="admincp":-1!=c.search("modcp")&&(d="modcp");c=d+"/help.php?"+a.param(a(b.currentTarget).data());b=window;d=document;c=b.open(c,"helpwindow","status=yes,resizable=yes,scrollbars=yes,width=600, height=450, top="+((b.innerHeight||d.documentElement.clientHeight||
screen.height)/2-225+(b.screenTop||screen.top))+", left="+((b.innerWidth||d.documentElement.clientWidth||screen.width)/2-300+(b.screenLeft||screen.left)));b.focus&&c.focus();return c});a(".js-copy-default").on("click",b=>{b=a(b.currentTarget);let c=a("#"+b.data("sourceid")).val();""==c?alert(e("default_text_is_empty")):a("#"+b.data("targetid")).val(c)})}function r(){var b=(c,d)=>{var f=a(`[data-groupid="${c}"]`),k=a(`.js-collapse-group[data-groupid="${c}"]:not(.no-collapse) > *:not(.no-collapse)`),
l=`.js-acp-collapse[for="${c}"][data-action='expand']`,n=a(l),t=a(`.js-acp-collapse[for="${c}"]:not([data-action='expand'])`);l=k.has(l).first();d&&(c=f.find(`[data-groupid!="${c}"].js-local-closed *:not(.collapse-toggle)`),k=k.not(c));k.toggle(d);f.toggleClass("js-local-closed",!d);l.toggle(!0);n.toggleClass("h-hide-imp",d);t.toggleClass("h-hide-imp",!d)};a(".js-collapsed").each((c,d)=>{c=a(d);c=c.attr("for")||c.data("groupid");b(c,!1)});a(".js-acp-collapse").on("click",c=>{var d=a(c.currentTarget);
c=d.attr("for");d="expand"==d.data("action");b(c,d)})}a(()=>{a:{var b=window;for(var c=0;2>c&&b.parent;++c)if(b=b.parent,b.frames&&b.frames.length&&b.frames.head){b=b.frames.head;break a}b=!1}b&&(b.parent.document.title=""!=document.title?document.title:"vBulletin",c=a(".js-admincp-data"),b=b.document,a(".js-debug-warning-message",b).toggleClass("hide",!c.data("debug")),a(".js-siteoff-warning-message",b).toggleClass("hide",!c.data("siteoff")));m();g();p();q();r()});return{copy_default_text:function(b){var c=
a("#default_phrase, [name=deftext]").val();""==c?alert(e("default_text_is_empty")):a("#text_"+b).val(c)},vBRedirect:h,vBAdmin:{ensureFun:function(b){return"function"==typeof b?b:()=>{}},renderPhrase:e,vBRedirect:h,htmlspecialchars:function(b){let c=[RegExp("&(?!#[0-9]+;)","g"),RegExp("<","g"),RegExp(">","g"),RegExp('"',"g")],d=["&amp;","&lt;","&gt;","&quot;"];for(let f=0;f<c.length;f++)b=b.replace(c[f],d[f]);return b},initJumpControl:function(b,c){function d(f){let k=f.val(),l=f.data(b+"id"),n="";
k in c?n=c[k].replace("{id}",l):"default"in c?n=c["default"].replace("{id}",l).replace("{value}",k):alert(vBAdmin.renderPhrase("invalid_action_specified_gcpglobal"));""!=n&&vBAdmin.vBRedirect(n,!n.match(/^(?:mailto:|modcp\/|admincp\/)/))&&(f.get(0).selectedIndex=0);return!1}a(".js-"+b+"-select").off("change").on("change",f=>d(a(f.delegateTarget)));a(".js-"+b+"-go").off("click").on("click",f=>d(a("#"+b+a(f.currentTarget).data(b+"id"))))}}}})(jQuery));
