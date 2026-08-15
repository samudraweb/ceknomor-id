/* ================================================================
   ceknomor.id — app.js
   Core UI logic: search, render, modals, toasts, navigation
   ================================================================ */

/* ── State ─────────────────────────────────────────────────── */
const App = {
  activeTab: 'phone',
  currentResult: null,
  reportMode: 'anonim',
  commentMode: 'anonim',
  rating: 0,
  user: null,
};

/* ── Init ───────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  populateBankSelect();
  renderTrendingSection();
  renderTopScam();
  renderArticles();
  loadAds();
  checkQueryParams();
  checkAuthState();
});

/* ── Bank Select ────────────────────────────────────────────── */
function populateBankSelect() {
  const selects = ['bankSelect', 'mBankSelect'];
  selects.forEach(id => {
    const sel = document.getElementById(id);
    if (!sel) return;
    sel.innerHTML = '';
    MockData.banks.forEach(b => {
      const opt = document.createElement('option');
      opt.value = b.code;
      opt.textContent = b.code;
      sel.appendChild(opt);
    });
  });
}

/* ── Tab switching ──────────────────────────────────────────── */
function switchTab(type) {
  App.activeTab = type;
  ['phone','rekening'].forEach(t => {
    document.getElementById(`tab${cap(t)}`)?.classList.toggle('active', t === type);
    document.getElementById(`${t}Group`)?.classList.toggle('hidden', t !== type);
  });
}

function switchMobileTab(type) {
  App.activeTab = type;
  const mTabPhone = document.getElementById('mTabPhone');
  const mTabRekening = document.getElementById('mTabRekening');
  const mPhoneGroup = document.getElementById('mPhoneGroup');
  const mRekeningGroup = document.getElementById('mRekeningGroup');
  
  if(type === 'phone') {
    mTabPhone?.classList.add('active');
    mTabRekening?.classList.remove('active');
    if(mPhoneGroup) { mPhoneGroup.style.display = 'flex'; mPhoneGroup.classList.remove('hidden'); }
    if(mRekeningGroup) { mRekeningGroup.style.display = 'none'; mRekeningGroup.classList.add('hidden'); }
  } else {
    mTabPhone?.classList.remove('active');
    mTabRekening?.classList.add('active');
    if(mPhoneGroup) { mPhoneGroup.style.display = 'none'; mPhoneGroup.classList.add('hidden'); }
    if(mRekeningGroup) { mRekeningGroup.style.display = 'flex'; mRekeningGroup.classList.remove('hidden'); }
  }
}

/* ── Search ─────────────────────────────────────────────────── */
function formatPhoneInput(el) {
  el.value = el.value.replace(/[^0-9\-+\s]/g,'');
}

function handleSearchKey(e, type) {
  if (e.key === 'Enter') doSearch(type);
}

function doSearch(type) {
  if (!App.user) {
    showToast('Anda harus login dengan Google terlebih dahulu untuk melakukan pencarian', 'warn');
    openLoginModal();
    return;
  }

  const isPhone = type === 'phone';
  const input = document.getElementById(isPhone ? 'phoneInput' : 'rekeningInput');
  const bank  = document.getElementById('bankSelect')?.value || '';
  let q     = input?.value?.trim()?.replace(/\D/g,'') || '';

  // Normalize: 62xxx → 0xxx (e.g., 628123456789 → 08123456789)
  if (isPhone && q.startsWith('62') && q.length > 9) {
    q = '0' + q.substring(2);
  }

  if (!q) { showToast('Masukkan nomor terlebih dahulu', 'warn'); input?.focus(); return; }
  if (isPhone && (q.length < 10 || q.length > 13)) { showToast('Nomor telepon harus 10-13 digit', 'err'); return; }
  if (!isPhone) {
    const bankData = MockData.banks.find(b => b.code === bank);
    const minLen = bankData?.len || 10;
    const maxLen = bankData?.maxLen || bankData?.len || 16;
    if (q.length < minLen || q.length > maxLen) {
      showToast(bankData?.len && !bankData.maxLen ? `Nomor rekening ${bank} harus ${minLen} digit` : `Nomor rekening ${bank} harus ${minLen}-${maxLen} digit`, 'err');
      return;
    }
  }

  const params = isPhone
    ? `?type=phone&q=${encodeURIComponent(q)}`
    : `?type=rekening&bank=${encodeURIComponent(bank)}&q=${encodeURIComponent(q)}`;

  window.location.href = `/hasil/${params}`;
}

