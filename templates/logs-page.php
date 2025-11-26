<div class="wrap">
    <h1><?php echo esc_html__('Update Logs', 'update-controller'); ?></h1>
    <p class="description"><?php echo esc_html__('View the history of all plugin updates performed by Update Controller.', 'update-controller'); ?></p>
    
    <?php if (!empty($sites)) : ?>
    <div class="uc-filter-bar" style="margin: 20px 0;">
        <label for="uc-filter-site"><?php echo esc_html__('Filter by Site:', 'update-controller'); ?></label>
        <select id="uc-filter-site" onchange="filterLogs(this.value)">
            <option value=""><?php echo esc_html__('All Sites', 'update-controller'); ?></option>
            <?php foreach ($sites as $site) : ?>
                <option value="<?php echo esc_attr($site->id); ?>"><?php echo esc_html($site->site_name); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 180px;"><?php echo esc_html__('Date/Time', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Site', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Plugin', 'update-controller'); ?></th>
                <th style="width: 100px;"><?php echo esc_html__('From Version', 'update-controller'); ?></th>
                <th style="width: 100px;"><?php echo esc_html__('To Version', 'update-controller'); ?></th>
                <th style="width: 80px;"><?php echo esc_html__('Status', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Message', 'update-controller'); ?></th>
                <th style="width: 120px;"><?php echo esc_html__('Backup', 'update-controller'); ?></th>
            </tr>
        </thead>
        <tbody id="uc-logs-table-body">
            <?php if (!empty($logs)) : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr data-site-id="<?php echo esc_attr($log->site_id); ?>">
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></td>
                        <td><?php echo esc_html($log->site_name ?: __('Unknown Site', 'update-controller')); ?></td>
                        <td><strong><?php echo esc_html($log->plugin_name); ?></strong></td>
                        <td><?php echo esc_html($log->from_version ?: '-'); ?></td>
                        <td><?php echo esc_html($log->to_version ?: '-'); ?></td>
                        <td>
                            <?php if ($log->status === 'success') : ?>
                                <span class="uc-status uc-status-active"><?php echo esc_html__('Success', 'update-controller'); ?></span>
                            <?php else : ?>
                                <span class="uc-status uc-status-inactive"><?php echo esc_html__('Failed', 'update-controller'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($log->message ?: '-'); ?></td>
                        <td>
                            <?php if (!empty($log->backup_file) && file_exists($log->backup_file)) : ?>
                                <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=uc_download_backup&log_id=' . $log->id . '&nonce=' . wp_create_nonce('uc_download_backup_' . $log->id))); ?>" class="button button-small">
                                    <?php echo esc_html__('Download', 'update-controller'); ?>
                                </a>
                                <button type="button" class="button button-small uc-delete-backup" data-log-id="<?php echo esc_attr($log->id); ?>" title="<?php echo esc_attr__('Delete Backup', 'update-controller'); ?>">
                                    <?php echo esc_html__('Delete', 'update-controller'); ?>
                                </button>
                            <?php else : ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr id="uc-no-logs-row">
                    <td colspan="8"><?php echo esc_html__('No update logs found. Logs will appear here after running plugin updates.', 'update-controller'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script type="text/javascript">
function filterLogs(siteId) {
    var rows = document.querySelectorAll('#uc-logs-table-body tr[data-site-id]');
    rows.forEach(function(row) {
        if (!siteId || row.getAttribute('data-site-id') === siteId) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Delete backup handler
jQuery(document).ready(function($) {
    $(document).on('click', '.uc-delete-backup', function() {
        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this backup file?', 'update-controller')); ?>')) {
            return;
        }
        
        var $button = $(this);
        var logId = $button.data('log-id');
        var $cell = $button.closest('td');
        
        $button.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'update-controller')); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'uc_delete_backup',
                log_id: logId,
                nonce: '<?php echo wp_create_nonce('uc_delete_backup'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $cell.html('-');
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error deleting backup', 'update-controller')); ?>');
                    $button.prop('disabled', false).text('<?php echo esc_js(__('Delete', 'update-controller')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error deleting backup', 'update-controller')); ?>');
                $button.prop('disabled', false).text('<?php echo esc_js(__('Delete', 'update-controller')); ?>');
            }
        });
    });
});
</script>
