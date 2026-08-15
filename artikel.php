<?php
require_once 'config/database.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header("Location: /");
    exit;
}

$dbClass = new Database();
$pdo = $dbClass->getConnection();

// Fetch article
$stmt = $pdo->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header("HTTP/1.0 404 Not Found");
    $title = "Artikel Tidak Ditemukan";
    $content = "<p style='text-align:center; padding: 5rem 0;'>Maaf, artikel yang Anda cari tidak ditemukan atau belum dipublikasikan.</p>";
    $metaDesc = "Artikel tidak ditemukan di ceknomor.id";
    $coverImage = '';
} else {
    // Increment view count
    $pdo->prepare("UPDATE articles SET view_count = view_count + 1 WHERE id = ?")->execute([$article['id']]);
    $article['view_count']++; // Update for display
    
    $title = htmlspecialchars($article['title']);
    $content = $article['content']; // HTML content from CMS
    $metaDesc = htmlspecialchars($article['excerpt']);
    $date = date('d F Y', strtotime($article['published_at'] ?? $article['created_at']));
    $views = number_format($article['view_count'], 0, ',', '.');
    
    // Extract first image for cover and SEO
    $coverImage = '';
    if (preg_match('/<img[^>]+src="([^">]+)"/i', $content, $matches)) {
        $coverImage = $matches[1];
    }
}

