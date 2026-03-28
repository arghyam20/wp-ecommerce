<?php
defined('ABSPATH') || exit;

/**
 * Custom JWT Auth endpoint — compatible with the JWT Authentication for WP-API plugin.
 * Registers: POST /wp-json/jwt-auth/v1/token
 *            POST /wp-json/jwt-auth/v1/token/validate
 *
 * Uses JWT_AUTH_SECRET_KEY from wp-config.php (already defined).
 */

add_action('rest_api_init', function () {

    register_rest_route('jwt-auth/v1', '/token', [
        'methods'             => 'POST',
        'callback'            => 'custom_jwt_generate_token',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('jwt-auth/v1', '/token/validate', [
        'methods'             => 'POST',
        'callback'            => 'custom_jwt_validate_token',
        'permission_callback' => '__return_true',
    ]);
});

// ─── Token generation ─────────────────────────────────────────────────────────

function custom_jwt_generate_token(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $username = sanitize_text_field($request->get_param('username'));
    $password = $request->get_param('password');

    if (empty($username) || empty($password)) {
        return new WP_Error(
            'jwt_auth_missing_credentials',
            'Username and password are required.',
            ['status' => 400]
        );
    }

    // Support login by email
    $user = is_email($username)
        ? get_user_by('email', $username)
        : get_user_by('login', $username);

    if (!$user || !wp_check_password($password, $user->user_pass, $user->ID)) {
        return new WP_Error(
            'jwt_auth_failed',
            'Invalid credentials.',
            ['status' => 403]
        );
    }

    $secret = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : AUTH_KEY;
    $issued = time();
    $expiry = $issued + (DAY_IN_SECONDS * 7); // 7-day token

    $payload = [
        'iss'  => get_bloginfo('url'),
        'iat'  => $issued,
        'nbf'  => $issued,
        'exp'  => $expiry,
        'data' => [
            'user' => [
                'id' => $user->ID,
            ],
        ],
    ];

    $token = custom_jwt_encode($payload, $secret);

    return rest_ensure_response([
        'token'             => $token,
        'user_email'        => $user->user_email,
        'user_nicename'     => $user->user_nicename,
        'user_display_name' => $user->display_name,
        'user_id'           => $user->ID,
        'roles'             => $user->roles,
    ]);
}

// ─── Token validation ─────────────────────────────────────────────────────────

function custom_jwt_validate_token(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $auth_header = $request->get_header('Authorization') ?? '';

    if (!str_starts_with($auth_header, 'Bearer ')) {
        return new WP_Error('jwt_auth_no_auth_header', 'Authorization header missing.', ['status' => 403]);
    }

    $token  = trim(substr($auth_header, 7));
    $secret = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : AUTH_KEY;
    $result = custom_jwt_decode($token, $secret);

    if (is_wp_error($result)) {
        return $result;
    }

    return rest_ensure_response(['code' => 'jwt_auth_valid_token', 'data' => ['status' => 200]]);
}

// ─── Authenticate REST requests via Bearer token ──────────────────────────────

add_filter('determine_current_user', function ($user_id) {
    if ($user_id) return $user_id; // already authenticated

    $auth_header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (!str_starts_with($auth_header, 'Bearer ')) {
        return $user_id;
    }

    $token  = trim(substr($auth_header, 7));
    $secret = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : AUTH_KEY;
    $result = custom_jwt_decode($token, $secret);

    if (is_wp_error($result)) {
        return $user_id;
    }

    return $result->data->user->id ?? $user_id;
}, 10);

// ─── Pure-PHP JWT encode/decode (HS256, no library needed) ───────────────────

function custom_jwt_encode(array $payload, string $secret): string {
    $header  = custom_jwt_base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $body    = custom_jwt_base64url_encode(json_encode($payload));
    $sig     = custom_jwt_base64url_encode(hash_hmac('sha256', "$header.$body", $secret, true));
    return "$header.$body.$sig";
}

function custom_jwt_decode(string $token, string $secret): stdClass|WP_Error {
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return new WP_Error('jwt_auth_invalid_token', 'Malformed token.', ['status' => 403]);
    }

    [$header_b64, $body_b64, $sig_b64] = $parts;

    $expected_sig = custom_jwt_base64url_encode(
        hash_hmac('sha256', "$header_b64.$body_b64", $secret, true)
    );

    if (!hash_equals($expected_sig, $sig_b64)) {
        return new WP_Error('jwt_auth_invalid_token', 'Signature verification failed.', ['status' => 403]);
    }

    $payload = json_decode(custom_jwt_base64url_decode($body_b64));

    if (isset($payload->exp) && time() > $payload->exp) {
        return new WP_Error('jwt_auth_token_expired', 'Token has expired.', ['status' => 403]);
    }

    if (isset($payload->nbf) && time() < $payload->nbf) {
        return new WP_Error('jwt_auth_token_not_before', 'Token not yet valid.', ['status' => 403]);
    }

    return $payload;
}

function custom_jwt_base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function custom_jwt_base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}
