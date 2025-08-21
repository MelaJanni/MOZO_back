<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>{{ $business->name }} | Mesa {{ $table->number }}</title>
    <meta name="theme-color" content="#0d1117" />
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="Pragma" content="no-cache" />
        <meta http-equiv="Expires" content="0" />
    {{-- IMPORTANTE: Para asegurar que nginx/apache no cachee este CSS, establecer en server:
         Cache-Control: no-store para /live-scss/ o desactivar proxy cache. --}}
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    {{-- Incrustar SCSS compilado inline para bypass TOTAL de caché (solo usar en debug) --}}
    @php
        try {
            $scssPath = resource_path('css/pdf-viewer.scss');
            $publicCssPath = public_path('css/pdf-viewer.css');
            $meta = [
                'time' => microtime(true),
                'scss_exists' => file_exists($scssPath),
                'scss_md5' => file_exists($scssPath)? md5_file($scssPath): null,
                'public_exists' => file_exists($publicCssPath),
                'public_md5' => file_exists($publicCssPath)? md5_file($publicCssPath): null,
            ];
            if(class_exists(\ScssPhp\ScssPhp\Compiler::class) && $meta['scss_exists']) {
                $__scss_compiler = new \ScssPhp\ScssPhp\Compiler();
                $scssRaw = file_get_contents($scssPath);
                $compiled = $__scss_compiler->compileString($scssRaw)->getCss();
                // Guardamos comentario con metadata y comprobamos si aparece padding-bottom:120px (indicador de versión vieja)
                $flagOld = (strpos($compiled,'padding-bottom: 120px') !== false);
                $cssInline = '/*INLINE-SCSS meta='.json_encode($meta).' oldPadding='.(int)$flagOld.' */\n'. $compiled;
            } else {
                $fallback = $meta['public_exists']? file_get_contents($publicCssPath): '/* scssphp no disponible y sin fallback */';
                $cssInline = '/*INLINE-FALLBACK meta='.json_encode($meta).' */\n'.$fallback;
            }
        } catch(\Throwable $e) { $cssInline = '/* Error SCSS: '. addslashes($e->getMessage()) .' */'; }
    @endphp
    <style id="pdf-viewer-inline-css">{!! $cssInline !!}</style>
    <!-- Hammer.js CDN para garantizar disponibilidad en producción (fallback a node si bundler lo incluye) -->
    <!-- Hammer.js sin SRI (hash previo inválido causaba bloqueo). Si quieres SRI válido, calcular y reponer -->
    <script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8/hammer.min.js" crossorigin="anonymous"></script>
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
    <!-- Header simplificado -->
    <div class="pdf-header" role="banner">
        <div class="brand" title="{{ $business->name }}">
            <span>MOZO</span>
            <small style="opacity:.7;">Mesa {{ $table->number }} • {{ $business->name }}</small>
        </div>
    </div>
    <div id="pageIndicator" class="page-indicator" aria-live="polite">
        <span class="curr" id="pageNumTop">-</span>
        <span class="sep">/</span>
        <span id="pageCountTop">-</span>
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
    <div class="pdf-controls-footer d-none" role="toolbar" aria-label="Controles del visor PDF">
        <div class="controls-container row ">
            <!-- Navegación de páginas -->
            <div class="control-group col p-0">
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
            <!-- Acciones adicionales -->
            <div class="control-group col col-btn p-0">
                <a class="control-btn" id="btnDownload" href="{{ $menuUrl ?? '#' }}" target="_blank" rel="noopener" title="Abrir en nueva pestaña" @if(!$menuUrl) style="pointer-events:none;opacity:.4;" @endif>
                    <i class="fas fa-external-link-alt"></i>
                    <span class="d-none d-md-inline">Abrir</span>
                </a>
            </div>
        </div>
    </div>
    @if(!$menuUrl)
        <div id="missingMenuMsg" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:40;pointer-events:none;">
            <div style="text-align:center;max-width:300px;padding:24px 20px;border:1px solid rgba(139,92,246,.35);background:rgba(17,24,39,.75);backdrop-filter:blur(12px);border-radius:16px;box-shadow:0 6px 24px -8px rgba(0,0,0,.7);">
                <h3 style="margin:0 0 10px;font:600 16px 'Inter',var(--font-sans);color:#fff;letter-spacing:.5px;">Menú no disponible</h3>
                <p style="margin:0 0 12px;font:13px/1.4 'Inter',var(--font-sans);opacity:.78;">Por favor llamá al mozo. Te traerá la carta física en unos segundos.</p>
                <button onclick="togglePanel()" style="background:#8b5cf6;border:none;color:#fff;font:600 13px 'Inter',var(--font-sans);padding:8px 14px;border-radius:6px;pointer-events:auto;">Llamar al mozo</button>
            </div>
        </div>
    @endif
