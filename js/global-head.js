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
(()=>{function f(a,c={},d=b){a=a.split(".");let e=0;for(;e<a.length-1;e++)d=d[a[e]]=d[a[e]]||{};return d[a[e]]=d[a[e]]||c}let b=vBulletin={};b.ensureObj=f;b.ensureFun=a=>"function"==typeof a?a:()=>{};b.ensureMethod=(a,c)=>b.ensureFun(a[c]).bind(a);b.data=(a="pagedata")=>(a=document.getElementById(a))?a.dataset:{};b.xsmall=479;b.small=987;f("Responsive.Debounce").checkBrowserSize=()=>{if(Modernizr){var a=document.body;a.classList.toggle("l-xsmall",Modernizr.mq("(max-width: "+b.xsmall+"px)"));a.classList.toggle("l-small",
Modernizr.mq("(max-width: "+b.small+"px)"));a.classList.toggle("l-desktop",Modernizr.mq("(min-width: "+(b.small+1)+"px)"))}};var h=f("phrase.precache",[]),k=f("options.precache",[]);b.precache=function(a,c){h.push(...a);k.push(...c)};let g={};b.ready=a=>{if(Array.isArray(a))return Promise.all(a.map(b.ready));if(!(a in g)){var c,d=new Promise((e,l)=>{c=e});d.resolve=c;g[a]=d}return g[a]}})();
