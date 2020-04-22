<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'Alex-Technologies' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'ttJ:}JljC3]}Gj;%VB6FozwzOzfXo3Pk!dXieFE#/@CMt1q]#;uqeGG}na&UQ4&W' );
define( 'SECURE_AUTH_KEY',  '_5[uMF>.xNiB)FTtEISg7RM0y<!V)(-b5(xY7RrN@8w8(bAUs<d1RQQmx@it{}*&' );
define( 'LOGGED_IN_KEY',    'nIC)}(>?t=),ow$_GBVblVoV&,v2;mG|bkHvslXvu?`1eU2>Q~X,b3-v{cnh;_p0' );
define( 'NONCE_KEY',        ':Mo9LG,hlQLY6|P&fE?E<)uKO>}t+M/TYQt$m[C%p_<d?kvJ7/LuGK<(3_gwCoo(' );
define( 'AUTH_SALT',        'V!76] {2m0q|t<oJ8@ds@8$+}FNvCG[ Vwz) Y_!_sVhu|h;YaYayjG|~lwFcD8q' );
define( 'SECURE_AUTH_SALT', 'VJu7PDl</g4A5xD4T|W^2!*Ma&_n{A?f<ffiR~RB3fH!nX>p@aU78F@:Cc_!3bA$' );
define( 'LOGGED_IN_SALT',   'c]:$wU<*ZY=M-&?c aXFVq]K!Yk/ {T2%iS(5)+>j<#_]wpXR<PFoYK#`_zliCW%' );
define( 'NONCE_SALT',       'p[6cSyD6]hMW5RxYU8H<VyMR7DUlBay6j6ubUy+_z2za:5/xwHktVYXEFj(!/rnK' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
