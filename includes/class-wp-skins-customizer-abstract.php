<?php

/**
 * Abstract class for easy integrate Customizer to different kinds of elements/blocks
 *
 * @author     Maxim K <support@wp-vote.net>
 */
abstract class WP_Skins_Customizer_Abstract
{
    protected $customizer_slug = null;

    // Do not used for now
    protected $api_version;

    protected $supports_customizer = false;
    protected $customizer_settings = array();
    protected $customizer_section_title = '[CHANGE THIS]';
    protected $customizer_section_panel = '';

    protected $output_handle = null;
    protected $output_position = 'wp_footer';
    protected $output_priority = 0;
    protected $output_css_prefix = '';

    /**
     * Init
     */
    public function init_customizer()
    {
        if ( $this->supports_customizer ) {
            if ( !null === $this->output_handle ) {
                throw new \Exception( "'output_handle' is not set!" );
            }
            if ( !$this->customizer_slug ) {
                throw new \Exception( "'customizer_slug' is not set!" );
            }

            add_action( 'customize_register', array($this, '_register_customizer_fields'), 99 );
            add_action( 'customize_preview_init', array($this, '_register_customizer_preview_js'), 11 );

            $this->register_customizer_settings();
        }
    }

    /**
     * Here you should define all fields
     * @since 2.2.815
     */
    public function register_customizer_settings() {

    }

    /**
     * enqueue customized CSS
     * @since 2.2.815
     * @access private
     */
    public function _enqueue_output_customized_css () {
        if ( ! $this->supports_customizer ) {
            return;
        }

        add_action( $this->output_position, array($this, '_output_customized_css'), $this->output_priority );
    }

    /**
     * @since 2.2.815
     * @access private
     */
    public function _output_customized_css(){
        if ( ! $this->supports_customizer ) {
            return;
        }

        $skin_settings_css_map = array();
        $customized_css = array();
        $attribute_value = null;

        foreach ($this->customizer_settings as $setting_key=> $setting) {

            foreach ( $setting['css'] as $css_selector => $css_data ) {
                $attribute_value = $this->get_customized_value( $setting['key'] );
                $attribute_value_src = $attribute_value;

                if ( $css_data['units'] ) {
                    $attribute_value .= $css_data['units'];
                }

                if ( $css_data['important'] ) {
                    $attribute_value .= ' !important';
                }

                if ( $css_data['callback'] && is_callable($css_data['callback']) ) {
                    $customized_css[] = $css_data['callback']( $attribute_value, $attribute_value_src, $css_selector, $css_data, $setting );
                    continue;
                }

                if ( $css_data['media'] ) {
                    $customized_css[] = sprintf('@media (%s){ %s{%s: %s;} }', $css_data['media'], $css_selector, $css_data['attribute'], $attribute_value);
                } else {
                    $customized_css[] = sprintf('%s{%s: %s;}', $css_selector, $css_data['attribute'], $attribute_value);
                }
            }
        }

        if ( $this->output_handle && ! wp_style_is($this->output_handle, 'done') ) {
            wp_add_inline_style($this->output_handle, implode(' ', $customized_css));
        } else {
            echo '<style type="text/css">', implode(' ', $customized_css), '</style>';
        }
    }

    /**
     * Add new setting for later generate it in Customizer
     * @param string $key
     * @param array $options
     * @param array $css_map    Format: ["css selector" => ["attribute"=>"color", "type"=>"css,style"]]
     *
     * @since 2.2.815
     */
    public function _register_customizer_setting($key, $options, $css_map = array() ) {

        $css_map_corrected = array();

        // Let's add more specific classes
        foreach ($css_map as $css_row_key => $css_row) {
            // Add defaults
            $css_row = array_merge(array('attribute' => '','type' => 'css','units' => '','media' => '','important' => '','callback' => false), $css_row);
            // Add prefix - mostly for skins
            $css_map_corrected[$this->output_css_prefix . ' ' . $css_row_key] = $css_row;
        }

        // Fill empty data with defaults
        $options = array_merge(array(
            'key' => $key,
            'label' => '',
            'description' => '',
            'section' => $this->customizer_slug,
            'type' => '',
            'type_class' => '',
            'choices' => array(),

            'default' => '',
            'setting_type' => 'option',         // Optional. 'theme_mod' or 'option'. Default: 'theme_mod'
            'setting_transport' => 'postMessage',
            'sanitize_callback' => '',

            'css' => $css_map_corrected,
        ), $options);

        $this->customizer_settings[ $this->customizer_slug . $key ] = $options;
    }

