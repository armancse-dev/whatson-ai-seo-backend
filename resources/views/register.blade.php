<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Register • WhatsOn AI SEO</title>
  <style>
    :root { color-scheme: light dark; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', Arial, sans-serif; margin: 0; padding: 0; background: #0b1221; color: #e6edf3; }
    .container { max-width: 720px; margin: 40px auto; padding: 24px; }
    .card { background: #111a2f; border: 1px solid #1f2a48; border-radius: 16px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); }
    h1 { margin: 0 0 12px; font-size: 24px; }
    p { margin: 0 0 16px; color: #9bb4d0; }
    label { display: block; font-size: 13px; color: #9bb4d0; margin-bottom: 6px; }
    input { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #243154; background: #0e1730; color: #e6edf3; outline: none; }
    input:focus { border-color: #3b82f6; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .row > div { }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 10px; border: 1px solid #243154; background: #152345; color: #e6edf3; cursor: pointer; }
    .btn.primary { background: #2563eb; border-color: #2563eb; }
    .btn:hover { filter: brightness(1.1); }
    .actions { display: flex; gap: 12px; margin-top: 12px; }
    .muted { color: #9bb4d0; font-size: 12px; }
    .nav { display: flex; gap: 12px; justify-content: space-between; margin-top: 16px; }
    pre { background: #0e1730; border: 1px solid #243154; padding: 12px; border-radius: 10px; overflow: auto; }
    a { color: #7cc4ff; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>Create your account</h1>
      <p>Stateful, cookie-based registration using Laravel's web middleware.</p>

      <div class="row">
        <div>
          <label for="name">Name</label>
          <input id="name" type="text" placeholder="Jane Doe" />
        </div>
        <div>
          <label for="email">Email</label>
          <input id="email" type="email" placeholder="jane@example.com" />
        </div>
      </div>
      <div style="margin-top: 16px;">
        <label for="password">Password</label>
        <input id="password" type="password" placeholder="••••••••" />
      </div>

      <div class="actions">
        <button class="btn primary" id="registerBtn">Register</button>
        <a class="btn" href="/login">Go to Login</a>
      </div>

      <div class="nav">
        <span class="muted">This will set a session cookie.</span>
        <a href="/tester">Open API Tester</a>
      </div>

      <div style="margin-top: 16px;">
        <label>Response</label>
        <pre id="output">No requests yet</pre>
      </div>
    </div>
  </div>

  <script>
    const out = document.getElementById('output');
    function show(obj) { out.textContent = JSON.stringify(obj, null, 2); }

    async function register() {
      const name = document.getElementById('name').value.trim();
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      if (!name || !email || !password) { show({ error: 'name, email, and password are required' }); return; }

      // Ensure CSRF is set (for SPA flows) — optional for Blade since @csrf is available.
      try { await fetch('/sanctum/csrf-cookie', { method: 'GET', credentials: 'same-origin' }); } catch (_) {}

      const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      try {
        const res = await fetch('/register', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
          },
          credentials: 'same-origin',
          body: JSON.stringify({ name, email, password }),
        });
        const data = await res.json().catch(() => ({ status: res.status, text: 'Non-JSON response' }));
        show(data);
        if (res.ok) {
          // If controller returns token, store for API tester convenience
          if (data.token) localStorage.setItem('token', data.token);
        }
      } catch (err) {
        show({ error: String(err) });
      }
    }

    document.getElementById('registerBtn').addEventListener('click', register);
  </script>
</body>
</html>