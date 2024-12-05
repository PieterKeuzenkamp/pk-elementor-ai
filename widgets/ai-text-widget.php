<?php

// Verzeker dat Elementor beschikbaar is voordat we verder gaan
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Blokkeer directe toegang
}

class EAI_Custom_AI_Text_Widget extends \Elementor\Widget_Base {

    // Geef de widget een unieke naam
    public function get_name() {
        return 'eai_custom_ai_text';
    }

    // Geef de widget een titel weer in Elementor
    public function get_title() {
        return __( 'AI Text Generator', 'eai-custom' );
    }

    // Specificeer een icoon voor de widget
    public function get_icon() {
        return 'eicon-text';
    }

    // Koppel de widget aan een categorie binnen Elementor
    public function get_categories() {
        return [ 'general' ];
    }

    // Registreer de bedieningsopties die Elementor biedt
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Content', 'eai-custom' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'prompt',
            [
                'label' => __( 'AI Prompt', 'eai-custom' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'input_type' => 'text',
                'placeholder' => __( 'Voer je prompt in...', 'eai-custom' ),
            ]
        );

        $this->end_controls_section();
    }

    // Render de inhoud op de frontend
    protected function render() {
        $settings = $this->get_settings_for_display();
        $prompt = $settings['prompt'];

        if ( ! empty( $prompt ) ) {
            $response = eai_custom_get_ai_response( $prompt );
            echo '<div class="ai-generated-content">' . esc_html( $response ) . '</div>';
        }
    }
}