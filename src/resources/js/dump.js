let Sfdump=window.Sfdump||function(e){e.documentElement.classList.add("sf-js-enabled");var t=/([.*+?^${}()|\[\]\/\\])/g,n=0<=navigator.platform.toUpperCase().indexOf("MAC")?"Cmd":"Ctrl",s=function(e,t,n){e.addEventListener(t,n,!1)};function a(t,n){var s,a,r=t.nextSibling||{},i=r.className;if(/\bsf-dump-compact\b/.test(i))s="▼",a="sf-dump-expanded";else{if(!/\bsf-dump-expanded\b/.test(i))return!1;s="▶",a="sf-dump-compact"}if(e.createEvent&&r.dispatchEvent){var l=e.createEvent("Event");l.initEvent("sf-dump-expanded"===a?"sfbeforedumpexpand":"sfbeforedumpcollapse",!0,!1),r.dispatchEvent(l)}if(t.lastChild.innerHTML=s,r.className=r.className.replace(/\bsf-dump-(compact|expanded)\b/,a),n)try{for(t=r.querySelectorAll("."+i),r=0;r<t.length;++r)-1==t[r].className.indexOf(a)&&(t[r].className=a,t[r].previousSibling.lastChild.innerHTML=s)}catch(c){}return!0}function r(e,t){var n=(e.nextSibling||{}).className;return!!/\bsf-dump-compact\b/.test(n)&&(a(e,t),!0)}function i(e){var t,n,s,i=e.querySelector("a.sf-dump-toggle");return!!i&&(n=!0,s=((t=i).nextSibling||{}).className,/\bsf-dump-expanded\b/.test(s)&&a(t,n),r(i),!0)}function l(e){Array.from(e.querySelectorAll(".sf-dump-str, .sf-dump-key, .sf-dump-public, .sf-dump-protected, .sf-dump-private")).forEach(function(e){e.className=e.className.replace(/\bsf-dump-highlight\b/,""),e.className=e.className.replace(/\bsf-dump-highlight-active\b/,"")})}return e.addEventListener||(s=function(e,t,n){e.attachEvent("on"+t,function(e){e.preventDefault=function(){e.returnValue=!1},e.target=e.srcElement,n(e)})}),function(c,p){for(var u,o,d=RegExp("^("+((c=e.getElementById(c)).getAttribute("data-indent-pad")||" ").replace(t,"\\$1")+")+","m"),f={maxDepth:1,maxStringLength:160,fileLinkFormat:!1},m=c.getElementsByTagName("A"),h=m.length,g=0,_=[];g<h;)_.push(m[g++]);for(g in p)f[g]=p[g];function b(e,t){s(c,e,function(e,n){"A"==e.target.tagName?t(e.target,e):"A"==e.target.parentNode.tagName?t(e.target.parentNode,e):(n=(n=/\bsf-dump-ellipsis\b/.test(e.target.className)?e.target.parentNode:e.target).nextElementSibling)&&"A"==n.tagName&&(/\bsf-dump-toggle\b/.test(n.className)||(n=n.nextElementSibling||n),t(n,e,!0))})}function N(e){return e.ctrlKey||e.metaKey}function v(e){return"concat("+e.match(/[^'"]+|['"]/g).map(function(e){return"'"==e?'"\'"':'"'==e?"'\"'":"'"+e+"'"}).join(",")+", '')"}function x(e){return"contains(concat(' ', normalize-space(@class), ' '), ' "+e+" ')"}for(b("mouseover",function(e,t,n){n&&(t.target.style.cursor="pointer")}),b("click",function(n,s,r){if(/\bsf-dump-toggle\b/.test(n.className)){if(s.preventDefault(),!a(n,N(s))){var i=e.getElementById(n.getAttribute("href").slice(1)),l=i.previousSibling,c=i.parentNode,p=n.parentNode;p.replaceChild(i,n),c.replaceChild(n,l),p.insertBefore(l,i),c=c.firstChild.nodeValue.match(d),p=p.firstChild.nodeValue.match(d),c&&p&&c[0]!==p[0]&&(i.innerHTML=i.innerHTML.replace(RegExp("^"+c[0].replace(t,"\\$1"),"mg"),p[0])),/\bsf-dump-compact\b/.test(i.className)&&a(l,N(s))}if(r);else if(e.getSelection)try{e.getSelection().removeAllRanges()}catch(u){e.getSelection().empty()}else e.selection.empty()}else/\bsf-dump-str-toggle\b/.test(n.className)&&(s.preventDefault(),(s=n.parentNode.parentNode).className=s.className.replace(/\bsf-dump-str-(expand|collapse)\b/,n.parentNode.className))}),h=(m=c.getElementsByTagName("SAMP")).length,g=0;g<h;)_.push(m[g++]);for(g=0,h=_.length;g<h;++g)if("SAMP"==(m=_[g]).tagName)"A"!=(b=m.previousSibling||{}).tagName?((b=e.createElement("A")).className="sf-dump-ref",m.parentNode.insertBefore(b,m)):b.innerHTML+=" ",b.title=(b.title?b.title+"\n[":"[")+n+"+click] Expand all children",b.innerHTML+="sf-dump-compact"==m.className?"<span>▶</span>":"<span>▼</span>",b.className+=" sf-dump-toggle",p=1,"sf-dump"!=m.parentNode.className&&(p+=m.parentNode.getAttribute("data-depth")/1);else if(/\bsf-dump-ref\b/.test(m.className)&&(b=m.getAttribute("href"))&&(b=b.slice(1),m.className+=" sf-dump-hover",m.className+=" "+b,/[\[{]$/.test(m.previousSibling.nodeValue))){b=b!=m.nextSibling.id&&e.getElementById(b);try{u=b.nextSibling,m.appendChild(b),u.parentNode.insertBefore(b,u),/^[@#]/.test(m.innerHTML)?m.innerHTML+=" <span>▶</span>":(m.innerHTML="<span>▶</span>",m.className="sf-dump-ref"),m.className+=" sf-dump-toggle"}catch($){"&"==m.innerHTML.charAt(0)&&(m.innerHTML="…",m.className="sf-dump-ref")}}if(e.evaluate&&Array.from&&c.children.length>1){function y(e){var t,n,s,a,i,p=e.current();p&&(function e(t){for(var n,s=[];(n=(t=t.parentNode||{}).previousSibling)&&"A"===n.tagName;)s.push(n);return 0!==s.length&&(s.forEach(function(e){r(e)}),!0)}(p),t=c,n=p,s=e.nodes,l(t),Array.from(s||[]).forEach(function(e){/\bsf-dump-highlight\b/.test(e.className)||(e.className=e.className+" sf-dump-highlight")}),/\bsf-dump-highlight-active\b/.test(n.className)||(n.className=n.className+" sf-dump-highlight-active"),"scrollIntoView"in p&&(p.scrollIntoView(!0),a=p.getBoundingClientRect(),i=S.getBoundingClientRect(),a.top<i.top+i.height&&window.scrollBy(0,-(i.top+i.height+5)))),L.textContent=(e.isEmpty()?0:e.idx+1)+" of "+e.count()}c.setAttribute("style","z-index: 1"),c.setAttribute("tabindex",0),(SearchState=function(){this.nodes=[],this.idx=0}).prototype={next:function(){return this.isEmpty()||(this.idx=this.idx<this.nodes.length-1?this.idx+1:0),this.current()},previous:function(){return this.isEmpty()||(this.idx=this.idx>0?this.idx-1:this.nodes.length-1),this.current()},isEmpty:function(){return 0===this.count()},current:function(){return this.isEmpty()?null:this.nodes[this.idx]},reset:function(){this.nodes=[],this.idx=0},count:function(){return this.nodes.length}};var S=e.createElement("div");S.className="sf-dump-search-wrapper sf-dump-search-hidden",S.innerHTML=' <input type="text" class="sf-dump-search-input"> <span class="sf-dump-search-count">0 of 0</span> <button type="button" class="sf-dump-search-input-previous" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 1331l-166 165q-19 19-45 19t-45-19L896 965l-531 531q-19 19-45 19t-45-19l-166-165q-19-19-19-45.5t19-45.5l742-741q19-19 45-19t45 19l742 741q19 19 19 45.5t-19 45.5z"/></svg> </button> <button type="button" class="sf-dump-search-input-next" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 808l-742 741q-19 19-45 19t-45-19L109 808q-19-19-19-45.5t19-45.5l166-165q19-19 45-19t45 19l531 531 531-531q19-19 45-19t45 19l166 165q19 19 19 45.5t-19 45.5z"/></svg> </button> ',c.insertBefore(S,c.firstChild);var E=new SearchState,C=S.querySelector(".sf-dump-search-input"),L=S.querySelector(".sf-dump-search-count"),A=0,T="";s(C,"keyup",function(t){var n=t.target.value;n!==T&&(T=n,clearTimeout(A),A=setTimeout(function(){if(E.reset(),i(c),l(c),""===n){L.textContent="0 of 0";return}for(var t=["sf-dump-str","sf-dump-key","sf-dump-public","sf-dump-protected","sf-dump-private",].map(x).join(" or "),s=e.evaluate(".//span["+t+"][contains(translate(child::text(), "+v(n.toUpperCase())+", "+v(n.toLowerCase())+"), "+v(n.toLowerCase())+")]",c,null,XPathResult.ORDERED_NODE_ITERATOR_TYPE,null);node=s.iterateNext();)E.nodes.push(node);y(E)},400))}),Array.from(S.querySelectorAll(".sf-dump-search-input-next, .sf-dump-search-input-previous")).forEach(function(e){s(e,"click",function(e){e.preventDefault(),-1!==e.target.className.indexOf("next")?E.next():E.previous(),C.focus(),i(c),y(E)})}),s(c,"keydown",function(e){var t=!/\bsf-dump-search-hidden\b/.test(S.className);if(114===e.keyCode&&!t||N(e)&&70===e.keyCode){if(70===e.keyCode&&document.activeElement===C)return;e.preventDefault(),S.className=S.className.replace(/\bsf-dump-search-hidden\b/,""),C.focus()}else t&&(27===e.keyCode?(S.className+=" sf-dump-search-hidden",e.preventDefault(),l(c),C.value=""):(N(e)&&71===e.keyCode||13===e.keyCode||114===e.keyCode)&&(e.preventDefault(),e.shiftKey?E.previous():E.next(),i(c),y(E)))})}if(!(0>=f.maxStringLength))try{for(h=(m=c.querySelectorAll(".sf-dump-str")).length,g=0,_=[];g<h;)_.push(m[g++]);for(g=0,h=_.length;g<h;++g)p=(u=(m=_[g]).innerText||m.textContent).length-f.maxStringLength,0<p&&(o=m.innerHTML,m[m.innerText?"innerText":"textContent"]=u.substring(0,f.maxStringLength),m.className+=" sf-dump-str-collapse",m.innerHTML="<span class=sf-dump-str-collapse>"+o+'<a class="sf-dump-ref sf-dump-str-toggle" title="Collapse"> ◀</a></span><span class=sf-dump-str-expand>'+m.innerHTML+'<a class="sf-dump-ref sf-dump-str-toggle" title="'+p+' remaining characters"> ▶</a></span>')}catch(M){}}}(document),SearchState=function(){this.nodes=[],this.idx=0};export{Sfdump,SearchState};
