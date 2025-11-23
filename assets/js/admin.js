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
        
        // Get site data from table row
        var $row = $(this).closest('tr');
        var siteName = $row.find('td:eq(0)').text();
        var siteUrl = $row.find('td:eq(1) a').attr('href');
        var username = $row.find('td:eq(2)').text();
        
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
    
    // Open add plugin modal
    $('#uc-add-plugin-btn').on('click', function() {
        currentPluginId = null;
        $('#uc-plugin-modal-title').text('Add Plugin Configuration');
        $('#uc-plugin-form')[0].reset();
        $('#uc-plugin-id').val('');
        $('#uc-auto-update').prop('checked', true);
        $('#uc-plugin-modal').fadeIn();
    });
    
    // Edit plugin
    $(document).on('click', '.uc-edit-plugin', function() {
        var pluginId = $(this).data('id');
        currentPluginId = pluginId;
        
        // Get plugin data from table row
        var $row = $(this).closest('tr');
        var pluginName = $row.find('td:eq(1)').text();
        var pluginSlug = $row.find('td:eq(2) code').text();
        var updateSource = $row.find('td:eq(3) a').attr('href');
        var sourceType = $row.find('td:eq(4) .uc-badge').text().trim();
        var autoUpdate = $row.find('td:eq(5)').text().trim() === 'Yes';
        
        $('#uc-plugin-modal-title').text('Edit Plugin Configuration');
        $('#uc-plugin-id').val(pluginId);
        $('#uc-plugin-name').val(pluginName);
        $('#uc-plugin-slug').val(pluginSlug);
        $('#uc-update-source').val(updateSource);
        $('#uc-source-type').val(sourceType);
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
        
        var formData = {
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
                    $('#uc-update-progress').html('<p class="uc-notice uc-notice-error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#uc-update-progress').html('<p class="uc-notice uc-notice-error">An error occurred</p>');
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
