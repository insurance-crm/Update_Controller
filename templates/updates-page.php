<div class="wrap">
    <h1><?php echo esc_html__('Update Packages', 'update-controller'); ?></h1>
    <p class="description"><?php echo esc_html__('Upload and manage plugin ZIP files here. These packages can be used as update sources for your plugins.', 'update-controller'); ?></p>
    
    <div class="uc-actions">
        <button type="button" class="button button-primary" id="uc-add-update-btn">
            <?php echo esc_html__('Upload New Package', 'update-controller'); ?>
        </button>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Package Name', 'update-controller'); ?></th>
                <th><?php echo esc_html__('File Name', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Version', 'update-controller'); ?></th>
                <th><?php echo esc_html__('File Size', 'update-controller'); ?></th>
                <th><?php echo esc_html__('URL', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Uploaded', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Actions', 'update-controller'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($updates)) : ?>
                <?php foreach ($updates as $update) : ?>
                    <tr data-update-id="<?php echo esc_attr($update->id); ?>"
                        data-package-name="<?php echo esc_attr($update->package_name); ?>"
                        data-file-url="<?php echo esc_attr($update->file_url); ?>">
                        <td><strong><?php echo esc_html($update->package_name); ?></strong></td>
                        <td><code><?php echo esc_html($update->file_name); ?></code></td>
                        <td><?php echo esc_html($update->version ?: '-'); ?></td>
                        <td><?php echo esc_html(size_format($update->file_size)); ?></td>
                        <td>
                            <input type="text" readonly value="<?php echo esc_attr($update->file_url); ?>" class="regular-text uc-copy-url" style="width:200px;">
                            <button type="button" class="button button-small uc-copy-btn" data-url="<?php echo esc_attr($update->file_url); ?>">
                                <?php echo esc_html__('Copy', 'update-controller'); ?>
                            </button>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($update->created_at))); ?></td>
                        <td>
                            <button type="button" class="button button-small uc-delete-update" data-id="<?php echo esc_attr($update->id); ?>">
                                <?php echo esc_html__('Delete', 'update-controller'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7"><?php echo esc_html__('No update packages found. Upload your first package to get started.', 'update-controller'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Update Package Modal -->
<div id="uc-update-package-modal" class="uc-modal" style="display: none;">
    <div class="uc-modal-content">
        <span class="uc-modal-close">&times;</span>
        <h2><?php echo esc_html__('Upload Update Package', 'update-controller'); ?></h2>
        <form id="uc-update-package-form" enctype="multipart/form-data">
            <p>
                <label for="uc-package-name"><?php echo esc_html__('Package Name', 'update-controller'); ?></label>
                <input type="text" id="uc-package-name" name="package_name" class="regular-text" placeholder="<?php echo esc_attr__('My Plugin Update', 'update-controller'); ?>" required>
                <span class="description"><?php echo esc_html__('A descriptive name for this update package', 'update-controller'); ?></span>
            </p>
            
            <p>
                <label for="uc-package-version"><?php echo esc_html__('Version (Optional)', 'update-controller'); ?></label>
                <input type="text" id="uc-package-version" name="version" class="regular-text" placeholder="<?php echo esc_attr__('1.0.0', 'update-controller'); ?>">
            </p>
            
            <p>
                <label for="uc-update-file"><?php echo esc_html__('Plugin ZIP File', 'update-controller'); ?></label>
                <input type="file" id="uc-update-file" name="update_file" accept=".zip" required>
                <span class="description"><?php echo esc_html__('Upload a ZIP file containing the plugin (max 50MB)', 'update-controller'); ?></span>
            </p>
            
            <p id="uc-upload-progress" style="display:none;">
                <span class="spinner is-active" style="float:none;"></span>
                <?php echo esc_html__('Uploading...', 'update-controller'); ?>
            </p>
            
            <p>
                <button type="submit" class="button button-primary" id="uc-upload-btn"><?php echo esc_html__('Upload', 'update-controller'); ?></button>
                <button type="button" class="button uc-modal-close"><?php echo esc_html__('Cancel', 'update-controller'); ?></button>
            </p>
        </form>
    </div>
</div>
