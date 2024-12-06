<?php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

// Verzeker dat Elementor beschikbaar is voordat we verder gaan
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Blokkeer directe toegang
}

class PK_AI_Text_Widget extends Widget_Base {

    // Geef de widget een unieke naam
    public function get_name() {
        return 'pk_ai_text';
    }

    // Geef de widget een titel weer in Elementor
    public function get_title() {
        return __( 'AI Text Generator', 'pk-elementor-widgets' );
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
                'label' => __( 'Content', 'pk-elementor-widgets' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'prompt',
            [
                'label' => __( 'AI Prompt', 'pk-elementor-widgets' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'input_type' => 'text',
                'placeholder' => __( 'Voer je prompt in...', 'pk-elementor-widgets' ),
            ]
        );

        $this->end_controls_section();
    }

    // Render de inhoud op de frontend
    protected function render() {
        $settings = $this->get_settings_for_display();
        $prompt = $settings['prompt'];

        if ( ! empty( $prompt ) ) {
            $response = pk_get_ai_response( $prompt );
            echo '<div class="ai-generated-content">' . esc_html( $response ) . '</div>';
        }
    }
}