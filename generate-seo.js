const fs = require('fs');
const path = require('path');

const DIR_SEO = path.join(__dirname, 'seo');
if (!fs.existsSync(DIR_SEO)) {
  fs.mkdirSync(DIR_SEO);
}

const banks = [
  { slug: "kode-bank-ocbc-028", name: "Bank OCBC", code: "028" },
  { slug: "kode-bank-bca-014", name: "Bank BCA", code: "014" },
  { slug: "kode-bank-bca-syariah-536", name: "Bank BCA Syariah", code: "536" },
  { slug: "kode-bank-bri-002", name: "Bank BRI", code: "002" },
  { slug: "kode-bank-bni-009", name: "Bank BNI", code: "009" },
  { slug: "kode-bank-mandiri-008", name: "Bank Mandiri", code: "008" },
  { slug: "kode-bank-btn-200", name: "Bank BTN", code: "200" },
  { slug: "kode-bsi-451", name: "BSI", code: "451" },
  { slug: "kode-bank-permata-013", name: "Bank Permata", code: "013" },
  { slug: "kode-bank-cimb-niaga-022", name: "Bank CIMB Niaga", code: "022" },
  { slug: "kode-bank-cimb-niaga-syariah-022", name: "Bank CIMB Niaga Syariah", code: "022" },
  { slug: "kode-bank-muamalat-147", name: "Bank Muamalat", code: "147" },
  { slug: "kode-bank-danamon-011", name: "Bank Danamon", code: "011" },
  { slug: "kode-bank-mega-426", name: "Bank Mega", code: "426" },
  { slug: "kode-bank-bukopin-441", name: "Bank Bukopin", code: "441" },
  { slug: "kode-bank-bii-maybank-016", name: "Bank BII Maybank", code: "016" },
  { slug: "kode-bank-btpn-213", name: "Bank BTPN", code: "213" },
  { slug: "kode-citibank-031", name: "Citibank", code: "031" },
  { slug: "kode-bank-panin-019", name: "Bank Panin", code: "019" },
  { slug: "kode-bank-standard-chartered-050", name: "Bank Standard Chartered", code: "050" },
  { slug: "kode-bank-mayapada-097", name: "Bank Mayapada", code: "097" },
  { slug: "kode-bank-sinarmas-153", name: "Bank Sinarmas", code: "153" },
  { slug: "kode-bank-jenius-btpn-213", name: "Bank Jenius BTPN", code: "213" },
  { slug: "kode-bank-dbs-indonesia-046", name: "Bank DBS Indonesia", code: "046" },
  { slug: "kode-bank-artha-graha-037", name: "Bank Artha Graha", code: "037" }
];

