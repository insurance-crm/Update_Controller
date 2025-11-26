<div class="wrap">
    <h1><?php echo esc_html__('Companion Plugin Check', 'update-controller'); ?></h1>
    <p class="description"><?php echo esc_html__('Compare companion plugin versions between server and remote sites.', 'update-controller'); ?></p>
    
    <div class="uc-companion-info" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
        <h3 style="margin-top: 0;"><?php echo esc_html__('Server Companion Plugin', 'update-controller'); ?></h3>
        <table class="form-table">
            <tr>
                <th><?php echo esc_html__('Version', 'update-controller'); ?></th>
                <td><strong style="font-size: 16px; color: #0073aa;">v<?php echo esc_html($local_version); ?></strong></td>
            </tr>
            <tr>
                <th><?php echo esc_html__('File Size', 'update-controller'); ?></th>
                <td><?php echo esc_html(size_format($local_size)); ?> (<?php echo esc_html(number_format($local_size)); ?> bytes)</td>
            </tr>
        </table>
    </div>
    
    <h2><?php echo esc_html__('Remote Sites Companion Status', 'update-controller'); ?></h2>
    
    <div class="uc-actions" style="margin-bottom: 20px;">
        <button type="button" class="button button-primary" id="uc-check-all-companions">
            <?php echo esc_html__('Check All Sites', 'update-controller'); ?>
        </button>
    </div>
    
    <table class="wp-list-table widefat fixed striped" id="uc-companion-table">
        <thead>
            <tr>
                <th style="width: 200px;"><?php echo esc_html__('Site Name', 'update-controller'); ?></th>
                <th style="width: 120px;"><?php echo esc_html__('Remote Version', 'update-controller'); ?></th>
                <th style="width: 120px;"><?php echo esc_html__('Server Version', 'update-controller'); ?></th>
                <th style="width: 120px;"><?php echo esc_html__('Remote Size', 'update-controller'); ?></th>
                <th style="width: 120px;"><?php echo esc_html__('Server Size', 'update-controller'); ?></th>
                <th style="width: 100px;"><?php echo esc_html__('Status', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Actions', 'update-controller'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sites)) : ?>
                <?php foreach ($sites as $site) : ?>
                    <tr data-site-id="<?php echo esc_attr($site->id); ?>">
                        <td>
                            <strong><?php echo esc_html($site->site_name); ?></strong>
                            <br><small><a href="<?php echo esc_url($site->site_url); ?>" target="_blank"><?php echo esc_html($site->site_url); ?></a></small>
                        </td>
                        <td class="remote-version">-</td>
                        <td class="local-version">v<?php echo esc_html($local_version); ?></td>
                        <td class="remote-size">-</td>
                        <td class="local-size"><?php echo esc_html(size_format($local_size)); ?></td>
                        <td class="companion-status">
                            <span class="uc-status uc-status-pending"><?php echo esc_html__('Pending', 'update-controller'); ?></span>
                        </td>
                        <td class="companion-actions">
                            <button type="button" class="button button-small uc-check-companion" data-site-id="<?php echo esc_attr($site->id); ?>">
                                <?php echo esc_html__('Check', 'update-controller'); ?>
                            </button>
                            <button type="button" class="button button-small button-primary uc-update-companion-btn" data-site-id="<?php echo esc_attr($site->id); ?>" style="display: none;">
                                <?php echo esc_html__('Update', 'update-controller'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7"><?php echo esc_html__('No sites found. Add a site first.', 'update-controller'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.uc-status-pending { background: #f0f0f1; color: #50575e; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
.uc-status-ok { background: #d4edda; color: #155724; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
.uc-status-outdated { background: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
.uc-status-error { background: #f8d7da; color: #721c24; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
.uc-status-size_mismatch { background: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 3px; font-size: 12px; }
.version-match { color: #155724; }
.version-mismatch { color: #721c24; font-weight: bold; }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Check single site companion
    $(document).on('click', '.uc-check-companion', function() {
        var $button = $(this);
        var siteId = $button.data('site-id');
        var $row = $button.closest('tr');
        
        $button.prop('disabled', true).text('<?php echo esc_js(__('Checking...', 'update-controller')); ?>');
        $row.find('.companion-status').html('<span class="uc-status uc-status-pending"><?php echo esc_js(__('Checking...', 'update-controller')); ?></span>');
        
        checkCompanion(siteId, $row, $button);
    });
    
    // Check all sites
    $('#uc-check-all-companions').on('click', function() {
        var $mainButton = $(this);
        $mainButton.prop('disabled', true).text('<?php echo esc_js(__('Checking...', 'update-controller')); ?>');
        
        var $rows = $('#uc-companion-table tbody tr[data-site-id]');
        var total = $rows.length;
        var completed = 0;
        
        $rows.each(function(index) {
            var $row = $(this);
            var siteId = $row.data('site-id');
            var $button = $row.find('.uc-check-companion');
            
            $button.prop('disabled', true).text('<?php echo esc_js(__('Checking...', 'update-controller')); ?>');
            $row.find('.companion-status').html('<span class="uc-status uc-status-pending"><?php echo esc_js(__('Checking...', 'update-controller')); ?></span>');
            
            // Delay each request slightly to avoid overwhelming the server
            setTimeout(function() {
                checkCompanion(siteId, $row, $button, function() {
                    completed++;
                    if (completed >= total) {
                        $mainButton.prop('disabled', false).text('<?php echo esc_js(__('Check All Sites', 'update-controller')); ?>');
                    }
                });
            }, index * 500);
        });
    });
    
    // Update companion on a site
    $(document).on('click', '.uc-update-companion-btn', function() {
        var $button = $(this);
        var siteId = $button.data('site-id');
        var $row = $button.closest('tr');
        
        if (!confirm('<?php echo esc_js(__('Update companion plugin on this site?', 'update-controller')); ?>')) {
            return;
        }
        
        $button.prop('disabled', true).text('<?php echo esc_js(__('Updating...', 'update-controller')); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'uc_update_companion',
                nonce: ucAdmin.nonce,
                site_id: siteId
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Companion plugin updated successfully!', 'update-controller')); ?>');
                    // Re-check the companion
                    var $checkBtn = $row.find('.uc-check-companion');
                    $checkBtn.trigger('click');
                } else {
                    alert('<?php echo esc_js(__('Update failed: ', 'update-controller')); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Update request failed', 'update-controller')); ?>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php echo esc_js(__('Update', 'update-controller')); ?>');
            }
        });
    });
    
    function checkCompanion(siteId, $row, $button, callback) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'uc_check_companion',
                nonce: ucAdmin.nonce,
                site_id: siteId
            },
            success: function(response) {
                var data = response.data;
                
                if (response.success) {
                    // Update version columns
                    var versionClass = data.needs_update ? 'version-mismatch' : 'version-match';
                    $row.find('.remote-version').html('<span class="' + versionClass + '">v' + data.remote_version + '</span>');
                    
                    // Show remote size - if 0, show "N/A (old version)"
                    var remoteSizeText = data.remote_size > 0 ? formatBytes(data.remote_size) : 'N/A (old version)';
                    $row.find('.remote-size').text(remoteSizeText);
                    
                    // Update status
                    var statusHtml = '';
                    var $updateBtn = $row.find('.uc-update-companion-btn');
                    
                    if (data.status === 'ok') {
                        statusHtml = '<span class="uc-status uc-status-ok"><?php echo esc_js(__('Up to date', 'update-controller')); ?></span>';
                        $updateBtn.hide();
                    } else if (data.status === 'outdated') {
                        statusHtml = '<span class="uc-status uc-status-outdated"><?php echo esc_js(__('Outdated', 'update-controller')); ?></span>';
                        $updateBtn.show();
                    } else if (data.status === 'size_mismatch') {
                        statusHtml = '<span class="uc-status uc-status-size_mismatch"><?php echo esc_js(__('Size Mismatch', 'update-controller')); ?></span>';
                        $updateBtn.show();
                    }
                    
                    $row.find('.companion-status').html(statusHtml);
                } else {
                    $row.find('.remote-version').html('<span class="version-mismatch">' + (data.remote_version || 'N/A') + '</span>');
                    $row.find('.remote-size').text(data.remote_size ? formatBytes(data.remote_size) : '-');
                    
                    var statusHtml = '';
                    if (data.status === 'not_installed') {
                        statusHtml = '<span class="uc-status uc-status-error"><?php echo esc_js(__('Not Installed', 'update-controller')); ?></span>';
                    } else {
                        statusHtml = '<span class="uc-status uc-status-error"><?php echo esc_js(__('Error', 'update-controller')); ?></span>';
                    }
                    $row.find('.companion-status').html(statusHtml);
                    $row.find('.uc-update-companion-btn').hide();
                }
            },
            error: function() {
                $row.find('.remote-version').text('Error');
                $row.find('.remote-size').text('-');
                $row.find('.companion-status').html('<span class="uc-status uc-status-error"><?php echo esc_js(__('Error', 'update-controller')); ?></span>');
                $row.find('.uc-update-companion-btn').hide();
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php echo esc_js(__('Check', 'update-controller')); ?>');
                if (typeof callback === 'function') {
                    callback();
                }
            }
        });
    }
    
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>
