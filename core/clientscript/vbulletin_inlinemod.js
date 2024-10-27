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
function vB_Inline_Mod(e,f,g,h,k){this.varname=e;this.type=f.toLowerCase();this.go_phrase=h;this.cookieprefix=k;this.list=this.type+"list_";this.cookie_ids=null;this.cookie_array=[];this.init=function(a){a=$("#"+g);let c=a.find("input[type=checkbox]");c.filter((b,d)=>this.is_in_list(d)).off("click").on("click",b=>{this.toggle(b.currentTarget)});this.cookie_array=[];if(this.fetch_ids())for(const b of this.cookie_ids)""!=b&&($("#"+this.list+b).prop("checked",!0),this.cookie_array.push(b));0<c.length&&
0==c.filter(".js-checkbox-child:not(:checked)").length&&a.closest(".js-checkbox-container").find(".js-checkbox-master").prop("checked",!0);this.set_output_counters()};this.fetch_ids=function(){this.cookie_ids=Cookies.get(this.cookieprefix+this.type);return null!=this.cookie_ids&&""!=this.cookie_ids&&(this.cookie_ids=this.cookie_ids.split("-"),0<this.cookie_ids.length)?!0:!1};this.toggle=function(a){this.save(a.id.substring(this.list.length),a.checked)};this.save=function(a,c){this.cookie_array=[];
if(this.fetch_ids())for(var b=0;b<this.cookie_ids.length;b++){var d=this.cookie_ids[b];""!=d&&d!=a&&this.cookie_array.push(d)}c&&this.cookie_array.push(a);this.set_output_counters();this.set_cookie();return!0};this.set_cookie=function(){expires=new Date;expires.setTime(expires.getTime()+36E5);Cookies.set(this.cookieprefix+this.type,this.cookie_array.join("-"),{expires,path:"/"})};this.is_in_list=function(a){return"checkbox"==a.type&&0==a.id.indexOf(this.list)&&(0==a.disabled||"undefined"==a.disabled)};
this.set_output_counters=function(){$("#"+this.type+"_inlinego").prop("value",construct_phrase(this.go_phrase,this.cookie_array.length))};this.init()};
