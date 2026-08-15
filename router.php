<?php
// router.php - Untuk keperluan server lokal (php -S localhost:8000 router.php)

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Jika request root, arahkan ke index.html
if ($path === '/') {
    require __DIR__ . '/index.html';
    return;
}

// Hapus trailing slash untuk menyamakan dengan file
$pathWithoutSlash = rtrim($path, '/');

// Cek apakah file .html ada
if (file_exists(__DIR__ . $pathWithoutSlash . '.html')) {
    require __DIR__ . $pathWithoutSlash . '.html';
    return;
}

// Jika request adalah API atau file fisik lainnya (CSS/JS/Gambar)
if (file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
    return false; // Biarkan server bawaan PHP yang menangani (serve file)
}

// SEO Route untuk Artikel (/artikel/slug)
if (preg_match('#^/artikel/([^/]+)/?$#', $path, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/artikel.php';
    return;
}

// Tangani kasus direktori admin
if (strpos($path, '/admin') === 0) {
    if (file_exists(__DIR__ . '/admin/index.php')) {
        require __DIR__ . '/admin/index.php';
        return;
    }
}

// Fallback 404
http_response_code(404);
echo "404 Not Found";
