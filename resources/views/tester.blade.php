<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SEO Tools API Tester</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, sans-serif; margin: 0; background: #0f172a; color: #e2e8f0; }
    header { padding: 20px; border-bottom: 1px solid #1e293b; }
    h1 { font-size: 20px; margin: 0; }
    .container { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 16px; }
    .card { background: #111827; border: 1px solid #1f2937; border-radius: 8px; padding: 16px; }
    .card h2 { margin-top: 0; font-size: 16px; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    label { font-size: 12px; display: block; margin-bottom: 4px; color: #93c5fd; }
    input, textarea, select { width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #374151; background: #0b1220; color: #e2e8f0; }
    button { background: #3b82f6; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
    button:disabled { opacity: 0.6; cursor: not-allowed; }
    .muted { color: #9ca3af; font-size: 12px; }
    pre { white-space: pre-wrap; word-wrap: break-word; background: #0b1220; border: 1px solid #1f2937; padding: 8px; border-radius: 6px; max-height: 260px; overflow: auto; }
    .cols-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .danger { background: #ef4444; }
    .success { background: #22c55e; }
  </style>
</head>
<body>
  <header>
    <h1>SEO Tools API Tester</h1>
    <div class="muted">Token-based tester for register/login, projects, keywords, audits, reports.</div>
  </header>

  <div class="container">
    <div class="card">
      <h2>Auth</h2>
      <div class="row">
        <div>
          <label>Name</label>
          <input id="name" value="Tester">
        </div>
        <div>
          <label>Email</label>
          <input id="email" placeholder="you@example.com">
        </div>
      </div>
      <div class="row">
        <div>
          <label>Password</label>
          <input id="password" type="password" value="secret123">
        </div>
        <div style="align-self:end; display:flex; gap:8px;">
          <button id="btn-register">Register</button>
          <button id="btn-login" class="success">Login</button>
          <button id="btn-logout" class="danger">Logout</button>
        </div>
      </div>
      <div class="muted">Token: <span id="token-preview">(none)</span></div>
      <pre id="auth-output"></pre>
    </div>

    <div class="card">
      <h2>Projects</h2>
      <div class="row">
        <div>
          <label>Project Name</label>
          <input id="project_name" value="Demo Project">
        </div>
        <div>
          <label>Website URL</label>
          <input id="website_url" value="https://example.com">
        </div>
      </div>
      <div style="margin-top:8px; display:flex; gap:8px;">
        <button id="btn-create-project">Create Project</button>
        <button id="btn-list-projects">List Projects</button>
      </div>
      <div class="muted">Active project id: <span id="project-id">(none)</span></div>
      <pre id="project-output"></pre>
    </div>

    <div class="card">
      <h2>Keywords</h2>
      <label>Keywords (comma separated)</label>
      <textarea id="keywords" rows="3">best seo tools, keyword clustering, laravel sanctum, on-page audit, seo tips</textarea>
      <div style="margin-top:8px; display:flex; gap:8px;">
        <button id="btn-add-keywords">Add Keywords</button>
        <input id="kw-q" placeholder="search (q=)">
        <select id="kw-per-page">
          <option>10</option>
          <option selected>25</option>
          <option>50</option>
        </select>
        <button id="btn-list-keywords">List Keywords</button>
      </div>
      <pre id="keywords-output"></pre>
    </div>

    <div class="card">
      <h2>Audit & Reports</h2>
      <div class="row">
        <div>
          <label>Max Clusters (optional)</label>
          <input id="max-clusters" placeholder="10">
        </div>
        <div style="align-self:end; display:flex; gap:8px;">
          <button id="btn-start-clusters">Start Clustering</button>
          <button id="btn-run-audit">Run Audit</button>
          <button id="btn-list-reports">List Reports</button>
        </div>
      </div>
      <pre id="reports-output"></pre>
    </div>
  </div>

  <script>
    const base = location.origin + '/api';
    const $ = id => document.getElementById(id);
    const setJSON = (el, obj) => el.textContent = JSON.stringify(obj, null, 2);
    const getToken = () => localStorage.getItem('api_token') || '';
    const setToken = (t) => { localStorage.setItem('api_token', t || ''); $('token-preview').textContent = t ? (t.slice(0,8)+'…'+t.slice(-6)) : '(none)'; };
    setToken(getToken());

    const authHeaders = () => ({ 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + getToken() });
    const authed = () => !!getToken();

    async function post(path, body, useAuth=true) {
      const res = await fetch(base + path, {
        method: 'POST', headers: useAuth ? authHeaders() : { 'Content-Type': 'application/json' },
        body: body ? JSON.stringify(body) : undefined
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw { status: res.status, data };
      return data;
    }
    async function get(path) {
      const res = await fetch(base + path, { headers: authHeaders() });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw { status: res.status, data };
      return data;
    }

    $('btn-register').onclick = async () => {
      try {
        const out = await post('/register', { name: $('name').value, email: $('email').value, password: $('password').value }, false);
        setToken(out.token);
        setJSON($('auth-output'), out);
      } catch (e) { setJSON($('auth-output'), e); }
    };
    $('btn-login').onclick = async () => {
      try {
        const out = await post('/login', { email: $('email').value, password: $('password').value }, false);
        setToken(out.token);
        setJSON($('auth-output'), out);
      } catch (e) { setJSON($('auth-output'), e); }
    };
    $('btn-logout').onclick = async () => {
      try {
        const out = await post('/logout');
        setToken('');
        setJSON($('auth-output'), out);
      } catch (e) { setJSON($('auth-output'), e); }
    };

    $('btn-create-project').onclick = async () => {
      try {
        if (!authed()) throw { status: 401, data: { message: 'Login first' } };
        const out = await post('/projects', { project_name: $('project_name').value, website_url: $('website_url').value });
        $('project-id').textContent = out.id;
        setJSON($('project-output'), out);
      } catch (e) { setJSON($('project-output'), e); }
    };
    $('btn-list-projects').onclick = async () => {
      try {
        const out = await get('/projects');
        setJSON($('project-output'), out);
      } catch (e) { setJSON($('project-output'), e); }
    };

    $('btn-add-keywords').onclick = async () => {
      try {
        const pid = $('project-id').textContent.trim();
        if (!pid || pid === '(none)') throw { status: 400, data: { message: 'Create/select a project' } };
        const kws = $('keywords').value.split(',').map(s => s.trim()).filter(Boolean);
        const out = await post(`/projects/${pid}/keywords`, { keywords: kws });
        setJSON($('keywords-output'), out);
      } catch (e) { setJSON($('keywords-output'), e); }
    };
    $('btn-list-keywords').onclick = async () => {
      try {
        const pid = $('project-id').textContent.trim();
        const q = $('kw-q').value.trim();
        const per = $('kw-per-page').value;
        const path = `/projects/${pid}/keywords?per_page=${encodeURIComponent(per)}${q?`&q=${encodeURIComponent(q)}`:''}`;
        const out = await get(path);
        setJSON($('keywords-output'), out);
      } catch (e) { setJSON($('keywords-output'), e); }
    };

    $('btn-start-clusters').onclick = async () => {
      try {
        const pid = $('project-id').textContent.trim();
        const max = $('max-clusters').value.trim();
        const body = max ? { max_clusters: parseInt(max, 10) } : {};
        const out = await post(`/projects/${pid}/clusters/start`, body);
        setJSON($('reports-output'), out);
      } catch (e) { setJSON($('reports-output'), e); }
    };
    $('btn-run-audit').onclick = async () => {
      try {
        const pid = $('project-id').textContent.trim();
        const out = await post(`/projects/${pid}/run-audit`);
        setJSON($('reports-output'), out);
      } catch (e) { setJSON($('reports-output'), e); }
    };
    $('btn-list-reports').onclick = async () => {
      try {
        const pid = $('project-id').textContent.trim();
        const out = await get(`/projects/${pid}/reports`);
        setJSON($('reports-output'), out);
      } catch (e) { setJSON($('reports-output'), e); }
    };
  </script>
</body>
</html>