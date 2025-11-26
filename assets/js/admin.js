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
        
        var siteId = $(this).data('id');
        var $button = $(this);
        var originalText = $button.text();
        
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
                if (response.success) {
                    var details = response.data.details;
                    var message = 'Connection Test Successful!\n\n' + response.data.message + 
                          '\n\nDetails:\n' +
                          'Companion Plugin: ' + details.companion_status + '\n' +
                          'Remote Version: v' + details.companion_version + '\n' +
                          'Local Version: v' + details.local_companion_version + '\n' +
                          'Authentication: ' + details.auth_status + '\n' +
                          'WordPress Version: ' + (details.wp_version || 'unknown');
                    
                    // Check if companion plugin needs update
                    if (details.companion_needs_update) {
                        var updateConfirm = confirm(
                            message + '\n\n' +
                            '⚠️ WARNING: Companion plugin version mismatch detected!\n' +
                            'Remote site has v' + details.companion_version + ' but server has v' + details.local_companion_version + '\n\n' +
                            'Do you want to update the companion plugin on the remote site?'
                        );
                        
                        if (updateConfirm) {
                            // Update companion plugin
                            updateCompanionPlugin(siteId, $button, originalText);
                            return;
                        }
                    } else {
                        alert(message);
                    }
                } else {
                    alert('Connection Test Failed!\n\n' + response.data.message +
                          (response.data.details ? '\n\nDetails:\n' + JSON.stringify(response.data.details, null, 2) : ''));
                }
            },
            error: function(xhr, status, error) {
                alert('Connection Test Error!\n\nFailed to connect to server: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Update companion plugin on remote site
    function updateCompanionPlugin(siteId, $button, originalText) {
        $button.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: ucAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'uc_update_companion',
                nonce: ucAdmin.nonce,
                site_id: siteId
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ Companion Plugin Updated Successfully!\n\n' + response.data.message);
                } else {
                    alert('Companion Plugin Update Failed!\n\n' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Update Error!\n\nFailed to update companion plugin: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
    
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
    
    // Toggle source method (URL vs Package)
    function toggleSourceMethod() {
        var method = $('#uc-source-method').val();
        if (method === 'package') {
            $('#uc-source-url-fields').hide();
            $('#uc-source-package-fields').show();
            $('#uc-update-source').prop('required', false);
        } else {
            $('#uc-source-url-fields').show();
            $('#uc-source-package-fields').hide();
            $('#uc-update-source').prop('required', true);
        }
    }
    
    // Use both direct binding and event delegation for source method toggle
    $('#uc-source-method').on('change', function() {
        toggleSourceMethod();
    });
    
    $(document).on('change', '#uc-source-method', function() {
        toggleSourceMethod();
    });
    
    // When package is selected, update hidden URL field
    $(document).on('change', '#uc-package-select', function() {
        var packageUrl = $(this).val();
        if (packageUrl) {
            $('#uc-update-source').val(packageUrl);
            $('#uc-source-type').val('local');
        }
    });
    
    // Open add plugin modal
    $('#uc-add-plugin-btn').on('click', function() {
        currentPluginId = null;
        $('#uc-plugin-modal-title').text('Add Plugin Configuration');
        $('#uc-plugin-form')[0].reset();
        $('#uc-plugin-id').val('');
        $('#uc-source-method').val('url');
        toggleSourceMethod();
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
        
        // Check if source is a local package (check if URL matches any package)
        var $packageOption = $('#uc-package-select option[value="' + updateSource + '"]');
        if ($packageOption.length > 0 || sourceType === 'local') {
            $('#uc-source-method').val('package');
            $('#uc-package-select').val(updateSource);
        } else {
            $('#uc-source-method').val('url');
            $('#uc-update-source').val(updateSource);
            $('#uc-source-type').val(sourceType);
        }
        toggleSourceMethod();
        
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
        var updateSource, sourceType;
        
        if (sourceMethod === 'package') {
            updateSource = $('#uc-package-select').val();
            sourceType = 'local';
            
            if (!updateSource) {
                showNotice('Please select an update package', 'error');
                return;
            }
        } else {
            updateSource = $('#uc-update-source').val();
            sourceType = $('#uc-source-type').val();
            
            if (!updateSource) {
                showNotice('Please enter an update source URL', 'error');
                return;
            }
        }
        
        var formData = {
            action: currentPluginId ? 'uc_update_plugin' : 'uc_add_plugin',
            nonce: ucAdmin.nonce,
            plugin_id: $('#uc-plugin-id').val(),
            site_id: $('#uc-plugin-site').val(),
            plugin_name: $('#uc-plugin-name').val(),
            plugin_slug: $('#uc-plugin-slug').val(),
            update_source: updateSource,
            source_type: sourceType,
            auto_update: $('#uc-auto-update').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: ucAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $('#uc-plugin-modal').fadeOut();
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
    
    // ============================================
    // Updates Management
    // ============================================
    
    // Open add update package modal - use event delegation for reliability
    $(document).on('click', '#uc-add-update-btn', function(e) {
        e.preventDefault();
        console.log('Upload New Package button clicked');
        var $form = $('#uc-update-package-form');
        if ($form.length) {
            $form[0].reset();
        }
        $('#uc-upload-progress').hide();
        $('#uc-upload-btn').prop('disabled', false);
        $('#uc-update-package-modal').fadeIn();
    });
    
    // Submit update package form
    $('#uc-update-package-form').on('submit', function(e) {
        e.preventDefault();
        
        var fileInput = $('#uc-update-file')[0];
        if (!fileInput.files || !fileInput.files[0]) {
            showNotice('Please select a ZIP file to upload', 'error');
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'uc_add_update_package');
        formData.append('nonce', ucAdmin.nonce);
        formData.append('package_name', $('#uc-package-name').val());
        formData.append('version', $('#uc-package-version').val());
        formData.append('update_file', fileInput.files[0]);
        
        $('#uc-upload-progress').show();
        $('#uc-upload-btn').prop('disabled', true);
        
        $.ajax({
            url: ucAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $('#uc-update-package-modal').fadeOut();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice(response.data.message, 'error');
                    $('#uc-upload-progress').hide();
                    $('#uc-upload-btn').prop('disabled', false);
                }
            },
            error: function() {
                showNotice('An error occurred during upload', 'error');
                $('#uc-upload-progress').hide();
                $('#uc-upload-btn').prop('disabled', false);
            }
        });
    });
    
    // Delete update package
    $(document).on('click', '.uc-delete-update', function() {
        if (!confirm(ucAdmin.strings.confirmDelete)) {
            return;
        }
        
        var updateId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: ucAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'uc_delete_update_package',
                nonce: ucAdmin.nonce,
                update_id: updateId
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
    
    // Copy URL button
    $(document).on('click', '.uc-copy-btn', function() {
        var url = $(this).data('url');
        var $button = $(this);
        
        // Create temporary input
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(url).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Update button text temporarily
        var originalText = $button.text();
        $button.text('Copied!');
        setTimeout(function() {
            $button.text(originalText);
        }, 1500);
    });
});