const providers = [
  { slug: "nomor-0852-kartu-apa", prefix: "0852", name: "Kartu AS" },
  { slug: "nomor-0853-kartu-apa", prefix: "0853", name: "Kartu AS" },
  { slug: "nomor-0811-kartu-apa", prefix: "0811", name: "Kartu Halo" },
  { slug: "nomor-0812-kartu-apa", prefix: "0812", name: "Kartu Simpati atau Halo" },
  { slug: "nomor-0813-kartu-apa", prefix: "0813", name: "Kartu Simpati" },
  { slug: "nomor-0821-kartu-apa", prefix: "0821", name: "Kartu Simpati" },
  { slug: "nomor-0822-kartu-apa", prefix: "0822", name: "Kartu Loop" },
  { slug: "nomor-0851-kartu-apa", prefix: "0851", name: "Kartu AS atau By.u" },
  { slug: "nomor-0857-kartu-apa", prefix: "0857", name: "Indosat" },
  { slug: "nomor-0856-kartu-apa", prefix: "0856", name: "Indosat (limited edition)" },
  { slug: "nomor-0896-kartu-apa", prefix: "0896", name: "Tri" },
  { slug: "nomor-0895-kartu-apa", prefix: "0895", name: "Tri" },
  { slug: "nomor-0897-kartu-apa", prefix: "0897", name: "Tri" },
  { slug: "nomor-0898-kartu-apa", prefix: "0898", name: "Tri" },
  { slug: "nomor-0899-kartu-apa", prefix: "0899", name: "Tri" },
  { slug: "nomor-0817-kartu-apa", prefix: "0817", name: "XL" },
  { slug: "nomor-0818-kartu-apa", prefix: "0818", name: "XL" },
  { slug: "nomor-0819-kartu-apa", prefix: "0819", name: "XL" },
  { slug: "nomor-0859-kartu-apa", prefix: "0859", name: "XL" },
  { slug: "nomor-0877-kartu-apa", prefix: "0877", name: "XL" },
  { slug: "nomor-0878-kartu-apa", prefix: "0878", name: "XL" },
  { slug: "nomor-0831-kartu-apa", prefix: "0831", name: "Axis" }, 
  { slug: "nomor-0832-kartu-apa", prefix: "0832", name: "Axis" },
  { slug: "nomor-0833-kartu-apa", prefix: "0833", name: "Axis" },
  { slug: "nomor-0838-kartu-apa", prefix: "0838", name: "Axis" },
  { slug: "nomor-0881-kartu-apa", prefix: "0881", name: "Smartfren" },
  { slug: "nomor-0882-kartu-apa", prefix: "0882", name: "Smartfren" },
  { slug: "nomor-0883-kartu-apa", prefix: "0883", name: "Smartfren" },
  { slug: "nomor-0884-kartu-apa", prefix: "0884", name: "Smartfren" },
  { slug: "nomor-0885-kartu-apa", prefix: "0885", name: "Smartfren" },
  { slug: "nomor-0886-kartu-apa", prefix: "0886", name: "Smartfren" },
  { slug: "nomor-0887-kartu-apa", prefix: "0887", name: "Smartfren" },
  { slug: "nomor-0888-kartu-apa", prefix: "0888", name: "Smartfren" },
  { slug: "nomor-0889-kartu-apa", prefix: "0889", name: "Smartfren" }
];

const templateHtml = `
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{TITLE}}</title>
  <meta name="description" content="{{DESC}}">
  <link rel="stylesheet" href="/assets/css/main.css">
  <script src="/assets/js/icons.js"></script>
  <script src="/assets/js/mock-data.js"></script>
  <script src="/assets/js/app.js"></script>
  <style>
    .seo-header { padding: 3rem 1rem; text-align: center; background: var(--bg-1); border-bottom: 1px solid var(--border); }
    .seo-title { font-size: 2rem; font-weight: 800; color: var(--t-1); margin-bottom: 1rem; }
    .seo-desc { font-size: 1.125rem; color: var(--t-3); max-width: 600px; margin: 0 auto; line-height: 1.6; }
    .seo-content { padding: 4rem 1rem; }
    .seo-card { background: var(--bg-2); border: 1px solid var(--border); border-radius: var(--r-xl); padding: 2rem; max-width: 800px; margin: 0 auto; box-shadow: var(--shadow-sm); }
    .seo-card p { font-size: 1rem; color: var(--t-2); line-height: 1.7; margin-bottom: 1.25rem; }
    .seo-card h2 { font-size: 1.375rem; color: var(--t-1); margin: 2rem 0 1rem; }
    .seo-cta { margin-top: 2.5rem; text-align: center; }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="container">
    <div class="navbar-inner">
      <a href="/" class="logo">
        <div class="logo-mark"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <span class="logo-name">cek<span>nomor</span>.id</span>
      </a>
      <ul class="nav-links">
        <li><a href="/">Beranda</a></li>
        <li><a href="/trending/">Trending</a></li>
        <li><a href="/leaderboard/">Leaderboard</a></li>
      </ul>
    </div>
  </div>
</nav>

<header class="seo-header">
  <div class="container">
    <h1 class="seo-title">{{H1}}</h1>
    <p class="seo-desc">{{SUB}}</p>
  </div>
</header>

<main class="seo-content container">
  <div class="seo-card">
    {{BODY}}
    <div class="seo-cta">
      <a href="/" class="btn btn-primary" style="display:inline-flex;padding:0.75rem 2rem;font-size:1rem;">Cek Nomor / Rekening Sekarang</a>
    </div>
  </div>
</main>

<footer class="footer" style="border-top:1px solid var(--border);margin-top:4rem;padding:2rem 0;text-align:center;color:var(--t-4);font-size:0.875rem;">
  <p>&copy; 2026 ceknomor.id - Platform Komunitas Keamanan</p>
</footer>

</body>
</html>
`;

