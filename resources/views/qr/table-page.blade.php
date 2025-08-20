<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>{{ $business->name }} | Mesa {{ $table->number }}</title>
    <meta name="theme-color" content="#0d1117" />
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/pdf-viewer.css') }}">
</head>
<body>
@php
    $menuUrl = null;
    if($defaultMenu && $defaultMenu->file_path){
        $filename = basename($defaultMenu->file_path);
        $menuUrl = route('menu.pdf', ['business_id'=>$business->id,'filename'=>$filename]);
    } elseif($business->menu_pdf) {
        $filename = basename($business->menu_pdf);
        $menuUrl = route('menu.pdf', ['business_id'=>$business->id,'filename'=>$filename]);
    }
@endphp

<div class="viewer-shell" id="viewerShell">
    @if($menuUrl)
    <!-- Header simplificado -->
    <div class="pdf-header" role="banner">
        <div class="brand" title="{{ $business->name }}">
            <span>MOZO</span>
            <small style="opacity:.7;">Mesa {{ $table->number }} • {{ $business->name }}</small>
        </div>
    </div>
    
    <!-- Área principal del PDF -->
    <div class="pdf-workspace">
        <aside class="thumbs-panel" id="thumbsPanel" aria-label="Miniaturas"></aside>
        <div class="canvas-stage" id="canvasStage">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner" role="status" aria-label="Cargando"></div>
                <div style="font-size:.85rem; letter-spacing:.5px; opacity:.75;">Cargando menú...</div>
            </div>
            <canvas id="pdfCanvas" aria-label="Página PDF"></canvas>
        </div>
    </div>
    
    <!-- Footer con controles -->
    <div class="pdf-controls-footer" role="toolbar" aria-label="Controles del visor PDF">
        <div class="controls-container row ">
            <!-- Navegación de páginas -->
            <div class="control-group col-5">
                <button class="control-btn" id="btnPrev" disabled title="Página anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="page-display">
                    <span id="pageNum">-</span>/<span id="pageCount">-</span>
                </div>
                <button class="control-btn" id="btnNext" disabled title="Página siguiente">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <!-- Controles de zoom -->
            <div class="control-group col-5">
                <button class="control-btn" id="btnZoomOut" disabled title="Alejar">
                    <i class="fas fa-search-minus"></i>
                </button>
                <div class="zoom-display" id="zoomLevel">100%</div>
                <button class="control-btn" id="btnZoomIn" disabled title="Acercar">
                    <i class="fas fa-search-plus"></i>
                </button>
            </div>
            
            
            <!-- Acciones adicionales -->
            <div class="control-group col-2">
                <a class="control-btn" id="btnDownload" href="{{ $menuUrl }}" target="_blank" rel="noopener" title="Abrir en nueva pestaña">
                    <i class="fas fa-external-link-alt"></i>
                    <span class="d-none d-md-inline">Abrir</span>
                </a>
            </div>
        </div>
    </div>
    @else
        <div class="pdf-fallback">
            <h2>Menú no disponible</h2>
            <p>No se encontró un PDF. Llamá al mozo y solicitá el menú físico.</p>
            <button class="btn btn-primary" onclick="togglePanel()">Llamar Mozo</button>
        </div>
    @endif
</div>

<!-- FAB -->
<button id="mozoFab" class="fab" aria-haspopup="dialog" aria-controls="waiterPanel" aria-expanded="false" title="Llamar mozo" onclick="togglePanel()">
    <img src="{{ asset('images/logo.jpeg') }}" alt="Logo Mozo" style="width: 48px; height: 48px; border-radius: 16px; object-fit: cover;">
    <span class="fab-text">MOZO</span>
</button>

<!-- Panel llamar mozo -->
<section id="waiterPanel" class="waiter-panel" role="dialog" aria-modal="true" aria-label="Panel para llamar al mozo">
    <div class="panel-grabber" onclick="togglePanel()"></div>
    <div class="panel-header">
        <h1>¿Necesitás ayuda?</h1>
        <small>Mesa {{ $table->number }} • {{ $business->name }}</small>
    </div>
    <button id="call-waiter-btn" class="call-btn" onclick="callWaiter()">🔔 Llamar Mozo</button>
    <div id="status-message" class="status-area"></div>
    @if(session('success'))
        <div class="status-area status-success" style="display:block;">🎉 {{ session('success') }} @if(session('notification_id'))<div class="ack-extra">⏳ Esperando confirmación...</div>@endif</div>
    @endif
    @if(session('error'))
        <div class="status-area status-error" style="display:block;">❌ {{ session('error') }}</div>
    @endif
