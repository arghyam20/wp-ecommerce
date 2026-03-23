<?php
/**
 * Headless theme — frontend handled by Next.js.
 * WordPress is used as a REST API backend only.
 */
if (!is_admin() && !defined('REST_REQUEST')) {
    $nextjs_url = 'http://localhost:3000'; // replace with production URL
    wp_redirect($nextjs_url, 302);
    exit;
}
