
/* ── Admin Init ──────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', async () => {
  if (window.innerWidth <= 768) document.getElementById('sidebarToggle').style.display = 'flex';
  buildKPI('kpiToday', todayKPIs());
  buildKPI('kpiOverall', overallKPIs());
  initCharts();
  buildTopSearch();
  buildNewUsers();
  buildLiveFeed();
  
  // Make API calls sequential to prevent PHP single-thread deadlock
  try {
      await buildModeration();
      await buildUsers();
  } catch(e) {
      console.error(e);
  }
  
  buildServerMetrics();
  setInterval(tickLiveFeed, 4000);
});

const panelTitles = { dashboard:'Dashboard', live:'Live Activity', moderation:'Laporan Pending', comments:'Komentar', users:'Pengguna', numbers:'Nomor Telepon', rekening:'Rekening Bank', analytics:'Statistik', fraud:'Fraud Detection', community:'Komunitas', seo:'SEO', ads:'Iklan', cms:'CMS Artikel', server:'Server Monitor', logs:'Audit Log', roles:'Role & Permission' };

function showPanel(name, el) {
  document.querySelectorAll('[id^="panel-"]').forEach(p => p.classList.add('hidden'));
  document.getElementById(`panel-${name}`)?.classList.remove('hidden');
  document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
  el?.classList.add('active');
  document.getElementById('topbarTitle').textContent = panelTitles[name] || name;
  document.getElementById('topbarSub').textContent = panelTitles[name] || name;
  // Init server chart on first open
  if (name === 'server' && !window._serverChartDone) { window._serverChartDone = true; initServerChart(); }
  if (name === 'numbers') buildPhones();
  if (name === 'articles') buildArticles();
  if (name === 'banners') buildBanners();
}

/* ── KPI ─────────────────────────────────────────────────── */
function todayKPIs() {
  const t = MockData.adminStats.today;
  return [
    { label:'Pengunjung', val: t.visitors.toLocaleString('id'), icon:'eye', change:'+12.3%' },
    { label:'Pengguna Login', val: t.loggedIn.toLocaleString('id'), icon:'user', change:'+8.7%' },
    { label:'User Baru', val: t.newUsers.toLocaleString('id'), icon:'users', change:'+15.2%' },
    { label:'Total Pencarian', val: t.searches.toLocaleString('id'), icon:'search', change:'+22.1%' },
    { label:'Cek Telepon', val: t.phoneChecks.toLocaleString('id'), icon:'phone', change:'+18.4%' },
    { label:'Cek Rekening', val: t.rekeningChecks.toLocaleString('id'), icon:'credit', change:'+9.2%' },
    { label:'Laporan Baru', val: t.newReports.toLocaleString('id'), icon:'flag', change:'+34.8%' },
    { label:'Komentar Baru', val: t.newComments.toLocaleString('id'), icon:'message', change:'+7.3%' },
  ];
}

function overallKPIs() {
  const o = MockData.adminStats.overall;
  return [
    { label:'Total User', val: o.users.toLocaleString('id'), icon:'users', change:null },
    { label:'Total Nomor', val: o.phones.toLocaleString('id'), icon:'phone', change:null },
    { label:'Total Rekening', val: o.rekening.toLocaleString('id'), icon:'credit', change:null },
    { label:'Total Laporan', val: o.reports.toLocaleString('id'), icon:'flag', change:null },
    { label:'Total Komentar', val: o.comments.toLocaleString('id'), icon:'message', change:null },
    { label:'Total Pencarian', val: o.searches.toLocaleString('id'), icon:'search', change:null },
    { label:'Halaman SEO', val: o.seoPages.toLocaleString('id'), icon:'globe', change:null },
    { label:'Bank Didukung', val: o.banks, icon:'bank', change:null },
  ];
}

function buildKPI(id, items) {
  const el = document.getElementById(id);
  if (!el) return;
  items.forEach(item => {
    const div = document.createElement('div');
    div.className = 'kpi-card';
    div.innerHTML = `
      <div class="kpi-head">
        <span class="kpi-label">${item.label}</span>
        <div class="kpi-icon">${svgStr(item.icon)}</div>
      </div>
      <div class="kpi-val">${item.val}</div>
      ${item.change ? `<div class="kpi-change">+ ${item.change} vs kemarin</div>` : ''}
    `;
    el.appendChild(div);
  });
}

/* ── Charts ──────────────────────────────────────────────── */
const chartOpts = (color) => ({
  responsive: true, maintainAspectRatio: true,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#52525B', font: { size: 10 } } },
    y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#52525B', font: { size: 10 } } },
  }
});

