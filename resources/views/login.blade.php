<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, sans-serif; margin: 0; background: #0f172a; color: #e2e8f0; }
    .wrap { max-width: 560px; margin: 40px auto; padding: 20px; background: #111827; border: 1px solid #1f2937; border-radius: 10px; }
    h1 { margin: 0 0 12px; font-size: 20px; }
    label { font-size: 12px; display: block; margin-bottom: 4px; color: #93c5fd; }
    input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #374151; background: #0b1220; color: #e2e8f0; }
    .row { display: grid; grid-template-columns: 1fr; gap: 10px; margin-bottom: 10px; }
    button { background: #3b82f6; color: white; border: none; padding: 10px 14px; border-radius: 6px; cursor: pointer; }
    .muted { color: #9ca3af; font-size: 12px; }
    pre { white-space: pre-wrap; word-wrap: break-word; background: #0b1220; border: 1px solid #1f2937; padding: 10px; border-radius: 6px; max-height: 280px; overflow: auto; }
    .actions { display: flex; gap: 8px; align-items: center; }
    a { color: #93c5fd; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Login</h1>
    <div class="row">
      <div>
        <label>Email</label>
        <input id="email" placeholder="you@example.com">
      </div>
      <div>
        <label>Password</label>
        <input id="password" type="password" value="secret123">
      </div>
    </div>
    <div class="actions">
      <button id="btn-login">Login</button>
      <button id="btn-register">Register</button>
      <span class="muted">Token: <span id="token-preview">(none)</span></span>
    </div>
    <div class="muted" style="margin-top:8px;">Try the full tester: <a href="/tester">/tester</a></div>
    <pre id="output"></pre>
  </div>
  <script>
    const base = location.origin + '/api';
    const $ = id => document.getElementById(id);
    const setJSON = (el, obj) => el.textContent = JSON.stringify(obj, null, 2);
    const getToken = () => localStorage.getItem('api_token') || '';
    const setToken = (t) => { localStorage.setItem('api_token', t || ''); $('token-preview').textContent = t ? (t.slice(0,8)+'…'+t.slice(-6)) : '(none)'; };
    setToken(getToken());

    async function post(path, body) {
      const res = await fetch(base + path, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw { status: res.status, data };
      return data;
    }

    $('btn-login').onclick = async () => {
      try {
        const out = await post('/login', { email: $('email').value, password: $('password').value });
        setToken(out.token);
        setJSON($('output'), out);
      } catch (e) { setJSON($('output'), e); }
    };

    $('btn-register').onclick = async () => {
      try {
        const name = $('email').value.split('@')[0] || 'Tester';
        const out = await post('/register', { name, email: $('email').value, password: $('password').value });
        setToken(out.token);
        setJSON($('output'), out);
      } catch (e) { setJSON($('output'), e); }
    };
  </script>
</body>
</html>