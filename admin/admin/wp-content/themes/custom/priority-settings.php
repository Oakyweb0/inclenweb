<?php
/**
 * Research Priority Setting Manager
 */

add_action('admin_menu', function () {
    add_submenu_page(
        'group-resources',
        'Research Priority Setting',
        'Research Priority Setting',
        'manage_options',
        'priority-settings',
        'priority_settings_page'
    );
});

add_action('rest_api_init', function () {
    $namespace = 'inclen-priority/v1';

    register_rest_route($namespace, '/all', [
        'methods' => 'GET',
        'callback' => 'get_all_priority_settings',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route($namespace, '/add', [
        'methods' => 'POST',
        'callback' => 'add_priority_setting',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route($namespace, '/update/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'update_priority_setting',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route($namespace, '/delete/(?P<id>\d+)', [
        'methods' => ['POST', 'DELETE'],
        'callback' => 'delete_priority_setting',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route($namespace, '/upload-pdf', [
        'methods' => 'POST',
        'callback' => 'upload_pdf_to_r2_priority',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);
});

function upload_pdf_to_r2_priority() {
    try {
        require_once __DIR__ . '/cloud_config.php';
        
        if (!isset($_FILES['file'])) {
            return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
        }

        if (!class_exists('Aws\S3\S3Client')) {
            return new WP_Error('sdk_missing', 'AWS SDK not loaded', ['status' => 500]);
        }

        $client = new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://" . R2_ACCOUNT_ID . ".r2.cloudflarestorage.com",
            'credentials' => [
                'key' => R2_ACCESS_KEY,
                'secret' => R2_SECRET_KEY,
            ],
        ]);

        $file = $_FILES['file'];
        $fileName = time() . '-' . sanitize_file_name($file['name']);

        $result = $client->putObject([
            'Bucket' => R2_BUCKET,
            'Key' => 'admin/priority-settings/pdfs/' . $fileName,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $file['type'],
            'ACL' => 'public-read',
        ]);

        return ['url' => R2_PUBLIC_URL . '/priority-settings/pdfs/' . $fileName];
    } catch (Throwable $e) {
        return new WP_Error('upload_error', $e->getMessage(), ['status' => 500]);
    }
}

function get_all_priority_settings() {
    global $wpdb;
    $table = $wpdb->prefix . 'priority_settings';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC") ?: [];
    return ['value' => $results];
}

function add_priority_setting($request) {
    try {
        global $wpdb;
        $table = $wpdb->prefix . 'priority_settings';
        $params = $request->get_json_params();

        if (!$params) return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);

        $wpdb->insert($table, [
            'name' => sanitize_text_field($params['name'] ?? ''),
            'category' => sanitize_text_field($params['category'] ?? ''),
            'duration' => sanitize_text_field($params['duration'] ?? ''),
            'file_url' => esc_url_raw($params['file_url'] ?? ''),
            'file_size' => sanitize_text_field($params['file_size'] ?? ''),
        ]);

        return ['status' => 'success', 'id' => $wpdb->insert_id];
    } catch (Throwable $e) {
        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
    }
}

function update_priority_setting($request) {
    try {
        global $wpdb;
        $table = $wpdb->prefix . 'priority_settings';
        $id = intval($request['id']);
        $params = $request->get_json_params();

        if (!$params) return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);

        $wpdb->update($table, [
            'name' => sanitize_text_field($params['name'] ?? ''),
            'category' => sanitize_text_field($params['category'] ?? ''),
            'duration' => sanitize_text_field($params['duration'] ?? ''),
            'file_url' => esc_url_raw($params['file_url'] ?? ''),
            'file_size' => sanitize_text_field($params['file_size'] ?? ''),
        ], ['id' => $id]);

        return ['status' => 'success'];
    } catch (Throwable $e) {
        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
    }
}

function delete_priority_setting($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'priority_settings';
    $wpdb->delete($table, ['id' => intval($request['id'])]);
    return ['status' => 'success'];
}

function priority_settings_page() {
    ?>
    <style>
        .inclen-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 40px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        .form-label {
            width: 200px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }
        .form-label span { color: #ef4444; }
        .form-input {
            flex: 1;
            max-width: 400px;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
        }
        .btn-upload {
            background: #fff !important;
            border: 1px solid #3b82f6 !important;
            color: #3b82f6 !important;
            border-radius: 6px !important;
            padding: 6px 15px !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-save {
            background: #00558f !important;
            color: #fff !important;
            border: none !important;
            padding: 10px 24px !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
            cursor: pointer;
        }
        .btn-clear {
            background: #fff !important;
            border: 1px solid #d1d5db !important;
            color: #374151 !important;
            padding: 10px 24px !important;
            border-radius: 6px !important;
            margin-left: 10px !important;
            cursor: pointer;
        }
        .pdf-preview-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            font-size: 13px;
            color: #4b5563;
        }
        .remove-pdf { color: #ef4444; cursor: pointer; }
    </style>

    <div class="wrap">
        <h1>Research Priority Setting Manager</h1>

        <div class="inclen-card" id="priority-form-container">
            <h2 id="form-heading" style="font-size: 18px; margin-bottom: 30px;">Add New Priority Setting</h2>
            <input type="hidden" id="setting-id" value="">

            <div class="form-row">
                <div class="form-label">Project Name <span>*</span></div>
                <input type="text" id="name" class="form-input" placeholder="Enter project name">
            </div>

            <div class="form-row">
                <div class="form-label">Category</div>
                <input type="text" id="category" class="form-input" placeholder="Enter category (e.g. Health, Nutrition)">
            </div>

            <div class="form-row">
                <div class="form-label">Duration / Year</div>
                <input type="text" id="duration" class="form-input" placeholder="Enter duration or year (e.g. 2022-2025)">
            </div>

            <div class="form-row">
                <div class="form-label">PDF Document</div>
                <div>
                    <button type="button" class="btn-upload" id="upload-pdf-btn">
                        <span class="dashicons dashicons-upload"></span> Upload PDF
                    </button>
                    <input type="hidden" id="file_url">
                    <div id="pdf-preview" style="display:none;" class="pdf-preview-item">
                        <span class="dashicons dashicons-pdf" style="color: #ef4444;"></span>
                        <a id="pdf-link" href="#" target="_blank" style="color:#2563eb; text-decoration:none;"><span id="pdf-name"></span></a>
                        <span class="remove-pdf dashicons dashicons-no-alt" id="remove-pdf-btn" title="Remove"></span>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">File Size</div>
                <input type="text" id="file_size" class="form-input" placeholder="e.g. 2.5 MB">
            </div>

            <div style="margin-top: 40px; border-top: 1px solid #f3f4f6; padding-top: 20px;">
                <button type="button" class="btn-save" id="save-btn">Save Priority Setting</button>
                <button type="button" class="btn-clear" id="clear-btn">Clear Form</button>
            </div>
        </div>

        <h2 style="margin-top: 40px;">All Priority Settings</h2>
        <table class="wp-list-table widefat fixed striped" style="border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
            <thead>
                <tr>
                    <th style="padding: 15px; width: 50px;">Sr No</th>
                    <th style="padding: 15px;">Project Name</th>
                    <th style="padding: 15px;">Category</th>
                    <th style="padding: 15px;">Duration / Year</th>
                    <th style="padding: 15px;">File Size</th>
                    <th style="padding: 15px;">PDF Document</th>
                    <th style="padding: 15px; width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody id="priority-list"></tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        const API_BASE = '<?php echo rest_url('inclen-priority/v1'); ?>';
        const WP_NONCE = '<?php echo wp_create_nonce('wp_rest'); ?>';
        let priorityMap = {};

        function loadPriority() {
            $.get(API_BASE + '/all', function(res) {
                let html = '';
                priorityMap = {};
                const list = res.value || [];
                if (list.length === 0) {
                    $('#priority-list').html('<tr><td colspan="7" style="padding:20px; text-align:center; color:#888;">No priority settings found.</td></tr>');
                    return;
                }
                list.forEach((p, index) => {
                    priorityMap[p.id] = p;
                    html += `<tr>
                        <td style="padding: 15px;">${index + 1}</td>
                        <td style="padding: 15px;"><strong>${$('<div>').text(p.name || '').html()}</strong></td>
                        <td style="padding: 15px;">${$('<div>').text(p.category || '-').html()}</td>
                        <td style="padding: 15px;">${$('<div>').text(p.duration || '-').html()}</td>
                        <td style="padding: 15px;">${$('<div>').text(p.file_size || '-').html()}</td>
                        <td style="padding: 15px;">
                            ${p.file_url ? `<a href="${p.file_url}" target="_blank" style="color: #ef4444;"><span class="dashicons dashicons-pdf"></span> View PDF</a>` : '-'}
                        </td>
                        <td style="padding: 15px;">
                            <button class="button edit-btn" data-id="${p.id}">Edit</button>
                            <button class="button delete-btn" data-id="${p.id}" style="color: #ef4444; border-color:#fca5a5;">Delete</button>
                        </td>
                    </tr>`;
                });
                $('#priority-list').html(html);
            });
        }

        $('#upload-pdf-btn').click(function() {
            const fileInput = $('<input type="file" accept="application/pdf">');
            fileInput.on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);

                const btn = $('#upload-pdf-btn');
                btn.prop('disabled', true).text('Uploading...');

                $.ajax({
                    url: API_BASE + '/upload-pdf',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                    success: function(res) {
                        $('#file_url').val(res.url);
                        $('#pdf-name').text(file.name);
                        $('#pdf-link').attr('href', res.url);
                        $('#pdf-preview').show();
                        
                        const size = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                        if (!$('#file_size').val()) {
                            $('#file_size').val(size);
                        }

                        btn.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Upload PDF');
                    },
                    error: function(xhr) {
                        alert('Upload failed: ' + (xhr.responseJSON?.message || 'Server error'));
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Upload PDF');
                    }
                });
            });
            fileInput.click();
        });

        $('#remove-pdf-btn').click(function() {
            $('#file_url').val('');
            $('#pdf-preview').hide();
        });

        $('#save-btn').click(function() {
            const editId = $('#setting-id').val();
            const data = {
                name: $('#name').val().trim(),
                category: $('#category').val().trim(),
                duration: $('#duration').val().trim(),
                file_url: $('#file_url').val(),
                file_size: $('#file_size').val().trim()
            };

            if (!data.name) {
                alert('Project Name is required');
                $('#name').focus();
                return;
            }

            const url = editId ? API_BASE + '/update/' + editId : API_BASE + '/add';
            const btn = $(this);
            btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                success: function() {
                    alert(editId ? 'Priority setting updated successfully!' : 'Priority setting saved successfully!');
                    clearForm();
                    loadPriority();
                    btn.prop('disabled', false).text(editId ? 'Update Priority Setting' : 'Save Priority Setting');
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || xhr.responseText || 'Failed to save'));
                    btn.prop('disabled', false).text(editId ? 'Update Priority Setting' : 'Save Priority Setting');
                }
            });
        });

        function clearForm() {
            $('#setting-id').val('');
            $('#name').val('');
            $('#category').val('');
            $('#duration').val('');
            $('#file_url').val('');
            $('#file_size').val('');
            $('#pdf-preview').hide();
            $('#form-heading').text('Add New Priority Setting');
            $('#save-btn').text('Save Priority Setting');
        }

        $('#clear-btn').click(clearForm);

        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            const p = priorityMap[id];
            if (!p) return;

            $('#setting-id').val(p.id);
            $('#name').val(p.name || '');
            $('#category').val(p.category || '');
            $('#duration').val(p.duration || '');
            $('#file_url').val(p.file_url || '');
            $('#file_size').val(p.file_size || '');
            
            if (p.file_url) {
                const parts = p.file_url.split('/');
                $('#pdf-name').text(parts[parts.length - 1] || 'Current PDF');
                $('#pdf-link').attr('href', p.file_url);
                $('#pdf-preview').show();
            } else {
                $('#pdf-preview').hide();
            }

            $('#form-heading').text('Edit Priority Setting (ID: ' + p.id + ')');
            $('#save-btn').text('Update Priority Setting');
            $('html, body').animate({ scrollTop: $('#priority-form-container').offset().top - 40 }, 300);
        });

        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            if(!confirm('Are you sure you want to delete this priority setting?')) return;
            $.ajax({
                url: API_BASE + '/delete/' + id,
                method: 'POST',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                success: function() {
                    loadPriority();
                    if ($('#setting-id').val() == id) {
                        clearForm();
                    }
                },
                error: function() {
                    alert('Failed to delete priority setting');
                }
            });
        });

        loadPriority();
    });
    </script>
    <?php
}