function initCharts() {
  const d = MockData.chartData;
  new Chart(document.getElementById('chartSearches'), { type:'line', data:{ labels:d.labels, datasets:[{ data:d.searches, borderColor:'#DC2626', backgroundColor:'rgba(220,38,38,0.07)', fill:true, tension:0.4, pointRadius:3, pointBackgroundColor:'#DC2626' }] }, options:chartOpts() });
  new Chart(document.getElementById('chartStatus'), { type:'doughnut', data:{ labels:['Aman','Waspada','Hati-hati','Berbahaya'], datasets:[{ data:[72,14,8,6], backgroundColor:['#22C55E','#EAB308','#F97316','#EF4444'], borderWidth:0, hoverOffset:3 }] }, options:{ responsive:true, cutout:'72%', plugins:{ legend:{ display:false } } } });
  new Chart(document.getElementById('chartRevenue'), { type:'bar', data:{ labels:d.labels, datasets:[{ data:d.revenue, backgroundColor:'rgba(34,197,94,0.6)', borderRadius:4, borderSkipped:false }] }, options:chartOpts() });
  new Chart(document.getElementById('chartReports'), { type:'bar', data:{ labels:d.labels, datasets:[{ data:d.reports, backgroundColor:'rgba(220,38,38,0.6)', borderRadius:4, borderSkipped:false }] }, options:chartOpts() });
}

function initServerChart() {
  const canvas = document.getElementById('chartServer');
  if (!canvas) return;
  new Chart(canvas, { type:'line', data:{ labels:['1h','2h','3h','4h','5h','6h','7h'], datasets:[
    { label:'CPU', data:[18,23,19,31,25,22,23], borderColor:'#DC2626', fill:false, tension:0.4, pointRadius:2 },
    { label:'RAM', data:[24,25,26,27,26,26,26], borderColor:'#4F6EF7', fill:false, tension:0.4, pointRadius:2 },
  ]}, options:{ responsive:true, plugins:{ legend:{ display:true, labels:{ color:'#71717A', font:{ size:10 } } } }, scales:{ x:{ grid:{ color:'rgba(255,255,255,0.03)' }, ticks:{ color:'#52525B', font:{ size:10 } } }, y:{ grid:{ color:'rgba(255,255,255,0.03)' }, ticks:{ color:'#52525B', font:{ size:10 } } } } } });
}

/* ── Tables ──────────────────────────────────────────────── */
function buildTopSearch() {
  const tb = document.getElementById('topSearchTbody');
  MockData.trendingPhones.slice(0,6).forEach(item => {
    const sc = DataHelper.getStatusConfig(item.status);
    const tr = document.createElement('tr');
    tr.innerHTML = `<td style="color:var(--t-1);font-weight:600;">${item.number}</td><td>${DataHelper.formatNumber(item.searches)}</td><td><span class="badge badge-${item.status}">${sc.label}</span></td>`;
    tb.appendChild(tr);
  });
}

