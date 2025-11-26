<div class="wrap">
    <h1><?php echo esc_html__('WordPress Sites', 'update-controller'); ?></h1>
    
    <div class="uc-actions">
        <button type="button" class="button button-primary" id="uc-add-site-btn">
            <?php echo esc_html__('Add New Site', 'update-controller'); ?>
        </button>
        <button type="button" class="button" id="uc-check-all-sites-btn">
            <?php echo esc_html__('Check All Sites', 'update-controller'); ?>
        </button>
    </div>
    
    <table class="wp-list-table widefat fixed striped" id="uc-sites-table">
        <thead>
            <tr>
                <th><?php echo esc_html__('Site Name', 'update-controller'); ?></th>
                <th><?php echo esc_html__('URL', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Username', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Connection', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Companion', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Insurance CRM', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Last Update', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Backups', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Actions', 'update-controller'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sites)) : ?>
                <?php foreach ($sites as $site) : ?>
                    <tr data-site-id="<?php echo esc_attr($site->id); ?>"
                        data-site-name="<?php echo esc_attr($site->site_name); ?>"
                        data-site-url="<?php echo esc_attr($site->site_url); ?>"
                        data-username="<?php echo esc_attr($site->username); ?>">
                        <td><?php echo esc_html($site->site_name); ?></td>
                        <td><a href="<?php echo esc_url($site->site_url); ?>" target="_blank"><?php echo esc_html($site->site_url); ?></a></td>
                        <td><?php echo esc_html($site->username); ?></td>
                        <td class="uc-connection-status"><span class="uc-status uc-status-<?php echo esc_attr($site->status); ?>"><?php echo esc_html($site->status); ?></span></td>
                        <td class="uc-companion-version">-</td>
                        <td class="uc-insurance-crm-version">-</td>
                        <td><?php echo $site->last_update ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($site->last_update))) : '-'; ?></td>
                        <td>
                            <?php 
                            $backups = UC_Database::get_site_backups($site->id);
                            $backup_count = count($backups);
                            ?>
                            <?php if ($backup_count > 0) : ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=update-controller-logs&site_id=' . $site->id)); ?>" class="button button-small">
                                    <?php echo sprintf(esc_html__('%d Backups', 'update-controller'), $backup_count); ?>
                                </a>
                            <?php else : ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small uc-test-connection" data-id="<?php echo esc_attr($site->id); ?>" title="<?php echo esc_attr__('Test Connection', 'update-controller'); ?>">
                                <?php echo esc_html__('Test', 'update-controller'); ?>
                            </button>
                            <button type="button" class="button button-small uc-edit-site" data-id="<?php echo esc_attr($site->id); ?>">
                                <?php echo esc_html__('Edit', 'update-controller'); ?>
                            </button>
                            <button type="button" class="button button-small uc-delete-site" data-id="<?php echo esc_attr($site->id); ?>">
                                <?php echo esc_html__('Delete', 'update-controller'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="9"><?php echo esc_html__('No sites found. Add your first WordPress site to get started.', 'update-controller'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Site Modal -->
<div id="uc-site-modal" class="uc-modal" style="display: none;">
    <div class="uc-modal-content">
        <span class="uc-modal-close">&times;</span>
        <h2 id="uc-site-modal-title"><?php echo esc_html__('Add New Site', 'update-controller'); ?></h2>
        <form id="uc-site-form">
            <input type="hidden" id="uc-site-id" name="site_id" value="">
            
            <p>
                <label for="uc-site-name"><?php echo esc_html__('Site Name', 'update-controller'); ?></label>
                <input type="text" id="uc-site-name" name="site_name" class="regular-text" required>
            </p>
            
            <p>
                <label for="uc-site-url"><?php echo esc_html__('Site URL', 'update-controller'); ?></label>
                <input type="url" id="uc-site-url" name="site_url" class="regular-text" placeholder="https://example.com" required>
            </p>
            
            <p>
                <label for="uc-username"><?php echo esc_html__('Admin Username', 'update-controller'); ?></label>
                <input type="text" id="uc-username" name="username" class="regular-text" required>
            </p>
            
            <p>
                <label for="uc-password"><?php echo esc_html__('Admin Password / Application Password', 'update-controller'); ?></label>
                <input type="password" id="uc-password" name="password" class="regular-text">
                <span class="description"><?php echo esc_html__('Leave empty when editing to keep current password', 'update-controller'); ?></span>
            </p>
            
            <p>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Save', 'update-controller'); ?></button>
                <button type="button" class="button uc-modal-close"><?php echo esc_html__('Cancel', 'update-controller'); ?></button>
            </p>
        </form>
    </div>
</div>
