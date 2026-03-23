<?php
defined('ABSPATH') || exit;

// ─── Expose WooCommerce REST API ──────────────────────────────────────────────
add_filter('woocommerce_rest_check_permissions', '__return_true');

// Allow public read access to products/categories without auth
add_filter('woocommerce_rest_product_object_query', function ($args, $request) {
    return $args;
}, 10, 2);

// ─── Add extra fields to product REST response ────────────────────────────────
add_filter('woocommerce_rest_prepare_product_object', function ($response, $object, $request) {
    $data = $response->get_data();

    // Gallery image URLs
    $gallery_ids = $object->get_gallery_image_ids();
    $data['gallery_images'] = array_map(fn($id) => [
        'id'  => $id,
        'src' => wp_get_attachment_url($id),
        'alt' => get_post_meta($id, '_wp_attachment_image_alt', true),
    ], $gallery_ids);

    // Average rating & review count
    $data['average_rating'] = $object->get_average_rating();
    $data['review_count']   = $object->get_review_count();

    $response->set_data($data);
    return $response;
}, 10, 3);

// ─── Add extra fields to order REST response ─────────────────────────────────
add_filter('woocommerce_rest_prepare_shop_order_object', function ($response, $object, $request) {
    $data = $response->get_data();
    $data['customer_note'] = $object->get_customer_note();
    $response->set_data($data);
    return $response;
}, 10, 3);

// ─── Increase REST API product per_page max ───────────────────────────────────
add_filter('woocommerce_rest_product_collection_params', function ($params) {
    $params['per_page']['maximum'] = 100;
    return $params;
});

// ─── Expose product meta for Next.js ─────────────────────────────────────────
add_action('rest_api_init', function () {
    register_rest_field('product', 'custom_meta', [
        'get_callback' => function ($post) {
            return [
                'featured'     => get_post_meta($post['id'], '_featured', true),
                'purchase_note' => get_post_meta($post['id'], '_purchase_note', true),
            ];
        },
        'schema' => null,
    ]);
});