function buildNewUsers() {
  const tb = document.getElementById('newUserTbody');
  [['Ahmad Fauzi','30','5 mnt lalu'],['Siti Nurbaya','30','12 mnt lalu'],['Budi D.','30','23 mnt lalu'],['Rina S.','30','41 mnt lalu'],['Anonim','30','1 jam lalu']].forEach(u => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td style="color:var(--t-1);">${u[0]}</td><td><span style="font-weight:700;">${u[1]}</span></td><td>${u[2]}</td>`;
    tb.appendChild(tr);
  });
}

/* ── Live Feed ───────────────────────────────────────────── */
let liveIdx = 0;
function buildLiveFeed() {
  const list = document.getElementById('liveList');
  if (!list) return;
  MockData.liveActivities.forEach(a => list.appendChild(makeLiveItem(a)));
}

const liveActivitiesExtra = [
  { icon:'search', user:'Anonim',     content:'memeriksa 0857-3333-7777',          time:'baru saja' },
  { icon:'flag',   user:'Ahmad F.',   content:'melaporkan nomor pinjol baru',       time:'baru saja' },
  { icon:'thumbUp',user:'Siti N.',    content:'menandai komentar sebagai helpful',  time:'baru saja' },
  { icon:'user',   user:'User Baru',  content:'bergabung ke komunitas',             time:'baru saja' },
];

function tickLiveFeed() {
  const list = document.getElementById('liveList');
  if (!list) return;
  const item = makeLiveItem(liveActivitiesExtra[liveIdx % liveActivitiesExtra.length]);
  item.style.animation = 'fadeUp 0.3s ease';
  list.insertBefore(item, list.firstChild);
  while (list.children.length > 12) list.removeChild(list.lastChild);
  liveIdx++;
}

function makeLiveItem(a) {
  const div = document.createElement('div');
  div.className = 'live-item';
  div.innerHTML = `
    <div class="live-icon">${svgStr(a.icon)}</div>
    <div class="live-text"><strong>${a.user}</strong> ${a.content}</div>
    <div class="live-time">${a.time}</div>
  `;
  return div;
}


// ── Admin API helper ──────────────────────────────────────────
const ADMIN_TOKEN = 'ceknomor_admin_2024';

async function adminAPI(action, params = {}) {
  const qs = new URLSearchParams({ action, token: ADMIN_TOKEN, ...params });
  const res = await fetch(`/api/admin.php?${qs}`);
  if (!res.ok) throw new Error('API Error ' + res.status);
  const ct = res.headers.get('content-type') || '';
  if (!ct.includes('application/json')) throw new Error('Not PHP response');
  return res.json();
}

async function adminPost(payload) {
  const res = await fetch('/api/admin.php?token=' + ADMIN_TOKEN, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Admin-Token': ADMIN_TOKEN },
    body: JSON.stringify(payload)
  });
  const ct = res.headers.get('content-type') || '';
  if (!ct.includes('application/json')) throw new Error('Not PHP response');
  return res.json();
}

/* ── Moderation (Real API) ─────────────────────────────────── */
async function buildModeration() {
  const list = document.getElementById('moderationList');
  if (!list) return;
  list.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Memuat data...</p>';
  
  try {
    const data = await adminAPI('comments', { status: 'pending' });
    list.innerHTML = '';
    
    if (!data.comments || data.comments.length === 0) {
      list.innerHTML = '<p style="color:var(--c-aman);font-size:0.85rem;padding:1rem;text-align:center;">✅ Tidak ada laporan pending. Semua sudah diproses!</p>';
      return;
    }

    data.comments.forEach(c => {
      const targetLabel = c.phone_number 
        ? c.phone_number 
        : (c.bank_name ? `${c.bank_name} — ${c.account_number}` : `ID ${c.target_id}`);
      const div = document.createElement('div');
      div.className = 'mod-item';
      div.id = 'mod-' + c.id;
      div.innerHTML = `
        <div style="flex:1;min-width:0;">
          <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;flex-wrap:wrap;">
            <span style="font-size:0.875rem;font-weight:700;color:var(--t-1);">${targetLabel}</span>
            <span class="badge badge-cat">${c.category_name || 'Lainnya'}</span>
            ${c.fraud_score > 0 ? `<span class="badge" style="background:rgba(220,38,38,0.1);color:#DC2626;border:1px solid rgba(220,38,38,0.2);">Fraud ${c.fraud_score}</span>` : ''}
          </div>
          <div style="font-size:0.8rem;color:var(--t-2);margin-bottom:0.25rem;line-height:1.5;">${c.content}</div>
          <div style="font-size:0.6875rem;color:var(--t-3);">${c.is_anonymous ? 'Anonim' : 'User'} · ${c.ip_address} · ${new Date(c.created_at).toLocaleString('id-ID')}</div>
        </div>
        <div class="mod-actions">
          <button class="mod-btn mod-approve" onclick="moderateComment(${c.id}, 'approve')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Setujui
          </button>
          <button class="mod-btn mod-reject" onclick="moderateComment(${c.id}, 'reject')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Tolak
          </button>
        </div>
      `;
      list.appendChild(div);
    });
  } catch(e) {
    list.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Mode demo: koneksi PHP tidak tersedia.</p>';
    buildModerationMock();
  }
}

async function moderateComment(id, action) {
  const el = document.getElementById('mod-' + id);
  try {
    const data = await adminPost({ action: action + '_comment', id });
    if (data.success) {
      showToast(data.message, 'ok');
      if (el) { el.style.opacity = '0.3'; el.style.pointerEvents = 'none'; }
    } else {
      showToast(data.error || 'Gagal', 'err');
    }
  } catch(e) {
    // Demo fallback
    showToast(action === 'approve' ? 'Disetujui (demo)' : 'Ditolak (demo)', 'ok');
    if (el) { el.style.opacity = '0.3'; el.style.pointerEvents = 'none'; }
  }
}

function buildModerationMock() {
  const list = document.getElementById('moderationList');
  if (!list) return;
  const reports = [
    { num:'0812-3456-7890', cat:'Penipuan', desc:'Mengaku debt collector, tidak kooperatif.', time:'1 jam lalu' },
    { num:'BCA — 1234567890', cat:'Penipuan', desc:'Penjual fiktif marketplace.', time:'2 jam lalu' },
  ];
  reports.forEach(r => {
    const div = document.createElement('div');
    div.className = 'mod-item';
    div.innerHTML = `
      <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
          <span style="font-size:0.875rem;font-weight:700;color:var(--t-1);">${r.num}</span>
          <span class="badge badge-cat">${r.cat}</span>
        </div>
        <div style="font-size:0.8rem;color:var(--t-2);">${r.desc}</div>
        <div style="font-size:0.6875rem;color:var(--t-3);">Anonim · ${r.time}</div>
      </div>
      <div class="mod-actions">
        <button class="mod-btn mod-approve" onclick="this.closest('.mod-item').style.opacity='.3';showToast('Disetujui','ok');">Setujui</button>
        <button class="mod-btn mod-reject" onclick="this.closest('.mod-item').style.opacity='.3';showToast('Ditolak','warn');">Tolak</button>
      </div>
    `;
    list.appendChild(div);
  });
}

/* ── Users (Real API) ────────────────────────────────────────── */
async function buildUsers() {
  const tb = document.getElementById('userTbody');
  if (!tb) return;
  
  const q = document.getElementById('userSearch')?.value?.trim() || '';
  tb.innerHTML = '<tr><td colspan="5" style="color:var(--t-3);padding:1rem;">Memuat...</td></tr>';

  try {
    const data = await adminAPI('users', q ? { q } : {});
    tb.innerHTML = '';
    
    if (!data.users || data.users.length === 0) {
      tb.innerHTML = '<tr><td colspan="5" style="color:var(--t-3);padding:1rem;">Tidak ada pengguna ditemukan.</td></tr>';
      return;
    }
    
    data.users.forEach(c => {
      const tsColor = c.trust_score >= 90 ? 'var(--c-aman)' : c.trust_score >= 70 ? 'var(--c-waspada)' : 'var(--t-3)';
      const bannedBadge = c.is_banned ? '<span style="color:var(--c-bahaya);font-size:0.7rem;font-weight:700;">BANNED</span>' : '';
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="color:var(--t-1);font-weight:600;">${c.name} ${bannedBadge}</td>
        <td style="color:var(--t-2);font-size:0.8rem;">${c.email}</td>
        <td><span style="font-weight:800;color:${tsColor};">${c.trust_score}</span></td>
        <td><span class="badge badge-cat">${c.role}</span></td>
        <td>
          <div style="display:flex;gap:0.375rem;">
            ${!c.is_banned ? `<button class="mod-btn mod-reject" onclick="banUser(${c.id})">Ban</button>` : ''}
          </div>
        </td>
      `;
      tb.appendChild(tr);
    });
  } catch(e) {
    tb.innerHTML = '';
    // Demo fallback
    if (typeof MockData !== 'undefined') {
      MockData.contributors?.forEach(c => {
        const tsColor = c.trustScore >= 90 ? 'var(--c-aman)' : 'var(--t-3)';
        const tr = document.createElement('tr');
        tr.innerHTML = `<td style="color:var(--t-1);font-weight:600;">${c.name}</td><td style="color:var(--t-2);">-</td><td><span style="font-weight:800;color:${tsColor};">${c.trustScore}</span></td><td><span class="badge badge-cat">${c.badge}</span></td><td></td>`;
        tb.appendChild(tr);
      });
    }
  }
}

