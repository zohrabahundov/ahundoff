/* Ahundoff Studio — визитка/препресс. Вынесено из inline в внешний файл (CSP script-src 'self'). */
(function(){
'use strict';
var URL_TG='https://t.me/ahundoff_studio';
var DEFAULTS={theme:'dark',zoom:100,grid:true,guides:false,bleed:false,qrSize:22,qrRound:0.3,qrLogo:true,accent:'#3B82F6',dpi:300};
var state=Object.assign({},DEFAULTS);
var QR=null, fontCSSCache=null, toastTimer=null;
var $=function(id){return document.getElementById(id)};
var shell=$('shell'), stage=$('stage'), printArea=$('printArea');
var f=function(v){return +v.toFixed(3)};

/* ---------- QR ---------- */
function buildQR(){
  if(typeof window.qrcode!=='function') return false;
  try{
    var q=window.qrcode(0,'H'); q.addData(URL_TG); q.make();
    var n=q.getModuleCount(), dark=[], r, c, row;
    for(r=0;r<n;r++){row=[];for(c=0;c<n;c++)row.push(q.isDark(r,c));dark.push(row);}
    QR={n:n,dark:dark}; return true;
  }catch(e){return false;}
}
function ensureQR(){
  if(buildQR()) return Promise.resolve(true);
  return new Promise(function(res){
    var s=document.createElement('script');
    s.src='/assets/vendor/qrcode.min.js';
    s.onload=function(){res(buildQR())}; s.onerror=function(){res(false)};
    document.head.appendChild(s);
  });
}
function inFinder(r,c,n){return (r<7&&c<7)||(r<7&&c>=n-7)||(r>=n-7&&c<7);}
function finderSVG(x,y,m,fx,fy){
  var out='',layers=[[0,7,2,'#030712'],[1,5,1.4,'#FFFFFF'],[2,3,0.9,'#030712']];
  for(var i=0;i<3;i++){var L=layers[i];
    out+='<rect x="'+f(x+(fx+L[0])*m)+'" y="'+f(y+(fy+L[0])*m)+'" width="'+f(L[1]*m)+'" height="'+f(L[1]*m)+'" rx="'+f(L[2]*m)+'" fill="'+L[3]+'"/>';}
  return out;
}
function qrSVG(){
  if(!QR) return '<text x="73" y="24" text-anchor="middle" font-size="2" fill="#EF4444">QR: библиотека не загрузилась</text>';
  var s=state.qrSize, x=73-s/2, y=12.5+(22-s)/2, n=QR.n, m=s/n, cx=n/2, cy=n/2;
  var clearR=state.qrLogo?3.9:0, rx=state.qrRound*m, rects='', r, c, c0;
  var keep=function(r,c){return QR.dark[r][c]&&!inFinder(r,c,n)&&((c+0.5-cx)*(c+0.5-cx)+(r+0.5-cy)*(r+0.5-cy)>clearR*clearR);};
  for(r=0;r<n;r++){c=0;
    while(c<n){ if(keep(r,c)){c0=c;while(c<n&&keep(r,c))c++;
        rects+='<rect x="'+f(x+c0*m)+'" y="'+f(y+r*m+0.05*m)+'" width="'+f((c-c0)*m)+'" height="'+f(0.9*m)+'" rx="'+f(rx)+'"/>';}
      else c++;}}
  var g='<g fill="#030712">'+rects+finderSVG(x,y,m,0,0)+finderSVG(x,y,m,n-7,0)+finderSVG(x,y,m,0,n-7);
  if(state.qrLogo){
    var ps=4.4*m/24, px=x+cx*m-12*ps, py=y+cy*m-12*ps;
    g+='<circle cx="'+f(x+cx*m)+'" cy="'+f(y+cy*m)+'" r="'+f(3.3*m)+'"/>';
    g+='<g transform="translate('+f(px)+' '+f(py)+') scale('+f(ps)+')"><polygon points="22 2 15 22 11 13 2 9" fill="#FFFFFF"/></g>';
  }
  return g+'</g>';
}

/* ---------- Card ---------- */
function pal(){
  if(state.theme==='light') return {bg:'#F8FAFC',txt:'#030712',sub:'#5B6B82',a1:'#0F172A',a2:'#475569',tile:'#0F172A',qrStroke:'#E2E8F0',glowOp:0.10};
  return {bg:'#030712',txt:'#F8FAFC',sub:'#94A3B8',a1:'#F8FAFC',a2:'#94A3B8',tile:'#0F172A',qrStroke:'none',glowOp:0.22};
}
function cardSVG(pre,withGuides,exportMode){
  var p=pal(), A=state.accent;
  var s='<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="94mm" height="54mm" viewBox="0 0 94 54">';
  if(exportMode) s+='<style>/*FONTS*/</style>';
  s+='<defs>'
   +'<linearGradient id="'+pre+'ag" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="'+p.a1+'"/><stop offset="1" stop-color="'+p.a2+'"/></linearGradient>'
   +'<radialGradient id="'+pre+'gl" cx="0.78" cy="0.12" r="0.9"><stop offset="0" stop-color="'+A+'" stop-opacity="'+p.glowOp+'"/><stop offset="1" stop-color="'+A+'" stop-opacity="0"/></radialGradient>'
   +'<pattern id="'+pre+'gr" width="6" height="6" patternUnits="userSpaceOnUse"><path d="M6 0H0V6" fill="none" stroke="'+p.sub+'" stroke-opacity="0.06" stroke-width="0.15"/></pattern>'
   +'</defs>';
  s+='<rect width="94" height="54" fill="'+p.bg+'"/>';
  if(state.grid) s+='<rect width="94" height="54" fill="url(#'+pre+'gr)"/>';
  s+='<rect width="94" height="54" fill="url(#'+pre+'gl)"/>';
  /* brand block */
  s+='<g transform="translate(7 7) scale(0.140625)">'
   +'<rect width="64" height="64" rx="14" fill="'+p.tile+'" stroke="#94A3B8" stroke-opacity="0.25" stroke-width="2"/>'
   +'<path d="M19 12 L31 12 L43 52 L34 52 L32.2 46 L17.8 46 L16 52 L7 52 Z M25 22 L30.1 39 L19.9 39 Z" fill="url(#'+pre+'ag)" fill-rule="evenodd" stroke="url(#'+pre+'ag)" stroke-width="1.5" stroke-linejoin="round"/>'
   +'<circle cx="51.5" cy="46.5" r="5.5" fill="'+A+'"/></g>';
  s+='<text x="18.5" y="11" font-family="Unbounded, Arial, sans-serif" font-weight="700" font-size="4.4" fill="'+p.txt+'">Ahundoff<tspan fill="'+A+'">.</tspan></text>';
  s+='<text x="18.6" y="15" font-family="Inter, Arial, sans-serif" font-weight="500" font-size="1.85" letter-spacing="0.1" fill="'+p.sub+'">AI-СТУДИЯ · AHUNDOFF.RU</text>';
  /* invitation */
  s+='<text x="7" y="26.5" font-family="Unbounded, Arial, sans-serif" font-weight="700" font-size="3.6" fill="'+p.txt+'">Любишь Kaggle-</text>';
  s+='<text x="7" y="31.3" font-family="Unbounded, Arial, sans-serif" font-weight="700" font-size="3.6" fill="'+p.txt+'">соревнования<tspan fill="'+A+'">?</tspan></text>';
  s+='<text x="7" y="36.6" font-family="Inter, Arial, sans-serif" font-size="2.5" fill="'+p.sub+'">Разборы решений победителей,</text>';
  s+='<text x="7" y="40" font-family="Inter, Arial, sans-serif" font-size="2.5" fill="'+p.sub+'">соревновательный ML и AI-стримы —</text>';
  s+='<text x="7" y="43.4" font-family="Inter, Arial, sans-serif" font-size="2.5" fill="'+p.sub+'">в Telegram-канале Ahundoff Studio.</text>';
  /* CTA */
  /*s+='<g transform="translate(7 44.9) scale(0.1333)" fill="none" stroke="'+A+'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9"/></g>';
  s+='<text x="11.6" y="47.4" font-family="Inter, Arial, sans-serif" font-weight="600" font-size="2.9" fill="'+p.txt+'">t.me/ahundoff_studio</text>';*/
  /* QR card */
  s+='<rect x="58" y="8.5" width="30" height="37" rx="4" fill="#FFFFFF"'+(p.qrStroke!=='none'?' stroke="'+p.qrStroke+'" stroke-width="0.4"':'')+'/>';
  s+=qrSVG();
  s+='<text x="73" y="39.6" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-weight="700" font-size="2.1" letter-spacing="0.05" fill="#030712">@AHUNDOFF_STUDIO</text>';
  s+='<text x="73" y="42.9" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="1.7" fill="#030712" fill-opacity="0.55">Наведи камеру телефона</text>';
  /* guides (preview only) */
  if(withGuides){
    if(state.bleed) s+='<path d="M0 0H94V54H0Z M2 2H92V52H2Z" fill="#EF4444" fill-opacity="0.10" fill-rule="evenodd"/>';
    if(state.guides){
      s+='<g fill="none" stroke-width="0.25" stroke-dasharray="1.2 0.9">'
       +'<rect x="2" y="2" width="90" height="50" stroke="#F59E0B"/>'
       +'<rect x="6" y="6" width="82" height="42" stroke="#60A5FA"/></g>';
    }
  }
  return s+'</svg>';
}
function renderAll(){
  shell.innerHTML=cardSVG('p',true,false);
  printArea.innerHTML=cardSVG('q',false,false);
  layout();
}
function layout(){
  var base=Math.min(stage.clientWidth-68,780);
  shell.style.width=Math.max(220,base*state.zoom/100)+'px';
  $('zoomChip').textContent=state.zoom+'%';
  $('vZoom').textContent=state.zoom+'%';
}

/* ---------- Fonts for export ---------- */
var CARD_CHARS='Ahundoff.AI-СТУДИЯ·AHUNDOFFRUЛюбишьKaggle-соревнования?Разборырешенийпобедителей,соревновательныйMLиAI-стримы—вTelegram-каналеAhundoffStudiot.me/ahundoff_studio@AHUNDOFF_STUDIOНаведикамерутелефона ';
function rangeCovers(range,cp){
  var parts=range.split(','),i,j,m,a,b;
  for(i=0;i<parts.length;i++){
    var t=parts[i].trim().replace(/^U\+/i,'');
    m=t.match(/^([0-9A-Fa-f]+)(?:-([0-9A-Fa-f]+))?$/);
    if(!m)continue; a=parseInt(m[1],16); b=m[2]?parseInt(m[2],16):a;
    if(cp>=a&&cp<=b)return true;
  }
  return false;
}
function abToB64(buf){
  var u=new Uint8Array(buf),s='',i;
  for(i=0;i<u.length;i+=0x8000)s+=String.fromCharCode.apply(null,u.subarray(i,i+0x8000));
  return btoa(s);
}
function buildFontCSS(){
  if(fontCSSCache)return Promise.resolve(fontCSSCache);
  // Аудит 21.11.2026: встраиваем self-hosted шрифты (same-origin), без запросов к Google.
  var CY='U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116';
  var LA='U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD';
  var files=[
    {fam:'Inter',w:'400 600',url:'/assets/fonts/inter-cyrillic.woff2',range:CY},
    {fam:'Inter',w:'400 600',url:'/assets/fonts/inter-latin.woff2',range:LA},
    {fam:'Unbounded',w:'500 700',url:'/assets/fonts/unbounded-cyrillic.woff2',range:CY},
    {fam:'Unbounded',w:'500 700',url:'/assets/fonts/unbounded-latin.woff2',range:LA}
  ];
  var jobs=files.map(function(f){
    return fetch(f.url).then(function(r){return r.arrayBuffer()}).then(function(buf){
      return "@font-face{font-family:'"+f.fam+"';font-style:normal;font-weight:"+f.w+";src:url(data:font/woff2;base64,"+abToB64(buf)+") format('woff2');unicode-range:"+f.range+";}";
    });
  });
  return Promise.all(jobs).then(function(arr){fontCSSCache=arr.join('\n');return fontCSSCache;});
}
function exportSVGString(px){
  var s=cardSVG('e',false,true);
  if(px)s=s.replace('width="94mm" height="54mm"','width="'+px[0]+'" height="'+px[1]+'"');
  return buildFontCSS().then(function(css){return s.replace('/*FONTS*/',css);});
}

/* ---------- Export actions ---------- */
function downloadBlob(blob,name){
  var a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;
  document.body.appendChild(a);a.click();setTimeout(function(){URL.revokeObjectURL(a.href);a.remove();},400);
}
function toast(msg,err){
  var t=$('toast');t.textContent=msg;t.className='show'+(err?' err':'');
  clearTimeout(toastTimer);toastTimer=setTimeout(function(){t.className=''},2600);
}
function doPNG(){
  var dpi=state.dpi,W=Math.round(94/25.4*dpi),H=Math.round(54/25.4*dpi);
  toast('Рендер PNG '+dpi+' dpi…');
  exportSVGString([W,H]).then(function(svg){
    var url=URL.createObjectURL(new Blob([svg],{type:'image/svg+xml'}));
    var img=new Image();
    img.onload=function(){
      var c=document.createElement('canvas');c.width=W;c.height=H;
      c.getContext('2d').drawImage(img,0,0,W,H);URL.revokeObjectURL(url);
      c.toBlob(function(b){downloadBlob(b,'ahundoff-vizitka-'+dpi+'dpi.png');toast('PNG сохранён · '+W+'×'+H+' px');},'image/png');
    };
    img.onerror=function(){URL.revokeObjectURL(url);toast('Не удалось растеризовать SVG',true);};
    img.src=url;
  }).catch(function(){toast('Ошибка загрузки шрифтов для экспорта',true);});
}
function doSVG(){
  exportSVGString(null).then(function(svg){
    downloadBlob(new Blob([svg],{type:'image/svg+xml'}),'ahundoff-vizitka.svg');
    toast('SVG сохранён (шрифты встроены)');
  }).catch(function(){toast('Ошибка загрузки шрифтов для экспорта',true);});
}
function doCopy(){
  exportSVGString(null).then(function(svg){
    if(navigator.clipboard&&navigator.clipboard.writeText){
      navigator.clipboard.writeText(svg).then(function(){toast('SVG скопирован в буфер');},function(){fallbackCopy(svg);});
    }else fallbackCopy(svg);
  }).catch(function(){toast('Ошибка загрузки шрифтов для экспорта',true);});
}
function fallbackCopy(txt){
  var ta=document.createElement('textarea');ta.value=txt;ta.style.position='fixed';ta.style.opacity='0';
  document.body.appendChild(ta);ta.select();
  try{document.execCommand('copy');toast('SVG скопирован в буфер');}catch(e){toast('Копирование недоступно',true);}
  ta.remove();
}
function doPDF(){renderAll();setTimeout(function(){window.print();},60);}

/* ---------- Controls ---------- */
function syncControls(){
  $('cTheme').value=state.theme;$('cZoom').value=state.zoom;$('cGrid').checked=state.grid;
  $('cGuides').checked=state.guides;$('cBleed').checked=state.bleed;
  $('cQrSize').value=state.qrSize;$('cQrRound').value=state.qrRound;$('cQrLogo').checked=state.qrLogo;
  $('cAccent').value=state.accent;$('cDpi').value=state.dpi;
  $('vQrSize').textContent=state.qrSize+' мм';$('vQrRound').textContent=(+state.qrRound).toFixed(2);
  $('legend').className='legend'+(state.guides||state.bleed?' on':'');
}
function wire(){
  $('cTheme').addEventListener('change',function(){state.theme=this.value;renderAll();});
  $('cZoom').addEventListener('input',function(){state.zoom=+this.value;layout();});
  $('cGrid').addEventListener('change',function(){state.grid=this.checked;renderAll();});
  $('cGuides').addEventListener('change',function(){state.guides=this.checked;syncControls();renderAll();});
  $('cBleed').addEventListener('change',function(){state.bleed=this.checked;syncControls();renderAll();});
  $('cQrSize').addEventListener('input',function(){state.qrSize=+this.value;$('vQrSize').textContent=this.value+' мм';renderAll();});
  $('cQrRound').addEventListener('input',function(){state.qrRound=+this.value;$('vQrRound').textContent=(+this.value).toFixed(2);renderAll();});
  $('cQrLogo').addEventListener('change',function(){state.qrLogo=this.checked;renderAll();});
  $('cAccent').addEventListener('input',function(){state.accent=this.value;renderAll();});
  $('cDpi').addEventListener('change',function(){state.dpi=+this.value;});
  $('btnReset').addEventListener('click',function(){state=Object.assign({},DEFAULTS);syncControls();renderAll();toast('Настройки сброшены');});
  $('btnPng').addEventListener('click',doPNG);
  $('btnSvg').addEventListener('click',doSVG);
  $('btnPdf').addEventListener('click',doPDF);
  $('btnPdfTop').addEventListener('click',doPDF);
  $('btnCopy').addEventListener('click',doCopy);
  $('btnCopyTop').addEventListener('click',doCopy);
  window.addEventListener('resize',layout);
  window.addEventListener('beforeprint',renderAll);
}
function fontChip(){
  var ok=document.fonts&&document.fonts.check('700 16px Unbounded')&&document.fonts.check('400 16px Inter');
  var chip=$('fontChip');chip.className='chip'+(ok?' ok':'');
  $('fontChipTxt').textContent=ok?'Unbounded + Inter активны':'Шрифты загружаются…';
}

/* ---------- Init ---------- */
ensureQR().then(function(ok){
  if(!ok)toast('QR-библиотека не загрузилась — проверьте сеть',true);
  syncControls();wire();renderAll();fontChip();
  if(document.fonts){document.fonts.ready.then(fontChip);document.fonts.addEventListener&&document.fonts.addEventListener('loadingdone',fontChip);}
});
})();
