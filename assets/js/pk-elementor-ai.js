(function($) {
    'use strict';

    var PK_AI_Widget = {
        init: function() {
            this.bindEvents();
            this.initializeTooltips();
        },

        bindEvents: function() {
            $(document).on('click', '.pk-ai-regenerate', this.regenerateContent);
            $(document).on('input', '.pk-ai-prompt-input', this.handlePromptInput);
            $(document).on('click', '.pk-ai-generate', this.generateContent);
        },

        initializeTooltips: function() {
            $('.pk-ai-tooltip').tooltipster({
                theme: 'tooltipster-light',
                maxWidth: 300,
                animation: 'fade'
            });
        },

        generateContent: function(e) {
            e.preventDefault();
            var $widget = $(this).closest('.pk-ai-widget');
            var $content = $widget.find('.pk-ai-content');
            var $prompt = $widget.find('.pk-ai-prompt-input');
            
            if (!$prompt.val().trim()) {
                PK_AI_Widget.showError($widget, pk_ai_vars.empty_prompt_error);
                return;
            }

            PK_AI_Widget.startLoading($widget);

            $.ajax({
                url: pk_ai_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'pk_ai_generate',
                    nonce: pk_ai_vars.nonce,
                    prompt: $prompt.val(),
                    async: true
                },
                success: function(response) {
                    if (response.success && response.data.request_id) {
                        PK_AI_Widget.pollForResult($widget, response.data.request_id);
                    } else {
                        PK_AI_Widget.showError($widget, response.data.message || pk_ai_vars.general_error);
                    }
                },
                error: function() {
                    PK_AI_Widget.showError($widget, pk_ai_vars.network_error);
                }
            });
        },

        pollForResult: function($widget, requestId) {
            var pollInterval = setInterval(function() {
                $.ajax({
                    url: pk_ai_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pk_ai_check_status',
                        nonce: pk_ai_vars.nonce,
                        request_id: requestId
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data.status === 'completed') {
                                clearInterval(pollInterval);
                                PK_AI_Widget.updateContent($widget, response.data.content);
                            } else if (response.data.status === 'error') {
                                clearInterval(pollInterval);
                                PK_AI_Widget.showError($widget, response.data.message);
                            }
                        }
                    }
                });
            }, 2000);

            // Stop polling after 2 minutes
            setTimeout(function() {
                clearInterval(pollInterval);
                if ($widget.hasClass('pk-ai-loading')) {
                    PK_AI_Widget.showError($widget, pk_ai_vars.timeout_error);
                }
            }, 120000);
        },

        startLoading: function($widget) {
            $widget.addClass('pk-ai-loading');
            $widget.find('.pk-ai-error').slideUp();
            $widget.find('.pk-ai-content').html(
                '<div class="pk-ai-loading-animation">' +
                '<div class="pk-ai-loading-spinner"></div>' +
                '<div class="pk-ai-loading-text">' + pk_ai_vars.loading_text + '</div>' +
                '</div>'
            );
        },

        showError: function($widget, message) {
            $widget.removeClass('pk-ai-loading');
            var $error = $widget.find('.pk-ai-error');
            if ($error.length === 0) {
                $error = $('<div class="pk-ai-error"></div>').insertBefore($widget.find('.pk-ai-content'));
            }
            $error.html(message).slideDown();
        },

        updateContent: function($widget, content) {
            $widget.removeClass('pk-ai-loading');
            var $content = $widget.find('.pk-ai-content');
            
            // Fade out old content
            $content.fadeOut(200, function() {
                // Update and fade in new content
                $(this).html(content).fadeIn(200);
                
                // Initialize any new tooltips
                PK_AI_Widget.initializeTooltips();
                
                // Highlight code blocks if any
                if (typeof Prism !== 'undefined') {
                    Prism.highlightAllUnder($content[0]);
                }
            });
        },

        handlePromptInput: function() {
            var $input = $(this);
            var $widget = $input.closest('.pk-ai-widget');
            var $counter = $widget.find('.pk-ai-character-counter');
            var maxLength = parseInt($input.attr('maxlength') || 500);
            var remaining = maxLength - $input.val().length;
            
            $counter.text(remaining);
            
            if (remaining < 50) {
                $counter.addClass('pk-ai-counter-warning');
            } else {
                $counter.removeClass('pk-ai-counter-warning');
            }
        },

        regenerateContent: function(e) {
            e.preventDefault();
            var $widget = $(this).closest('.pk-ai-widget');
            var $content = $widget.find('.pk-ai-content');
            
            // Add loading state
            $content.addClass('pk-ai-loading');
            
            // Here you can add AJAX call to regenerate content
            // For now, we'll just remove loading state after 2 seconds
            setTimeout(function() {
                $content.removeClass('pk-ai-loading');
            }, 2000);
        }
    };

    $(window).on('elementor/frontend/init', function() {
        PK_AI_Widget.init();
    });

})(jQuery);