async function banUser(id) {
  if (!confirm('Yakin ingin memblokir pengguna ini?')) return;
  try {
    const data = await adminPost({ action: 'ban_user', id });
    showToast(data.message || 'Berhasil', 'ok');
    buildUsers();
  } catch(e) {
    showToast('Ban gagal (mode demo)', 'warn');
  }
}

// Search users on enter key
document.getElementById('userSearch')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') buildUsers();
});

/* ── Fraud Logs (Real API) ─────────────────────────────────── */
async function buildFraudLogs() {
  const cont = document.getElementById('fraudLogsContainer');
  if (!cont) return;
  cont.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Memuat fraud logs...</p>';
  
  try {
    const data = await adminAPI('fraud_logs');
    cont.innerHTML = '';
    
    if (!data.logs || data.logs.length === 0) {
      cont.innerHTML = '<p style="color:var(--c-aman);font-size:0.85rem;padding:1rem;text-align:center;">✅ Tidak ada aktivitas fraud terdeteksi.</p>';
      return;
    }
    
    const table = document.createElement('table');
    table.style.cssText = 'width:100%;border-collapse:collapse;font-size:0.8rem;';
    table.innerHTML = `<thead><tr style="border-bottom:1px solid var(--border);">
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">Waktu</th>
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">Target</th>
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">IP</th>
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">Rule</th>
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">Score</th>
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">Aksi</th>
    </tr></thead><tbody id="fraudTbody"></tbody>`;
    cont.appendChild(table);
    
    const tbody = table.querySelector('#fraudTbody');
    data.logs.forEach(log => {
      const scoreColor = log.fraud_score >= 80 ? 'var(--c-bahaya)' : log.fraud_score >= 40 ? 'var(--c-hatihati)' : 'var(--c-waspada)';
      const tr = document.createElement('tr');
      tr.style.borderBottom = '1px solid var(--border)';
      tr.innerHTML = `
        <td style="padding:0.5rem;color:var(--t-3);">${new Date(log.created_at).toLocaleString('id-ID')}</td>
        <td style="padding:0.5rem;color:var(--t-1);">${log.target_id ? 'ID #' + log.target_id : '-'}</td>
        <td style="padding:0.5rem;color:var(--t-2);font-family:monospace;">${log.ip_address}</td>
        <td style="padding:0.5rem;"><span class="badge badge-cat">${log.action_type}</span></td>
        <td style="padding:0.5rem;font-weight:800;color:${scoreColor};">${log.fraud_score}</td>
        <td style="padding:0.5rem;color:var(--t-2);">${log.reason || '-'}</td>
      `;
      tbody.appendChild(tr);
    });
    
  } catch(e) {
    cont.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Fraud logs tersedia saat PHP backend aktif.</p>';
  }
}

