/**
 * WooCommerce Role Permission Health Check - Admin JavaScript
 * 
 * Handles AJAX interactions and UI updates for the admin interface.
 * 
 * @package WC_Role_Permission_Health_Check
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Main plugin object
    var WCRPHC = {
        
        /**
         * Initialize the plugin
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Health Check
            $('#run-health-check').on('click', this.runHealthCheck);
            
            // Apply Fixes
            $('#run-health-fix').on('click', this.applyFixes);
            
            // Nuclear Repair
            $('#nuclear-repair').on('click', this.nuclearRepair);
            
            // Export System Info
            $('#export-system-info').on('click', this.exportSystemInfo);
            
            // Create Emergency User
            $('#create-emergency-user').on('click', this.createEmergencyUser);
            
            // Auto-refresh system status
            this.autoRefreshStatus();
        },
        
        /**
         * Run health check
         */
        runHealthCheck: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var resultsDiv = $('#health-check-results');
            var contentDiv = $('#results-content');
            
            button.prop('disabled', true).text(wcRphc.strings.checking);
            resultsDiv.show();
            contentDiv.html('<p>' + wcRphc.strings.checking + '</p>');
            
            $.ajax({
                url: wcRphc.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_rphc_health_check',
                    nonce: wcRphc.nonce
                },
                success: function(response) {
                    if (response.success) {
                        contentDiv.html(response.data.html);
                        if (response.data.has_issues) {
                            $('#run-health-fix').prop('disabled', false);
                        }
                    } else {
                        contentDiv.html('<p class="health-issue critical">Error: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    contentDiv.html('<p class="health-issue critical">AJAX error occurred</p>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Run Health Check');
                }
            });
        },
        
        /**
         * Apply fixes
         */
        applyFixes: function(e) {
            e.preventDefault();
            
            if (!confirm(wcRphc.strings.confirm_fixes)) {
                return;
            }
            
            var button = $(this);
            var resultsDiv = $('#health-fix-results');
            var contentDiv = $('#fix-results-content');
            
            button.prop('disabled', true).text(wcRphc.strings.fixing);
            resultsDiv.show();
            contentDiv.html('<p>Applying fixes...</p>');
            
            $.ajax({
                url: wcRphc.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_rphc_health_fix',
                    nonce: wcRphc.nonce
                },
                success: function(response) {
                    if (response.success) {
                        contentDiv.html(response.data.html);
                        // Re-run health check after a short delay
                        setTimeout(function() {
                            $('#run-health-check').trigger('click');
                        }, 1000);
                    } else {
                        contentDiv.html('<p class="health-issue critical">Error: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    contentDiv.html('<p class="health-issue critical">AJAX error occurred</p>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Apply Fixes');
                }
            });
        },
        
        /**
         * Nuclear repair
         */
        nuclearRepair: function(e) {
            e.preventDefault();
            
            if (!confirm(wcRphc.strings.confirm_nuclear)) {
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true).text('Performing Nuclear Repair...');
            
            $.ajax({
                url: wcRphc.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_rphc_nuclear_repair',
                    nonce: wcRphc.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Nuclear repair completed successfully! Please refresh the page.');
                        location.reload();
                    } else {
                        alert('Nuclear repair failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX error occurred during nuclear repair');
                },
                complete: function() {
                    button.prop('disabled', false).text('Nuclear Repair');
                }
            });
        },
        
        /**
         * Export system info
         */
        exportSystemInfo: function(e) {
            e.preventDefault();
            
            var button = $(this);
            button.prop('disabled', true).text('Exporting...');
            
            $.ajax({
                url: wcRphc.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_rphc_export_system_info',
                    nonce: wcRphc.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        var blob = new Blob([JSON.stringify(response.data, null, 2)], {type: 'application/json'});
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'wc-role-health-system-info.json';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    } else {
                        alert('Export failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX error occurred during export');
                },
                complete: function() {
                    button.prop('disabled', false).text('Export System Info');
                }
            });
        },
        
        /**
         * Create emergency user
         */
        createEmergencyUser: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to create an emergency admin user?')) {
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true).text('Creating...');
            
            // This would need to be implemented as an AJAX endpoint
            alert('Emergency user creation feature will be implemented in a future version.');
            button.prop('disabled', false).text('Create Emergency User');
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                var $element = $(this);
                var tooltipText = $element.data('tooltip');
                
                $element.on('mouseenter', function() {
                    $('<div class="wc-rphc-tooltip">' + tooltipText + '</div>')
                        .appendTo('body')
                        .css({
                            position: 'absolute',
                            top: $element.offset().top - 30,
                            left: $element.offset().left,
                            background: '#333',
                            color: '#fff',
                            padding: '5px 10px',
                            borderRadius: '3px',
                            fontSize: '12px',
                            zIndex: 9999
                        });
                }).on('mouseleave', function() {
                    $('.wc-rphc-tooltip').remove();
                });
            });
        },
        
        /**
         * Auto-refresh system status
         */
        autoRefreshStatus: function() {
            // Refresh system status every 30 seconds
            setInterval(function() {
                // This could be enhanced to update the status table via AJAX
            }, 30000);
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="wc-rphc-notification wc-rphc-notification-' + type + '">' + message + '</div>');
            
            $notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '10px 15px',
                borderRadius: '3px',
                color: '#fff',
                zIndex: 9999,
                maxWidth: '300px'
            });
            
            if (type === 'success') {
                $notification.css('background', '#46b450');
            } else if (type === 'error') {
                $notification.css('background', '#dc3232');
            } else if (type === 'warning') {
                $notification.css('background', '#ffb900');
            } else {
                $notification.css('background', '#0073aa');
            }
            
            $('body').append($notification);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Format file size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        /**
         * Format date
         */
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        WCRPHC.init();
    });
    
    // Make WCRPHC available globally
    window.WCRPHC = WCRPHC;
    
})(jQuery);
