# chat

[index.html](https://github.com/user-attachments/files/22587008/index.html)
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Chat Privado (Local)</title>
<style>
  body{font-family:system-ui,Segoe UI,Arial;background:#0f1724;color:#e6eef8;margin:0;display:flex;align-items:center;justify-content:center;height:100vh}
  .app{width:960px;max-width:96%;background:#0b1220;padding:18px;border-radius:10px;box-shadow:0 6px 30px rgba(0,0,0,0.6)}
  .login{display:grid;gap:8px}
  input,button,textarea{padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:transparent;color:inherit}
  .row{display:flex;gap:8px}
  #messages{height:360px;overflow:auto;background:rgba(255,255,255,0.02);padding:12px;border-radius:8px;margin-bottom:8px}
  .msg{padding:8px;border-radius:8px;margin-bottom:6px;background:rgba(255,255,255,0.03)}
  .me{background:rgba(110,210,255,0.08);text-align:right}
  .meta{font-size:12px;opacity:0.7}
  footer{display:flex;gap:8px}
</style>
</head>
<body>
  <div class="app">
    <div id="screen">
      <div class="login" id="loginScreen">
        <h2>Chat privado (local)</h2>
        <label>Contraseña de la sala (crea o ingresa):</label>
        <input id="pwdInput" placeholder="escribe una contraseña segura" type="password"/>
        <label>Tu nombre:</label>
        <input id="nameInput" placeholder="ej: Juan" />
        <div class="row">
          <button id="openBtn">Abrir chat</button>
          <button id="clearBtn">Borrar historial (local)</button>
        </div>
        <small>Nota: el chat queda guardado solo en este navegador y solo se accede con la contraseña correcta.</small>
      </div>

      <div id="chatScreen" style="display:none">
        <div class="row" style="justify-content:space-between;align-items:center;margin-bottom:8px">
          <div><strong>Chat privado</strong> · <span id="roomLabel"></span></div>
          <div><button id="lockBtn">Bloquear</button> <button id="logoutBtn">Salir</button></div>
        </div>

        <div id="messages" aria-live="polite"></div>

        <footer>
          <input id="msgInput" placeholder="Escribe un mensaje..." style="flex:1"/>
          <button id="sendBtn">Enviar</button>
        </footer>
      </div>
    </div>
  </div>

<script>
/*
  Funcionamiento:
  - Al abrir se pide una contraseña y un nombre.
  - Se deriva una clave segura simple (hash SHA-256 Web API) para identificar la "sala" en localStorage.
  - Mensajes se guardan en localStorage bajo la clave de la sala. Sólo alguien con la misma contraseña podrá abrir la misma sala en ese navegador.
  - Este método NO sincroniza con otros usuarios; es local.
*/

// util: SHA-256 -> hex (async)
async function sha256hex(str){
  const enc = new TextEncoder();
  const buf = await crypto.subtle.digest('SHA-256', enc.encode(str));
  return Array.from(new Uint8Array(buf)).map(b=>b.toString(16).padStart(2,'0')).join('');
}

const loginScreen = document.getElementById('loginScreen');
const chatScreen = document.getElementById('chatScreen');
const pwdInput = document.getElementById('pwdInput');
const nameInput = document.getElementById('nameInput');
const openBtn = document.getElementById('openBtn');
const clearBtn = document.getElementById('clearBtn');
const logoutBtn = document.getElementById('logoutBtn');
const lockBtn = document.getElementById('lockBtn');

const messagesDiv = document.getElementById('messages');
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
const roomLabel = document.getElementById('roomLabel');

let ROOM_KEY = null;
let displayName = 'Anon';

// render mensajes desde array
function renderMessages(arr){
  messagesDiv.innerHTML = '';
  for(const m of arr){
    const d = document.createElement('div');
    d.className = 'msg' + (m.name === displayName ? ' me' : '');
    d.innerHTML = `<div class="meta">${escapeHtml(m.name)} · ${new Date(m.t).toLocaleString()}</div><div>${escapeHtml(m.text)}</div>`;
    messagesDiv.appendChild(d);
  }
  messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// escape simple
function escapeHtml(s){ return (s||'').toString().replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c]); }

async function openRoom(){
  const pwd = pwdInput.value.trim();
  const name = nameInput.value.trim() || 'Anon';
  if(!pwd){ alert('Escribe una contraseña'); return; }
  displayName = name;
  const hv = await sha256hex(pwd);
  ROOM_KEY = 'chatroom_' + hv;
  roomLabel.textContent = 'Sala: ' + hv.slice(0,8) + '...';
  // cargar mensajes
  const raw = localStorage.getItem(ROOM_KEY);
  const arr = raw ? JSON.parse(raw) : [];
  renderMessages(arr);
  loginScreen.style.display = 'none';
  chatScreen.style.display = 'block';
  msgInput.focus();
}

// enviar mensaje (y guardar)
function sendMessage(){
  if(!ROOM_KEY) return;
  const text = msgInput.value.trim();
  if(!text) return;
  const raw = localStorage.getItem(ROOM_KEY);
  const arr = raw ? JSON.parse(raw) : [];
  const msg = { name: displayName, text, t: Date.now() };
  arr.push(msg);
  // limitar historial (por ejemplo 500 mensajes)
  if(arr.length > 500) arr.splice(0, arr.length-500);
  localStorage.setItem(ROOM_KEY, JSON.stringify(arr));
  renderMessages(arr);
  msgInput.value = '';
  msgInput.focus();
}

// bloquear (ocultar chat y volver a pedir contraseña)
function lockRoom(){
  loginScreen.style.display = 'block';
  chatScreen.style.display = 'none';
  pwdInput.value = '';
  nameInput.value = displayName;
  pwdInput.focus();
  ROOM_KEY = null;
}

// borrar historial de esta sala (solo local)
function clearHistory(){
  const pwd = pwdInput.value.trim();
  if(!pwd){
    if(!confirm('¿Quieres borrar TODO el historial local guardado (todas las salas)?')) return;
    // borrar todas las claves que empiecen con chatroom_
    for(const k of Object.keys(localStorage)){
      if(k.startsWith('chatroom_')) localStorage.removeItem(k);
    }
    alert('Historial local borrado.');
    return;
  }
  // si hay pwd -> borrar esa sala derivada
  sha256hex(pwd).then(hv=>{
    const k = 'chatroom_' + hv;
    localStorage.removeItem(k);
    alert('Historial de esa sala (local) borrado.');
    // si está abierta la misma sala, limpiar vista
    if(ROOM_KEY === k) renderMessages([]);
  });
}

openBtn.addEventListener('click', openRoom);
sendBtn.addEventListener('click', sendMessage);
msgInput.addEventListener('keydown', e=>{ if(e.key==='Enter') sendMessage(); });
logoutBtn.addEventListener('click', ()=>{ if(confirm('Salir del chat?')) lockRoom(); });
lockBtn.addEventListener('click', ()=>{ lockRoom(); });
clearBtn.addEventListener('click', clearHistory);

// si se abre otra pestaña y el localStorage cambia, actualizar vista
window.addEventListener('storage', (ev)=>{
  if(!ROOM_KEY) return;
  if(ev.key === ROOM_KEY){
    const arr = ev.newValue ? JSON.parse(ev.newValue) : [];
    renderMessages(arr);
  }
});
</script>
</body>
</html>