/* ── Activity Logs (Real API) ──────────────────────────────── */
async function buildActivityLogs() {
  const cont = document.getElementById('activityLogsContainer');
  if (!cont) return;
  cont.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Memuat activity logs...</p>';
  
  try {
    const data = await adminAPI('audit_logs');
    cont.innerHTML = '';
    
    if (!data.logs || data.logs.length === 0) {
      cont.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;text-align:center;">Belum ada aktivitas tercatat.</p>';
      return;
    }
    
    data.logs.forEach(log => {
      const div = document.createElement('div');
      div.style.cssText = 'display:flex;align-items:center;gap:0.75rem;padding:0.625rem 0;border-bottom:1px solid var(--border);font-size:0.8rem;';
      div.innerHTML = `
        <div style="width:8px;height:8px;border-radius:50%;background:var(--brand);flex-shrink:0;"></div>
        <div style="flex:1;">
          <span style="color:var(--t-1);font-weight:600;">${log.action}</span>
          <span style="color:var(--t-3);"> (oleh ${log.admin_name || 'Admin'})</span>
          ${log.target_type ? `<span style="color:var(--t-3);"> → ${log.target_type} #${log.target_id || ''}</span>` : ''}
        </div>
        <div style="color:var(--t-3);font-family:monospace;font-size:0.75rem;">${log.ip_address || ''}</div>
        <div style="color:var(--t-4);font-size:0.7rem;flex-shrink:0;">${new Date(log.created_at).toLocaleString('id-ID')}</div>
      `;
      cont.appendChild(div);
    });
    
  } catch(e) {
    cont.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Activity logs tersedia saat PHP backend aktif.</p>';
  }
}

/* ── Server Metrics (Static) ─────────────────────────────── */
function buildServerMetrics() {
  const grid = document.getElementById('serverMetrics');
  if (!grid) return;
  const metrics = [
    { label:'CPU Usage', val:'23%', color:'var(--c-aman)', pct:23 },
    { label:'RAM', val:'4.2 / 16 GB', color:'var(--c-aman)', pct:26 },
    { label:'Disk', val:'142 / 500 GB', color:'var(--c-waspada)', pct:28 },
    { label:'MySQL Conn', val:'124 aktif', color:'var(--c-aman)', pct:62 },
    { label:'Redis Hit', val:'98.7%', color:'var(--c-aman)', pct:98 },
    { label:'Queue', val:'34 pending', color:'var(--c-waspada)', pct:34 },
  ];
  metrics.forEach(m => {
    const div = document.createElement('div');
    div.className = 'server-card';
    div.innerHTML = `<div class="server-lbl">${m.label}</div><div class="server-val" style="color:${m.color};">${m.val}</div><div class="prog-bar"><div class="prog-fill" style="width:${m.pct}%;background:${m.color};"></div></div>`;
    grid.appendChild(div);
  });
}

// Override DOMContentLoaded section to call real API functions sequentially
document.addEventListener('DOMContentLoaded', async () => {
  try {
    await buildModeration();
    await buildFraudLogs();
    await buildActivityLogs();
  } catch (e) {
    console.error(e);
  }
});
/* ── Phones (Real API) ────────────────────────────────────── */
let phoneDataMap = {}; // Global store for loaded phones

async function buildPhones() {
  const tbody = document.getElementById('phoneTbody');
  const search = document.getElementById('phoneSearch')?.value || '';
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1rem;">Memuat data...</td></tr>';
  
  try {
    const data = await adminAPI('phones', { q: search });
    tbody.innerHTML = '';
    
    if (document.getElementById('phoneCountTitle')) {
      document.getElementById('phoneCountTitle').innerText = `${data.total.toLocaleString('id-ID')} Nomor`;
    }

    if (!data.data || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1rem;">Tidak ada nomor ditemukan.</td></tr>';
      return;
    }

    phoneDataMap = {}; // reset
    data.data.forEach(p => {
      phoneDataMap[p.id] = p;
      const bColor = p.status === 'bahaya' ? 'badge-bahaya' : (p.status === 'hatihati' ? 'badge-waspada' : (p.status === 'waspada' ? 'badge-waspada' : 'badge-aman'));
      
      let contactsHtml = '';
      if (p.contacts && p.contacts.length > 0) {
        contactsHtml = `<div style="margin-top:0.375rem;display:flex;flex-wrap:wrap;gap:0.25rem;">` + 
          p.contacts.map(c => `<span class="badge" style="background:var(--bg-2);color:var(--t-2);border:1px solid var(--border);font-size:0.65rem;font-weight:400;text-transform:none;">${c.contact_name}</span>`).join('') + 
          `</div>`;
      }

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><strong style="color:var(--t-1);font-weight:600;">${p.phone_number}</strong>${contactsHtml}</td>
        <td><span class="badge ${bColor}">${p.status}</span></td>
        <td style="font-weight:700;color:var(--primary);">${p.security_score}/100</td>
        <td>${p.report_count}</td>
        <td>${p.search_count}</td>
        <td><button class="mod-btn" style="border-color:var(--border);" onclick="editPhone(${p.id})">Edit</button></td>
      `;
      tbody.appendChild(tr);
    });
  } catch(e) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1rem;">Gagal memuat data dari server.</td></tr>';
  }
}

