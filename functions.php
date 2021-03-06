<?php
/**
 * Dark-mode related functions & hooks.
 *
 * @package wordpress/twentytwentyone-dark-mode
 */

 /**
 * Editor custom color variables.
 *
 * @since 1.0.0
 *
 * @return void
 */
function tt1_dark_mode_editor_custom_color_variables() {
	if ( ! tt1_dark_mode_switch_should_render() ) {
		return;
	}
	$background_color            = get_theme_mod( 'background_color', 'D1E4DD' );
	$should_respect_color_scheme = get_theme_mod( 'respect_user_color_preference', false );
	if ( $should_respect_color_scheme && Twenty_Twenty_One_Custom_Colors::get_relative_luminance_from_hex( $background_color ) > 127 ) {
		// Add dark mode variable overrides.
		wp_add_inline_style(
			'twenty-twenty-one-custom-color-overrides',
			'html.is-dark-mode .editor-styles-wrapper { --global--color-background: var(--global--color-dark-gray); --global--color-primary: var(--global--color-light-gray); --global--color-secondary: var(--global--color-light-gray); }'
		);
	}
	wp_enqueue_script(
		'twentytwentyone-dark-mode-support-toggle',
		plugins_url( 'assets/js/toggler.js', __FILE__ ),
		array(),
		'1.0.0',
		true
	);

	wp_enqueue_script(
		'twentytwentyone-editor-dark-mode-support',
		plugins_url( 'assets/js/editor-dark-mode-support.js', __FILE__ ),
		array( 'twentytwentyone-dark-mode-support-toggle' ),
		'1.0.0',
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'tt1_dark_mode_editor_custom_color_variables' );

/**
 * Enqueue scripts and styles.
 *
 * @since 1.0.0
 *
 * @return void
 */
function tt1_dark_mode_scripts() {
	if ( ! tt1_dark_mode_switch_should_render() ) {
		return;
	}
	wp_enqueue_style(
		'tt1-dark-mode',
		plugins_url( 'assets/css/style.css', __FILE__ ),
		array( 'twenty-twenty-one-style' ),
		'1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'tt1_dark_mode_scripts' );

/**
 * Enqueue scripts for the customizer.
 *
 * @since 1.0.0
 *
 * @return void
 */
function tt1_dark_mode_customize_controls_enqueue_scripts() {
	if ( ! tt1_dark_mode_switch_should_render() ) {
		return;
	}
	wp_enqueue_script(
		'twentytwentyone-customize-controls',
		plugins_url( 'assets/js/customize.js', __FILE__ ),
		array( 'customize-base', 'customize-controls', 'underscore', 'jquery', 'twentytwentyone-customize-helpers' ),
		'1.0.0',
		true
	);

	wp_localize_script(
		'twentytwentyone-customize-controls',
		'backgroundColorNotice',
		array(
			'message' => esc_html__( 'You currently have dark mode enabled on your device. Changing the color picker will allow you to preview light mode.', 'twentytwentyone-dark-mode' ),
		)
	);
}
add_action( 'customize_controls_enqueue_scripts', 'tt1_dark_mode_customize_controls_enqueue_scripts' );

/**
 * Register customizer options.
 *
 * @since 1.0.0
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 *
 * @return void
 */
function tt1_dark_mode_register_customizer_controls( $wp_customize ) {
	$wp_customize->add_setting(
		'respect_user_color_preference',
		array(
			'capability'        => 'edit_theme_options',
			'default'           => false,
			'sanitize_callback' => function( $value ) {
				return (bool) $value;
			},
		)
	);

	$wp_customize->add_control(
		'respect_user_color_preference',
		array(
			'type'            => 'checkbox',
			'section'         => 'colors',
			'label'           => esc_html__( 'Respect visitor\'s device dark mode settings', 'twentytwentyone-dark-mode' ),
			'description'     => __( 'Dark mode is a device setting. If a visitor to your site requests it, your site will be shown with a dark background and light text.', 'twentytwentyone-dark-mode' ),
			'active_callback' => function( $value ) {
				return 127 < Twenty_Twenty_One_Custom_Colors::get_relative_luminance_from_hex( get_theme_mod( 'background_color', 'D1E4DD' ) );
			},
		)
	);
}
add_action( 'customize_register', 'tt1_dark_mode_register_customizer_controls' );

/**
 * Calculate classes for the main <html> element.
 *
 * @since 1.0.0
 *
 * @param string $classes The classes for <html> element.
 *
 * @return string
 */
function tt1_dark_mode_the_html_classes( $classes ) {
	if ( ! tt1_dark_mode_switch_should_render() ) {
		return $classes;
	}

	$background_color            = get_theme_mod( 'background_color', 'D1E4DD' );
	$should_respect_color_scheme = get_theme_mod( 'respect_user_color_preference', false );
	if ( $should_respect_color_scheme && 127 <= Twenty_Twenty_One_Custom_Colors::get_relative_luminance_from_hex( $background_color ) ) {
		return ( $classes ) ? ' respect-color-scheme-preference' : 'respect-color-scheme-preference';
	}

	return $classes;
}
add_filter( 'twentytwentyone_html_classes', 'tt1_dark_mode_the_html_classes' );

/**
 * Adds a class to the <body> element in the editor to accommodate dark-mode.
 *
 * @since 1.0.0
 *
 * @param string $classes The admin body-classes.
 *
 * @return string
 */
function tt1_dark_mode_admin_body_classes( $classes ) {
	if ( ! tt1_dark_mode_switch_should_render() ) {
		return $classes;
	}

	global $current_screen;
	if ( empty( $current_screen ) ) {
		set_current_screen();
	}

	if ( $current_screen->is_block_editor() ) {
		$should_respect_color_scheme = get_theme_mod( 'respect_user_color_preference', false );
		$background_color            = get_theme_mod( 'background_color', 'D1E4DD' );

		if ( $should_respect_color_scheme && Twenty_Twenty_One_Custom_Colors::get_relative_luminance_from_hex( $background_color ) > 127 ) {
			$classes .= ' twentytwentyone-supports-dark-theme';
		}
	}

	return $classes;
}
add_filter( 'admin_body_class', 'tt1_dark_mode_admin_body_classes' );

/**
 * Determine if we want to print the dark-mode switch or not.
 *
 * @since 1.0.0
 *
 * @return void
 */
function tt1_dark_mode_switch_should_render() {
	global $is_IE;
	return (
		get_theme_mod( 'respect_user_color_preference', false ) &&
		! $is_IE &&
		127 <= Twenty_Twenty_One_Custom_Colors::get_relative_luminance_from_hex( get_theme_mod( 'background_color', 'D1E4DD' ) )
	);
}

/**
 * Add night/day switch.
 *
 * @since 1.0.0
 *
 * @return void
 */
function tt1_the_dark_mode_switch() {
	if ( ! tt1_dark_mode_switch_should_render() ) {
		return;
	}
	tt1_dark_mode_switch_the_html();
	tt1_dark_mode_switch_the_script();
}
add_action( 'get_template_part_template-parts/header/site-nav', 'tt1_the_dark_mode_switch' );


/**
 * Print the dark-mode switch HTML.
 *
 * Inspired from https://codepen.io/aaroniker/pen/KGpXZo (MIT-licensed)
 *
 * @since 1.0.0
 *
 * @param string $classes The classes to add.
 *
 * @return void
 */
function tt1_dark_mode_switch_the_html( $classes = 'relative' ) {
	?>
	<button id="dark-mode-toggler" class="<?php echo esc_attr( $classes ); ?>" aria-pressed="false" onClick="toggleDarkMode()">
		<?php
		printf(
			esc_html__( 'Dark Mode: %s', 'twentytwentyone-dark-mode' ),
			'<span aria-hidden="true"></span>'
		);
		?>
	</button>
	<style>
		#dark-mode-toggler > span {
			margin-<?php echo is_rtl() ? 'right' : 'left'; ?>: 5px;
		}
		#dark-mode-toggler > span::before {
			content: '<?php esc_attr_e( 'Off', 'twentytwentyone-dark-mode' ); ?>';
		}
		#dark-mode-toggler[aria-pressed="true"] > span::before {
			content: '<?php esc_attr_e( 'On', 'twentytwentyone-dark-mode' ); ?>';
		}
		<?php if ( is_admin() || wp_is_json_request() ) : ?>
			.components-editor-notices__pinned ~ .edit-post-visual-editor #dark-mode-toggler {
				z-index: 20;
			}
			@media only screen and (max-width: 782px) {
				#dark-mode-toggler {
					margin-top: 32px;
				}
			}
		<?php endif; ?>
	</style>

	<?php
}

/**
 * Print the dark-mode switch script.
 *
 * @since 1.0.0
 *
 * @return void
 */
function tt1_dark_mode_switch_the_script() {
	echo '<script>';
	include 'assets/js/toggler.js'; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude
	echo '</script>';
}

/**
 * Print the dark-mode switch styles.
 *
 * @since 1.0.0
 *
 * @return void
 */
function tt1_dark_mode_switch_the_styles() {
	echo '<style>';
	include 'assets/css/style.css'; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude
	echo '</style>';
}

/**
 * Call the tt1_the_dark_mode_switch and exit.
 *
 * @since 1.0.0
 *
 * @return void
 */
function tt1_dark_mode_editor_ajax_callback() {
	tt1_dark_mode_switch_the_html();
	tt1_dark_mode_switch_the_styles();
	wp_die();
}
add_action( 'wp_ajax_tt1_dark_mode_editor_switch', 'tt1_dark_mode_editor_ajax_callback' );
