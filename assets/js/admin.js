jQuery(document).ready(function($) {
    'use strict';
    
    // Sites Management
    var currentSiteId = null;
    
    // Open add site modal
    $('#uc-add-site-btn').on('click', function() {
        currentSiteId = null;
        $('#uc-site-modal-title').text('Add New Site');
        $('#uc-site-form')[0].reset();
        $('#uc-site-id').val('');
        $('#uc-password').prop('required', true);
        $('#uc-site-modal').fadeIn();
    });
    
    // Edit site
    $(document).on('click', '.uc-edit-site', function() {
        var siteId = $(this).data('id');
        currentSiteId = siteId;
        
        // Get site data from table row data attributes
        var $row = $(this).closest('tr');
        var siteName = $row.data('site-name');
        var siteUrl = $row.data('site-url');
        var username = $row.data('username');
        
        $('#uc-site-modal-title').text('Edit Site');
        $('#uc-site-id').val(siteId);
        $('#uc-site-name').val(siteName);
        $('#uc-site-url').val(siteUrl);
        $('#uc-username').val(username);
        $('#uc-password').val('').prop('required', false);
        $('#uc-site-modal').fadeIn();
    });
    
    // Delete site
    $(document).on('click', '.uc-delete-site', function() {
        if (!confirm(ucAdmin.strings.confirmDelete)) {
            return;
        }
        
        var siteId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: ucAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'uc_delete_site',
                nonce: ucAdmin.nonce,
                site_id: siteId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(function() {
                        $(this).remove();
                    });
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('An error occurred', 'error');
            }
        });
    });
    
    // Test connection
    $(document).on('click', '.uc-test-connection', function(e) {
        e.preventDefault();
        console.log('Test connection button clicked');
        
        var siteId = $(this).data('id');
        var $button = $(this);
        var originalText = $button.text();
        
        console.log('Testing site ID:', siteId);
        console.log('AJAX URL:', ucAdmin.ajaxUrl);
        console.log('Nonce:', ucAdmin.nonce);
        
        $button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: ucAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'uc_test_connection',
                nonce: ucAdmin.nonce,
                site_id: siteId
            },
            success: function(response) {
                console.log('Test response:', response);
                if (response.success) {
                    alert('Connection Test Successful!\n\n' + response.data.message + 
                          '\n\nDetails:\n' +
                          'Companion Plugin: ' + response.data.details.companion_status + '\n' +
                          'Authentication: ' + response.data.details.auth_status + '\n' +
                          'WordPress Version: ' + (response.data.details.wp_version || 'unknown'));
                } else {
                    alert('Connection Test Failed!\n\n' + response.data.message +
                          (response.data.details ? '\n\nDetails:\n' + JSON.stringify(response.data.details, null, 2) : ''));
                }
            },
            error: function(xhr, status, error) {
                console.log('Test error:', xhr, status, error);
                alert('Connection Test Error!\n\nFailed to connect to server: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Submit site form
    $('#uc-site-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: currentSiteId ? 'uc_update_site' : 'uc_add_site',
            nonce: ucAdmin.nonce,
            site_id: $('#uc-site-id').val(),
            site_name: $('#uc-site-name').val(),
            site_url: $('#uc-site-url').val(),
            username: $('#uc-username').val(),
            password: $('#uc-password').val()
        };
        
        $.ajax({
            url: ucAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $('#uc-site-modal').fadeOut();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('An error occurred', 'error');
            }
        });
    });
    
    // Plugins Management
    var currentPluginId = null;
    
    // Toggle source method (URL vs Upload)
    $(document).on('change', '#uc-source-method', function() {
        var method = $(this).val();
        if (method === 'upload') {
            $('#uc-source-url-fields').hide();
            $('#uc-source-upload-fields').show();
            $('#uc-update-source').prop('required', false);
        } else {
            $('#uc-source-url-fields').show();
            $('#uc-source-upload-fields').hide();
            $('#uc-update-source').prop('required', true);
        }
    });
    
    // Change file button
    $(document).on('click', '#uc-change-file', function() {
        $('#uc-plugin-file').click();
    });
    
    // Open add plugin modal
    $('#uc-add-plugin-btn').on('click', function() {
        currentPluginId = null;
        $('#uc-plugin-modal-title').text('Add Plugin Configuration');
        $('#uc-plugin-form')[0].reset();
        $('#uc-plugin-id').val('');
        $('#uc-source-method').val('url').trigger('change');
        $('#uc-current-file-info').hide();
        $('#uc-auto-update').prop('checked', true);
        $('#uc-plugin-modal').fadeIn();
    });
    
    // Edit plugin
    $(document).on('click', '.uc-edit-plugin', function() {
        var pluginId = $(this).data('id');
        currentPluginId = pluginId;
        
        // Get plugin data from table row data attributes
        var $row = $(this).closest('tr');
        var siteId = $row.data('site-id');
        var pluginName = $row.data('plugin-name');
        var pluginSlug = $row.data('plugin-slug');
        var updateSource = $row.data('update-source');
        var sourceType = $row.data('source-type');
        var autoUpdate = $row.data('auto-update') == 1;
        
        $('#uc-plugin-modal-title').text('Edit Plugin Configuration');
        $('#uc-plugin-id').val(pluginId);
        $('#uc-plugin-site').val(siteId);
        $('#uc-plugin-name').val(pluginName);
        $('#uc-plugin-slug').val(pluginSlug);
        
        // Check if it's a file upload (local:// prefix) or URL
        if (updateSource && updateSource.startsWith('local://')) {
            $('#uc-source-method').val('upload').trigger('change');
            var fileName = updateSource.replace('local://', '');
            $('#uc-current-file-name').text(fileName);
            $('#uc-current-file-info').show();
        } else {
            $('#uc-source-method').val('url').trigger('change');
            $('#uc-update-source').val(updateSource);
            $('#uc-source-type').val(sourceType);
        }
        
        $('#uc-auto-update').prop('checked', autoUpdate);
        $('#uc-plugin-modal').fadeIn();
    });
    
    // Delete plugin
    $(document).on('click', '.uc-delete-plugin', function() {
        if (!confirm(ucAdmin.strings.confirmDelete)) {
            return;
        }
        
        var pluginId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: ucAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'uc_delete_plugin',
                nonce: ucAdmin.nonce,
                plugin_id: pluginId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(function() {
                        $(this).remove();
                    });
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('An error occurred', 'error');
            }
        });
    });
    
    // Submit plugin form
    $('#uc-plugin-form').on('submit', function(e) {
        e.preventDefault();
        
        var sourceMethod = $('#uc-source-method').val();
        var formData;
        var ajaxSettings;
        
        if (sourceMethod === 'upload') {
            // File upload method
            var fileInput = $('#uc-plugin-file')[0];
            if (!currentPluginId && (!fileInput.files || !fileInput.files[0])) {
                showNotice('Please select a ZIP file to upload', 'error');
                return;
            }
            
            formData = new FormData();
            formData.append('action', currentPluginId ? 'uc_update_plugin' : 'uc_add_plugin');
            formData.append('nonce', ucAdmin.nonce);
            formData.append('plugin_id', $('#uc-plugin-id').val());
            formData.append('site_id', $('#uc-plugin-site').val());
            formData.append('plugin_name', $('#uc-plugin-name').val());
            formData.append('plugin_slug', $('#uc-plugin-slug').val());
            formData.append('source_type', 'upload');
            formData.append('auto_update', $('#uc-auto-update').is(':checked') ? 1 : 0);
            
            if (fileInput.files && fileInput.files[0]) {
                formData.append('plugin_file', fileInput.files[0]);
            }
            
            ajaxSettings = {
                url: ucAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            };
        } else {
            // URL method
            formData = {
                action: currentPluginId ? 'uc_update_plugin' : 'uc_add_plugin',
                nonce: ucAdmin.nonce,
                plugin_id: $('#uc-plugin-id').val(),
                site_id: $('#uc-plugin-site').val(),
                plugin_name: $('#uc-plugin-name').val(),
                plugin_slug: $('#uc-plugin-slug').val(),
                update_source: $('#uc-update-source').val(),
                source_type: $('#uc-source-type').val(),
                auto_update: $('#uc-auto-update').is(':checked') ? 1 : 0
            };
            
            ajaxSettings = {
                url: ucAdmin.ajaxUrl,
                type: 'POST',
                data: formData
            };
        }
        
        ajaxSettings.success = function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                $('#uc-plugin-modal').fadeOut();
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showNotice(response.data.message, 'error');
            }
        };
        
        ajaxSettings.error = function() {
            showNotice('An error occurred', 'error');
        };
        
        $.ajax(ajaxSettings);
    });
    
    // Run update
    $(document).on('click', '.uc-run-update', function() {
        var pluginId = $(this).data('id');
        
        $('#uc-update-progress').html('<p>Updating plugin... <span class="uc-loading"></span></p>');
        $('#uc-update-modal').fadeIn();
        
        $.ajax({
            url: ucAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'uc_run_update',
                nonce: ucAdmin.nonce,
                plugin_id: pluginId
            },
            success: function(response) {
                if (response.success) {
                    $('#uc-update-progress').html('<p class="uc-notice uc-notice-success">' + response.data.message + '</p>');
                    setTimeout(function() {
                        $('#uc-update-modal').fadeOut();
                        location.reload();
                    }, 2000);
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                    $('#uc-update-progress').html('<p class="uc-notice uc-notice-error">' + errorMessage + '</p>');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Connection error: ' + (error || status || 'Unknown error');
                if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                    } catch(e) {
                        // If not JSON, show status text
                        errorMessage = xhr.statusText || errorMessage;
                    }
                }
                $('#uc-update-progress').html('<p class="uc-notice uc-notice-error">' + errorMessage + '</p>');
            }
        });
    });
    
    // Close modal
    $('.uc-modal-close').on('click', function() {
        $(this).closest('.uc-modal').fadeOut();
    });
    
    // Close modal on outside click
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('uc-modal')) {
            $('.uc-modal').fadeOut();
        }
    });
    
    // Show notice
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});
