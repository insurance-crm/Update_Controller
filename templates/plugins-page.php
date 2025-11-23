<div class="wrap">
    <h1><?php echo esc_html__('Plugin Configurations', 'update-controller'); ?></h1>
    
    <div class="uc-actions">
        <button type="button" class="button button-primary" id="uc-add-plugin-btn">
            <?php echo esc_html__('Add Plugin Configuration', 'update-controller'); ?>
        </button>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Site', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Plugin Name', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Plugin Slug', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Update Source', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Source Type', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Auto Update', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Last Update', 'update-controller'); ?></th>
                <th><?php echo esc_html__('Actions', 'update-controller'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($plugins)) : ?>
                <?php foreach ($plugins as $plugin) : ?>
                    <?php
                    $site = UC_Database::get_site($plugin->site_id);
                    ?>
                    <tr data-plugin-id="<?php echo esc_attr($plugin->id); ?>">
                        <td><?php echo $site ? esc_html($site->site_name) : '-'; ?></td>
                        <td><?php echo esc_html($plugin->plugin_name); ?></td>
                        <td><code><?php echo esc_html($plugin->plugin_slug); ?></code></td>
                        <td><a href="<?php echo esc_url($plugin->update_source); ?>" target="_blank"><?php echo esc_html($plugin->update_source); ?></a></td>
                        <td><span class="uc-badge"><?php echo esc_html($plugin->source_type); ?></span></td>
                        <td><?php echo $plugin->auto_update ? esc_html__('Yes', 'update-controller') : esc_html__('No', 'update-controller'); ?></td>
                        <td><?php echo $plugin->last_update ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($plugin->last_update))) : '-'; ?></td>
                        <td>
                            <button type="button" class="button button-small button-primary uc-run-update" data-id="<?php echo esc_attr($plugin->id); ?>">
                                <?php echo esc_html__('Update Now', 'update-controller'); ?>
                            </button>
                            <button type="button" class="button button-small uc-edit-plugin" data-id="<?php echo esc_attr($plugin->id); ?>">
                                <?php echo esc_html__('Edit', 'update-controller'); ?>
                            </button>
                            <button type="button" class="button button-small uc-delete-plugin" data-id="<?php echo esc_attr($plugin->id); ?>">
                                <?php echo esc_html__('Delete', 'update-controller'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8"><?php echo esc_html__('No plugin configurations found. Add your first plugin configuration to get started.', 'update-controller'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Plugin Modal -->
<div id="uc-plugin-modal" class="uc-modal" style="display: none;">
    <div class="uc-modal-content">
        <span class="uc-modal-close">&times;</span>
        <h2 id="uc-plugin-modal-title"><?php echo esc_html__('Add Plugin Configuration', 'update-controller'); ?></h2>
        <form id="uc-plugin-form">
            <input type="hidden" id="uc-plugin-id" name="plugin_id" value="">
            
            <p>
                <label for="uc-plugin-site"><?php echo esc_html__('WordPress Site', 'update-controller'); ?></label>
                <select id="uc-plugin-site" name="site_id" class="regular-text" required>
                    <option value=""><?php echo esc_html__('Select Site', 'update-controller'); ?></option>
                    <?php foreach ($sites as $site) : ?>
                        <option value="<?php echo esc_attr($site->id); ?>"><?php echo esc_html($site->site_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <label for="uc-plugin-name"><?php echo esc_html__('Plugin Name', 'update-controller'); ?></label>
                <input type="text" id="uc-plugin-name" name="plugin_name" class="regular-text" placeholder="My Plugin" required>
            </p>
            
            <p>
                <label for="uc-plugin-slug"><?php echo esc_html__('Plugin Slug', 'update-controller'); ?></label>
                <input type="text" id="uc-plugin-slug" name="plugin_slug" class="regular-text" placeholder="my-plugin/my-plugin.php" required>
                <span class="description"><?php echo esc_html__('Plugin directory/main file (e.g., akismet/akismet.php)', 'update-controller'); ?></span>
            </p>
            
            <p>
                <label for="uc-update-source"><?php echo esc_html__('Update Source URL', 'update-controller'); ?></label>
                <input type="url" id="uc-update-source" name="update_source" class="regular-text" placeholder="https://example.com/plugin.zip" required>
                <span class="description"><?php echo esc_html__('Direct download URL or GitHub repository URL', 'update-controller'); ?></span>
            </p>
            
            <p>
                <label for="uc-source-type"><?php echo esc_html__('Source Type', 'update-controller'); ?></label>
                <select id="uc-source-type" name="source_type" class="regular-text">
                    <option value="web"><?php echo esc_html__('Web URL', 'update-controller'); ?></option>
                    <option value="github"><?php echo esc_html__('GitHub Repository', 'update-controller'); ?></option>
                </select>
            </p>
            
            <p>
                <label>
                    <input type="checkbox" id="uc-auto-update" name="auto_update" value="1" checked>
                    <?php echo esc_html__('Enable Automatic Updates', 'update-controller'); ?>
                </label>
            </p>
            
            <p>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Save', 'update-controller'); ?></button>
                <button type="button" class="button uc-modal-close"><?php echo esc_html__('Cancel', 'update-controller'); ?></button>
            </p>
        </form>
    </div>
</div>

<!-- Update Progress Modal -->
<div id="uc-update-modal" class="uc-modal" style="display: none;">
    <div class="uc-modal-content">
        <h2><?php echo esc_html__('Update Progress', 'update-controller'); ?></h2>
        <div id="uc-update-progress">
            <p><?php echo esc_html__('Updating plugin...', 'update-controller'); ?></p>
        </div>
    </div>
</div>
