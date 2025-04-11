<?php
/*
Plugin Name: Footer Remover & Customizer
Plugin URI: https://surazacharya.com.np/footer-remover-customizer
Description: Remove default footer and set custom footer content with multiple display options. Perfect for customizing your WordPress site's footer area.
Version: 2.0.1
Author: suraj
Author URI: https://www.surazacharya.com.np
License: GPLv2 or later
Text Domain: footer-remover-customizer
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Footer_Remover_Customizer {

    public function __construct() {
        // Frontend footer modification
        add_action('wp_head', array($this, 'inject_css'));
        add_action('wp_footer', array($this, 'inject_js'), 999);
        add_action('wp_footer', array($this, 'display_custom_footer'), 1000);
        add_filter('the_content', array($this, 'remove_footer_from_content'), 999);
        
        // Admin settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        
        // Admin footer removal
        add_filter('admin_footer_text', array($this, 'remove_admin_footer_text'));
        add_filter('update_footer', array($this, 'remove_admin_footer_version'), 999);
        
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('footer-remover-customizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    // Sanitize settings
    public function sanitize_settings($input) {
        $output = array();
        
        // Checkboxes
        $checkboxes = array(
            'remove_frontend_footer',
            'remove_admin_footer',
            'enable_custom_footer'
        );
        
        foreach ($checkboxes as $checkbox) {
            $output[$checkbox] = isset($input[$checkbox]) ? 1 : 0;
        }
        
        // Custom footer content
        if (isset($input['custom_footer_content'])) {
            $output['custom_footer_content'] = wp_kses_post($input['custom_footer_content']);
        }
        
        // Custom CSS
        if (isset($input['custom_css'])) {
            $output['custom_css'] = sanitize_textarea_field($input['custom_css']);
        }
        
        return $output;
    }

    // Inject CSS to hide footer elements and style custom footer
    public function inject_css() {
        $options = get_option('footer_remover_settings');
        $custom_css = isset($options['custom_css']) ? $options['custom_css'] : '';
        
        echo '<style id="footer-remover-css">
            /* Hide default footer elements */
            footer, .footer, #footer, .site-footer, .main-footer, 
            .footer-widgets, .footer-container, .footer-area {
                display: none !important;
                visibility: hidden !important;
            }
            
            /* Style for custom footer */
            .custom-footer-container {
                width: 100%;
                padding: 20px 0;
                text-align: center;
                background-color: #f5f5f5;
                border-top: 1px solid #ddd;
                font-size: 14px;
                color: #666;
            }
            
            .custom-footer-container a {
                color: #0073aa;
                text-decoration: none;
            }
            
            .custom-footer-container a:hover {
                color: #00a0d2;
                text-decoration: underline;
            }
            
            ' . wp_strip_all_tags($custom_css) . '
        </style>';
    }

    // Inject JavaScript as a fallback
    public function inject_js() {
        $options = get_option('footer_remover_settings');
        if (!isset($options['remove_frontend_footer'])) return;
        
        echo '<script id="footer-remover-js">
            document.addEventListener("DOMContentLoaded", function() {
                var footerElements = [
                    "footer", ".footer", "#footer", ".site-footer", 
                    ".main-footer", ".footer-widgets", ".footer-container"
                ];
                
                footerElements.forEach(function(selector) {
                    var elements = document.querySelectorAll(selector);
                    elements.forEach(function(element) {
                        element.style.display = "none";
                        element.style.visibility = "hidden";
                    });
                });
            });
        </script>';
    }

    // Display custom footer
    public function display_custom_footer() {
        $options = get_option('footer_remover_settings');
        if (!isset($options['enable_custom_footer']) || empty($options['custom_footer_content'])) return;
        
        $content = wp_kses_post($options['custom_footer_content']);
        $content = do_shortcode($content);
        
        echo '<div class="custom-footer-container">' . $content . '</div>';
    }

    // Remove footer from content using PHP
    public function remove_footer_from_content($content) {
        $options = get_option('footer_remover_settings');
        if (!isset($options['remove_frontend_footer'])) return $content;
        
        // Common footer patterns
        $patterns = array(
            '/<footer.*?<\/footer>/is',
            '/<div class="footer.*?<\/div>/is',
            '/<div id="footer.*?<\/div>/is',
            '/<div class="site-footer.*?<\/div>/is',
            '/<div class="footer-widgets.*?<\/div>/is'
        );
        
        return preg_replace($patterns, '', $content);
    }

    // Remove admin footer text
    public function remove_admin_footer_text() {
        $options = get_option('footer_remover_settings');
        return (isset($options['remove_admin_footer'])) ? '' : __('Thank you for creating with WordPress', 'footer-remover-customizer');
    }

    // Remove admin version text
    public function remove_admin_footer_version() {
        $options = get_option('footer_remover_settings');
        return (isset($options['remove_admin_footer'])) ? '' : '';
    }

    // Admin settings
    public function add_admin_menu() {
        add_options_page(
            __('Footer Settings', 'footer-remover-customizer'),
            __('Footer Customizer', 'footer-remover-customizer'),
            'manage_options',
            'footer_remover',
            array($this, 'options_page_html')
        );
    }

    public function settings_init() {
        register_setting(
            'footer_remover',
            'footer_remover_settings',
            array($this, 'sanitize_settings')
        );
        
        // General Settings Section
        add_settings_section(
            'footer_remover_general_section',
            __('General Settings', 'footer-remover-customizer'),
            array($this, 'general_section_callback'),
            'footer_remover'
        );
        
        add_settings_field(
            'remove_frontend_footer',
            __('Remove Default Footer', 'footer-remover-customizer'),
            array($this, 'checkbox_render'),
            'footer_remover',
            'footer_remover_general_section',
            array('label_for' => 'remove_frontend_footer')
        );
        
        add_settings_field(
            'remove_admin_footer',
            __('Remove Admin Footer', 'footer-remover-customizer'),
            array($this, 'checkbox_render'),
            'footer_remover',
            'footer_remover_general_section',
            array('label_for' => 'remove_admin_footer')
        );
        
        // Custom Footer Section
        add_settings_section(
            'footer_remover_custom_section',
            __('Custom Footer Settings', 'footer-remover-customizer'),
            array($this, 'custom_section_callback'),
            'footer_remover'
        );
        
        add_settings_field(
            'enable_custom_footer',
            __('Enable Custom Footer', 'footer-remover-customizer'),
            array($this, 'checkbox_render'),
            'footer_remover',
            'footer_remover_custom_section',
            array('label_for' => 'enable_custom_footer')
        );
        
        add_settings_field(
            'custom_footer_content',
            __('Custom Footer Content', 'footer-remover-customizer'),
            array($this, 'editor_render'),
            'footer_remover',
            'footer_remover_custom_section',
            array('label_for' => 'custom_footer_content')
        );
        
        add_settings_field(
            'custom_css',
            __('Custom CSS', 'footer-remover-customizer'),
            array($this, 'textarea_render'),
            'footer_remover',
            'footer_remover_custom_section',
            array(
                'label_for' => 'custom_css',
                'description' => __('Add custom CSS to style your footer', 'footer-remover-customizer')
            )
        );
    }

    public function general_section_callback() {
        echo '<p>' . __('Configure general footer settings.', 'footer-remover-customizer') . '</p>';
    }

    public function custom_section_callback() {
        echo '<p>' . __('Set up your custom footer content and styling.', 'footer-remover-customizer') . '</p>';
    }

    public function checkbox_render($args) {
        $options = get_option('footer_remover_settings');
        ?>
        <input type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>" 
               name="footer_remover_settings[<?php echo esc_attr($args['label_for']); ?>]" 
               value="1" <?php checked(isset($options[$args['label_for']])); ?>>
        <?php
    }

    public function editor_render($args) {
        $options = get_option('footer_remover_settings');
        $content = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
        
        wp_editor(
            $content,
            'custom_footer_content',
            array(
                'textarea_name' => 'footer_remover_settings[' . $args['label_for'] . ']',
                'textarea_rows' => 10,
                'teeny' => true,
                'media_buttons' => false
            )
        );
    }

    public function textarea_render($args) {
        $options = get_option('footer_remover_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
        ?>
        <textarea id="<?php echo esc_attr($args['label_for']); ?>" 
                  name="footer_remover_settings[<?php echo esc_attr($args['label_for']); ?>]" 
                  rows="10" cols="50" class="large-text code"><?php echo esc_textarea($value); ?></textarea>
        <?php if (isset($args['description'])) : ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }

    public function options_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show success message on settings save
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'footer_remover_messages',
                'footer_remover_message',
                __('Settings Saved', 'footer-remover-customizer'),
                'updated'
            );
        }
        
        settings_errors('footer_remover_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('footer_remover');
                do_settings_sections('footer_remover');
                submit_button(__('Save Settings', 'footer-remover-customizer'));
                ?>
            </form>
            
            <div class="footer-remover-preview">
                <h2><?php _e('Custom Footer Preview', 'footer-remover-customizer'); ?></h2>
                <div class="preview-container" style="border: 1px solid #ddd; padding: 20px; margin-top: 20px;">
                    <?php
                    $options = get_option('footer_remover_settings');
                    if (isset($options['enable_custom_footer']) && !empty($options['custom_footer_content'])) {
                        echo '<div class="custom-footer-container">' . wp_kses_post($options['custom_footer_content']) . '</div>';
                    } else {
                        _e('No custom footer content set. Preview will appear here when you add content.', 'footer-remover-customizer');
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
}

new Footer_Remover_Customizer();