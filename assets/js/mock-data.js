/* ================================================================
   ceknomor.id — Mock Data & Helpers
   Clean data, no emojis in labels
   ================================================================ */

const MockData = {

  /* ── Banks ──────────────────────────────────────────────── */
  banks: [
    { code: 'BCA',  name: 'Bank Central Asia', len: 10 },
    { code: 'BNI',  name: 'Bank Negara Indonesia', len: 10 },
    { code: 'BRI',  name: 'Bank Rakyat Indonesia', len: 15 },
    { code: 'Mandiri', name: 'Bank Mandiri', len: 13 },
    { code: 'CIMB', name: 'CIMB Niaga', len: 13 },
    { code: 'Danamon', name: 'Bank Danamon', len: 10 },
    { code: 'Permata', name: 'Bank Permata' },
    { code: 'BTN',  name: 'Bank Tabungan Negara', len: 16 },
    { code: 'Mega', name: 'Bank Mega' },
    { code: 'BJB',  name: 'Bank Jabar Banten' },
    { code: 'BSI',  name: 'Bank Syariah Indonesia', len: 10 },
    { code: 'OCBC', name: 'OCBC NISP', len: 12 },
    { code: 'Bukopin', name: 'Bank Bukopin', len: 10 },
    { code: 'Muamalat', name: 'Bank Muamalat', len: 10 },
    { code: 'Maybank', name: 'Maybank Indonesia' },
    { code: 'BCA Syariah', name: 'BCA Syariah', len: 10 },
    { code: 'CIMB Syariah', name: 'CIMB Niaga Syariah', len: 13 },
    { code: 'BTN Syariah', name: 'BTN Syariah', len: 10 },
    { code: 'Sinarmas Syariah', name: 'Sinarmas Syariah', len: 10 },
    { code: 'Sinarmas', name: 'Bank Sinarmas' },
    { code: 'BTPN', name: 'Bank BTPN' },
    { code: 'Citibank', name: 'Citibank' },
    { code: 'Panin', name: 'Bank Panin' },
    { code: 'Standard Chartered', name: 'Standard Chartered' },
    { code: 'Mayapada', name: 'Bank Mayapada' },
    { code: 'DBS', name: 'Bank DBS Indonesia' },
    { code: 'Artha Graha', name: 'Bank Artha Graha' },
    { code: 'GoPay', name: 'GoPay', len: 10, maxLen: 15 },
    { code: 'OVO',  name: 'OVO', len: 10, maxLen: 15 },
    { code: 'Dana', name: 'Dana', len: 10, maxLen: 15 },
    { code: 'ShopeePay', name: 'ShopeePay', len: 10, maxLen: 15 },
    { code: 'Jago', name: 'Bank Jago' },
    { code: 'Jenius', name: 'Jenius (BTPN)' },
    { code: 'SeaBank', name: 'SeaBank' },
    { code: 'Allo', name: 'Allo Bank' },
    { code: 'LinkAja', name: 'LinkAja', len: 10, maxLen: 15 }
  ],

  /* ── Categories ─────────────────────────────────────────── */
  categories: [
    { id: 'penipuan',    label: 'Penipuan' },
    { id: 'spam',        label: 'Spam' },
    { id: 'debt',        label: 'Debt Collector' },
    { id: 'judi',        label: 'Judi Online' },
    { id: 'pinjol',      label: 'Pinjaman Online' },
    { id: 'trading',     label: 'Robot Trading' },
    { id: 'marketplace', label: 'Scam Marketplace' },
    { id: 'fiktif',      label: 'Penjual Fiktif' },
    { id: 'lainnya',     label: 'Lainnya' },
  ],

  /* ── Phone Numbers ──────────────────────────────────────── */
  phones: [
    {
      number: '08123456789', normalized: '628123456789',
      status: 'bahaya', securityScore: 12,
      reportCount: 47, commentCount: 128, searchCount: 15832, helpfulCount: 201,
      safePercent: 8, dangerPercent: 92,
      categories: ['Penipuan', 'Pinjaman Online'],
      summary: 'Nomor ini sangat sering dilaporkan oleh pengguna. Mayoritas laporan menyebutkan modus penipuan mengaku dari bank dan meminta kode OTP, serta tawaran pinjaman ilegal.',
      comments: [
        { user: 'Anonim', rating: 1, time: '2 hari lalu', category: 'Penipuan', content: 'Nomor ini menghubungi saya mengaku dari BCA dan meminta kode OTP. Jangan pernah berikan OTP!', helpful: 89 },
        { user: 'Ahmad Fauzi', rating: 1, time: '5 hari lalu', category: 'Pinjaman Online', content: 'Tawaran pinjaman ilegal. Bunga sangat tinggi dan mengancam jika tidak bayar.', helpful: 67 },
        { user: 'Anonim', rating: 2, time: '1 minggu lalu', category: 'Penipuan', content: 'Sudah 3x ditelpon, selalu mengaku dari instansi berbeda. Waspada.', helpful: 45 },
      ]
    },
    {
      number: '081234567890', normalized: '6281234567890',
      status: 'waspada', securityScore: 62,
      reportCount: 2, commentCount: 5, searchCount: 3241, helpfulCount: 12,
      safePercent: 75, dangerPercent: 25,
      categories: ['Spam'],
      summary: 'Beberapa pengguna melaporkan nomor ini sebagai spam telemarketing. Belum ada laporan penipuan serius.',
      comments: [
        { user: 'Siti Nurbaya', rating: 3, time: '3 hari lalu', category: 'Spam', content: 'Telepon terus menerus menawarkan produk asuransi. Mengganggu tapi bukan scam.', helpful: 10 },
      ]
    },
    {
      number: '082111222333', normalized: '6282111222333',
      status: 'aman', securityScore: 98,
      reportCount: 0, commentCount: 1, searchCount: 892, helpfulCount: 3,
      safePercent: 100, dangerPercent: 0,
      categories: [],
      summary: 'Tidak ada laporan atau aktivitas mencurigakan untuk nomor ini. Aman untuk dihubungi.',
      comments: []
    },
  ],

  /* ── Rekening ────────────────────────────────────────────── */
  rekening: [
    {
      bank: 'BCA', number: '1234567890',
      status: 'bahaya', securityScore: 8,
      reportCount: 23, commentCount: 41, searchCount: 8923, helpfulCount: 87,
      safePercent: 5, dangerPercent: 95,
      categories: ['Penipuan', 'Scam Marketplace'],
      summary: 'Rekening ini dilaporkan banyak pengguna sebagai rekening penipu di marketplace. Modus: kirim barang palsu atau tidak kirim sama sekali setelah transfer.',
      comments: [
        { user: 'Budi Santoso', rating: 1, time: '1 hari lalu', category: 'Scam Marketplace', content: 'Transfer Rp 2.5 juta untuk beli laptop. Barang tidak pernah dikirim. Penipu!', helpful: 56 },
        { user: 'Anonim', rating: 1, time: '4 hari lalu', category: 'Penipuan', content: 'Sudah lapor ke polisi dan OJK. Rekening seharusnya sudah diblokir.', helpful: 34 },
      ]
    },
  ],

  /* ── Trending Phones ─────────────────────────────────────── */
  trendingPhones: [
    { rank: 1,  number: '0812-3456-789',  searches: 15832, status: 'bahaya',    category: 'Penipuan' },
    { rank: 2,  number: '0878-9999-1234', searches: 12441, status: 'bahaya',    category: 'Pinjol' },
    { rank: 3,  number: '0821-5555-6666', searches: 10982, status: 'waspada',   category: 'Spam' },
    { rank: 4,  number: '0857-1111-8888', searches: 9234,  status: 'bahaya',    category: 'Judi Online' },
    { rank: 5,  number: '0819-3333-5555', searches: 8771,  status: 'hatihati',  category: 'Pinjol' },
    { rank: 6,  number: '0896-7777-1111', searches: 7349,  status: 'bahaya',    category: 'Penipuan' },
    { rank: 7,  number: '0815-2222-9999', searches: 6921,  status: 'waspada',   category: 'Telemarketing' },
    { rank: 8,  number: '0831-4444-2222', searches: 5832,  status: 'bahaya',    category: 'Penipuan' },
    { rank: 9,  number: '0852-6666-3333', searches: 4723,  status: 'hatihati',  category: 'Debt Collector' },
    { rank: 10, number: '0877-8888-0001', searches: 4521,  status: 'bahaya',    category: 'Penipuan' },
  ],

  /* ── Trending Rekening ───────────────────────────────────── */
  trendingRekening: [
    { rank: 1, number: 'BCA - 1234567890', searches: 8923, status: 'bahaya',   category: 'Scam Marketplace' },
    { rank: 2, number: 'BRI - 009812345678', searches: 6721, status: 'bahaya', category: 'Penipuan' },
    { rank: 3, number: 'Mandiri - 1100012345', searches: 5412, status: 'waspada', category: 'Pinjol' },
    { rank: 4, number: 'BNI - 0123456789', searches: 4231, status: 'bahaya',   category: 'Penipuan' },
    { rank: 5, number: 'GoPay - 08111111111', searches: 3891, status: 'waspada', category: 'Marketplace' },
    { rank: 6, number: 'BCA - 9876543210', searches: 3234, status: 'bahaya',   category: 'Penipuan' },
    { rank: 7, number: 'Dana - 08222222222', searches: 2876, status: 'hatihati', category: 'Pinjol' },
  ],

  /* ── Contributors ────────────────────────────────────────── */
  contributors: [
    { rank: 1,  name: 'Andi Pratama',   avatar: 'AP', badge: 'Legend',    trustScore: 98, contributions: 2841, helpfulVotes: 8921, joinedSince: 'Jan 2024' },
    { rank: 2,  name: 'Sari Dewi',      avatar: 'SD', badge: 'Top Contributor', trustScore: 95, contributions: 2341, helpfulVotes: 7234, joinedSince: 'Mar 2024' },
    { rank: 3,  name: 'Rudi Hartono',   avatar: 'RH', badge: 'Senior',    trustScore: 91, contributions: 1923, helpfulVotes: 5671, joinedSince: 'Feb 2024' },
    { rank: 4,  name: 'Maya Sari',      avatar: 'MS', badge: 'Trusted',   trustScore: 87, contributions: 1562, helpfulVotes: 4123, joinedSince: 'Apr 2024' },
    { rank: 5,  name: 'Fajar Nugroho',  avatar: 'FN', badge: 'Trusted',   trustScore: 84, contributions: 1234, helpfulVotes: 3892, joinedSince: 'May 2024' },
    { rank: 6,  name: 'Rina Susanti',   avatar: 'RS', badge: 'Member',    trustScore: 79, contributions: 987,  helpfulVotes: 3021, joinedSince: 'Jun 2024' },
    { rank: 7,  name: 'Budi Darmawan',  avatar: 'BD', badge: 'Member',    trustScore: 75, contributions: 812,  helpfulVotes: 2341, joinedSince: 'Jul 2024' },
    { rank: 8,  name: 'Ayu Rahayu',     avatar: 'AR', badge: 'Member',    trustScore: 71, contributions: 634,  helpfulVotes: 1892, joinedSince: 'Aug 2024' },
    { rank: 9,  name: 'Deni Setiawan',  avatar: 'DS', badge: 'Member',    trustScore: 68, contributions: 521,  helpfulVotes: 1543, joinedSince: 'Sep 2024' },
    { rank: 10, name: 'Putri Amalia',   avatar: 'PA', badge: 'New Member', trustScore: 62, contributions: 423, helpfulVotes: 1201, joinedSince: 'Oct 2024' },
  ],

  /* ── Admin Stats ─────────────────────────────────────────── */
  adminStats: {
    today: {
      visitors: 48391, loggedIn: 12847, newUsers: 1294,
      searches: 93847, phoneChecks: 71234, rekeningChecks: 22613,
      newReports: 312, newReviews: 891, newComments: 1234,
      newNumbers: 87, newRekening: 23,
    },
    overall: {
      users: 524817, phones: 2847391, rekening: 891204,
      reports: 312847, reviews: 1893021, comments: 4812903,
      searches: 18493021, seoPages: 3842001, banks: 21,
    }
  },

  /* ── Chart Data ──────────────────────────────────────────── */
  chartData: {
    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
    searches: [72340, 81230, 79450, 93847, 88120, 102340, 95671],
    revenue:  [128, 145, 132, 167, 155, 189, 174],
    reports:  [245, 312, 289, 401, 367, 498, 423],
  },

  /* ── Live Activities ─────────────────────────────────────── */
  liveActivities: [
    { icon: 'search', user: 'Anonim',        content: 'memeriksa 0857-1111-8888',         time: '3 detik lalu' },
    { icon: 'flag',   user: 'Ahmad F.',       content: 'melaporkan 0812-3456-789',         time: '12 detik lalu' },
    { icon: 'user',   user: 'Pengguna Baru',  content: 'bergabung ke komunitas',           time: '28 detik lalu' },
    { icon: 'search', user: 'Anonim',         content: 'memeriksa BCA - 1234567890',       time: '45 detik lalu' },
    { icon: 'thumbUp',user: 'Siti N.',        content: 'menandai komentar sebagai helpful', time: '1 menit lalu' },
    { icon: 'message',user: 'Budi D.',        content: 'menambahkan komentar baru',        time: '2 menit lalu' },
    { icon: 'flag',   user: 'Anonim',         content: 'melaporkan 0878-9999-1234',        time: '3 menit lalu' },
    { icon: 'search', user: 'Anonim',         content: 'memeriksa 0821-5555-6666',         time: '5 menit lalu' },
  ],

  /* ── Articles ────────────────────────────────────────────── */
  articles: [
    { tag: 'Tips Keamanan', title: '7 Cara Mengenali Nomor Penipu Sebelum Mengangkat Telepon', excerpt: 'Penipu semakin canggih. Pelajari pola nomor yang sering digunakan untuk penipuan di Indonesia.', date: '5 Jan 2026', views: '12.4 rb' },
    { tag: 'Edukasi',       title: 'Waspada Modus Pinjaman Online Ilegal yang Semakin Marak', excerpt: 'OJK mencatat ribuan pinjol ilegal beroperasi. Kenali tanda-tandanya dan lindungi diri Anda.', date: '3 Jan 2026', views: '9.8 rb' },
    { tag: 'Hukum',         title: 'Cara Melaporkan Penipuan Online ke Polisi dan OJK secara Efektif', excerpt: 'Panduan lengkap langkah-langkah hukum yang bisa Anda ambil jika menjadi korban penipuan.', date: '1 Jan 2026', views: '8.2 rb' },
    { tag: 'Teknologi',     title: 'Bagaimana ceknomor.id Menghitung Skor Keamanan Sebuah Nomor', excerpt: 'Transparansi algoritma: pelajari bagaimana kami menentukan apakah suatu nomor berbahaya.', date: '28 Des 2025', views: '7.1 rb' },
  ],
};