document.getElementById('phoneSearch')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') buildPhones();
});

function editPhone(id) {
  const p = phoneDataMap[id];
  if (!p) return;
  
  document.getElementById('pemId').value = p.id;
  document.getElementById('pemPhoneTitle').innerText = p.phone_number;
  document.getElementById('pemStatus').value = p.status;
  document.getElementById('pemScore').value = p.security_score;
  
  const contactsContainer = document.getElementById('pemContactsContainer');
  contactsContainer.innerHTML = '';
  
  if (p.contacts && p.contacts.length > 0) {
    p.contacts.forEach((c, idx) => {
      contactsContainer.innerHTML += `
        <div style="display:flex;align-items:center;gap:0.5rem;" class="contact-edit-row">
          <input type="hidden" class="old-contact-name" value="${c.contact_name.replace(/"/g, '&quot;')}">
          <input type="text" class="new-contact-name" value="${c.contact_name.replace(/"/g, '&quot;')}" style="flex:1;padding:0.4rem;border:1px solid var(--border);border-radius:4px;background:var(--bg-3);color:var(--t-1);font-size:0.8rem;outline:none;" placeholder="Nama kontak">
        </div>
      `;
    });
  } else {
    contactsContainer.innerHTML = '<span style="font-size:0.8rem;color:var(--t-3);">Tidak ada riwayat nama kontak dari Google untuk nomor ini.</span>';
  }

  document.getElementById('phoneEditModal').style.display = 'flex';
}

function closePhoneModal() {
  document.getElementById('phoneEditModal').style.display = 'none';
}

