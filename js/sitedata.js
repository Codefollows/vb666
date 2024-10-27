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
vBulletin.createSiteDataTools=function(v){function r(){var a=[],c=[];(new Set(g)).forEach(function(b){a.push(b)});a=a.filter(function(b){return!h.isSet(b)});(new Set(k)).forEach(function(b){c.push(b)});c=c.filter(function(b){return!l.isSet(b+n)});vBulletin.loadingIndicator.suppressNextAjaxIndicator();$.ajax({url:v+"/ajax/loaddata",async:!1,data:{options:a,phrases:c},type:"POST",dataType:"json",success:function(b){if(b&&!b.hasOwnProperty("errors")){g.length=0;k.length=0;var m={},p=b.options;$.each(a,
function(w,e){m[e]=p.hasOwnProperty(e)?p[e]:null});h.set(m);var t={},u=b.phrases;$.each(c,function(w,e){t[e+n]=u.hasOwnProperty(e)?u[e]:e});l.set(t)}else console.warn("Unexpected result when fetching options",b)},error:function(b,m,p){console.warn("Error when fetching options: {0}".format(p))}})}var q={},l,k,d,n,f={},h,g;q.init=function(a,c,b,m){d=a;n="-"+c;l=b;k=m};q.get=function(){if(1>arguments.length)return console.log("vBulletin.phrase.get() called with no parameters"),"";var a=arguments;Array.isArray(a[0])&&
(a=a[0]);var c=a[0]+n;l.isSet(c)||(k.push(a[0]),r());var b=l.get(c);if(!b)return c;b=b.replace(/\{sitename\}/g,f.get("bbtitle")).replace(/\{musername\}/g,d.musername).replace(/\{username\}/g,d.username).replace(/\{userid\}/g,d.userid).replace(/\{registerurl\}/g,d.registerurl).replace(/\{activationurl\}/g,d.activationurl).replace(/\{helpurl\}/g,d.helpurl).replace(/\{contacturl\}/g,d.contacturl).replace(/\{homeurl\}/g,d.baseurl).replace(/\{date\}/g,d.datenow).replace(/\{webmasteremail\}/g,f.get("webmasteremail")).replace(/\{register_page\}/g,
d.registerurl).replace(/\{activation_page\}/g,d.activationurl).replace(/\{help_page\}/g,d.helpurl).replace(/\{sessionurl\}/g,"").replace(/\{sessionurl_q\}/g,"");for(c=1;c<a.length;++c)b=b.replace(new RegExp("%"+c+"\\$s","gm"),a[c]);return b};q.register=function(a){$.merge(k,$.makeArray(a))};f.init=function(a,c){h=a;g=c};f.get=function(a){h.isSet(a)||(g.push(a),r(a));return h.get(a)};f.register=function(a){$.merge(g,$.makeArray(a))};return{phrase:q,options:f}};