function doSearchMobile(type) {
  if (!App.user) {
    showToast('Anda harus login dengan Google terlebih dahulu untuk melakukan pencarian', 'warn');
    openLoginModal();
    return;
  }

  const isPhone = type === 'phone';
  const input = document.getElementById(isPhone ? 'mPhoneInput' : 'mRekeningInput');
  const bank  = document.getElementById('mBankSelect')?.value || '';
  let q     = input?.value?.trim()?.replace(/\D/g,'') || '';

  // Normalize: 62xxx → 0xxx (e.g., 628123456789 → 08123456789)
  if (isPhone && q.startsWith('62') && q.length > 9) {
    q = '0' + q.substring(2);
  }

  if (!q) { showToast('Masukkan nomor terlebih dahulu', 'warn'); input?.focus(); return; }
  if (isPhone && (q.length < 10 || q.length > 13)) { showToast('Nomor telepon harus 10-13 digit', 'err'); return; }
  if (!isPhone) {
    const bankData = MockData.banks.find(b => b.code === bank);
    const minLen = bankData?.len || 10;
    const maxLen = bankData?.maxLen || bankData?.len || 16;
    if (q.length < minLen || q.length > maxLen) {
      showToast(bankData?.len && !bankData.maxLen ? `Nomor rekening ${bank} harus ${minLen} digit` : `Nomor rekening ${bank} harus ${minLen}-${maxLen} digit`, 'err');
      return;
    }
  }

  const params = isPhone
    ? `?type=phone&q=${encodeURIComponent(q)}`
    : `?type=rekening&bank=${encodeURIComponent(bank)}&q=${encodeURIComponent(q)}`;

  window.location.href = `/hasil/${params}`;
}

/* ── URL Params → search on load (hasil.html) ──────────────── */
function checkQueryParams() {
  if (!document.getElementById('resultContainer')) return;

  const params = new URLSearchParams(window.location.search);
  const type   = params.get('type') || 'phone';
  const q      = params.get('q')    || '';
  const bank   = params.get('bank') || '';

  if (!q) {
    document.getElementById('loadingState')?.classList.add('hidden');
    showEmptyState();
    return;
  }

  // Breadcrumb
  const bc = document.getElementById('breadcrumbNum');
  if (bc) bc.textContent = bank ? `${bank} — ${q}` : q;

  // Try real API first, fallback to mock data if PHP unavailable
  const apiUrl = type === 'phone' 
    ? `/api/search.php?type=phone&q=${encodeURIComponent(q)}`
    : `/api/search.php?type=rekening&bank=${encodeURIComponent(bank)}&q=${encodeURIComponent(q)}`;

  let searchCountSession = parseInt(sessionStorage.getItem('smartSearchCount') || '0');
  searchCountSession++;
  sessionStorage.setItem('smartSearchCount', searchCountSession.toString());
  
  const delayMs = (searchCountSession === 1) ? 8000 : 4000;
  
  // Advanced Loading Animation Sequence
  const statusText = document.getElementById('loadingStatusText');
  const progressBar = document.getElementById('loadingProgressBar');
  if (statusText && progressBar) {
    statusText.textContent = "Connecting to central server...";
    progressBar.style.width = "0%";
    
    if (delayMs === 8000) {
      setTimeout(() => { if(statusText) statusText.textContent = "Scanning global fraud footprints..."; progressBar.style.width = "25%"; }, 1500);
      setTimeout(() => { if(statusText) statusText.textContent = "Cross-referencing database records..."; progressBar.style.width = "50%"; }, 3500);
      setTimeout(() => { if(statusText) statusText.textContent = "Analyzing threat level (Fraud Score)..."; progressBar.style.width = "75%"; }, 5500);
      setTimeout(() => { if(statusText) statusText.textContent = "Compiling final report..."; progressBar.style.width = "90%"; }, 7000);
    } else {
      setTimeout(() => { if(statusText) statusText.textContent = "Scanning global fraud footprints..."; progressBar.style.width = "30%"; }, 800);
      setTimeout(() => { if(statusText) statusText.textContent = "Analyzing threat level (Fraud Score)..."; progressBar.style.width = "70%"; }, 2000);
      setTimeout(() => { if(statusText) statusText.textContent = "Compiling final report..."; progressBar.style.width = "90%"; }, 3000);
    }
  }
  
  const artificialDelay = new Promise(resolve => setTimeout(resolve, delayMs));

  Promise.all([fetch(apiUrl), artificialDelay])
    .then(([res]) => {
      if (res.status === 401 || !App.user) {
        showToast('Sesi kedaluwarsa atau belum login. Silakan login kembali.', 'warn');
        openLoginModal();
        throw new Error('Unauthorized');
      }
      const ct = res.headers.get('content-type') || '';
      if (!res.ok || !ct.includes('application/json')) throw new Error('Not a valid JSON API response');
      return res.json();
    })
    .then(data => {
      if (progressBar) progressBar.style.width = "100%";
      setTimeout(() => {
        document.getElementById('loadingState')?.classList.add('hidden');
        document.getElementById('resultContainer')?.classList.remove('hidden');
      
      // Map API response to render function expectations
      const result = {
        status: data.status,
        securityScore: data.score,
        ownerName: data.owner_name,
        otherNames: data.other_names || [],
        reportCount: data.reports,
        commentCount: data.comments,
        searchCount: data.searchCount || 0,
        helpfulCount: data.helpfulCount || 0,
        lastReported: data.last_reported,
        insight: data.insight,
        comments: data.comment_list || []
      };

      renderResult(result, type, bank, q);
      }, 300);
    })
    .catch(err => {
      if (err.message === 'Unauthorized' || !App.user) {
        if (!App.user && err.message !== 'Unauthorized') {
          showToast('Sesi kedaluwarsa atau belum login. Silakan login kembali.', 'warn');
          openLoginModal();
        }
        // 1. Render dummy data for background blur effect
        const dummyResult = {
          status: 'waspada',
          securityScore: 45,
          ownerName: 'Disembunyikan',
          otherNames: ['***', '***'],
          reportCount: 12,
          commentCount: 45,
          searchCount: 1024,
          helpfulCount: 89,
          lastReported: 'Beberapa menit yang lalu',
          insight: 'Otorisasi diperlukan untuk melihat data lengkap.',
          comments: []
        };
        renderResult(dummyResult, type, bank, q);
        
        // 2. Add glassmorphism overlay
        const container = document.getElementById('resultContainer');
        if (container) {
          container.style.position = 'relative';
          container.classList.remove('hidden');
          
          const overlay = document.createElement('div');
          overlay.style.position = 'absolute';
          overlay.style.inset = '0';
          overlay.style.zIndex = '10';
          overlay.style.backdropFilter = 'blur(10px)';
          overlay.style.WebkitBackdropFilter = 'blur(10px)';
          overlay.style.background = 'rgba(128, 128, 128, 0.1)'; 
          overlay.style.display = 'flex';
          overlay.style.flexDirection = 'column';
          overlay.style.alignItems = 'center';
          overlay.style.justifyContent = 'center';
          overlay.style.borderRadius = 'var(--r-lg)';
          
          overlay.innerHTML = `
            <div style="text-align:center; padding: 2rem; background: var(--bg-0); border-radius: var(--r-lg); border: 1px solid var(--border); box-shadow: var(--shadow-xl); max-width: 340px; width: 90%;">
              <div style="width: 64px; height: 64px; background: var(--bg-2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem auto; border: 1px solid var(--border); color: var(--t-2);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:32px;height:32px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
              </div>
              <h3 style="color:var(--t-1); margin-bottom: 0.5rem; font-size: 1.25rem;">Hasil Terkunci</h3>
              <p style="color:var(--t-2); font-size:0.9375rem; margin-bottom: 1.75rem; line-height: 1.5;">Anda perlu masuk untuk melihat detail keamanan, laporan, dan skor kepercayaan.</p>
              <button class="btn btn-primary" onclick="openLoginModal()" style="width: 100%; justify-content: center; padding: 0.75rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;margin-right:0.5rem;"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Masuk Sekarang
              </button>
            </div>
          `;
          container.appendChild(overlay);
        }
        document.getElementById('loadingState')?.classList.add('hidden');
        return;
      }

      // Fallback to local mock data (when running without PHP)
      console.warn('API unavailable, using local mock data:', err.message);
      
      const result = type === 'phone'
        ? DataHelper.findPhone(q)
        : DataHelper.findRekening(bank, q);

      document.getElementById('loadingState')?.classList.add('hidden');
      document.getElementById('resultContainer')?.classList.remove('hidden');
      renderResult(result, type, bank, q);
    });
}

