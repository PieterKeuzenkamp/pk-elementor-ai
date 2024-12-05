<?php
/**
 * Plugin Name: PK Elementor AI
 * Description: Een professionele AI-extensie voor Elementor die gebruik maakt van ChatGPT.
 * Version: 1.0
 * Author: Pieter Keuzenkamp
 * Company: Pieter Keuzenkamp Websites
 * Author URI: https://www.pieterkeuzenkamp.nl
 * Text Domain: pk-elementor-widgets
 * Requires Elementor: 3.0.0
 * Requires PHP: 7.0
 */

if (!defined('ABSPATH')) {
    exit; // Blokkeer directe toegang
}

// Plugin constanten
define('PK_ELEMENTOR_AI_PATH', plugin_dir_path(__FILE__));
define('PK_ELEMENTOR_AI_URL', plugin_dir_url(__FILE__));
define('PK_ELEMENTOR_AI_VERSION', '1.0.0');

// Admin menu en instellingen
require_once(PK_ELEMENTOR_AI_PATH . 'includes/admin/settings.php');

/**
 * Initialiseer de plugin
 */
class PK_Elementor_AI {
    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        // Controleer of Elementor geïnstalleerd en geactiveerd is
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_elementor']);
            return;
        }

        // Widgets registreren
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        
        // Styles en scripts registreren
        add_action('wp_enqueue_scripts', [$this, 'register_styles']);
        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
    }

    public function register_widgets($widgets_manager) {
        require_once(PK_ELEMENTOR_AI_PATH . 'widgets/pk-ai-widget.php');
        $widgets_manager->register(new \PK_AI_Widget());
    }

    public function register_styles() {
        wp_register_style(
            'pk-elementor-ai',
            PK_ELEMENTOR_AI_URL . 'assets/css/pk-elementor-ai.css',
            [],
            PK_ELEMENTOR_AI_VERSION
        );
    }

    public function register_scripts() {
        wp_register_script(
            'pk-elementor-ai',
            PK_ELEMENTOR_AI_URL . 'assets/js/pk-elementor-ai.js',
            ['jquery'],
            PK_ELEMENTOR_AI_VERSION,
            true
        );
    }

    public function admin_notice_missing_elementor() {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            __('"%1$s" vereist "%2$s" om te functioneren.', 'pk-elementor-widgets'),
            '<strong>PK Elementor AI</strong>',
            '<strong>Elementor</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

// Initialiseer de plugin
PK_Elementor_AI::instance();
