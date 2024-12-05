<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if (!defined('ABSPATH')) {
    exit; // Blokkeer directe toegang
}

class PK_AI_Widget extends Widget_Base {
    public function get_name() {
        return 'pk_ai_widget';
    }

    public function get_title() {
        return __('AI Content Generator', 'pk-elementor-widgets');
    }

    public function get_icon() {
        return 'eicon-ai';
    }

    public function get_custom_help_url() {
        return 'https://www.pieterkeuzenkamp.nl';
    }

    public function get_categories() {
        return ['pkw-elements'];
    }

    public function get_style_depends() {
        return ['pk-elementor-ai'];
    }

    public function get_script_depends() {
        return ['pk-elementor-ai'];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'pk-elementor-widgets'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'ai_prompt',
            [
                'label' => __('AI Prompt', 'pk-elementor-widgets'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 4,
                'placeholder' => __('Voer hier je prompt in...', 'pk-elementor-widgets'),
                'description' => __('Beschrijf wat je wilt genereren. Wees zo specifiek mogelijk.', 'pk-elementor-widgets'),
            ]
        );

        $this->add_control(
            'max_tokens',
            [
                'label' => __('Maximum Tokens', 'pk-elementor-widgets'),
                'type' => Controls_Manager::NUMBER,
                'min' => 50,
                'max' => 2000,
                'step' => 50,
                'default' => 500,
                'description' => __('Hoeveel tokens maximaal gegenereerd mogen worden.', 'pk-elementor-widgets'),
            ]
        );

        $this->add_control(
            'show_regenerate',
            [
                'label' => __('Toon Regenereer Knop', 'pk-elementor-widgets'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'preview_mode',
            [
                'label' => __('Preview Mode', 'pk-elementor-widgets'),
                'type' => Controls_Manager::SELECT,
                'default' => 'live',
                'options' => [
                    'live' => __('Live Preview', 'pk-elementor-widgets'),
                    'button' => __('Generate Button', 'pk-elementor-widgets'),
                ],
            ]
        );

        $this->end_controls_section();

        // Advanced Section
        $this->start_controls_section(
            'advanced_section',
            [
                'label' => __('Geavanceerd', 'pk-elementor-widgets'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'temperature',
            [
                'label' => __('Creativiteit', 'pk-elementor-widgets'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range' => [
                    '' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'unit' => '',
                    'size' => 0.7,
                ],
                'description' => __('Hogere waarde = meer creativiteit, lagere waarde = meer focus.', 'pk-elementor-widgets'),
            ]
        );

        $this->add_control(
            'cache_duration',
            [
                'label' => __('Cache Duur (minuten)', 'pk-elementor-widgets'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1440,
                'step' => 1,
                'default' => 60,
                'description' => __('0 = geen cache', 'pk-elementor-widgets'),
            ]
        );

        $this->end_controls_section();

        // Style Section - Container
        $this->start_controls_section(
            'style_container_section',
            [
                'label' => __('Container', 'pk-elementor-widgets'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'container_background',
                'selector' => '{{WRAPPER}} .pk-ai-content',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'selector' => '{{WRAPPER}} .pk-ai-content',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'container_box_shadow',
                'selector' => '{{WRAPPER}} .pk-ai-content',
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label' => __('Padding', 'pk-elementor-widgets'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pk-ai-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_margin',
            [
                'label' => __('Margin', 'pk-elementor-widgets'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pk-ai-content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Content
        $this->start_controls_section(
            'style_content_section',
            [
                'label' => __('Content', 'pk-elementor-widgets'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .pk-ai-content',
            ]
        );

        $this->add_control(
            'content_color',
            [
                'label' => __('Text Color', 'pk-elementor-widgets'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pk-ai-content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Buttons
        $this->start_controls_section(
            'style_buttons_section',
            [
                'label' => __('Buttons', 'pk-elementor-widgets'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('button_styles');

        $this->start_controls_tab(
            'button_normal',
            [
                'label' => __('Normal', 'pk-elementor-widgets'),
            ]
        );

        $this->add_control(
            'button_background',
            [
                'label' => __('Background Color', 'pk-elementor-widgets'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pk-ai-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', 'pk-elementor-widgets'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pk-ai-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_hover',
            [
                'label' => __('Hover', 'pk-elementor-widgets'),
            ]
        );

        $this->add_control(
            'button_background_hover',
            [
                'label' => __('Background Color', 'pk-elementor-widgets'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pk-ai-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color_hover',
            [
                'label' => __('Text Color', 'pk-elementor-widgets'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pk-ai-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .pk-ai-button',
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .pk-ai-button',
            ]
        );

        $this->add_responsive_control(
            'button_border_radius',
            [
                'label' => __('Border Radius', 'pk-elementor-widgets'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pk-ai-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => __('Padding', 'pk-elementor-widgets'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pk-ai-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $processor = PK_Elementor_AI_Processor::instance();
        
        // Voeg widget wrapper toe
        ?>
        <div class="pk-ai-widget" data-preview-mode="<?php echo esc_attr($settings['preview_mode']); ?>">
            <?php if ($settings['preview_mode'] === 'button'): ?>
                <div class="pk-ai-prompt-container">
                    <textarea class="pk-ai-prompt-input" 
                              placeholder="<?php echo esc_attr__('Voer hier je prompt in...', 'pk-elementor-widgets'); ?>"
                              maxlength="500"><?php echo esc_textarea($settings['ai_prompt']); ?></textarea>
                    <div class="pk-ai-character-counter">500</div>
                </div>
                <button class="pk-ai-button pk-ai-generate">
                    <?php echo esc_html__('Genereer Content', 'pk-elementor-widgets'); ?>
                </button>
            <?php endif; ?>

            <div class="pk-ai-content">
                <?php 
                if (!empty($settings['ai_prompt'])) {
                    try {
                        $response = $processor->generate_content(
                            $settings['ai_prompt'],
                            $settings['max_tokens'],
                            $settings['preview_mode'] === 'live'
                        );
                        
                        if (is_array($response) && isset($response['request_id'])) {
                            echo '<div class="pk-ai-loading-animation">
                                    <div class="pk-ai-loading-spinner"></div>
                                    <div class="pk-ai-loading-text">' . 
                                    esc_html__('Content wordt gegenereerd...', 'pk-elementor-widgets') . 
                                    '</div>
                                  </div>';
                        } else {
                            echo wp_kses_post($response);
                        }
                    } catch (Exception $e) {
                        echo '<div class="pk-ai-error">' . esc_html($e->getMessage()) . '</div>';
                    }
                }
                ?>
            </div>

            <?php if ($settings['show_regenerate'] === 'yes'): ?>
                <button class="pk-ai-button pk-ai-regenerate">
                    <?php echo esc_html__('Regenereer Content', 'pk-elementor-widgets'); ?>
                </button>
            <?php endif; ?>
        </div>

        <?php
        // Voeg JavaScript variabelen toe
        wp_localize_script('pk-elementor-ai', 'pk_ai_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pk_ai_nonce'),
            'loading_text' => __('Content wordt gegenereerd...', 'pk-elementor-widgets'),
            'empty_prompt_error' => __('Voer eerst een prompt in.', 'pk-elementor-widgets'),
            'network_error' => __('Netwerkfout. Probeer het opnieuw.', 'pk-elementor-widgets'),
            'timeout_error' => __('Time-out bij het genereren van content.', 'pk-elementor-widgets'),
            'general_error' => __('Er is een fout opgetreden.', 'pk-elementor-widgets'),
        ]);
    }
}