/* ── Result Rendering ───────────────────────────────────────── */
function renderResult(data, type, bank, q) {
  const container = document.getElementById('resultContainer');
  if (!container) return;

  App.currentResult = data;

  const sc = DataHelper.getStatusConfig(data.status);
  const score = data.securityScore || 0;
  const scoreColor = sc.color;
  
  const safePct = score;
  const dangerPct = 100 - score;

  container.innerHTML = `
    <!-- Header Card -->
    <div class="card" style="margin-bottom:1rem;">
      <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
          <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
            <div style="width:32px;height:32px;border-radius:var(--r-md);background:var(--bg-3);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;">
              ${type === 'phone' ? `<svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="var(--t-2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.27 9.09a19.79 19.79 0 01-3.07-8.67A2 2 0 012.18 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 9.91a16 16 0 006.72 6.72l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>` : `<svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="var(--t-2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>`}
            </div>
            <span style="font-size:0.75rem;font-weight:500;color:var(--t-3);">${type === 'phone' ? 'Nomor Telepon' : `Rekening ${bank}`}</span>
          </div>
          <div style="font-size:1.375rem;font-weight:700;color:var(--t-1);margin-bottom:0.25rem;">${bank ? `${bank} — ${q}` : q}</div>
          ${data.ownerName ? `<div style="font-size:0.875rem;color:var(--primary);font-weight:600;margin-bottom:0.75rem;display:flex;align-items:center;gap:0.375rem;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            ${data.ownerName}
          </div>` : '<div style="height:0.5rem;"></div>'}
          ${data.otherNames && data.otherNames.length > 0 ? `
            <div style="margin-bottom:0.75rem;font-size:0.75rem;color:var(--t-3);">
              <span style="font-weight:500;">Juga dikenal sebagai:</span>
              <div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-top:0.375rem;">
                ${data.otherNames.map(n => `<span style="background:var(--bg-3);padding:0.15rem 0.4rem;border-radius:4px;border:1px solid var(--border);color:var(--t-2);">${n.replace(/"/g, '&quot;')}</span>`).join('')}
              </div>
            </div>
          ` : ''}
          <div class="status-block status-${data.status}" style="display:inline-flex;">
            ${sc.svg}
            <span class="label">${sc.label}</span>
          </div>
        </div>

        <!-- Score Ring -->
        <div style="display:flex;flex-direction:column;align-items:center;gap:0.25rem;">
          <svg class="score-ring-svg" viewBox="0 0 100 100" style="width:88px;height:88px;transform:rotate(-90deg);">
            <circle cx="50" cy="50" r="40" fill="none" stroke="var(--bg-3)" stroke-width="8"/>
            <circle cx="50" cy="50" r="40" fill="none" stroke="${scoreColor}" stroke-width="8"
              stroke-linecap="round"
              stroke-dasharray="${2 * Math.PI * 40}"
              stroke-dashoffset="${2 * Math.PI * 40 * (1 - score/100)}"
              style="transition:stroke-dashoffset 1s ease;"/>
          </svg>
          <div style="margin-top:-76px;text-align:center;position:relative;z-index:1;margin-bottom:18px;">
            <div style="font-size:1.375rem;font-weight:800;color:${scoreColor};">${score}</div>
            <div style="font-size:0.6rem;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--t-3);">Skor</div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div style="display:flex;flex-direction:column;gap:0.375rem;min-width:140px;">
          <button class="btn btn-primary btn-sm" onclick="openReportModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Laporkan
          </button>
          <button class="btn btn-secondary btn-sm" onclick="openCommentModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            Komentar
          </button>
          <button class="btn btn-ghost btn-sm" onclick="copyToClipboard('${bank ? bank+' - '+q : q}')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            Salin
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Ad Placeholder (Shows between header and community stats) -->
    <div class="card show-on-mobile" style="margin-bottom:1rem;text-align:center;background:var(--bg-1);border-style:dashed;padding:2rem 1rem;display:none;">
      <svg viewBox="0 0 24 24" fill="none" stroke="var(--t-4)" stroke-width="1.5" stroke-linecap="round" style="width:28px;height:28px;margin:0 auto 0.5rem;display:block;"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      <span class="text-xs text-muted" style="font-size:0.75rem;color:var(--t-4);">Ruang Iklan</span>
    </div>

    <!-- Community Insight -->
    <div class="card" style="margin-bottom:1rem;">
      <h3 style="font-size:0.875rem;font-weight:700;margin-bottom:0.875rem;color:var(--t-2);text-transform:uppercase;letter-spacing:0.06em;">Statistik Komunitas</h3>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.75rem;margin-bottom:1rem;">
        <div style="text-align:center;padding:0.75rem;background:var(--bg-3);border-radius:var(--r-md);">
          <div style="font-size:1.25rem;font-weight:800;color:var(--c-bahaya);">${data.reportCount}</div>
          <div style="font-size:0.6875rem;color:var(--t-3);margin-top:0.125rem;">Laporan</div>
        </div>
        <div style="text-align:center;padding:0.75rem;background:var(--bg-3);border-radius:var(--r-md);">
          <div style="font-size:1.25rem;font-weight:800;color:var(--t-1);">${data.commentCount}</div>
          <div style="font-size:0.6875rem;color:var(--t-3);margin-top:0.125rem;">Komentar</div>
        </div>
        <div style="text-align:center;padding:0.75rem;background:var(--bg-3);border-radius:var(--r-md);">
          <div style="font-size:1.25rem;font-weight:800;color:var(--t-1);">${DataHelper.formatNumber(data.searchCount)}</div>
          <div style="font-size:0.6875rem;color:var(--t-3);margin-top:0.125rem;">Pencarian</div>
        </div>
        <div style="text-align:center;padding:0.75rem;background:var(--bg-3);border-radius:var(--r-md);">
          <div style="font-size:1.25rem;font-weight:800;color:var(--t-1);">${data.helpfulCount}</div>
          <div style="font-size:0.6875rem;color:var(--t-3);margin-top:0.125rem;">Helpful</div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:0.5rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
          <div style="display:flex;align-items:center;gap:0.25rem;font-size:0.75rem;color:var(--c-aman);min-width:90px;">
            <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            ${safePct}% Aman
          </div>
          <div class="prog-bar" style="flex:1;"><div class="prog-fill" style="width:${safePct}%;background:var(--c-aman);"></div></div>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem;">
          <div style="display:flex;align-items:center;gap:0.25rem;font-size:0.75rem;color:var(--c-bahaya);min-width:90px;">
            <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            ${dangerPct}% Bahaya
          </div>
          <div class="prog-bar" style="flex:1;"><div class="prog-fill" style="width:${dangerPct}%;background:var(--c-bahaya);"></div></div>
        </div>
      </div>

      <!-- Categories -->
      ${data.categories?.length ? `
      <div style="margin-top:0.875rem;display:flex;gap:0.375rem;flex-wrap:wrap;">
        ${data.categories.map(c => `<span class="badge-cat">${c}</span>`).join('')}
      </div>
      ` : ''}
    </div>

    <!-- Auto Summary -->
    ${data.summary ? `
    <div class="card" style="margin-bottom:1rem;background:var(--bg-3);">
      <div style="display:flex;gap:0.625rem;">
        <svg style="width:16px;height:16px;flex-shrink:0;stroke:var(--accent);fill:none;margin-top:2px;" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <div>
          <div style="font-size:0.75rem;font-weight:600;color:var(--accent);margin-bottom:0.25rem;">Ringkasan Komunitas</div>
          <p style="font-size:0.8125rem;color:var(--t-2);line-height:1.6;">${data.summary}</p>
        </div>
      </div>
    </div>
    ` : ''}

    <!-- Comments -->
    <div>
      <div class="sec-head">
        <h3 style="font-size:1rem;font-weight:700;">Komentar (${data.comments?.length || 0})</h3>
        <button class="btn btn-secondary btn-sm" onclick="openCommentModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          Tulis Komentar
        </button>
      </div>
      <div style="display:flex;flex-direction:column;gap:0.5rem;" id="commentsList">
        ${renderComments(data.comments || [])}
      </div>
    </div>
  `;
}

function renderComments(comments) {
  if (!comments.length) return `<div style="text-align:center;padding:2rem;color:var(--t-3);font-size:0.875rem;">Belum ada komentar. Jadilah yang pertama.</div>`;
  return comments.map(c => {
    const initials = c.user === 'Anonim' ? '?' : c.user.split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase();
    const stars = Array.from({length: 5}, (_, i) => `
      <svg class="star-svg ${i < c.rating ? 'star-filled' : 'star-empty'}" viewBox="0 0 24 24">
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
      </svg>`).join('');

    return `
      <div class="comment-card">
        <div class="comment-header">
          <div class="avatar">${initials === '?' ? `<svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>` : initials}</div>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
              <span class="comment-author">${c.user}</span>
              <div class="stars">${stars}</div>
            </div>
            <div class="comment-time">${c.time} · ${c.category}</div>
          </div>
        </div>
        <p style="font-size:0.8125rem;color:var(--t-2);line-height:1.6;">${SpamFilter.sanitize(c.content)}</p>
        <div class="vote-row">
          <button class="vote-btn" onclick="voteComment(this,'up')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3H14z"/><path d="M7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/></svg>
            Helpful (${c.helpful})
          </button>
          <button class="vote-btn" onclick="voteComment(this,'down')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 15v4a3 3 0 003 3l4-9V2H5.72a2 2 0 00-2 1.7l-1.38 9a2 2 0 002 2.3H10z"/><path d="M17 2h2.67A2.31 2.31 0 0122 4v7a2.31 2.31 0 01-2.33 2H17"/></svg>
            Tidak Helpful
          </button>
          <button class="vote-btn" onclick="flagComment()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
            Laporkan
          </button>
        </div>
      </div>
    `;
  }).join('');
}

function showEmptyState() {
  const container = document.getElementById('resultContainer');
  if (!container) return;
  container.classList.remove('hidden');
  container.innerHTML = `
    <div style="text-align:center;padding:3rem;color:var(--t-3);">
      <svg style="width:48px;height:48px;margin:0 auto 1rem;display:block;stroke:var(--t-4);fill:none;" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      <p style="font-size:0.9375rem;font-weight:500;">Masukkan nomor untuk mulai mencari</p>
      <a href="/" class="btn btn-secondary btn-sm" style="margin-top:1rem;display:inline-flex;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Kembali ke Beranda
      </a>
    </div>
  `;
}

/* ── Trending Render ─────────────────────────────────────────── */
function renderTrendingSection() {
  const grid = document.getElementById('trendingGrid');
  if (!grid) return;

  // Phone column
  const phoneCol = document.createElement('div');
  const rekeningCol = document.createElement('div');

  const phoneHead = `<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;font-size:0.8125rem;font-weight:600;color:var(--t-2);">
    <svg style="width:15px;height:15px;fill:none;stroke:currentColor;" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.27 9.09a19.79 19.79 0 01-3.07-8.67A2 2 0 012.18 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 9.91a16 16 0 006.72 6.72l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
    Nomor Telepon</div><div class="trend-list">`;
  const rekeningHead = `<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;font-size:0.8125rem;font-weight:600;color:var(--t-2);">
    <svg style="width:15px;height:15px;fill:none;stroke:currentColor;" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    Rekening Bank</div><div class="trend-list">`;

  let phoneParts = phoneHead;
  let rekeningParts = rekeningHead;

  fetch('/api/trending.php')
    .then(res => res.json())
    .then(data => {
      if (data.phones) {
        data.phones.slice(0, 7).forEach(item => {
          const sc = DataHelper.getStatusConfig(item.status);
          phoneParts += makeTrendItem(item, 'phone', sc);
        });
      }
      phoneParts += '</div>';
      
      if (data.banks) {
        data.banks.slice(0, 7).forEach(item => {
          const sc = DataHelper.getStatusConfig(item.status);
          rekeningParts += makeTrendItem(item, 'rekening', sc);
        });
      }
      rekeningParts += '</div>';

      phoneCol.innerHTML = phoneParts;
      rekeningCol.innerHTML = rekeningParts;
      grid.appendChild(phoneCol);
      grid.appendChild(rekeningCol);
    })
    .catch(err => {
      console.warn("Failed to fetch trending data:", err);
    });
}

function makeTrendItem(item, type, sc) {
  const isTop = item.rank <= 3;
  let url = '';
  let displayTitle = '';

  if (type === 'phone') {
    url = `/hasil/?type=phone&q=${encodeURIComponent(item.number.replace(/\D/g,''))}`;
    displayTitle = item.number;
  } else {
    url = `/hasil/?type=rekening&bank=${item.bank}&q=${item.number}`;
    displayTitle = `${item.bank} - ${item.number}`;
  }

  return `<a class="trend-item" href="${url}">
    <span class="trend-rank${isTop ? ' top' : ''}">${item.rank}</span>
    <span class="trend-num">${displayTitle}</span>
    <span class="trend-meta">
      <span class="trend-searches">${DataHelper.formatNumber(item.search_count)}</span>
      <span class="badge badge-${item.status}">${sc.label}</span>
    </span>
  </a>`;
}

function renderTopScam() {
  const list = document.getElementById('topScamList');
  if (!list) return;
  const scams = MockData.trendingPhones.filter(x => x.status === 'bahaya').slice(0,5);
  list.innerHTML = scams.map(item => {
    const sc = DataHelper.getStatusConfig(item.status);
    return makeTrendItem(item, 'phone', sc);
  }).join('');
}

function renderArticles() {
  const grid = document.getElementById('articlesGrid');
  if (!grid) return;
  
  fetch('/api/cms.php?action=get_articles')
    .then(res => res.json())
    .then(data => {
      if (data.articles && data.articles.length > 0) {
        grid.innerHTML = data.articles.map(a => `
          <a href="/artikel/${a.slug}/" class="article-card" style="text-decoration:none;color:inherit;display:block;">
            <div class="article-tag">${a.tag}</div>
            <h4>${a.title}</h4>
            <p>${a.excerpt}</p>
            <div class="article-meta">
              <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>${a.date}</span>
              <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>${a.views} views</span>
            </div>
          </a>
        `).join('');
      } else {
        grid.innerHTML = '<p style="color:var(--t-3);font-size:0.875rem;">Belum ada artikel edukasi.</p>';
      }
    })
    .catch(err => {
      console.warn("Failed to fetch articles:", err);
    });
}

function loadAds() {
  fetch('/api/cms.php?action=get_banners')
    .then(res => res.json())
    .then(data => {
      if (!data.banners) return;
      
      const positions = ['landing_top', 'landing_mid', 'result_top', 'result_mid', 'sidebar', 'footer', 'article'];
      
      positions.forEach(pos => {
        const adContainer = document.getElementById('ad_' + pos);
        if (adContainer && data.banners[pos] && data.banners[pos].length > 0) {
          // Select random banner from active ones in this position
          const ads = data.banners[pos];
          const ad = ads[Math.floor(Math.random() * ads.length)];
          
          let html = '';
          if (ad.type === 'image') {
             html = `<a href="${ad.link_url || '#'}" target="_blank" rel="noopener noreferrer"><img src="${ad.content}" alt="${ad.name}" style="max-width:100%;height:auto;border-radius:var(--r-md);display:block;margin:0 auto;"></a>`;
          } else {
             html = ad.content; // HTML/AdSense code
          }
          
          adContainer.innerHTML = html;
          adContainer.classList.remove('hidden');
        }
      });
    })
    .catch(err => console.warn("Failed to fetch ads:", err));
}

/* ── Modals ──────────────────────────────────────────────────── */
function openLoginModal()  { openModal('loginModal'); }
function closeLoginModal() { closeModal('loginModal'); }
function openReportModal() {
  if (!App.user) { openLoginModal(); return; }
  openModal('reportModal');
}
function closeReportModal()  { closeModal('reportModal'); }
function openCommentModal()  {
  if (!App.user) { openLoginModal(); return; }
  openModal('commentModal');
}
function closeCommentModal() { closeModal('commentModal'); }

function openModal(id)  { document.getElementById(id)?.classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); document.body.style.overflow = ''; }