</section>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-database-compat.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
    const menuUrl=@json($menuUrl);
    // Inicializar visor sólo si hay URL y PDF.js cargó; usar versión 3.x confiable
    if(menuUrl){
        let pdfjsLib=window.pdfjsLib;
        if(!pdfjsLib){
            console.warn('pdfjsLib no disponible todavía, intento fallback unpkg...');
            const s=document.createElement('script');
            s.src='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js';
            s.onload=()=>{ if(window.pdfjsLib){ initPdf(window.pdfjsLib); } else { console.error('No se pudo cargar PDF.js'); }};
            document.head.appendChild(s);
        } else {
            initPdf(pdfjsLib);
        }
    }

    function initPdf(pdfjsLib){
        try{ pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js'; }catch(e){ console.error('No se pudo configurar worker PDF',e); }
    const canvas=document.getElementById('pdfCanvas');
    const ctx=canvas.getContext('2d');
    const thumbsPanel=document.getElementById('thumbsPanel');
    const overlay=document.getElementById('loadingOverlay');
    const btnPrev=document.getElementById('btnPrev');
    const btnNext=document.getElementById('btnNext');
    const btnZoomIn=document.getElementById('btnZoomIn');
    const btnZoomOut=document.getElementById('btnZoomOut');
    const btnFitWidth=document.getElementById('btnFitWidth');
    const btnFitPage=document.getElementById('btnFitPage');
    const btnRotate=document.getElementById('btnRotate');
    const pageNumSpan=document.getElementById('pageNum');
    const pageCountSpan=document.getElementById('pageCount');
    const zoomLevel=document.getElementById('zoomLevel');
    const stage=document.getElementById('canvasStage');
    let pdfDoc=null,currentPage=1,scale=1,rotation=0,fitMode='width',rendering=false,pendingPage=null;
    function enable(v){[btnPrev,btnNext,btnZoomIn,btnZoomOut,btnFitWidth,btnFitPage,btnRotate].forEach(b=>b.disabled=!v);}  
    function calcFitScale(page){const vw=stage.clientWidth-24;const vh=stage.clientHeight-24;const w0=page.view[2];const h0=page.view[3];let w=(rotation%180===0)?w0:h0;let h=(rotation%180===0)?h0:w0;const sW=vw/w;const sP=Math.min(vh/h,vw/w);if(fitMode==='width')return sW;if(fitMode==='page')return sP;return scale;}  
    async function renderPage(num){rendering=true;const page=await pdfDoc.getPage(num);const effective=(['width','page'].includes(fitMode))?calcFitScale(page):scale;const vp=page.getViewport({scale:effective,rotation});canvas.width=vp.width;canvas.height=vp.height;zoomLevel.textContent=Math.round(effective*100)+'%';await page.render({canvasContext:ctx,viewport:vp}).promise;rendering=false;if(pendingPage){const p=pendingPage;pendingPage=null;renderPage(p);}}  
    function queueRender(p){rendering?pendingPage=p:renderPage(p);}  
    function update(){pageNumSpan.textContent=currentPage;pageCountSpan.textContent=pdfDoc.numPages;btnPrev.disabled=currentPage<=1;btnNext.disabled=currentPage>=pdfDoc.numPages;[...thumbsPanel.querySelectorAll('.thumb')].forEach(el=>el.classList.toggle('active',+el.dataset.page===currentPage));}
    function change(delta){const t=currentPage+delta;if(t>=1&&t<=pdfDoc.numPages){currentPage=t;update();queueRender(currentPage);}}  
    function setScale(s){scale=s;fitMode='manual';queueRender(currentPage);}  
    btnPrev.onclick=()=>change(-1);btnNext.onclick=()=>change(1);btnZoomIn.onclick=()=>setScale(scale*1.15);btnZoomOut.onclick=()=>setScale(Math.max(.25,scale/1.15));btnFitWidth.onclick=()=>{fitMode='width';queueRender(currentPage);};btnFitPage.onclick=()=>{fitMode='page';queueRender(currentPage);};btnRotate.onclick=()=>{rotation=(rotation+90)%360;queueRender(currentPage);};
    window.addEventListener('keydown',e=>{if(['INPUT','TEXTAREA'].includes(e.target.tagName))return; if(e.key==='ArrowLeft')change(-1); else if(e.key==='ArrowRight')change(1); else if((e.ctrlKey||e.metaKey)&&['+','=','Add'].includes(e.key)){e.preventDefault();btnZoomIn.click();} else if((e.ctrlKey||e.metaKey)&&['-','Subtract'].includes(e.key)){e.preventDefault();btnZoomOut.click();}});
    pdfjsLib.getDocument({url:menuUrl}).promise.then(pdf=>{pdfDoc=pdf;enable(true);pageCountSpan.textContent=pdf.numPages;update();renderPage(currentPage).then(()=>overlay.remove());for(let i=1;i<=Math.min(pdf.numPages,120);i++){pdf.getPage(i).then(p=>{const vp=p.getViewport({scale:.25});const c=document.createElement('canvas');c.width=vp.width;c.height=vp.height;const cx=c.getContext('2d');p.render({canvasContext:cx,viewport:vp}).promise.then(()=>{const wrap=document.createElement('div');wrap.className='thumb'+(i===1?' active':'');wrap.dataset.page=p.pageNumber;wrap.appendChild(c);wrap.onclick=()=>{currentPage=p.pageNumber;update();queueRender(currentPage);};thumbsPanel.appendChild(wrap);});});} });
        window.addEventListener('resize',()=>{if(['width','page'].includes(fitMode))queueRender(currentPage);});
    }
    // Panel & Firebase
    const CLIENT_IP='{{ $clientIp }}';
    let currentNotificationId=null,firebaseListener=null;
    @if(session('notification_id')) currentNotificationId='{{ session('notification_id') }}';startFirebaseListener();openPanel(); @endif
    function togglePanel(){const p=document.getElementById('waiterPanel');p.classList.contains('open')?closePanel():openPanel();}
    function openPanel(){document.getElementById('waiterPanel').classList.add('open');document.getElementById('mozoFab').classList.add('open');document.getElementById('mozoFab').setAttribute('aria-expanded','true');}
    function closePanel(){document.getElementById('waiterPanel').classList.remove('open');document.getElementById('mozoFab').classList.remove('open');document.getElementById('mozoFab').setAttribute('aria-expanded','false');}
    document.addEventListener('keydown',e=>{if(e.key==='Escape')closePanel();});
    function startFirebaseListener(){const cfg={projectId:"mozoqr-7d32c",apiKey:"AIzaSyDGJJKNfSSxD6YnXnNjwRb6VUtPSyGN5CM",authDomain:"mozoqr-7d32c.firebaseapp.com",databaseURL:"https://mozoqr-7d32c-default-rtdb.firebaseio.com",storageBucket:"mozoqr-7d32c.appspot.com"};if(!window.firebase||!firebase.apps.length)firebase.initializeApp(cfg);const ref=firebase.database().ref(`active_calls/${currentNotificationId}`);firebaseListener=ref.on('value',s=>{const d=s.val();if(!d)return; if(d.status==='acknowledged'){showAck(d.waiter?.name||d.waiter_name);ref.off('value',firebaseListener);} else if(d.status==='completed'){showDone(d.waiter?.name||d.waiter_name);ref.off('value',firebaseListener);} });}
    function statusEl(){return document.getElementById('status-message');}
    function showAck(w){const e=statusEl();e.className='status-area status-success';e.style.display='block';e.innerHTML=`🎉 ${w||'El mozo'} confirmó tu solicitud.<div class="ack-extra">🚶‍♂️ En camino...</div>`;notify('¡Mozo confirmado!',`${w||'El mozo'} está en camino`);} 
    function showDone(w){const e=statusEl();e.className='status-area status-success';e.style.display='block';e.innerHTML=`✅ ${w||'El mozo'} completó tu solicitud.`;notify('Servicio completado',`${w||'El mozo'} finalizó la asistencia`);} 
    function notify(title,body){if('Notification'in window&&Notification.permission==='granted'){new Notification(title,{body,icon:'/favicon.ico'});} }
    if('Notification'in window&&Notification.permission==='default'){Notification.requestPermission();}
    async function callWaiter(){const btn=document.getElementById('call-waiter-btn');const e=statusEl();btn.disabled=true;btn.classList.add('loading');btn.textContent='⏳ Llamando...';e.style.display='none';try{const r=await fetch('{{ route('waiter.call') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({restaurant_id:{{ $business->id }},table_id:{{ $table->id }},message:'Cliente solicita atención'})});const d=await r.json();if(r.ok&&d.success){e.className='status-area status-success';e.innerHTML='🎉 '+(d.message||'Mozo llamado exitosamente')+'<div class="ack-extra">⏳ Esperando confirmación...</div>';e.style.display='block';if(d.notification_id){currentNotificationId=d.notification_id;startFirebaseListener();}}else{e.className='status-area status-error';e.innerHTML='❌ '+(d.message||'Error al llamar al mozo');e.style.display='block';}}catch(err){e.className='status-area status-error';e.innerHTML='❌ Error de conexión, intenta de nuevo.';e.style.display='block';}finally{btn.disabled=false;btn.classList.remove('loading');btn.textContent='🔔 Llamar Mozo';}}
</script>

</body>
</html>