async function savePhoneModal() {
  const id = document.getElementById('pemId').value;
  const p = phoneDataMap[id];
  if (!p) return;

  const newStatus = document.getElementById('pemStatus').value;
  const newScore = parseInt(document.getElementById('pemScore').value);
  if (isNaN(newScore) || newScore < 0 || newScore > 100) {
    showToast("Skor tidak valid! Harus angka 0-100.", "err");
    return;
  }

  // Gather contact updates
  const updates = [];
  document.querySelectorAll('.contact-edit-row').forEach(row => {
    const oldName = row.querySelector('.old-contact-name').value;
    const newName = row.querySelector('.new-contact-name').value;
    if (oldName !== newName && newName.trim() !== '') {
      updates.push({ old_name: oldName, new_name: newName.trim() });
    }
  });

  try {
    // 1. Update phone details
    let res = await adminPost({ action: 'update_phone', id: id, status: newStatus, score: newScore });
    if (!res.success) throw new Error(res.error || 'Gagal update nomor');

    // 2. Update contacts if any changed
    if (updates.length > 0) {
      res = await adminPost({ action: 'update_phone_contacts', phone_number: p.phone_number, updates: updates });
      if (!res.success) throw new Error(res.error || 'Gagal update kontak');
    }

    showToast('Data berhasil disimpan.', 'ok');
    closePhoneModal();
    buildPhones();
  } catch(e) {
    showToast(e.message || 'Terjadi kesalahan jaringan.', 'err');
  }
}

/* ── CMS Articles ──────────────────────────────────────────── */
let articlesData = [];

async function buildArticles() {
  try {
    const res = await fetch('/api/admin_cms.php?action=list_articles&token='+ADMIN_TOKEN).then(r => r.json());
    if (res.articles) {
      articlesData = res.articles;
      const tb = document.getElementById('articlesTbody');
      tb.innerHTML = '';
      if (articlesData.length === 0) {
        tb.innerHTML = '<tr><td colspan="6" style="text-align:center;">Belum ada artikel</td></tr>';
        return;
      }
      articlesData.forEach(a => {
        const tr = document.createElement('tr');
        const stClass = a.status === 'published' ? 'aman' : (a.status === 'draft' ? 'waspada' : 'bahaya');
        tr.innerHTML = `
          <td style="color:var(--t-1);font-weight:600;">${a.title}</td>
          <td>${a.slug}</td>
          <td><span class="badge badge-${stClass}">${a.status}</span></td>
          <td>${a.view_count}</td>
          <td>${a.published_at || a.created_at}</td>
          <td>
            <button class="mod-btn" style="border-color:var(--border);" onclick="editArticle(${a.id})">Edit</button>
            <button class="mod-btn mod-reject" onclick="deleteArticle(${a.id})">Hapus</button>
          </td>
        `;
        tb.appendChild(tr);
      });
    }
  } catch(e) { console.error(e); }
}

function openArticleModal(id = null) {
  document.getElementById('artId').value = '';
  document.getElementById('artTitle').value = '';
  document.getElementById('artSlug').value = '';
  document.getElementById('artExcerpt').value = '';
  document.getElementById('artContent').value = '';
  document.getElementById('artStatus').value = 'draft';
  document.getElementById('articleEditModal').style.display = 'flex';
}

async function editArticle(id) {
  try {
    const res = await fetch('/api/admin_cms.php?action=get_article&id=' + id + '&token=' + ADMIN_TOKEN).then(r => r.json());
    if (res.article) {
      document.getElementById('artId').value = res.article.id;
      document.getElementById('artTitle').value = res.article.title;
      document.getElementById('artSlug').value = res.article.slug;
      document.getElementById('artExcerpt').value = res.article.excerpt;
      document.getElementById('artContent').value = res.article.content;
      document.getElementById('artStatus').value = res.article.status;
      document.getElementById('articleEditModal').style.display = 'flex';
    } else {
      showToast("Artikel tidak ditemukan", "err");
    }
  } catch (e) {
    showToast("Gagal memuat artikel", "err");
  }
}

async function saveArticle() {
  const payload = {
    id: document.getElementById('artId').value,
    title: document.getElementById('artTitle').value,
    slug: document.getElementById('artSlug').value,
    excerpt: document.getElementById('artExcerpt').value,
    content: document.getElementById('artContent').value,
    status: document.getElementById('artStatus').value,
  };
  if (!payload.title) return showToast('Judul wajib diisi', 'err');
  
  try {
    const res = await fetch('/api/admin_cms.php?action=save_article&token='+ADMIN_TOKEN, {
      method: 'POST',
      body: JSON.stringify(payload)
    }).then(r => r.json());
    if (res.success) {
      showToast('Artikel disimpan', 'ok');
      closeArticleModal();
      buildArticles();
    } else throw new Error(res.error);
  } catch(e) { showToast('Gagal menyimpan artikel', 'err'); }
}

function closeArticleModal() {
  document.getElementById('articleEditModal').style.display = 'none';
}

