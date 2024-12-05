<?php
if (!defined('ABSPATH')) {
    exit;
}

class PK_Elementor_AI_Processor {
    private static $_instance = null;
    private $settings;
    private $batch_queue = [];
    private $batch_size = 5;
    private $ip_rate_limits = [];

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->settings = PK_Elementor_AI_Settings::instance();
        add_action('init', [$this, 'init_async_handlers']);
        add_action('wp_ajax_pk_ai_process_async', [$this, 'handle_async_request']);
        add_action('shutdown', [$this, 'process_batch_queue']);
    }

    public function init_async_handlers() {
        if (!wp_next_scheduled('pk_ai_key_rotation')) {
            wp_schedule_event(time(), 'daily', 'pk_ai_key_rotation');
        }
        add_action('pk_ai_key_rotation', [$this, 'rotate_api_keys']);
    }

    public function generate_content($prompt, $max_tokens = 500, $async = false) {
        // IP-based rate limiting
        if (!$this->check_ip_rate_limit()) {
            throw new Exception(__('Te veel verzoeken vanaf dit IP-adres.', 'pk-elementor-widgets'));
        }

        // Sanitize input
        $prompt = $this->sanitize_prompt($prompt);
        $max_tokens = absint($max_tokens);

        if ($async) {
            return $this->queue_async_request($prompt, $max_tokens);
        }

        return $this->process_request($prompt, $max_tokens);
    }

    private function process_request($prompt, $max_tokens) {
        try {
            // Check cache first
            $cached = $this->settings->get_cached_response($prompt);
            if ($cached !== false) {
                $this->log_cache_hit();
                return $cached;
            }

            // Check if we should batch this request
            if (count($this->batch_queue) < $this->batch_size) {
                $this->batch_queue[] = [
                    'prompt' => $prompt,
                    'max_tokens' => $max_tokens
                ];
                return null;
            }

            // Process immediately if queue is full
            return $this->make_api_request($prompt, $max_tokens);
        } catch (Exception $e) {
            $this->settings->log_error('Processing error', [
                'error' => $e->getMessage(),
                'prompt' => $prompt
            ]);
            throw $e;
        }
    }

    private function make_api_request($prompt, $max_tokens) {
        $api_key = $this->get_active_api_key();
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $max_tokens,
                'temperature' => 0.7,
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['choices'][0]['message']['content'])) {
            throw new Exception(__('Ongeldig antwoord van API', 'pk-elementor-widgets'));
        }

        $content = wp_kses_post($body['choices'][0]['message']['content']);
        $this->settings->cache_response($prompt, $content);
        
        return $content;
    }

    private function queue_async_request($prompt, $max_tokens) {
        $request_id = wp_generate_uuid4();
        
        wp_schedule_single_event(time(), 'pk_ai_async_process', [
            'request_id' => $request_id,
            'prompt' => $prompt,
            'max_tokens' => $max_tokens
        ]);

        return [
            'request_id' => $request_id,
            'status' => 'queued'
        ];
    }

    public function handle_async_request() {
        check_ajax_referer('pk_ai_async_nonce', 'nonce');

        $request_id = sanitize_text_field($_POST['request_id']);
        $status = get_transient('pk_ai_request_' . $request_id);

        if ($status === false) {
            wp_send_json_error(__('Ongeldig verzoek ID', 'pk-elementor-widgets'));
        }

        wp_send_json_success($status);
    }

    public function process_batch_queue() {
        if (empty($this->batch_queue)) {
            return;
        }

        $responses = [];
        $api_key = $this->get_active_api_key();

        // Batch verwerking
        foreach (array_chunk($this->batch_queue, $this->batch_size) as $batch) {
            $messages = array_map(function($item) {
                return [
                    'role' => 'user',
                    'content' => $item['prompt']
                ];
            }, $batch);

            try {
                $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode([
                        'model' => 'gpt-3.5-turbo',
                        'messages' => $messages,
                    ]),
                    'timeout' => 60,
                ]);

                if (!is_wp_error($response)) {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (isset($body['choices'])) {
                        foreach ($body['choices'] as $index => $choice) {
                            $content = wp_kses_post($choice['message']['content']);
                            $this->settings->cache_response($batch[$index]['prompt'], $content);
                            $responses[] = $content;
                        }
                    }
                }
            } catch (Exception $e) {
                $this->settings->log_error('Batch processing error', [
                    'error' => $e->getMessage(),
                    'batch_size' => count($batch)
                ]);
            }
        }

        $this->batch_queue = [];
        return $responses;
    }

    private function check_ip_rate_limit() {
        $ip = $this->get_client_ip();
        $current_hour = date('Y-m-d H');
        
        if (!isset($this->ip_rate_limits[$ip])) {
            $this->ip_rate_limits[$ip] = [
                'count' => 0,
                'hour' => $current_hour
            ];
        }

        if ($this->ip_rate_limits[$ip]['hour'] !== $current_hour) {
            $this->ip_rate_limits[$ip] = [
                'count' => 0,
                'hour' => $current_hour
            ];
        }

        $this->ip_rate_limits[$ip]['count']++;
        
        return $this->ip_rate_limits[$ip]['count'] <= 100; // Maximum 100 requests per hour per IP
    }

    private function get_client_ip() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }

    private function sanitize_prompt($prompt) {
        // Basis sanitization
        $prompt = sanitize_text_field($prompt);
        
        // Verwijder potentieel gevaarlijke karakters
        $prompt = preg_replace('/[^\p{L}\p{N}\s\-_.,!?]/u', '', $prompt);
        
        // Maximum lengte
        $prompt = substr($prompt, 0, 500);
        
        return $prompt;
    }

    private function get_active_api_key() {
        $options = get_option('pk_elementor_ai_settings');
        $api_keys = isset($options['api_keys']) ? $options['api_keys'] : [];
        
        if (empty($api_keys)) {
            // Fallback naar oude enkele key
            return isset($options['api_key']) ? $options['api_key'] : '';
        }

        // Roteer door beschikbare keys
        $current_key_index = get_option('pk_ai_current_key_index', 0);
        $api_key = $api_keys[$current_key_index % count($api_keys)];
        
        return $api_key;
    }

    public function rotate_api_keys() {
        $options = get_option('pk_elementor_ai_settings');
        $api_keys = isset($options['api_keys']) ? $options['api_keys'] : [];
        
        if (count($api_keys) > 1) {
            $current_index = get_option('pk_ai_current_key_index', 0);
            update_option('pk_ai_current_key_index', ($current_index + 1) % count($api_keys));
        }
    }

    private function log_cache_hit() {
        $hits = get_option('pk_elementor_ai_cache_hits', 0);
        $total = get_option('pk_elementor_ai_total_requests', 0);
        
        update_option('pk_elementor_ai_cache_hits', $hits + 1);
        update_option('pk_elementor_ai_total_requests', $total + 1);
        
        $hit_rate = ($hits + 1) / ($total + 1) * 100;
        update_option('pk_elementor_ai_cache_hit_rate', $hit_rate);
    }
}