    /**
     * @return array
     * @since 2.2.815
     */
    public function _get_customizer_settings() {
        return $this->customizer_settings;
    }

    /**
     * @param string $key
     * @return mixed|void
     * @throws Exception
     *
     * @since 2.2.815
     */
    public function get_customized_value($key ) {
        if ( ! isset( $this->customizer_settings[$this->customizer_slug . $key ] ) ) {
            throw new \Exception( "Customized setting '{$key}' is not exists!" );
        }

        if ( 'option' == $this->customizer_settings[$this->customizer_slug . $key ]['setting_type'] ) {
            $value = get_option( $this->customizer_slug . $key, null );
        } else {
            $value = get_theme_mod( $this->customizer_slug . $key, null );
        }

        if ( null === $value ) {
            return $this->customizer_settings[$this->customizer_slug . $key ]['default'];
        }

        return $value;
    }


    /**
     * @param WP_Customize_Manager $wp_customize
     * @since 2.2.815
     */
    public function _register_customizer_fields($wp_customize)
    {

        $wp_customize->add_section( $this->customizer_slug,
            array(
                'title' => $this->customizer_section_title,
                //'description' => esc_html__( 'Here you can customize modal styles.' ),
                'panel' => $this->customizer_section_panel, // Only needed if adding your Section to a Panel
                'priority' => 162, // Not typically needed. Default is 160
                'capability' => 'edit_theme_options', // Not typically needed. Default is edit_theme_options
                'theme_supports' => '', // Rarely needed
                'active_callback' => '', // Rarely needed
                'description_hidden' => 'false', // Rarely needed. Default is False
            )
        );

        foreach ($this->customizer_settings as $setting_key=> $setting) {
            $wp_customize->add_setting( $setting_key,
                array(
                    'default' => $setting['default'],
                    'type' => $setting['setting_type'], // Optional. 'theme_mod' or 'option'. Default: 'theme_mod'
                    'transport' => $setting['setting_transport'],
                    'sanitize_callback' => $setting['sanitize_callback']
                )
            );

            if ( $setting['type_class'] && class_exists($setting['type_class']) ) {
                $wp_customize->add_control(
                    new $setting['type_class'] (
                        $wp_customize,
                        $setting_key,
                        array(
                            'label' => $setting['label'],
                            'description' => $setting['description'],
                            'section' => $setting['section'],
                            'priority' => 10, // Optional. Order priority to load the control. Default: 10
                            'type' => $setting['type'],
                            'choices' => $setting['choices'],
                            'capability' => 'edit_theme_options', // Optional. Default: 'edit_theme_options'
                        )
                    )
                );
            } else {
                $wp_customize->add_control($setting_key,
                    array(
                        'label' => $setting['label'],
                        'description' => $setting['description'],
                        'section' => $setting['section'],
                        'priority' => 10, // Optional. Order priority to load the control. Default: 10
                        'type' => $setting['type'],
                        'choices' => $setting['choices'],
                        'capability' => 'edit_theme_options', // Optional. Default: 'edit_theme_options'
                    )
                );
            }

        }

    }

    /**
     * Register Script for dynamically update skin design
     * @since 2.2.815
     */
    public function _register_customizer_preview_js()
    {
        $skin_settings_css_map = array();

        foreach ($this->customizer_settings as $setting_key=> $setting) {
            $skin_settings_css_map[$setting_key] = $setting['css'];
        }

        wp_localize_script( 'wp-skins-customizer-preview', 'WP_Skin_'.$this->slug, $skin_settings_css_map );

        wp_add_inline_script(
            'wp-skins-customizer-preview',
            "var WP_Skins_Settings = WP_Skins_Settings || {}; WP_Skins_Settings = jQuery.extend(WP_Skins_Settings, WP_Skin_{$this->slug});",
            'before'
        );
    }
    
}