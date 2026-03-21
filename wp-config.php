<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_ecommerce' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '5pfO#5}bQgb[GJtl8ee<GNT1NLloIyTWqu^a4.!>q_Dh=S_g^Qj1dF%q?/CO(-Iu' );
define( 'SECURE_AUTH_KEY',  'GHwa#oLx<?M{2@+1 fw:GTKc*8O+;uEB@p{lzmTUt)^UkN|V!J}7dLU>gzsKn}7a' );
define( 'LOGGED_IN_KEY',    ':e*i~#&GNrjv: |J~t*xKL|6t:4XZK;KRL<,A[gBk zGk;-I_14`{D;1B,xI_,nO' );
define( 'NONCE_KEY',        'h)[[o(FOON2h<Iu@2yx1XGg&23wJb;?63k&Pl=.N~+dK[G#e5[n#~h-Oo^S/n,hO' );
define( 'AUTH_SALT',        ':`?Bcuy]C[t5S~+Lm<X<r6-72J7OI;b?Tpn1WqF;JOz`c#@W-CX9+phL@Z}z4f~~' );
define( 'SECURE_AUTH_SALT', '9no2^n5X#4@S>fs$Ir(EY|a}.k]W(+`&.2aFWPd:$?g3r7<ia-IE<fKzxI-}|26M' );
define( 'LOGGED_IN_SALT',   'r80icL8aigDO!T$9<) B;^U?,z1eW=^~%jTP%DA|K2bs&,!pT~MUuXXgR|c]W>@F' );
define( 'NONCE_SALT',       'nvoc_lH~Q.W zrIY+:^_myB-!zEEtM,du`Dxxoah -dUgz}V<%`^Mi(Jr%J`^:@@' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');