banks.forEach(b => {
  const title = `Kode Bank ${b.name} (${b.code}) - Cek Rekening Penipu | ceknomor.id`;
  const desc = `Informasi lengkap kode transfer antar bank untuk ${b.name} adalah ${b.code}. Cek juga apakah nomor rekening ${b.name} tersebut pernah dilaporkan sebagai penipu.`;
  const h1 = `Kode Bank ${b.name} adalah ${b.code}`;
  const sub = `Panduan lengkap kode transfer ${b.name} dan tips aman bertransaksi.`;
  
  const body = `
    <p>Jika Anda ingin melakukan transfer antar bank ke rekening <strong>${b.name}</strong> melalui mesin ATM, *mobile banking*, atau *internet banking*, Anda harus memasukkan kode bank <strong>${b.code}</strong> sebelum nomor rekening tujuan.</p>
    
    <h2>Cara Transfer ke ${b.name}</h2>
    <p>Sebagai contoh, jika nomor rekening tujuan Anda adalah 1234567890, maka pada mesin ATM bank lain Anda harus memasukkan format: <strong>${b.code}1234567890</strong>.</p>
    
    <h2>Cek Keamanan Rekening</h2>
    <p>Sebelum melakukan transfer dana yang besar, sangat disarankan untuk memeriksa apakah nomor rekening tujuan tersebut pernah dilaporkan oleh orang lain terkait penipuan, penipuan online shop, atau tindakan mencurigakan lainnya.</p>
    <p>Anda bisa menggunakan layanan pencarian gratis dari <strong>ceknomor.id</strong> untuk menelusuri riwayat pelaporan nomor rekening ${b.name} tersebut dari seluruh komunitas di Indonesia.</p>
  `;

  const html = templateHtml
    .replace('{{TITLE}}', title)
    .replace('{{DESC}}', desc)
    .replace('{{H1}}', h1)
    .replace('{{SUB}}', sub)
    .replace('{{BODY}}', body);

  fs.writeFileSync(path.join(DIR_SEO, `${b.slug}.html`), html);
});

providers.forEach(p => {
  const title = `Nomor ${p.prefix} Kartu Apa? Ini Jawabannya | ceknomor.id`;
  const desc = `Awalan nomor telepon ${p.prefix} adalah nomor dari provider ${p.name}. Cek apakah nomor ${p.prefix} tersebut pernah dilaporkan spam atau penipuan.`;
  const h1 = `Nomor ${p.prefix} Adalah Kartu ${p.name}`;
  const sub = `Informasi detail mengenai awalan kode telepon ${p.prefix} di Indonesia.`;
  
  const body = `
    <p>Banyak pengguna yang bertanya-tanya, awalan nomor <strong>${p.prefix}</strong> itu kartu apa dan dari operator seluler mana? Jawabannya adalah nomor tersebut merupakan bagian dari layanan provider <strong>${p.name}</strong>.</p>
    
    <h2>Kenapa Penting Mengetahui Provider ${p.prefix}?</h2>
    <p>Mengetahui asal provider dari sebuah nomor telepon sangat berguna untuk berbagai hal, mulai dari memperkirakan biaya panggilan telepon beda operator, memaketkan SMS, hingga mengidentifikasi asal-usul nomor tak dikenal yang menghubungi Anda.</p>
    
    <h2>Waspada Penipuan & Spam</h2>
    <p>Sering mendapat panggilan atau pesan WhatsApp dari nomor ${p.prefix} yang tidak dikenal? Jangan langsung percaya jika mereka mengatasnamakan instansi resmi atau menawarkan hadiah.</p>
    <p>Gunakan fitur pencarian di <strong>ceknomor.id</strong> untuk mengecek apakah nomor ${p.prefix} spesifik yang menghubungi Anda memiliki riwayat dilaporkan sebagai spammer, scammer, atau penipuan oleh pengguna lainnya.</p>
  `;

  const html = templateHtml
    .replace('{{TITLE}}', title)
    .replace('{{DESC}}', desc)
    .replace('{{H1}}', h1)
    .replace('{{SUB}}', sub)
    .replace('{{BODY}}', body);

  fs.writeFileSync(path.join(DIR_SEO, `${p.slug}.html`), html);
});

console.log('Successfully generated ' + banks.length + ' bank pages and ' + providers.length + ' provider pages.');
