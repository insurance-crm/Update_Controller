<div class="wrap">
    <h1><?php echo esc_html__('Update Packages', 'update-controller'); ?></h1>
    <p class="description"><?php echo esc_html__('Upload and manage plugin ZIP files here. These packages can be used as update sources for your plugins.', 'update-controller'); ?></p>
    
    <?php
    // Show directory info
    $upload_dir = wp_upload_dir();
    $uc_dir = $upload_dir['basedir'] . '/update-controller';
    $uc_url = $upload_dir['baseurl'] . '/update-controller';
    ?>
    <div class="notice notice-info">
        <p>
            <strong><?php echo esc_html__('Upload Directory:', 'update-controller'); ?></strong> <code><?php echo esc_html($uc_dir); ?></code><br>
            <strong><?php echo esc_html__('URL Base:', 'update-controller'); ?></strong> <code><?php echo esc_html($uc_url); ?></code>
        </p>
        <p>
            <?php echo esc_html__('If you get 403 Forbidden errors when downloading, check:', 'update-controller'); ?>
            <ol>
                <li><?php echo esc_html__('Web server allows access to wp-content/uploads/update-controller/', 'update-controller'); ?></li>
                <li><?php echo esc_html__('No security plugins blocking direct file access', 'update-controller'); ?></li>
                <li><?php echo esc_html__('.htaccess rules are not blocking access', 'update-controller'); ?></li>
            </ol>
        </p>
    </div>
    
    <div class="uc-actions">
        <button type="button" class="button button-primary" id="uc-add-update-btn" onclick="openUploadModal()">
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
        <tbody id="uc-updates-table-body">
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
                            <button type="button" class="button button-small" onclick="copyToClipboard('<?php echo esc_js($update->file_url); ?>', this)">
                                <?php echo esc_html__('Copy', 'update-controller'); ?>
                            </button>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($update->created_at))); ?></td>
                        <td>
                            <button type="button" class="button button-small" onclick="deleteUpdate(<?php echo esc_attr($update->id); ?>, this)">
                                <?php echo esc_html__('Delete', 'update-controller'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr id="uc-no-updates-row">
                    <td colspan="7"><?php echo esc_html__('No update packages found. Upload your first package to get started.', 'update-controller'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Update Package Modal -->
<div id="uc-update-package-modal" class="uc-modal" style="display: none;">
    <div class="uc-modal-content">
        <span class="uc-modal-close" onclick="closeUploadModal()">&times;</span>
        <h2><?php echo esc_html__('Upload Update Package', 'update-controller'); ?></h2>
        <form id="uc-update-package-form" enctype="multipart/form-data" onsubmit="return uploadPackage(event)">
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
            
            <p id="uc-upload-message" style="display:none;"></p>
            
            <p>
                <button type="submit" class="button button-primary" id="uc-upload-btn"><?php echo esc_html__('Upload', 'update-controller'); ?></button>
                <button type="button" class="button" onclick="closeUploadModal()"><?php echo esc_html__('Cancel', 'update-controller'); ?></button>
            </p>
        </form>
    </div>
</div>

<script type="text/javascript">
// Inline JavaScript to ensure it works regardless of jQuery loading issues
function openUploadModal() {
    var modal = document.getElementById('uc-update-package-modal');
    if (modal) {
        modal.style.display = 'block';
        // Reset form
        var form = document.getElementById('uc-update-package-form');
        if (form) form.reset();
        document.getElementById('uc-upload-progress').style.display = 'none';
        document.getElementById('uc-upload-message').style.display = 'none';
        document.getElementById('uc-upload-btn').disabled = false;
    } else {
        alert('Error: Modal not found');
    }
}

function closeUploadModal() {
    var modal = document.getElementById('uc-update-package-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function copyToClipboard(text, button) {
    var textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    var originalText = button.innerText;
    button.innerText = 'Copied!';
    setTimeout(function() {
        button.innerText = originalText;
    }, 1500);
}

function deleteUpdate(updateId, button) {
    if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this package?', 'update-controller')); ?>')) {
        return;
    }
    
    var row = button.closest('tr');
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'uc_delete_update_package',
            nonce: '<?php echo wp_create_nonce('uc_admin_nonce'); ?>',
            update_id: updateId
        },
        success: function(response) {
            if (response.success) {
                jQuery(row).fadeOut(function() {
                    jQuery(this).remove();
                });
            } else {
                alert(response.data.message || 'Error deleting package');
            }
        },
        error: function() {
            alert('Error deleting package');
        }
    });
}

function uploadPackage(event) {
    event.preventDefault();
    
    var fileInput = document.getElementById('uc-update-file');
    if (!fileInput.files || !fileInput.files[0]) {
        showUploadMessage('Please select a ZIP file', 'error');
        return false;
    }
    
    var formData = new FormData();
    formData.append('action', 'uc_add_update_package');
    formData.append('nonce', '<?php echo wp_create_nonce('uc_admin_nonce'); ?>');
    formData.append('package_name', document.getElementById('uc-package-name').value);
    formData.append('version', document.getElementById('uc-package-version').value);
    formData.append('update_file', fileInput.files[0]);
    
    document.getElementById('uc-upload-progress').style.display = 'block';
    document.getElementById('uc-upload-btn').disabled = true;
    document.getElementById('uc-upload-message').style.display = 'none';
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            document.getElementById('uc-upload-progress').style.display = 'none';
            if (response.success) {
                showUploadMessage(response.data.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                document.getElementById('uc-upload-btn').disabled = false;
                showUploadMessage(response.data.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            document.getElementById('uc-upload-progress').style.display = 'none';
            document.getElementById('uc-upload-btn').disabled = false;
            showUploadMessage('Upload error: ' + error, 'error');
        }
    });
    
    return false;
}

function showUploadMessage(message, type) {
    var msgEl = document.getElementById('uc-upload-message');
    msgEl.innerHTML = message;
    msgEl.className = 'uc-notice uc-notice-' + type;
    msgEl.style.display = 'block';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('uc-update-package-modal');
    if (event.target == modal) {
        closeUploadModal();
    }
}
</script>
