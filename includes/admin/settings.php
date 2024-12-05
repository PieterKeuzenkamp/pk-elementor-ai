<?php
if (!defined('ABSPATH')) {
    exit;
}

class PK_Elementor_AI_Settings {
    private static $_instance = null;
    private $options_name = 'pk_elementor_ai_settings';
    private $cache_group = 'pk_elementor_ai';
    private $rate_limit_key = 'pk_elementor_ai_rate_limit';

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'init_settings']);
        
        // Initialize cache
        wp_cache_add_non_persistent_groups([$this->cache_group]);
    }

    public function add_admin_menu() {
        add_submenu_page(
            'elementor',
            __('AI Settings', 'pk-elementor-widgets'),
            __('AI Settings', 'pk-elementor-widgets'),
            'manage_options',
            'pk-elementor-ai-settings',
            [$this, 'render_settings_page']
        );
    }

    public function init_settings() {
        register_setting($this->options_name, $this->options_name, [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);

        add_settings_section(
            'pk_elementor_ai_main',
            __('API Instellingen', 'pk-elementor-widgets'),
            [$this, 'render_section_info'],
            'pk-elementor-ai-settings'
        );

        // API Key
        add_settings_field(
            'api_key',
            __('OpenAI API Key', 'pk-elementor-widgets'),
            [$this, 'render_api_key_field'],
            'pk-elementor-ai-settings',
            'pk_elementor_ai_main'
        );

        // Cache Duration
        add_settings_field(
            'cache_duration',
            __('Cache Duur (minuten)', 'pk-elementor-widgets'),
            [$this, 'render_cache_duration_field'],
            'pk-elementor-ai-settings',
            'pk_elementor_ai_main'
        );

        // Rate Limit
        add_settings_field(
            'rate_limit',
            __('Rate Limit (verzoeken per uur)', 'pk-elementor-widgets'),
            [$this, 'render_rate_limit_field'],
            'pk-elementor-ai-settings',
            'pk_elementor_ai_main'
        );

        // Error Logging
        add_settings_field(
            'error_logging',
            __('Error Logging', 'pk-elementor-widgets'),
            [$this, 'render_error_logging_field'],
            'pk-elementor-ai-settings',
            'pk_elementor_ai_main'
        );
    }

    public function sanitize_settings($input) {
        $sanitized = [];
        
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }

        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = absint($input['cache_duration']);
        }

        if (isset($input['rate_limit'])) {
            $sanitized['rate_limit'] = absint($input['rate_limit']);
        }

        if (isset($input['error_logging'])) {
            $sanitized['error_logging'] = (bool) $input['error_logging'];
        }

        return $sanitized;
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'pk_elementor_ai_messages',
                'pk_elementor_ai_message',
                __('Instellingen opgeslagen', 'pk-elementor-widgets'),
                'updated'
            );
        }

        settings_errors('pk_elementor_ai_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->options_name);
                do_settings_sections('pk-elementor-ai-settings');
                submit_button('Instellingen opslaan');
                ?>
            </form>
            <div class="pk-elementor-ai-stats">
                <h2><?php _e('Statistieken', 'pk-elementor-widgets'); ?></h2>
                <?php $this->render_stats(); ?>
            </div>
        </div>
        <?php
    }

    private function render_stats() {
        $stats = $this->get_usage_stats();
        ?>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><?php _e('API Verzoeken (laatste uur)', 'pk-elementor-widgets'); ?></td>
                    <td><?php echo esc_html($stats['hourly_requests']); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Cache Hit Rate', 'pk-elementor-widgets'); ?></td>
                    <td><?php echo esc_html($stats['cache_hit_rate']); ?>%</td>
                </tr>
                <tr>
                    <td><?php _e('Gecachte Items', 'pk-elementor-widgets'); ?></td>
                    <td><?php echo esc_html($stats['cached_items']); ?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    private function get_usage_stats() {
        return [
            'hourly_requests' => get_transient($this->rate_limit_key) ?: 0,
            'cache_hit_rate' => get_option('pk_elementor_ai_cache_hit_rate', 0),
            'cached_items' => get_option('pk_elementor_ai_cached_items', 0),
        ];
    }

    public function render_section_info() {
        echo '<p>' . esc_html__('Configureer hier je AI instellingen en bekijk gebruiksstatistieken.', 'pk-elementor-widgets') . '</p>';
    }

    public function render_api_key_field() {
        $options = get_option($this->options_name);
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        ?>
        <input type="password" 
               id="api_key" 
               name="<?php echo esc_attr($this->options_name); ?>[api_key]" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text">
        <p class="description">
            <?php _e('Je OpenAI API key. Verkrijg deze via je OpenAI dashboard.', 'pk-elementor-widgets'); ?>
        </p>
        <?php
    }

    public function render_cache_duration_field() {
        $options = get_option($this->options_name);
        $duration = isset($options['cache_duration']) ? $options['cache_duration'] : 60;
        ?>
        <input type="number" 
               id="cache_duration" 
               name="<?php echo esc_attr($this->options_name); ?>[cache_duration]" 
               value="<?php echo esc_attr($duration); ?>" 
               min="1" 
               max="1440">
        <p class="description">
            <?php _e('Hoe lang moeten AI responses worden gecached? (in minuten)', 'pk-elementor-widgets'); ?>
        </p>
        <?php
    }

    public function render_rate_limit_field() {
        $options = get_option($this->options_name);
        $rate_limit = isset($options['rate_limit']) ? $options['rate_limit'] : 100;
        ?>
        <input type="number" 
               id="rate_limit" 
               name="<?php echo esc_attr($this->options_name); ?>[rate_limit]" 
               value="<?php echo esc_attr($rate_limit); ?>" 
               min="1" 
               max="1000">
        <p class="description">
            <?php _e('Maximum aantal API verzoeken per uur.', 'pk-elementor-widgets'); ?>
        </p>
        <?php
    }

    public function render_error_logging_field() {
        $options = get_option($this->options_name);
        $logging = isset($options['error_logging']) ? $options['error_logging'] : true;
        ?>
        <label>
            <input type="checkbox" 
                   id="error_logging" 
                   name="<?php echo esc_attr($this->options_name); ?>[error_logging]" 
                   <?php checked($logging); ?>>
            <?php _e('Activeer error logging', 'pk-elementor-widgets'); ?>
        </label>
        <p class="description">
            <?php _e('Log errors voor debugging doeleinden.', 'pk-elementor-widgets'); ?>
        </p>
        <?php
    }

    // Cache functies
    public function get_cached_response($prompt) {
        return wp_cache_get(md5($prompt), $this->cache_group);
    }

    public function cache_response($prompt, $response) {
        $options = get_option($this->options_name);
        $duration = isset($options['cache_duration']) ? $options['cache_duration'] * 60 : 3600;
        wp_cache_set(md5($prompt), $response, $this->cache_group, $duration);
    }

    // Rate limiting functies
    public function check_rate_limit() {
        $options = get_option($this->options_name);
        $limit = isset($options['rate_limit']) ? $options['rate_limit'] : 100;
        
        $current = get_transient($this->rate_limit_key);
        if ($current === false) {
            set_transient($this->rate_limit_key, 1, HOUR_IN_SECONDS);
            return true;
        }

        if ($current >= $limit) {
            return false;
        }

        set_transient($this->rate_limit_key, $current + 1, HOUR_IN_SECONDS);
        return true;
    }

    // Error logging functies
    public function log_error($message, $context = []) {
        $options = get_option($this->options_name);
        if (!isset($options['error_logging']) || !$options['error_logging']) {
            return;
        }

        $log_entry = [
            'timestamp' => current_time('mysql'),
            'message' => $message,
            'context' => $context,
        ];

        $logs = get_option('pk_elementor_ai_error_logs', []);
        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 100); // Keep only last 100 entries
        
        update_option('pk_elementor_ai_error_logs', $logs);
    }
}

// Initialize settings
PK_Elementor_AI_Settings::instance();
