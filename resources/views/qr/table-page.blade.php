<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $business->name }} - Mesa {{ $table->number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .header {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .logo img {
            width: 45px;
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8" />
                <meta name="viewport" content="width=device-width,initial-scale=1" />
                <title>{{ $business->name }} | Mesa {{ $table->number }}</title>
                <meta name="theme-color" content="#8b5cf6" />
                <style>
                    :root {
                        --mozo-primary:#8b5cf6; /* morado */
                        --mozo-primary-dark:#6d3ff0;
                        --mozo-bg:#111217;
                        --panel-bg:#1d1f27;
                        --radius:20px;
                        --transition:0.35s cubic-bezier(.4,.0,.2,1);
                    }
                    *{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
                    body,html{height:100%;width:100%;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;background:var(--mozo-bg);color:#fff;overscroll-behavior:none;}
                    body{display:flex;flex-direction:column;}
                    .pdf-wrapper{flex:1;position:relative;height:100vh;width:100%;overflow:hidden;background:#000;}
                    .pdf-iframe{position:absolute;top:0;left:0;width:100%;height:100%;border:0;background:#000;}
                    .pdf-fallback{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:40px;gap:22px;height:100%;}
                    .pdf-fallback h2{font-size:clamp(1.3rem,4vw,2rem);}
                    .pdf-fallback p{opacity:.75;line-height:1.4;font-size:1rem;}
                    .fab{position:fixed;bottom:20px;right:20px;width:78px;height:78px;border-radius:32px;background:linear-gradient(145deg,var(--mozo-primary),var(--mozo-primary-dark));display:flex;align-items:center;justify-content:center;box-shadow:0 10px 32px -4px rgba(139,92,246,.55),0 4px 8px -2px rgba(0,0,0,.4);cursor:pointer;border:none;outline:none;color:#fff;font-weight:600;font-size:14px;letter-spacing:.5px;transition:var(--transition);z-index:50;overflow:hidden;}
                    .fab:active{transform:scale(.94);}        
                    .fab svg{width:48px;height:48px;}
                    .fab::after{content:'Llamar';position:absolute;bottom:6px;font-size:11px;font-weight:500;opacity:.9;}
                    .fab.open{transform:rotate(45deg);} /* X effect */
                    .waiter-panel{position:fixed;left:0;right:0;bottom:0;transform:translateY(105%);background:var(--panel-bg);border-radius:32px 32px 0 0;padding:28px 22px 34px;box-shadow:0 -8px 32px -8px rgba(0,0,0,.65);transition:var(--transition);z-index:49;max-height:85vh;display:flex;flex-direction:column;gap:22px;}
                    .waiter-panel.open{transform:translateY(0);}        
                    .panel-grabber{width:56px;height:6px;border-radius:3px;background:#2c2f3a;margin:0 auto 4px;}
                    .panel-header{display:flex;flex-direction:column;gap:6px;text-align:center;}
                    .panel-header h1{font-size:clamp(1.1rem,3.1vw,1.6rem);font-weight:700;letter-spacing:.5px;}
                    .panel-header small{opacity:.75;font-weight:500;}
                    .call-btn{background:linear-gradient(145deg,#f87171,#dc2626);border:none;border-radius:18px;padding:20px 26px;color:#fff;font-size:1.1rem;font-weight:600;display:flex;align-items:center;justify-content:center;gap:10px;cursor:pointer;box-shadow:0 6px 24px -4px rgba(248,113,113,.55),0 2px 6px -1px rgba(0,0,0,.5);transition:var(--transition);}
                    .call-btn:disabled{background:linear-gradient(145deg,#64748b,#475569);opacity:.7;cursor:not-allowed;box-shadow:none;}
                    .call-btn.loading{background:linear-gradient(145deg,#fbbf24,#f59e0b);animation:pulse 1.5s infinite;}
                    .status-area{min-height:56px;border-radius:18px;padding:16px 18px;font-weight:500;font-size:.95rem;line-height:1.3;display:none;}
                    .status-success{background:#14532d;color:#d1fae5;border:1px solid #10b981;}
                    .status-error{background:#7f1d1d;color:#fecaca;border:1px solid #f87171;}
                    .status-pending{background:#78350f;color:#fde68a;border:1px solid #fbbf24;animation:pulse 2s infinite;}
                    @keyframes pulse{0%{opacity:1}50%{opacity:.65}100%{opacity:1}}
                    .open-menu-link{position:absolute;top:12px;right:12px;z-index:10;background:rgba(17,18,23,.6);backdrop-filter:blur(6px);padding:10px 16px;border-radius:14px;color:#fff;text-decoration:none;font-size:.8rem;font-weight:600;letter-spacing:.5px;display:flex;align-items:center;gap:6px;box-shadow:0 4px 14px -4px rgba(0,0,0,.6);}        
                    .open-menu-link:hover{background:rgba(17,18,23,.85);}        
                    .accessibility-hint{font-size:.7rem;text-transform:uppercase;letter-spacing:1px;opacity:.5;text-align:center;}
                    .logo-mini{display:inline-flex;align-items:center;justify-content:center;background:#fff;color:#000;font-weight:700;font-size:18px;width:46px;height:46px;border-radius:15px;box-shadow:0 4px 12px -3px rgba(0,0,0,.5);}
                    .ack-extra{font-size:.75rem;margin-top:6px;opacity:.8;font-weight:400;}
                    @media (min-width:780px){.waiter-panel{left:50%;right:auto;transform:translate(50%,105%);width:420px;border-radius:38px;padding:34px 30px;}.waiter-panel.open{transform:translate(50%,0)}.fab{bottom:28px;right:34px}}
                    @media (max-width:460px){.fab{width:68px;height:68px;border-radius:26px}.fab svg{width:42px;height:42px}}
                </style>
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

                <div class="pdf-wrapper" id="pdfWrapper" aria-label="Visor del menú">
                    @if($menuUrl)
                        <iframe id="menu-pdf-iframe" class="pdf-iframe" src="{{ $menuUrl }}" title="Menú {{ $business->name }}" onload="handlePdfLoad()" onerror="handlePdfError()" aria-describedby="menuActions"></iframe>
                        <a class="open-menu-link" id="openInNewTab" href="{{ $menuUrl }}" target="_blank" rel="noopener" aria-label="Abrir menú en nueva pestaña">📄 Abrir</a>
                    @else
                        <div class="pdf-fallback" id="menu-fallback">
                            <div class="logo-mini">MÖ</div>
                            <h2>Menú no disponible</h2>
                            <p>No encontramos un PDF cargado por el restaurante. Podés llamar al mozo y solicitar el menú físico.</p>
                        </div>
                    @endif
                </div>

                <!-- Botón flotante (logo MOZO estilizado) -->
                <button id="mozoFab" class="fab" aria-haspopup="dialog" aria-controls="waiterPanel" aria-expanded="false" title="Llamar mozo" onclick="togglePanel()">
                    <!-- SVG simple rostro MOZO -->
                    <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect x="4" y="4" width="112" height="112" rx="32" fill="white"/>
                        <circle cx="45" cy="50" r="18" fill="#000"/>
                        <circle cx="45" cy="50" r="8" fill="white"/>
                        <circle cx="75" cy="50" r="18" fill="#000"/>
                        <circle cx="75" cy="50" r="8" fill="white"/>
                        <circle cx="37" cy="30" r="4" fill="#000"/>
                        <circle cx="53" cy="30" r="4" fill="#000"/>
                        <circle cx="67" cy="30" r="4" fill="#000"/>
                        <circle cx="83" cy="30" r="4" fill="#000"/>
                        <path d="M40 78c6 8 16 14 20 14s14-6 20-14" stroke="#000" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <!-- Panel deslizable -->
                <!-- Firebase -->
                <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
                <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-database-compat.js"></script>
                <script>
                    const CLIENT_IP='{{ $clientIp }}';
                    const TABLE_ID={{ $table->id }};
                    let currentNotificationId=null;let firebaseListener=null;
                    @if(session('notification_id'))
                        currentNotificationId='{{ session('notification_id') }}';
                        startFirebaseListener();
                        openPanel();
                    @endif

                    function togglePanel(){const p=document.getElementById('waiterPanel');const f=document.getElementById('mozoFab');const open=!p.classList.contains('open');open?openPanel():closePanel();}
                    function openPanel(){const p=document.getElementById('waiterPanel');const f=document.getElementById('mozoFab');p.classList.add('open');f.classList.add('open');f.setAttribute('aria-expanded','true');}
                    function closePanel(){const p=document.getElementById('waiterPanel');const f=document.getElementById('mozoFab');p.classList.remove('open');f.classList.remove('open');f.setAttribute('aria-expanded','false');}
                    document.addEventListener('keydown',e=>{if(e.key==='Escape'){closePanel();}});

                    function startFirebaseListener(){const firebaseConfig={projectId:"mozoqr-7d32c",apiKey:"AIzaSyDGJJKNfSSxD6YnXnNjwRb6VUtPSyGN5CM",authDomain:"mozoqr-7d32c.firebaseapp.com",databaseURL:"https://mozoqr-7d32c-default-rtdb.firebaseio.com",storageBucket:"mozoqr-7d32c.appspot.com"};if(!window.firebase||!window.firebase.apps.length){firebase.initializeApp(firebaseConfig);}const db=firebase.database();const ref=db.ref(`active_calls/${currentNotificationId}`);firebaseListener=ref.on('value',snap=>{const data=snap.val();if(!data) return; if(data.status==='acknowledged'){showAcknowledgedMessage(data.waiter?.name||data.waiter_name);ref.off('value',firebaseListener);firebaseListener=null;} else if(data.status==='completed'){showCompletedMessage(data.waiter?.name||data.waiter_name);ref.off('value',firebaseListener);firebaseListener=null;}});}

                    function baseStatusDiv(){return document.getElementById('status-message');}
                    function showAcknowledgedMessage(waiter){const d=baseStatusDiv();d.className='status-area status-success';d.style.display='block';d.innerHTML=`🎉 ${waiter||'El mozo'} confirmó tu solicitud.<div class="ack-extra">🚶‍♂️ En camino...</div>`;notify('¡Mozo confirmado!',`${waiter||'El mozo'} está en camino`);}        
                    function showCompletedMessage(waiter){const d=baseStatusDiv();d.className='status-area status-success';d.style.display='block';d.innerHTML=`✅ ${waiter||'El mozo'} completó tu solicitud.`;notify('Servicio completado',`${waiter||'El mozo'} finalizó la asistencia`);}        
                    function notify(title,body){if('Notification'in window&&Notification.permission==='granted'){new Notification(title,{body,icon:'/favicon.ico',tag:'mozo-call'});} }
                    if('Notification'in window&&Notification.permission==='default'){Notification.requestPermission();}

                    async function callWaiter(){const btn=document.getElementById('call-waiter-btn');const d=baseStatusDiv();btn.disabled=true;btn.classList.add('loading');btn.textContent='⏳ Llamando...';d.style.display='none';try{const res=await fetch('{{ route('waiter.call') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({restaurant_id:{{ $business->id }},table_id:{{ $table->id }},message:'Cliente solicita atención'})});const data=await res.json();if(res.ok&&data.success){d.className='status-area status-success';d.innerHTML='🎉 '+(data.message||'Mozo llamado exitosamente')+'<div class="ack-extra">⏳ Esperando confirmación...</div>';d.style.display='block';if(data.notification_id){currentNotificationId=data.notification_id;startFirebaseListener();}} else {d.className='status-area status-error';d.innerHTML='❌ '+(data.message||'Error al llamar al mozo');d.style.display='block';}}catch(e){d.className='status-area status-error';d.innerHTML='❌ Error de conexión. Intenta nuevamente.';d.style.display='block';}finally{btn.disabled=false;btn.classList.remove('loading');btn.textContent='🔔 Llamar Mozo';}}

                    // PDF handling
                    let pdfLoadAttempts=0;const maxPdfLoadAttempts=3;function handlePdfLoad(){/* ok */}function handlePdfError(){pdfLoadAttempts++;if(pdfLoadAttempts>=maxPdfLoadAttempts){showPdfFallback();}}function showPdfFallback(){const iframe=document.getElementById('menu-pdf-iframe');const fallback=document.getElementById('menu-fallback');if(iframe&&fallback){iframe.style.display='none';fallback.style.display='flex';}}
                    setTimeout(()=>{const iframe=document.getElementById('menu-pdf-iframe');if(iframe){try{const doc=iframe.contentDocument||iframe.contentWindow.document;if(!doc||!doc.body||doc.body.innerHTML.trim()===''){showPdfFallback();}}catch(e){/* ignore cross origin */}}},5000);
                </script>
            </body>
            </html>