async function deleteArticle(id) {
  if (!confirm('Hapus artikel ini?')) return;
  try {
    const res = await fetch('/api/admin_cms.php?action=delete_article&token='+ADMIN_TOKEN, {
      method: 'POST', body: JSON.stringify({id})
    }).then(r => r.json());
    if (res.success) { showToast('Artikel dihapus', 'ok'); buildArticles(); }
  } catch(e) { showToast('Gagal menghapus', 'err'); }
}

/* ── Banners ──────────────────────────────────────────── */
let bannersData = [];

async function buildBanners() {
  try {
    const res = await fetch('/api/admin_cms.php?action=list_banners&token='+ADMIN_TOKEN).then(r => r.json());
    if (res.banners) {
      bannersData = res.banners;
      const tb = document.getElementById('bannersTbody');
      tb.innerHTML = '';
      if (bannersData.length === 0) {
        tb.innerHTML = '<tr><td colspan="5" style="text-align:center;">Belum ada banner</td></tr>';
        return;
      }
      bannersData.forEach(b => {
        const tr = document.createElement('tr');
        const stClass = parseInt(b.is_active) === 1 ? 'aman' : 'bahaya';
        const stText = parseInt(b.is_active) === 1 ? 'Aktif' : 'Non-Aktif';
        tr.innerHTML = `
          <td style="color:var(--t-1);font-weight:600;">${b.name}</td>
          <td><span style="font-family:monospace;font-size:0.75rem;padding:0.2rem 0.4rem;background:var(--bg-3);border-radius:4px;">${b.position}</span></td>
          <td>${b.type}</td>
          <td><span class="badge badge-${stClass}">${stText}</span></td>
          <td>
            <button class="mod-btn" style="border-color:var(--border);" onclick="editBanner(${b.id})">Edit</button>
            <button class="mod-btn mod-reject" onclick="deleteBanner(${b.id})">Hapus</button>
          </td>
        `;
        tb.appendChild(tr);
      });
    }
  } catch(e) { console.error(e); }
}

function openBannerModal(id = null) {
  document.getElementById('banId').value = '';
  document.getElementById('banName').value = '';
  document.getElementById('banPosition').value = 'landing_mid';
  document.getElementById('banType').value = 'image';
  document.getElementById('banContent').value = '';
  document.getElementById('banLink').value = '';
  document.getElementById('banStatus').value = '1';
  document.getElementById('bannerEditModal').style.display = 'flex';
}

async function editBanner(id) {
  try {
    const res = await fetch('/api/admin_cms.php?action=get_banner&id=' + id + '&token=' + ADMIN_TOKEN).then(r => r.json());
    if (res.banner) {
      document.getElementById('banId').value = res.banner.id;
      document.getElementById('banName').value = res.banner.name;
      document.getElementById('banPosition').value = res.banner.position;
      document.getElementById('banType').value = res.banner.type;
      document.getElementById('banContent').value = res.banner.content;
      document.getElementById('banLink').value = res.banner.link_url || '';
      document.getElementById('banStatus').value = res.banner.is_active;
      document.getElementById('bannerEditModal').style.display = 'flex';
    } else {
      showToast("Banner tidak ditemukan", "err");
    }
  } catch (e) {
    showToast("Gagal memuat banner", "err");
  }
}

async function saveBanner() {
  const payload = {
    id: document.getElementById('banId').value,
    name: document.getElementById('banName').value,
    position: document.getElementById('banPosition').value,
    type: document.getElementById('banType').value,
    content: document.getElementById('banContent').value,
    link_url: document.getElementById('banLink').value,
    is_active: parseInt(document.getElementById('banStatus').value),
  };
  if (!payload.name || !payload.content) return showToast('Nama dan Konten wajib diisi', 'err');
  
  try {
    const res = await fetch('/api/admin_cms.php?action=save_banner&token='+ADMIN_TOKEN, {
      method: 'POST',
      body: JSON.stringify(payload)
    }).then(r => r.json());
    if (res.success) {
      showToast('Banner disimpan', 'ok');
      closeBannerModal();
      buildBanners();
    } else throw new Error(res.error);
  } catch(e) { showToast('Gagal menyimpan banner', 'err'); }
}

function closeBannerModal() {
  document.getElementById('bannerEditModal').style.display = 'none';
}

async function deleteBanner(id) {
  if (!confirm('Hapus banner iklan ini?')) return;
  try {
    const res = await fetch('/api/admin_cms.php?action=delete_banner&token='+ADMIN_TOKEN, {
      method: 'POST', body: JSON.stringify({id})
    }).then(r => r.json());
    if (res.success) { showToast('Banner dihapus', 'ok'); buildBanners(); }
  } catch(e) { showToast('Gagal menghapus', 'err'); }
}
