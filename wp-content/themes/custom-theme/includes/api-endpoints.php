<?php
defined('ABSPATH') || exit;

add_action('rest_api_init', function () {

    $ns = 'custom/v1';

    // ── Site settings (logo, name, description) ───────────────────────────────
    register_rest_route($ns, '/settings', [
        'methods'             => 'GET',
        'callback'            => function () {
            $logo_id = get_theme_mod('custom_logo');
            return rest_ensure_response([
                'name'        => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'url'         => get_site_url(),
                'logo'        => $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : null,
            ]);
        },
        'permission_callback' => '__return_true',
    ]);

    // ── Navigation menus ──────────────────────────────────────────────────────
    register_rest_route($ns, '/menu/(?P<location>[a-zA-Z0-9_-]+)', [
        'methods'             => 'GET',
        'callback'            => function ($request) {
            $locations = get_nav_menu_locations();
            $location  = $request->get_param('location');

            if (empty($locations[$location])) {
                return new WP_Error('no_menu', 'Menu not found', ['status' => 404]);
            }

            $menu  = wp_get_nav_menu_object($locations[$location]);
            $items = wp_get_nav_menu_items($menu->term_id);

            $formatted = array_map(fn($item) => [
                'id'        => $item->ID,
                'title'     => $item->title,
                'url'       => $item->url,
                'parent'    => $item->menu_item_parent,
                'order'     => $item->menu_order,
                'target'    => $item->target,
            ], $items ?: []);

            return rest_ensure_response($formatted);
        },
        'permission_callback' => '__return_true',
    ]);

    // ── Featured products ─────────────────────────────────────────────────────
    register_rest_route($ns, '/featured-products', [
        'methods'             => 'GET',
        'callback'            => function ($request) {
            $limit = (int) ($request->get_param('limit') ?? 8);
            $args  = [
                'post_type'      => 'product',
                'posts_per_page' => $limit,
                'tax_query'      => [[
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => 'featured',
                ]],
            ];

            $query    = new WP_Query($args);
            $products = [];

            foreach ($query->posts as $post) {
                $product    = wc_get_product($post->ID);
                $products[] = [
                    'id'            => $product->get_id(),
                    'name'          => $product->get_name(),
                    'slug'          => $product->get_slug(),
                    'price'         => $product->get_price(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price'    => $product->get_sale_price(),
                    'image'         => wp_get_attachment_url($product->get_image_id()),
                    'permalink'     => get_permalink($product->get_id()),
                    'on_sale'       => $product->is_on_sale(),
                    'stock_status'  => $product->get_stock_status(),
                ];
            }

            return rest_ensure_response($products);
        },
        'permission_callback' => '__return_true',
    ]);

    // ── Homepage data (banner + featured + categories) ────────────────────────
    register_rest_route($ns, '/homepage', [
        'methods'             => 'GET',
        'callback'            => function () {
            $categories = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
                'number'     => 6,
                'parent'     => 0,
            ]);

            $cats = array_map(fn($cat) => [
                'id'    => $cat->term_id,
                'name'  => $cat->name,
                'slug'  => $cat->slug,
                'count' => $cat->count,
                'image' => function_exists('get_term_meta')
                    ? wp_get_attachment_url(get_term_meta($cat->term_id, 'thumbnail_id', true))
                    : null,
            ], $categories ?: []);

            return rest_ensure_response([
                'categories' => $cats,
            ]);
        },
        'permission_callback' => '__return_true',
    ]);

    // ── Customer orders by email (for guest order lookup) ─────────────────────
    register_rest_route($ns, '/orders/lookup', [
        'methods'             => 'POST',
        'callback'            => function ($request) {
            $email    = sanitize_email($request->get_param('email'));
            $order_id = absint($request->get_param('order_id'));

            if (!$email || !$order_id) {
                return new WP_Error('missing_params', 'email and order_id required', ['status' => 400]);
            }

            $order = wc_get_order($order_id);
            if (!$order || $order->get_billing_email() !== $email) {
                return new WP_Error('not_found', 'Order not found', ['status' => 404]);
            }

            return rest_ensure_response([
                'id'         => $order->get_id(),
                'status'     => $order->get_status(),
                'total'      => $order->get_total(),
                'date'       => $order->get_date_created()->date('Y-m-d H:i:s'),
                'items'      => array_map(fn($item) => [
                    'name'     => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'total'    => $item->get_total(),
                ], $order->get_items()),
            ]);
        },
        'permission_callback' => '__return_true',
    ]);
});
