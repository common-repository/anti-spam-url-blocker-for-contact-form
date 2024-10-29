<?php

/**
 * Plugin Name: Anti-Spam URL Blocker for Contact Form
 * Description: Securely prevent spam and malicious URL submissions in Contact Form 7 forms with this anti-spam plugin. It blocks unwanted URLs, protects against malware, and disables the submit button when a URL is detected, ensuring enhanced security for your WordPress website. Requires Contact Form 7.
 * Version: 1.0.1
 * Author: Ajay Patidar
 * Text Domain: anti-spam-url-blocker-for-contact-form
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.2
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('B47_CF_URL_VALIDATOR_VERSION', '1.0.1');
define('B47_CF_URL_VALIDATOR_PATH', plugin_dir_path(__FILE__));
define('B47_CF_URL_VALIDATOR_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class B47_CF_URL_Validator
{
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('wpcf7_init', array($this, 'add_cf7_validation'));
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Check if Contact Form 7 is active
        if (!class_exists('WPCF7')) {
            add_action('admin_notices', array($this, 'cf7_missing_notice'));
            return;
        }

        // Load textdomain for internationalization
        load_plugin_textdomain('anti-spam-url-blocker-for-contact-form', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));

        // Add AJAX handlers
        add_action('wp_ajax_b47_validate_url', array($this, 'ajax_validate_url'));
        add_action('wp_ajax_nopriv_b47_validate_url', array($this, 'ajax_validate_url'));
    }

    /**
     * Add CF7 validation
     */
    public function add_cf7_validation()
    {
        add_filter('wpcf7_validate', array($this, 'validate_url'), 10, 2);
    }

    /**
     * Validate URLs in form submission
     */
    public function validate_url($result, $tags)
    {
        $submission = WPCF7_Submission::get_instance();

        if ($submission) {
            $data = $submission->get_posted_data();

            foreach ($data as $key => $value) {
                if (is_string($value) && $this->contains_url($value)) {
                    $result->invalidate($key, __('URLs are not allowed in this field.', 'anti-spam-url-blocker-for-contact-form'));
                }
            }
        }

        return $result;
    }

    /**
     * Check if text contains URL
     */
    private function contains_url($text)
    {
        return preg_match('/(https?:\/\/[^\s]+)|(www\.[^\s]+)/i', $text);
    }

    /**
     * Handle AJAX validation request
     */
    public function ajax_validate_url()
    {
        check_ajax_referer('anti-spam-url-blocker-for-contact-form-nonce', 'nonce');

        // Check if the 'text' field is set
        if (isset($_POST['text'])) {
            // Unsplash and sanitize the input
            $text = sanitize_textarea_field(wp_unslash($_POST['text']));
            $contains_url = $this->contains_url($text);

            wp_send_json_success(['containsUrl' => $contains_url]);
        } else {
            wp_send_json_error(['message' => __('No text provided.', 'anti-spam-url-blocker-for-contact-form')]);
        }
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts()
    {
        // Enqueue CSS
        wp_enqueue_style(
            'anti-spam-url-blocker-for-contact-form',
            B47_CF_URL_VALIDATOR_URL . 'assets/css/47b-cf-url-validator.css',
            array(),
            B47_CF_URL_VALIDATOR_VERSION
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'anti-spam-url-blocker-for-contact-form',
            B47_CF_URL_VALIDATOR_URL . 'assets/js/47b-cf-url-validator.js',
            array('jquery'),
            B47_CF_URL_VALIDATOR_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'anti-spam-url-blocker-for-contact-form',
            'b47CFURLValidator',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'errorMessage' => esc_html__('URLs are not allowed in form fields. Please remove any URLs to submit the form.', 'anti-spam-url-blocker-for-contact-form'),
                'nonce' => wp_create_nonce('anti-spam-url-blocker-for-contact-form-nonce')
            )
        );
    }

    /**
     * Display admin notice if Contact Form 7 is not active
     */
    public function cf7_missing_notice()
    {
?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Anti-Spam URL Blocker for Contact Form requires Contact Form 7 to be installed and activated.', 'anti-spam-url-blocker-for-contact-form'); ?></p>
        </div>
<?php
    }

    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="' . admin_url('options-general.php?page=wpcf7') . '">' . esc_html__('Settings', 'anti-spam-url-blocker-for-contact-form') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
function b47_cf_url_validator_init()
{
    B47_CF_URL_Validator::get_instance();
}
add_action('init', 'b47_cf_url_validator_init');