// Current URL for sharing
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$encodedUrl = urlencode($currentUrl);
$encodedTitle = urlencode($title);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?> — ceknomor.id</title>
  <meta name="description" content="<?= $metaDesc ?>">
  
  <!-- Open Graph / Facebook / WhatsApp -->
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>">
  <meta property="og:title" content="<?= $title ?>">
  <meta property="og:description" content="<?= $metaDesc ?>">
  <?php if($coverImage): ?><meta property="og:image" content="<?= htmlspecialchars($coverImage) ?>"><?php endif; ?>
  
  <!-- Twitter / X -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= htmlspecialchars($currentUrl) ?>">
  <meta name="twitter:title" content="<?= $title ?>">
  <meta name="twitter:description" content="<?= $metaDesc ?>">
  <?php if($coverImage): ?><meta name="twitter:image" content="<?= htmlspecialchars($coverImage) ?>"><?php endif; ?>

  <link rel="canonical" href="<?= htmlspecialchars($currentUrl) ?>">
  <link rel="stylesheet" href="/assets/css/main.css">
  <style>
    .article-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 6rem 1.5rem 4rem;
        min-height: 70vh;
    }
    .article-header {
        margin-bottom: 2rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 1.5rem;
    }
    .article-header h1 {
        font-size: 2.25rem;
        color: var(--t-1);
        line-height: 1.3;
        margin-bottom: 1rem;
    }
    .article-meta-info {
        display: flex;
        gap: 1.5rem;
        color: var(--t-3);
        font-size: 0.9rem;
        flex-wrap: wrap;
    }
    .article-meta-info span {
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }
    .article-meta-info svg { width: 16px; height: 16px; stroke: currentColor; fill: none; }
    
    .article-cover {
        width: 100%;
        max-height: 400px;
        object-fit: cover;
        border-radius: var(--r-lg);
        margin-bottom: 2rem;
    }
    
    .article-body {
        font-size: 1.05rem;
        color: var(--t-2);
        line-height: 1.8;
    }
    .article-body h1, .article-body h2, .article-body h3, .article-body h4 { color: var(--t-1); margin: 1.5em 0 0.5em; line-height: 1.3; }
    .article-body p { margin-bottom: 1.25em; }
    .article-body ul, .article-body ol { margin-bottom: 1.25em; padding-left: 1.5rem; }
    .article-body li { margin-bottom: 0.5em; }
    .article-body a { color: var(--primary); text-decoration: none; }
    .article-body a:hover { text-decoration: underline; }
    .article-body img { max-width: 100%; border-radius: var(--r-md); margin: 1.5rem 0; height: auto; }
    .article-body blockquote { border-left: 4px solid var(--primary); padding-left: 1rem; margin-left: 0; color: var(--t-3); font-style: italic; }

    /* Share Bar */
    .share-bar {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }
    .share-title { font-weight: 600; color: var(--t-1); }
    .share-buttons {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        justify-content: center;
    }
    .share-btn {
        display: flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; border-radius: 50%;
        color: white; text-decoration: none; transition: transform 0.2s;
        border: none; cursor: pointer;
    }
    .share-btn:hover { transform: scale(1.1); }
    .share-btn svg { width: 20px; height: 20px; fill: currentColor; }
    
    .bg-wa { background: #25D366; }
    .bg-fb { background: #1877F2; }
    .bg-x { background: #000000; }
    .bg-threads { background: #000000; }
    .bg-ig { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
    .bg-link { background: var(--t-3); }

    /* Toast */
    .toast-wrap { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
    .toast { background: var(--bg-1); color: var(--t-1); padding: 12px 24px; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid var(--border); display: flex; align-items: center; gap: 8px; font-size: 0.875rem; font-weight: 500; animation: slideUp 0.3s ease forwards; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>

<!-- NAV -->
<nav class="navbar" id="navbar">
  <div class="container">
    <div class="navbar-inner">
      <a href="/" class="logo" aria-label="ceknomor.id">
        <div class="logo-mark">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          </svg>
        </div>
        <span class="logo-name">cek<span>nomor</span>.id</span>
      </a>
      <ul class="nav-links">
        <li><a href="/">Beranda</a></li>
        <li><a href="/trending/">Trending</a></li>
        <li><a href="/leaderboard/">Leaderboard</a></li>
      </ul>
      <div class="nav-actions">
        <a href="/" class="btn btn-primary btn-sm">Cek Nomor</a>
      </div>
    </div>
  </div>
</nav>

<main class="article-container">
    <?php if ($article): ?>
        <header class="article-header">
            <h1><?= $title ?></h1>
            <div class="article-meta-info">
                <span>
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?= $date ?>
                </span>
                <span>
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <?= $views ?> kali dibaca
                </span>
                <span style="color:var(--primary); font-weight:500;">Edukasi</span>
            </div>
        </header>
        
        <?php if($coverImage): ?>
            <img src="<?= htmlspecialchars($coverImage) ?>" alt="<?= $title ?>" class="article-cover">
        <?php endif; ?>
        
        <div class="article-body">
            <?= $content ?>
        </div>
        
        <div class="share-bar">
            <div class="share-title">Bagikan Artikel Ini</div>
            <div class="share-buttons">
                <a href="https://api.whatsapp.com/send?text=<?= $encodedTitle ?>%0A<?= $encodedUrl ?>" target="_blank" class="share-btn bg-wa" title="WhatsApp">
                    <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $encodedUrl ?>" target="_blank" class="share-btn bg-fb" title="Facebook">
                    <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?= $encodedUrl ?>&text=<?= $encodedTitle ?>" target="_blank" class="share-btn bg-x" title="X (Twitter)">
                    <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="https://www.threads.net/intent/post?text=<?= $encodedTitle ?>%20<?= $encodedUrl ?>" target="_blank" class="share-btn bg-threads" title="Threads">
                    <svg viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2zm0 3.5c-3.584 0-6.5 2.916-6.5 6.5s2.916 6.5 6.5 6.5c1.472 0 2.827-.492 3.905-1.319l-1.147-1.396c-.767.585-1.724.935-2.758.935-2.527 0-4.577-2.05-4.577-4.577h9.096v-.143C16.519 8.416 14.47 5.5 12 5.5zm-.105 1.78c1.334 0 2.457 1.05 2.666 2.392h-5.33c.21-1.34 1.331-2.392 2.664-2.392z"/></svg>
                </a>
                <button onclick="shareNative()" class="share-btn bg-ig" title="Share via Mobile / IG Story">
                    <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                </button>
                <button onclick="copyArticleLink()" class="share-btn bg-link" title="Salin Tautan">
                    <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="article-body">
            <?= $content ?>
            <div style="text-align:center;">
                <a href="/" class="btn btn-primary">Kembali ke Beranda</a>
            </div>
        </div>
    <?php endif; ?>
</main>

<div class="toast-wrap" id="toastWrap"></div>

<footer class="footer">
  <div class="container">
    <div class="footer-bottom" style="border-top:none; padding-top:2rem;">
      <p>&copy; 2026 ceknomor.id — Hak Cipta Dilindungi</p>
      <p>Membantu masyarakat Indonesia bebas dari penipuan</p>
    </div>
  </div>
</footer>

<script>
function showToast(msg) {
    const wrap = document.getElementById('toastWrap');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> ${msg}`;
    wrap.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function copyArticleLink() {
    navigator.clipboard.writeText("<?= $currentUrl ?>").then(() => {
        showToast("Tautan artikel berhasil disalin!");
    });
}

function shareNative() {
    if (navigator.share) {
        navigator.share({
            title: "<?= $title ?>",
            text: "<?= $metaDesc ?>",
            url: "<?= $currentUrl ?>"
        }).catch((error) => console.log('Error sharing:', error));
    } else {
        showToast("Fitur share native tidak didukung di browser ini. Silakan gunakan tombol lain.");
    }
}
</script>

</body>
</html>