/* ── Data Helpers ─────────────────────────────────────────── */
const DataHelper = {

  getStatusConfig(status) {
    const configs = {
      aman: {
        label: 'Aman', color: '#22C55E',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
      },
      waspada: {
        label: 'Waspada', color: '#EAB308',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
      },
      hatihati: {
        label: 'Hati-hati', color: '#F97316',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
      },
      bahaya: {
        label: 'Berbahaya', color: '#EF4444',
        svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
      },
    };
    return configs[status] || configs.aman;
  },

  findPhone(q) {
    const found = MockData.phones.find(p => p.normalized === '62' + q.replace(/^0/,'') || p.number.replace(/\D/g,'') === q);
    if (found) return found;
    return {
      number: q, normalized: '62' + q.replace(/^0/,''),
      status: 'aman', securityScore: 95,
      reportCount: 0, commentCount: 0, searchCount: 1, helpfulCount: 0,
      safePercent: 100, dangerPercent: 0, categories: [],
      summary: 'Nomor ini belum pernah dilaporkan. Tetap waspada dalam bertransaksi.',
      comments: []
    };
  },

  findRekening(bank, q) {
    const found = MockData.rekening.find(r => r.bank === bank && r.number === q);
    if (found) return found;
    return {
      bank, number: q,
      status: 'aman', securityScore: 95,
      reportCount: 0, commentCount: 0, searchCount: 1, helpfulCount: 0,
      safePercent: 100, dangerPercent: 0, categories: [],
      summary: 'Rekening ini belum pernah dilaporkan. Tetap waspada dalam bertransaksi.',
      comments: []
    };
  },

  formatNumber(n) {
    if (n >= 1000000) return (n/1000000).toFixed(1) + ' jt';
    if (n >= 1000)    return (n/1000).toFixed(1) + ' rb';
    return String(n);
  },
};