// Close on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
      document.body.style.overflow = '';
    });
  }
});

/* ── Anti-Spam Content Filter ───────────────────────────────── */
/*
 * Blocks comments/reports containing:
 * - Email addresses (user@domain.com)
 * - Phone numbers (08xxx, +62xxx, or sequences of 8+ digits)
 * - Website links (http://, https://, www., or domain.tld patterns)
 *
 * Returns { blocked: bool, reason: string }
 */
const SpamFilter = {
  patterns: [
    { regex: /[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/i, label: 'alamat email' },
    { regex: /(?:\+62|62|0)\s*8\d[\d\s\-().]{5,}/i, label: 'nomor telepon' },
    { regex: /\b\d{8,15}\b/, label: 'nomor telepon' },
    { regex: /https?:\/\/[^\s]+/i, label: 'link website' },
    { regex: /www\.[^\s]+/i, label: 'link website' },
    { regex: /[a-zA-Z0-9\-]+\.(com|net|org|id|co\.id|info|biz|xyz|online|site|web|io|me|cc)\b/i, label: 'link website' },
  ],

  /** Check text for spam content. Returns { blocked, reason } */
  check(text) {
    if (!text) return { blocked: false, reason: '' };
    for (const p of this.patterns) {
      if (p.regex.test(text)) {
        return { blocked: true, reason: p.label };
      }
    }
    return { blocked: false, reason: '' };
  },

  /**
   * Sanitize text for display — replace detected patterns with [dihapus].
   * This acts as a second layer defense for existing data.
   */
  sanitize(text) {
    if (!text) return '';
    let clean = text;
    // Strip emails
    clean = clean.replace(/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/gi, '[email dihapus]');
    // Strip URLs
    clean = clean.replace(/https?:\/\/[^\s]+/gi, '[link dihapus]');
    clean = clean.replace(/www\.[^\s]+/gi, '[link dihapus]');
    // Strip phone-like patterns (08xx, +62xx)
    clean = clean.replace(/(?:\+62|62|0)\s*8\d[\d\s\-().]{5,}/gi, '[nomor dihapus]');
    return clean;
  }
};

/* ── Report / Comment Submission ────────────────────────────── */
function setReportMode(mode, btn) {
  App.reportMode = mode;
  btn.closest('.tab-toggle').querySelectorAll('button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}
function setCommentMode(mode, btn) {
  App.commentMode = mode;
  btn.closest('.tab-toggle').querySelectorAll('button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

function submitReport() {
  const cat  = document.getElementById('reportCategory')?.value;
  const desc = document.getElementById('reportDesc')?.value?.trim();
  if (!cat)  { showToast('Pilih kategori laporan', 'warn'); return; }
  if (!desc || desc.length < 10) { showToast('Deskripsi minimal 10 karakter', 'warn'); return; }

  // Anti-spam check
  const spam = SpamFilter.check(desc);
  if (spam.blocked) {
    showToast(`Laporan tidak boleh mengandung ${spam.reason}. Demi keamanan, data pribadi akan otomatis diblokir.`, 'err');
    return;
  }

  const payload = {
    type: App.currentResult?.type || 'phone',
    q: App.currentResult?.q || '',
    bank: App.currentResult?.bank || '',
    category: cat,
    content: desc,
    rating: 1, // Report defaults to 1 star logic
    isAnonymous: App.reportMode === 'anon'
  };

  fetch('/api/report.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(res => {
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) throw new Error('NO_PHP');
    return res.json();
  })
  .then(data => {
    if (data.error) throw new Error(data.error);
    closeModal('reportModal');
    showToast(data.message || 'Laporan berhasil dikirim. Terima kasih!', 'ok');
    setTimeout(() => checkQueryParams(), 1500);
  })
  .catch(err => {
    // Fallback: if no PHP backend, still close and show success (demo mode)
    closeModal('reportModal');
    showToast('Laporan berhasil dikirim. Terima kasih!', 'ok');
  });
}

function submitComment() {
  const text = document.getElementById('commentText')?.value?.trim();
  if (App.rating < 1)     { showToast('Berikan rating bintang terlebih dahulu', 'warn'); return; }
  if (!text || text.length < 3) { showToast('Komentar minimal 3 karakter', 'warn'); return; }

  // Anti-spam check
  const spam = SpamFilter.check(text);
  if (spam.blocked) {
    showToast(`Komentar tidak boleh mengandung ${spam.reason}.`, 'err');
    return;
  }

  const payload = {
    type: App.currentResult?.type || 'phone',
    q: App.currentResult?.q || '',
    bank: App.currentResult?.bank || '',
    category: 'lainnya',
    content: text,
    rating: App.rating,
    isAnonymous: App.commentMode === 'anon'
  };

  fetch('/api/report.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(res => {
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) throw new Error('NO_PHP');
    return res.json();
  })
  .then(data => {
    if (data.error) throw new Error(data.error);
    closeModal('commentModal');
    showToast(data.message || 'Komentar berhasil dipublikasikan!', 'ok');
    setTimeout(() => checkQueryParams(), 1500);
  })
  .catch(err => {
    // Fallback: demo mode
    closeModal('commentModal');
    showToast('Komentar berhasil dipublikasikan!', 'ok');
  });
}

/* ── Star Rating ─────────────────────────────────────────────── */
function setRating(val) {
  App.rating = val;
  const inputs = document.querySelectorAll('#starRating button');
  inputs.forEach((btn, i) => {
    const svgEl = btn.querySelector('svg');
    if (!svgEl) return;
    if (i < val) {
      svgEl.style.fill = '#EAB308';
      svgEl.style.stroke = 'none';
    } else {
      svgEl.style.fill = 'none';
      svgEl.style.stroke = 'var(--t-4)';
    }
  });
}

/* ── Voting ─────────────────────────────────────────────────── */
  function voteComment(btn, type, commentId) {
    // Optimistic UI update
    btn.classList.toggle(type === 'up' ? 'up' : 'down');
    
    // Determine new text label if it's an upvote (basic optimistic increment)
    if (type === 'up' && btn.classList.contains('up')) {
      const match = btn.innerText.match(/\d+/);
      if (match) {
        const newCount = parseInt(match[0]) + 1;
        btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3H14z"/><path d="M7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/></svg>
            Helpful (${newCount})`;
      }
    }

    if (!commentId) {
      showToast(type === 'up' ? 'Ditandai Helpful' : 'Ditandai Tidak Helpful', 'ok');
      return;
    }

    fetch('/api/vote.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ commentId: commentId, type: type })
    })
    .then(res => res.json())
    .then(data => {
      if (data.error) throw new Error(data.error);
      showToast(type === 'up' ? 'Ditandai Helpful' : 'Ditandai Tidak Helpful', 'ok');
    })
    .catch(err => {
      showToast(err.message, 'err');
      btn.classList.remove(type === 'up' ? 'up' : 'down'); // Revert UI
    });
  }
function flagComment() { showToast('Komentar dilaporkan ke moderator', 'warn'); }
function copyToClipboard(text) {
  navigator.clipboard?.writeText(text).then(() => showToast('Disalin ke clipboard', 'ok'));
}

/* ── Auth Simulation ─────────────────────────────────────────── */
function loginWithGoogle() {
  window.location.href = '/api/auth.php?action=login';
}

function checkAuthState() {
  fetch('/api/auth.php?action=me')
    .then(res => res.json())
    .then(user => {
      if (!user.error) {
        App.user = user;
        const btnLogin = document.getElementById('btnLogin');
        const userProfileDropdown = document.getElementById('userProfileDropdown');
        
        if (btnLogin && userProfileDropdown) {
          btnLogin.classList.add('hidden');
          userProfileDropdown.classList.remove('hidden');

          const avatarUrl = App.user.avatar_url || 'https://ui-avatars.com/api/?name='+encodeURIComponent(App.user.name)+'&background=random';
          
          document.getElementById('userAvatar').src = avatarUrl;
          document.getElementById('menuAvatar').src = avatarUrl;
          document.getElementById('menuName').textContent = App.user.name;
          document.getElementById('menuEmail').textContent = App.user.email;
          
          document.getElementById('menuBadge').textContent = App.user.badge || 'Pemula';
          document.getElementById('menuScore').textContent = App.user.trust_score || 0;
          
          // Calculate width for score bar. Assuming max score logic (e.g., 1000 for Pahlawan)
          let score = parseInt(App.user.trust_score || 0);
          let progress = (score / 1000) * 100;
          if (progress > 100) progress = 100;
          document.getElementById('menuScoreBar').style.width = progress + '%';

          document.getElementById('menuReports').textContent = App.user.total_reports || 0;
          document.getElementById('menuReviews').textContent = App.user.total_reviews || 0;
        }
      }
    })
    .catch(() => {});
    
  // Global logout function
  window.doLogout = function() {
    if(confirm('Keluar dari akun?')) {
      fetch('/api/auth.php?action=logout').then(() => window.location.reload());
    }
  };
    
  // Check URL params for login success/error
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('login') && urlParams.get('login') === 'success') {
    showToast('Login berhasil!', 'ok');
    window.history.replaceState({}, document.title, window.location.pathname);
  }
  if (urlParams.has('error')) {
    showToast('Autentikasi Google gagal atau dibatalkan.', 'err');
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

// Ensure checkAuthState is called on load
document.addEventListener('DOMContentLoaded', () => {
  checkAuthState();

  // Enforce login for search forms
  const searchForms = document.querySelectorAll('form');
  searchForms.forEach(form => {
    // Override standard onsubmit if it exists
    const originalSubmit = form.onsubmit;
    form.onsubmit = (e) => {
      if (!App.user) {
        e.preventDefault();
        showToast('Anda harus login dengan Google terlebih dahulu', 'warn');
        openLoginModal();
        return false;
      }
      
      if (originalSubmit) {
        return originalSubmit.call(form, e);
      }
    };
  });
});

/* ── Mobile Nav ─────────────────────────────────────────────── */
function toggleDrawer() {
  const drawer = document.getElementById('mobileDrawer');
  drawer?.classList.toggle('open');
  document.body.style.overflow = drawer?.classList.contains('open') ? 'hidden' : '';
}

/* ── Toast ───────────────────────────────────────────────────── */
function showToast(msg, type = 'ok') {
  const wrap = document.getElementById('toastWrap');
  if (!wrap) return;
  const icons = {
    ok:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
    warn: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
    err:  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
  };
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `${icons[type] || icons.ok}<span class="msg">${msg}</span>`;
  wrap.prepend(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(100%)'; t.style.transition = '0.25s ease'; setTimeout(() => t.remove(), 300); }, 3200);
}

/* ── Helpers ─────────────────────────────────────────────────── */
function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
