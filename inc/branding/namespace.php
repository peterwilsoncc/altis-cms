<?php

namespace HM\Platform\CMS\Branding;

use function HM\Platform\get_environment_type;
use WP_Admin_Bar;
use WP_Http;
use WP_Theme;

const COLOR_BLUE = '#4667de';
const COLOR_DARKBLUE = '#152a4e';
const COLOR_GREEN = '#3fcf8e';
const COLOR_OFFWHITE = '#f3f5f9';

/**
 * Bootstrap the branding.
 */
function bootstrap() {
	add_action( 'add_admin_bar_menus', __NAMESPACE__ . '\\remove_wordpress_admin_bar_item' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_scripts' );
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\\admin_bar_menu' );
	add_filter( 'admin_footer_text', '__return_empty_string' );
	add_action( 'wp_network_dashboard_setup', __NAMESPACE__ . '\\remove_dashboard_widgets' );
	add_action( 'wp_user_dashboard_setup', __NAMESPACE__ . '\\remove_dashboard_widgets' );
	add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\remove_dashboard_widgets' );
	add_action( 'admin_init', __NAMESPACE__ . '\\add_color_scheme' );
	add_filter( 'get_user_option_admin_color', __NAMESPACE__ . '\\override_default_color_scheme' );
	add_action( 'template_redirect', __NAMESPACE__ . '\\detect_missing_default_theme' );
	add_filter( 'admin_title', __NAMESPACE__ . '\\override_admin_title' );
	add_filter( 'insert_user_meta', __NAMESPACE__ . '\\insert_user_meta', 10, 3 );
}

/**
 * Remove the WordPress logo admin menu bar item.
 */
function remove_wordpress_admin_bar_item() {
	remove_action( 'admin_bar_menu', 'wp_admin_bar_wp_menu' );
}

/**
 * Remove dashboard widgets that are not useful.
 */
function remove_dashboard_widgets() {
	remove_meta_box( 'dashboard_primary', [ 'dashboard', 'dashboard-network', 'dashboard-user' ], 'side' );
}

/**
 * Add the Platform color scheme to the user options.
 */
function add_color_scheme() {
	wp_admin_css_color(
		'platform',
		__( 'Platform', 'hm-platform' ),
		add_query_arg( 'version', '2019-04-25-1', plugin_dir_url( dirname( __FILE__, 2 ) ) . 'assets/admin-color-scheme.css' ),
		[
			COLOR_BLUE,
			COLOR_DARKBLUE,
			COLOR_GREEN,
			COLOR_OFFWHITE,
		],
		[
			'base' => '#e5f8ff',
			'focus' => 'white',
			'current' => 'white',
		]
	);
}

/**
 * Enqueue the branding scripts and styles
 */
function enqueue_admin_scripts() {
	wp_enqueue_style( 'hm-platform-branding', plugin_dir_url( dirname( __FILE__, 2 ) ) . 'assets/branding.css', [], '2019-04-24-1' );
}

/**
 * Override the default color scheme
 *
 * This is hooked into "get_user_option_admin_color" so we have to
 * make sure to return the value if it's already set.
 *
 * @param string|false $value
 * @return string
 */
function override_default_color_scheme( $value ) : string {
	if ( $value ) {
		return $value;
	}

	return 'platform';
}

/**
 * Filter meta for new users to set admin_color to HM theme.
 *
 * @param array    $meta
 * @param \WP_User $user
 * @param bool     $update
 * @return array
 */
function insert_user_meta( array $meta, $user, $update ) : array {
	if ( $update ) {
		return $meta;
	}

	$meta['admin_color'] = 'platform';

	return $meta;
}

/**
 * Detect a missing default theme.
 *
 * If the theme is still the default, and it's missing, we can show them a
 * custom splash page.
 */
function detect_missing_default_theme() {
	$env = get_environment_type();
	if ( ! in_array( $env, [ 'development', 'local' ], true ) ) {
		return;
	}

	// Only activate if the theme is missing.
	$theme = wp_get_theme();
	if ( $theme->exists() ) {
		return;
	}

	// Check that we're using the default theme.
	if ( $theme->get_stylesheet() !== WP_DEFAULT_THEME || WP_Theme::get_core_default_theme() !== false ) {
		return;
	}

	// No theme, load default helper.
	$title = __( 'Welcome to HM Platform', 'hm-platform' );
	$message = sprintf(
		'<h1>%s</h1><p>%s</p><p><small>%s</small></p>',
		$title,
		sprintf(
			__( 'HM Platform is installed and ready to go. <a href="%s">Activate a theme to get started</a>.', 'hm-platform' ),
			admin_url( 'themes.php' )
		),
		__( 'You‘re seeing this page because debug mode is enabled, and the default theme directory is missing.', 'hm-platform' )
	);

	wp_die( $message, $title, [ 'response' => WP_Http::NOT_FOUND ] );
}


/**
 * Add the Platform logo menu.
 *
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {
	$logo_menu_args = [
		'id'    => 'hm-platform',
		'title' => '<span class="icon"><img src="' . get_logo_url( 'white' ) . '" /></span>',
	];

	// Set tabindex="0" to make sub menus accessible when no URL is available.
	$logo_menu_args['meta'] = [
		'tabindex' => 0,
	];

	$wp_admin_bar->add_menu( $logo_menu_args );
}

/**
 * Get URL for the logo.
 *
 * @param string|null $variant Variant of the logo. One of 'white' or null.
 * @return string URL for the Altis logo.
 */
function get_logo_url( $variant = null ) {
	$file = $variant === 'white' ? 'logo-white.svg' : 'logo.svg';
	return sprintf( '%s/assets/%s', untrailingslashit( plugin_dir_url( dirname( __FILE__, 2 ) ) ), $file );
}

/**
 * Render the logo image.
 *
 * @param string|null $variant Variant of the logo. One of 'white' or null.
 * @return void Outputs the logo directly to the page.
 */
function render_logo( $variant = null ) {
	printf( '<img class="altis-logo" alt="Altis" src="%s" />', get_logo_url( $variant ) );
}

/**
 * Override the admin title.
 *
 * WordPress puts a '> WordPress' after all the <title>.
 *
 * @param string $admin_title
 * @return string
 */
function override_admin_title( string $admin_title ) : string {
	return str_replace( ' &#8212; WordPress', ' &#8212; HM Platform', $admin_title );
}
