<?php
/**
 * Plugin Name: PK Elementor AI Custom
 * Description: Een custom AI-extensie voor Elementor die gebruik maakt van ChatGPT.
 * Version: 1.0
 * Author: Pieter Keuzenkamp
 * Company: Pieter Keuzenkamp Websites
 * Author URI: https://www.pieterkeuzenkamp.nl
 * Text Domain: pk-elementor-widgets
 * Requires Elementor: 3.0.0
 * Requires PHP: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Blokkeer directe toegang tot dit bestand voor beveiliging
}

add_action( 'admin_menu', 'eai_custom_add_admin_menu' );
add_action( 'admin_init', 'eai_custom_settings_init' );

function eai_custom_add_admin_menu() {
    add_options_page( 'Elementor AI Custom', 'Elementor AI Custom', 'manage_options', 'elementor_ai_custom', 'eai_custom_options_page' );
}

function eai_custom_settings_init() {
    register_setting( 'pluginPage', 'eai_custom_settings' );

    add_settings_section(
        'eai_custom_pluginPage_section',
        __( 'Algemene instellingen', 'eai-custom' ),
        'eai_custom_settings_section_callback',
        'pluginPage'
    );

    add_settings_field(
        'eai_custom_api_key',
        __( 'OpenAI API Key', 'eai-custom' ),
        'eai_custom_api_key_render',
        'pluginPage',
        'eai_custom_pluginPage_section'
    );
}

function eai_custom_settings_section_callback() {
    echo __( 'Configureer hier je Elementor AI Custom instellingen.', 'eai-custom' );
}

function eai_custom_api_key_render() {
    $options = get_option( 'eai_custom_settings' );
    $api_key = isset($options['eai_custom_api_key']) ? $options['eai_custom_api_key'] : '';
    ?>
    <input type='text' name='eai_custom_settings[eai_custom_api_key]' value='<?php echo esc_attr($api_key); ?>'>
    <?php
}

function eai_custom_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h2>Elementor AI Custom</h2>
        <?php
        settings_fields( 'pluginPage' );
        do_settings_sections( 'pluginPage' );
        submit_button();
        ?>
    </form>
    <?php
}

// Functie om de plugin te initialiseren
function eai_custom_init() {
    // Controleer of Elementor is geladen
    if ( did_action( 'elementor/loaded' ) ) {
        // Laad aangepaste widgets
        add_action( 'elementor/widgets/register', 'eai_custom_register_widgets' );
    }
}
add_action( 'plugins_loaded', 'eai_custom_init' );

function eai_custom_register_widgets( $widgets_manager ) {
    require_once( __DIR__ . '/widgets/ai-text-widget.php' ); // Laad de widget-class
    $widgets_manager->register( new \EAI_Custom_AI_Text_Widget() ); // Registreer de widget
}

function eai_custom_get_ai_response( $prompt ) {
    $options = get_option('eai_custom_settings');
    $api_key = isset($options['eai_custom_api_key']) ? $options['eai_custom_api_key'] : ''; // Haal de API-sleutel op
    $endpoint = 'https://api.openai.com/v1/chat/completions'; // Gebruik de juiste endpoint voor het nieuwe model
    $prompt = sanitize_text_field( $prompt );
    $args = [
        'body'    => json_encode( [
            'model'           => 'gpt-3.5-turbo', // Update naar het nieuwe model
            'messages'        => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens'      => 150,
            'temperature'     => 0.7,
        ] ),
        'headers' => [
            'Content-Type'    => 'application/json',
            'Authorization'   => 'Bearer ' . $api_key,
        ],
    ];
    $response = wp_remote_post( $endpoint, $args );

    if ( is_wp_error( $response ) ) {
        return 'Er is een fout opgetreden bij het ophalen van de AI-respons. Fout: ' . $response->get_error_message();
    }

    $body = wp_remote_retrieve_body( $response );
    
    // Log de volledige respons voor debugging
    error_log($body);

    $data = json_decode( $body, true );

    if ( isset( $data['choices'][0]['message']['content'] ) ) {
        return trim( $data['choices'][0]['message']['content'] );
    } else {
        return 'Geen geldige respons ontvangen van de AI. Controleer de API-instellingen en log voor meer informatie.';
    }
}
