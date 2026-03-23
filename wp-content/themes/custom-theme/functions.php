<?php
defined('ABSPATH') || exit;

define('THEME_DIR', get_template_directory());
define('THEME_URI', get_template_directory_uri());
define('NO_IMAGE', THEME_URI . '/images/no-image.png');

// ─── Theme Setup ─────────────────────────────────────────────────────────────
add_action('after_setup_theme', function () {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_image_size('img_600_650', 600, 650, true);

    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
});

// ─── Headless: disable frontend asset loading ─────────────────────────────────
add_action('wp_enqueue_scripts', function () {
    // Only load minimal assets — frontend is handled by Next.js
    wp_dequeue_style('woocommerce-general');
    wp_dequeue_style('woocommerce-layout');
    wp_dequeue_style('woocommerce-smallscreen');
}, 99);

// ─── CORS for Next.js frontend ────────────────────────────────────────────────
add_action('init', function () {
    $allowed_origins = [
        'http://localhost:3000',
        'https://your-nextjs-domain.com', // replace with production domain
    ];

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $allowed_origins, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        status_header(200);
        exit;
    }
});

// ─── REST API ─────────────────────────────────────────────────────────────────
require_once THEME_DIR . '/includes/api-helpers.php';
require_once THEME_DIR . '/includes/api-endpoints.php';

// ─── Mail ─────────────────────────────────────────────────────────────────────
add_filter('wp_mail_content_type', fn() => 'text/html');
add_filter('wp_mail_from_name', fn() => get_bloginfo('name'));

// ─── Media ───────────────────────────────────────────────────────────────────
add_filter('mime_types', function ($mimes) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
});

// ─── Utilities ───────────────────────────────────────────────────────────────
function theme_encrypt(string $data): string {
    return base64_encode(base64_encode(base64_encode('644CBEF595BC9|' . $data)));
}

function theme_decrypt(string $data): string {
    $val = base64_decode(base64_decode(base64_decode($data)));
    return explode('|', $val)[1] ?? '';
}