</div>

<!-- FAB -->
<button id="mozoFab" class="fab" aria-haspopup="dialog" aria-controls="waiterPanel" aria-expanded="false" title="Llamar mozo" onclick="togglePanel()">
    <img src="{{ asset('images/logo.jpeg') }}" alt="Logo Mozo" style="width: 48px; height: 48px; border-radius: 16px; object-fit: cover;">
    <span class="fab-text">MOZO</span>
    <div class="fab-tooltip">Presioná para llamar al mozo</div>
    <div class="fab-callout show pulse-border" id="fabCallout">
        <p>Presioná acá y llamá al mozo</p>
    </div>
</button>

<!-- Panel llamar mozo -->
<section id="waiterPanel" class="waiter-panel" role="dialog" aria-modal="true" aria-label="Panel para llamar al mozo">
    <div class="panel-grabber" onclick="togglePanel()">
        <i class="fas fa-chevron-down"></i>
    </div>
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

<div id="waiterBackdrop" class="waiter-backdrop" onclick="closePanel()"></div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-database-compat.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
    const menuUrl=@json($menuUrl);
    
    // Función para inicializar PDF.js con retry
    function waitForPdfjs(callback, attempts = 0) {
        if (window.pdfjsLib && window.pdfjsLib.GlobalWorkerOptions) {
            callback(window.pdfjsLib);
        } else if (attempts < 10) {
            setTimeout(() => waitForPdfjs(callback, attempts + 1), 100);
        } else {
            console.error('PDF.js no pudo cargarse después de múltiples intentos');
            document.getElementById('loadingOverlay').innerHTML = 
                '<div style="text-align:center;"><h3>Error cargando visor PDF</h3><p>Por favor recarga la página</p></div>';
        }
    }
    
    // Inicializar visor sólo si hay URL
    if(menuUrl){
        // Esperar a que PDF.js esté completamente cargado
        waitForPdfjs(initPdf);
    } else {
        // Sin menú: desactivar overlay spinner y dejar mensaje (si no está ya)
        const overlay=document.getElementById('loadingOverlay');
        if(overlay){overlay.style.display='none';}
    }

    function initPdf(pdfjsLib){
        try{ 
            // Configurar worker de PDF.js
            if(pdfjsLib.GlobalWorkerOptions) {
                pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js'; 
            }
        }catch(e){ 
            console.error('Error configurando worker PDF:', e); 
        }
    const canvas=document.getElementById('pdfCanvas');
    const ctx=canvas.getContext('2d');
    const thumbsPanel=document.getElementById('thumbsPanel');
    const overlay=document.getElementById('loadingOverlay');
    const btnPrev=document.getElementById('btnPrev');
    const btnNext=document.getElementById('btnNext');
    const btnZoomIn=document.getElementById('btnZoomIn');
    const btnZoomOut=document.getElementById('btnZoomOut');
    const pageNumSpan=document.getElementById('pageNum');
    const pageCountSpan=document.getElementById('pageCount');
    const pageNumTop=document.getElementById('pageNumTop');
    const pageCountTop=document.getElementById('pageCountTop');
    let zoomLevel=document.getElementById('zoomLevel');
    if(!zoomLevel){
        // fallback: intenta crear un indicador flotante minimal si no existe en DOM
        const zl=document.createElement('div');
        zl.id='zoomLevel';
        zl.style.cssText='position:fixed;bottom:8px;left:8px;background:rgba(0,0,0,.55);color:#fff;font:12px/1 system-ui;padding:4px 6px;border-radius:6px;z-index:999;pointer-events:none;';
        zl.textContent='100%';
        document.body.appendChild(zl);
        zoomLevel=zl;
    }
    const stage=document.getElementById('canvasStage');
    let pdfDoc=null,currentPage=1,scale=1,rotation=0,fitMode='width',rendering=false,pendingPage=null; // rotation fijo 0
    function enable(v){[btnPrev,btnNext,btnZoomIn,btnZoomOut].forEach(b=>b&&(b.disabled=!v));}  
    function calcFitScale(page){const vw=stage.clientWidth-24;const vh=stage.clientHeight-24;const w0=page.view[2];const h0=page.view[3];const sW=(rotation%180===0)?(vw/w0):(vw/h0);const sP=Math.min(vh/h0,vw/w0);if(fitMode==='width')return sW;if(fitMode==='page')return sP;return scale;}  
    // Renderiza manteniendo opcionalmente un punto ancla (anchor) centrado tras el zoom
    async function renderPage(num, anchor){
        rendering=true;
        const page=await pdfDoc.getPage(num);
        const effective=(['width','page'].includes(fitMode))?calcFitScale(page):scale;
        const vp=page.getViewport({scale:effective,rotation});
        // High-DPI para nitidez
        const dpr=window.devicePixelRatio||1;
        canvas.width=vp.width*dpr; canvas.height=vp.height*dpr;
        canvas.style.width=vp.width+'px'; canvas.style.height=vp.height+'px';
        ctx.setTransform(dpr,0,0,dpr,0,0);
        if(zoomLevel) zoomLevel.textContent=Math.round(effective*100)+'%';
        await page.render({canvasContext:ctx,viewport:vp}).promise;
        // Reposicionar scroll para mantener anchor
        if(anchor){
            const scrollXRatio=anchor.x / anchor.prevWidth;
            const scrollYRatio=anchor.y / anchor.prevHeight;
            stage.scrollLeft = scrollXRatio * vp.width - anchor.viewportCenterX;
            stage.scrollTop  = scrollYRatio * vp.height - anchor.viewportCenterY;
        }
        rendering=false; if(pendingPage){const p=pendingPage; pendingPage=null; renderPage(p);} }
    function queueRender(p, anchor){rendering?pendingPage=p:renderPage(p, anchor);}  
    function update(){
        pageNumSpan.textContent=currentPage; pageCountSpan.textContent=pdfDoc.numPages;
        if(pageNumTop) pageNumTop.textContent=currentPage;
        if(pageCountTop) pageCountTop.textContent=pdfDoc.numPages;
        btnPrev.disabled=currentPage<=1; btnNext.disabled=currentPage>=pdfDoc.numPages;
        [...thumbsPanel.querySelectorAll('.thumb')].forEach(el=>el.classList.toggle('active',+el.dataset.page===currentPage));
    }
    function change(delta){const t=currentPage+delta;if(t>=1&&t<=pdfDoc.numPages){currentPage=t;update();queueRender(currentPage);}}  
    function setScale(s, anchor){
        scale=Math.min(6,Math.max(.2,s));
        fitMode='manual';
        queueRender(currentPage, anchor);
    }  
    if(btnPrev) btnPrev.onclick=()=>change(-1);
    if(btnNext) btnNext.onclick=()=>change(1);
    if(btnZoomIn) btnZoomIn.onclick=()=>setScale(scale*1.15);
    if(btnZoomOut) btnZoomOut.onclick=()=>setScale(Math.max(.25,scale/1.15));
    window.addEventListener('keydown',e=>{if(['INPUT','TEXTAREA'].includes(e.target.tagName))return; if(e.key==='ArrowLeft')change(-1); else if(e.key==='ArrowRight')change(1); else if((e.ctrlKey||e.metaKey)&&['+','=','Add'].includes(e.key)){e.preventDefault();btnZoomIn.click();} else if((e.ctrlKey||e.metaKey)&&['-','Subtract'].includes(e.key)){e.preventDefault();btnZoomOut.click();}});
        pdfjsLib.getDocument({url:menuUrl}).promise
            .then(pdf=>{pdfDoc=pdf;enable(true);pageCountSpan.textContent=pdf.numPages;update();renderPage(currentPage).then(()=>overlay.remove());for(let i=1;i<=Math.min(pdf.numPages,120);i++){pdf.getPage(i).then(p=>{const vp=p.getViewport({scale:.25});const c=document.createElement('canvas');c.width=vp.width;c.height=vp.height;const cx=c.getContext('2d');p.render({canvasContext:cx,viewport:vp}).promise.then(()=>{const wrap=document.createElement('div');wrap.className='thumb'+(i===1?' active':'');wrap.dataset.page=p.pageNumber;wrap.appendChild(c);wrap.onclick=()=>{currentPage=p.pageNumber;update();queueRender(currentPage);};thumbsPanel.appendChild(wrap);});});} })
            .catch(err=>{
                    console.error('Error cargando PDF', err);
                                overlay.innerHTML = '<div style="text-align:center;padding:24px;">'+
                                    '<h3 style="margin:0 0 8px;font:600 16px \'Inter\',var(--font-sans);color:#fff;">Menú no disponible</h3>'+
                                    '<p style="margin:0 0 14px;font:13px/1.45 \'Inter\',var(--font-sans);opacity:.78;">Por favor llamá al mozo, te acercará la carta física enseguida.</p>'+
                                    '<button style="background:#8b5cf6;border:none;color:#fff;padding:8px 16px;border-radius:6px;font:600 13px \'Inter\',var(--font-sans);" onclick="togglePanel()">Llamar al mozo</button>'+
                                '</div>';
            });
        window.addEventListener('resize',()=>{if(['width','page'].includes(fitMode))queueRender(currentPage);});
    // === Gestos táctiles (Hammer.js) ===
    // Simplifica y hace más confiable pinch / pan / tap / swipe.
    // Fallback si Hammer no está: se conserva lógica previa (parcial abajo) 
    let useHammer = typeof Hammer !== 'undefined';

        let tapTimes=[]; // almacenar timestamps para multi-tap
    let pinchStartDist=null; let startScaleRef=scale; let touchMode=null; let panStart=null; let stageScrollStart=null;
    let twoFingerTapTimer=null; let longPressTimer=null;
    let multiFingerStartTime=null; let multiFingerReleased=false; let activeTouches=0;
    let swipeStartX=null; let swipeStartY=null; let swipeFingerCount=0;
    function dist(a,b){const dx=a.clientX-b.clientX;const dy=a.clientY-b.clientY;return Math.hypot(dx,dy);}    
        function applyFitWidth(){fitMode='width';queueRender(currentPage);} 
        function applyFitPage(){fitMode='page';queueRender(currentPage);} 
    function smartZoomToggle(){ setScale(scale* (scale<1.5?1.6:(scale>2.5?0.6:1.2))); }
        function resetView(){scale=1;applyFitWidth();}
        function toggleThumbs(){thumbsPanel.style.display=thumbsPanel.style.display==='none'?'flex':'none';}
    // Inicializa modo peek
    thumbsPanel.classList.add('peek');
    // Variables edge & loupe
    let edgeTracking=false, edgeShown=false, edgeStartX=null; const EDGE_ZONE=24, EDGE_THRESHOLD=14;
    // Lupa eliminada

        if(useHammer){
            const h = new Hammer.Manager(stage);
            h.add(new Hammer.Pinch({enable:true}));
            h.add(new Hammer.Pan({direction: Hammer.DIRECTION_ALL, threshold:0}));
            h.add(new Hammer.Tap({event:'doubletap', taps:2, interval:400, posThreshold:60}));
            h.add(new Hammer.Tap({event:'tripletap', taps:3, interval:600, posThreshold:80}));
            h.add(new Hammer.Tap()); // single
            h.add(new Hammer.Swipe({direction: Hammer.DIRECTION_VERTICAL, velocity:0.3, threshold:18}));
            h.get('doubletap').recognizeWith('tripletap');
            let hammerInitialScale=scale; let pinchTempScale=scale; let pinchStartScroll=null; let pinchCenter=null;
            h.on('pinchstart',ev=>{
                hammerInitialScale=scale; pinchStartScroll={x:stage.scrollLeft,y:stage.scrollTop};
                const rect=stage.getBoundingClientRect();
                pinchCenter={x:ev.center.x-rect.left+stage.scrollLeft,y:ev.center.y-rect.top+stage.scrollTop};
                canvas.style.transformOrigin='0 0';
            });
            h.on('pinchmove',ev=>{
                pinchTempScale=Math.min(6,Math.max(.2,hammerInitialScale*ev.scale));
                const factor=pinchTempScale/hammerInitialScale;
                canvas.style.transform=`scale(${pinchTempScale})`;
                stage.scrollLeft = pinchCenter.x*factor - (ev.center.x - stage.getBoundingClientRect().left);
                stage.scrollTop  = pinchCenter.y*factor - (ev.center.y - stage.getBoundingClientRect().top);
            });
            h.on('pinchend',ev=>{
                // Preparar anchor antes de limpiar transform
                const rect=stage.getBoundingClientRect();
                const prevWidth=parseFloat(canvas.style.width)||canvas.width;
                const prevHeight=parseFloat(canvas.style.height)||canvas.height;
                const anchor={
                    x: pinchCenter.x,
                    y: pinchCenter.y,
                    prevWidth: prevWidth,
                    prevHeight: prevHeight,
                    viewportCenterX: ev.center.x - rect.left,
                    viewportCenterY: ev.center.y - rect.top
                };
                canvas.style.transform='';
                setScale(pinchTempScale, anchor);
                if(Math.abs(scale-1)<0.08) applyFitWidth();
            });
            h.on('panmove',ev=>{
                if(scale>1.02){
                    stage.scrollLeft -= ev.deltaX - (h.prevDeltaX||0);
                    stage.scrollTop  -= ev.deltaY - (h.prevDeltaY||0);
                }
                h.prevDeltaX=ev.deltaX; h.prevDeltaY=ev.deltaY;
            });
            h.on('panend',()=>{h.prevDeltaX=0;h.prevDeltaY=0;});
            h.on('doubletap',()=> smartZoomToggle());
            h.on('tripletap',()=> resetView());
            h.on('swipe',ev=>{ if(ev.direction===Hammer.DIRECTION_UP) change(1); else if(ev.direction===Hammer.DIRECTION_DOWN) change(-1); });
            // Long press manual
            let lpTimer=null; stage.addEventListener('touchstart',e=>{lpTimer=setTimeout(()=>toggleThumbs(),650);},{passive:true}); stage.addEventListener('touchend',()=>{clearTimeout(lpTimer);lpTimer=null;},{passive:true});
            // Loupe activación: pinch mantenido (>500ms) -> mostrar
            // Eliminada activación de lupa durante pinch
        }
        if(!useHammer){
        stage.addEventListener('touchstart',e=>{
            if(e.touches.length===1 && e.touches[0].clientX<EDGE_ZONE && thumbsPanel.classList.contains('peek')){edgeTracking=true; edgeStartX=e.touches[0].clientX;}
            activeTouches=e.touches.length;
            if(activeTouches===1){
                touchMode='single';
                panStart={x:e.touches[0].clientX,y:e.touches[0].clientY};
                stageScrollStart={x:stage.scrollLeft,y:stage.scrollTop};
                swipeStartX=e.touches[0].clientX;swipeStartY=e.touches[0].clientY;swipeFingerCount=1;
                longPressTimer=setTimeout(()=>{toggleThumbs();},600);
            } else if(activeTouches===2){
                touchMode='pinch';
                pinchStartDist=dist(e.touches[0],e.touches[1]); pinchCenter={x:(e.touches[0].clientX+e.touches[1].clientX)/2 - stage.getBoundingClientRect().left, y:(e.touches[0].clientY+e.touches[1].clientY)/2 - stage.getBoundingClientRect().top};
                startScaleRef=scale; multiFingerStartTime=Date.now(); rotationApplied=false; multiFingerReleased=false;
                // rotación deshabilitada
                twoFingerTapTimer=setTimeout(()=>{twoFingerTapTimer=null;},250); // ventana two-finger tap
                swipeFingerCount=2; swipeStartX=(e.touches[0].clientX+e.touches[1].clientX)/2; swipeStartY=(e.touches[0].clientY+e.touches[1].clientY)/2;
                // lupa eliminada
            }
        },{passive:true});
        stage.addEventListener('touchmove',e=>{
            if(longPressTimer && e.touches.length && (Math.abs(e.touches[0].clientX-panStart.x)>8 || Math.abs(e.touches[0].clientY-panStart.y)>8)) {clearTimeout(longPressTimer);longPressTimer=null;}
            if(touchMode==='pinch' && e.touches.length===2 && pinchStartDist){
                e.preventDefault();
                const d=dist(e.touches[0],e.touches[1]);
                const factor=d/pinchStartDist;
                const centerX=(e.touches[0].clientX+e.touches[1].clientX)/2 - stage.getBoundingClientRect().left;
                const centerY=(e.touches[0].clientY+e.touches[1].clientY)/2 - stage.getBoundingClientRect().top;
                setScale(startScaleRef*factor,centerX,centerY);
                // rotación eliminada
            } else if(touchMode==='single' && e.touches.length===1 && panStart){
                const dx=e.touches[0].clientX-panStart.x; const dy=e.touches[0].clientY-panStart.y;
                if(scale>calcFitScale({view:[0,0,canvas.width/scale,canvas.height/scale]})+0.02){
                    stage.scrollLeft=stageScrollStart.x-dx;
                    stage.scrollTop=stageScrollStart.y-dy;
                }
                if(edgeTracking){const d=e.touches[0].clientX-edgeStartX; if(d>EDGE_THRESHOLD && !edgeShown){thumbsPanel.classList.add('show'); edgeShown=true;}}
            }
            // lupa eliminada
        },{passive:false});
        stage.addEventListener('touchend',e=>{
            if(longPressTimer){clearTimeout(longPressTimer);longPressTimer=null;}
            if(e.touches.length===0){
                // Multi taps
                const now=Date.now(); tapTimes=tapTimes.filter(t=>now-t<600); tapTimes.push(now);
                if(tapTimes.length>=3){ // triple
                    resetView(); tapTimes=[];
                } else if(tapTimes.length>=2){ // doble
                    smartZoomToggle();
                }
                // Pinch release snap
                if(touchMode==='pinch' && Math.abs(scale-1)<0.08){applyFitWidth();}
                // Two finger tap detection (dos dedos tocar y soltar rápido sin mover ni hacer pinch)
                if(swipeFingerCount===2 && pinchStartDist && !multiFingerReleased){
                    const duration=Date.now()-multiFingerStartTime;
                    if(duration<220 && twoFingerTapTimer){ // toggle fit mode
                        fitMode = (fitMode==='width')? 'page':'width';
                        queueRender(currentPage);
                    }
                    multiFingerReleased=true;
                }
                // Swipe logic
                if(swipeStartX!=null){
                    const endX = (e.changedTouches[0]||{}).clientX||swipeStartX;
                    const dx=endX - swipeStartX; const dy=Math.abs(((e.changedTouches[0]||{}).clientY||swipeStartY)-swipeStartY);
                    if(Math.abs(dx)>60 && dy<60){
                        if(swipeFingerCount===2){ // salto rápido
                            change(dx<0? Math.min(3,pdfDoc.numPages-currentPage): -Math.min(3,currentPage-1));
                        } else {
                            if(dx<0) change(1); else change(-1);
                        }
                    }
                }
                pinchStartDist=null;panStart=null;touchMode=null;swipeStartX=null;swipeStartY=null;swipeFingerCount=0;
                if(edgeTracking){setTimeout(()=>{thumbsPanel.classList.remove('show'); edgeShown=false;},2200); edgeTracking=false;}
                // lupa eliminada
            }
    }); // cierre listeners manuales
    } // fin fallback sin Hammer
        // Evita scroll/refresh al hacer swipe down sobre el panel abierto
        document.addEventListener('touchmove',e=>{
            const panel=document.getElementById('waiterPanel');
            if(panel.classList.contains('open')){
                // Si se inicia en el panel, prevenir overscroll superior
                const tgt=e.target.closest('#waiterPanel');
                if(tgt){e.preventDefault();}
            }
        },{passive:false});
    }
    // Panel & Firebase
    const CLIENT_IP='{{ $clientIp }}';
    let currentNotificationId=null,firebaseListener=null;
    @if(session('notification_id')) currentNotificationId='{{ session('notification_id') }}';startFirebaseListener();openPanel(); @endif
    function togglePanel(){const p=document.getElementById('waiterPanel');p.classList.contains('open')?closePanel():openPanel();}
    function openPanel(){document.getElementById('waiterPanel').classList.add('open');document.getElementById('mozoFab').classList.add('open');document.getElementById('mozoFab').setAttribute('aria-expanded','true');document.getElementById('waiterBackdrop').classList.add('open');}
    function closePanel(){document.getElementById('waiterPanel').classList.remove('open');document.getElementById('mozoFab').classList.remove('open');document.getElementById('mozoFab').setAttribute('aria-expanded','false');document.getElementById('waiterBackdrop').classList.remove('open');}
    document.addEventListener('keydown',e=>{if(e.key==='Escape')closePanel();});
    // Dismiss callout al primer uso / scroll
    (function(){
        const callout=document.getElementById('fabCallout');
        if(!callout) return;
        function hide(){callout.classList.add('dismissed');setTimeout(()=>callout.remove(),600);window.removeEventListener('scroll',hide);document.removeEventListener('click',hide,true);}
        document.getElementById('mozoFab').addEventListener('click',hide,{once:true});
        window.addEventListener('scroll',hide,{passive:true});
        setTimeout(()=>{ // auto-dismiss tras 12s
            if(document.body.contains(callout)) hide();
        },12000);
    })();
    
    // Swipe down functionality
    let touchStartY = 0;
    let touchStartTime = 0;
    const panel = document.getElementById('waiterPanel');
    
    panel.addEventListener('touchstart', e => {
        touchStartY = e.touches[0].clientY;
        touchStartTime = Date.now();
    }, { passive: true });
    
    panel.addEventListener('touchmove', e => {
        if (!panel.classList.contains('open')) return;
        const touchY = e.touches[0].clientY;
        const deltaY = touchY - touchStartY;
        
        if (deltaY > 0) {
            const progress = Math.min(deltaY / 150, 1);
            panel.style.transform = `translateY(${deltaY * 0.5}px)`;
            panel.style.opacity = `${1 - progress * 0.3}`;
        }
    }, { passive: true });
    
    panel.addEventListener('touchend', e => {
        if (!panel.classList.contains('open')) return;
        const touchEndY = e.changedTouches[0].clientY;
        const deltaY = touchEndY - touchStartY;
        const deltaTime = Date.now() - touchStartTime;
        const velocity = deltaY / deltaTime;
        
        panel.style.transform = '';
        panel.style.opacity = '';
        
        if (deltaY > 80 || velocity > 0.5) {
            closePanel();
        }
    }, { passive: true });
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