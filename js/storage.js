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
vBulletin.createStorageTools=function(g,m){function f(a){if(!a.loaded){a.loaded=!0;try{var b=localStorage.getItem(g+a.name);if(b)if(b=JSON.parse(b),a.istimed){a.cache.values=b.values||a.cache.values;a.cache.times=b.times||a.cache.times;b=!1;var c=a.cache.values,d=a.cache.times,e;for(e in c)d[e]<a.latest&&(delete c[e],delete d[e],b=!0);b&&k(a)}else a.cache.values=b}catch(q){}}}function k(a){try{var b=g+a.name,c=a.cache;a.istimed||(c=c.values);localStorage.removeItem(b);localStorage.setItem(b,JSON.stringify(c))}catch(d){}}
function l(a,b,c,d){this.loaded=!1;this.name=a;this.cache={values:{},times:{}};if(this.istimed=!!b)this.latest=c,this.current=d}function h(a,b,c=!1,d=!0){let e={};c&&(!0===c&&(c=365),e.expires=c);m.set((d?g:"")+a,b,e)}function n(a,b=!0){return m.get((b?g:"")+a)}function p(a,b){this.name=a;this.permanent=b;try{var c=n(a);c&&(this.cache=JSON.parse(c))}catch(d){}this.cache=this.cache||{}}try{localStorage.removeItem("vbcache-")}catch(a){}l.prototype={get:function(a){f(this);var b=this.cache.values;return b.hasOwnProperty(a)?
b[a]:null},getAll:function(){f(this);return this.cache.values},isSet:function(a){f(this);return this.cache.values.hasOwnProperty(a)},set:function(a,b){f(this);var c=a;$.isPlainObject(a)||(c={},c[a]=b);for(_key in c)this.cache.values[_key]=c[_key],this.istimed&&(this.cache.times[_key]=this.current);k(this)},unset:function(a){f(this);delete this.cache.values[a];delete this.cache.times[a];k(this)}};p.prototype={get:function(a,b){return this.cache.hasOwnProperty(a)?this.cache[a]:void 0!==b?b:null},getAll:function(){return this.cache},
set:function(a,b){this.cache[a]=b;h(this.name,JSON.stringify(this.cache),this.permanent)},unset:function(a){delete this.cache[a];h(this.name,JSON.stringify(this.cache),this.permanent)}};return{createStorage:a=>new l(a),createStorageTimed:(a,b,c)=>new l(a,!0,b,c),createArrayCookie:(a,b)=>new p(a,b),setCookie:h,getCookie:n,deleteCookie:(a,b=!0)=>h(a,"",-1,b)}};
