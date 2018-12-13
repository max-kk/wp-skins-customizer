<?php

// If this file is called directly, abort.
if (!class_exists('WP')) {
    die();
}


/**
 * Integrate WP Customizer
 *
 * @author     Maxim K <support@wp-vote.net>
 */

class WP_Skins_Customizer {

    protected static $version = '1.0';
    protected static $url = '';
    protected static $path = '';

    static function has_version( $required_version ) {
        return version_compare($required_version, self::$version, '>=');
    }

    static function init() {

        self::$path = trailingslashit( dirname( __FILE__ ) );

        if ( ! self::$url ) {
            self::detect_url();
        }


        add_action( 'customize_preview_init', array('WP_Skins_Customizer', 'customizer_live_preview') );
        add_action( 'customize_controls_enqueue_scripts', array('WP_Skins_Customizer', 'customize_controls_enqueue_scripts') );

    }

    /**
     * Detect correct url to library folder (in Plugin or Theme)
     */
    static function detect_url() {

        $theme_url = parse_url( get_stylesheet_directory_uri() );
        $theme_pos = strpos( self::$path, $theme_url['path'] );

        // Library is loaded from theme.
        if ( $theme_pos !== false ) {

            $plugin_relative_dir = str_replace( $theme_url['path'], '', substr( self::$path, $theme_pos ) );
            $url = $theme_url['scheme'] . '://' . $theme_url['host'] . $theme_url['path'] . $plugin_relative_dir;

            self::$url = $url;

        } else {
            // Loaded from plugin.
            $plugin_url = trailingslashit( plugins_url( '', __FILE__ ) );
            self::$url = $plugin_url;
        }

    }

//    /**
//     * @param WP_Customize_Manager $wp_customize
//     */
//    static function customize_register($wp_customize ) {
//
////		/**
////		 * Add our Header & Navigation Panel
////		 */
//		$wp_customize->add_panel( 'wp_foto_vote',
//			array(
//				'title' => __( 'WP Foto Vote' ),
//				//'description' => esc_html__( 'Adjust your Header and Navigation sections.' ), // Include html tags such as
//
//				'priority' => 160, // Not typically needed. Default is 160
//				'capability' => 'edit_theme_options', // Not typically needed. Default is edit_theme_options
//				'theme_supports' => '', // Rarely needed
//				'active_callback' => '', // Rarely needed
//			)
//		);
//
//    }

    /**
     * Registers the Theme Customizer Preview with WordPress.
     */
    static function customizer_live_preview() {
        wp_enqueue_script(
            'wp-skins-customizer-preview',
            self::$url . '/assets/wp-skins-customizer-preview.js',
            array( 'customize-preview' ),
            self::$version,
            true
        );
    }

    static function customize_controls_enqueue_scripts () {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker');
    }
}


