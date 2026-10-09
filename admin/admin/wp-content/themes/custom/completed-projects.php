<?php
/**
 * Completed Projects Manager
 */

add_action('admin_menu', function () {
    add_submenu_page(
        'group-resources',
        'Completed Projects',
        'Completed Projects',
        'manage_options',
        'completed-projects',
        'completed_projects_page'
    );
});

add_action('rest_api_init', function () {
    $namespace = 'inclen-completed/v1';

    register_rest_route($namespace, '/all', [
        'methods' => 'GET',
        'callback' => 'get_all_completed_projects',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route($namespace, '/add', [
        'methods' => 'POST',
        'callback' => 'add_completed_project',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route($namespace, '/update/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'update_completed_project',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route($namespace, '/delete/(?P<id>\d+)', [
        'methods' => ['POST', 'DELETE'],
        'callback' => 'delete_completed_project',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route($namespace, '/upload-pdf', [
        'methods' => 'POST',
        'callback' => 'upload_pdf_to_r2_completed',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);
});

function upload_pdf_to_r2_completed() {
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
            'Key' => 'admin/completed-projects/pdfs/' . $fileName,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $file['type'],
            'ACL' => 'public-read',
        ]);

        return ['url' => R2_PUBLIC_URL . '/completed-projects/pdfs/' . $fileName];
    } catch (Throwable $e) {
        return new WP_Error('upload_error', $e->getMessage(), ['status' => 500]);
    }
}

function get_all_completed_projects() {
    global $wpdb;
    $table = $wpdb->prefix . 'completed_projects';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC") ?: [];
    return ['value' => $results];
}

function add_completed_project($request) {
    try {
        global $wpdb;
        $table = $wpdb->prefix . 'completed_projects';
        $params = $request->get_json_params();

        if (!$params) return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);

        $wpdb->insert($table, [
            'title' => sanitize_text_field($params['title'] ?? ''),
            'year' => sanitize_text_field($params['year'] ?? ''),
            'principal_investigator' => sanitize_text_field($params['principal_investigator'] ?? ''),
            'co_investigator' => sanitize_text_field($params['co_investigator'] ?? ''),
            'funder' => sanitize_text_field($params['funder'] ?? ''),
            'study_sites' => sanitize_text_field($params['study_sites'] ?? ''),
            'pdf_url' => esc_url_raw($params['pdf_url'] ?? ''),
            'summary' => sanitize_textarea_field($params['summary'] ?? '')
        ]);

        return ['status' => 'success', 'id' => $wpdb->insert_id];
    } catch (Throwable $e) {
        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
    }
}

function update_completed_project($request) {
    try {
        global $wpdb;
        $table = $wpdb->prefix . 'completed_projects';
        $id = intval($request['id']);
        $params = $request->get_json_params();

        if (!$params) return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);

        $wpdb->update($table, [
            'title' => sanitize_text_field($params['title'] ?? ''),
            'year' => sanitize_text_field($params['year'] ?? ''),
            'principal_investigator' => sanitize_text_field($params['principal_investigator'] ?? ''),
            'co_investigator' => sanitize_text_field($params['co_investigator'] ?? ''),
            'funder' => sanitize_text_field($params['funder'] ?? ''),
            'study_sites' => sanitize_text_field($params['study_sites'] ?? ''),
            'pdf_url' => esc_url_raw($params['pdf_url'] ?? ''),
            'summary' => sanitize_textarea_field($params['summary'] ?? '')
        ], ['id' => $id]);

        return ['status' => 'success'];
    } catch (Throwable $e) {
        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
    }
}

function delete_completed_project($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'completed_projects';
    $wpdb->delete($table, ['id' => intval($request['id'])]);
    return ['status' => 'success'];
}

function completed_projects_page() {
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
        <h1>Completed Projects Manager</h1>

        <div class="inclen-card" id="project-form-container">
            <h2 id="form-heading" style="font-size: 18px; margin-bottom: 30px;">Add New Completed Project</h2>
            <input type="hidden" id="project-id" value="">

            <div class="form-row">
                <div class="form-label">Project Title <span>*</span></div>
                <input type="text" id="title" class="form-input" placeholder="Enter project title">
            </div>

            <div class="form-row">
                <div class="form-label">Year of Completion</div>
                <input type="text" id="year" class="form-input" placeholder="Enter year (e.g. July 2019 or 2023)">
            </div>

            <div class="form-row">
                <div class="form-label">Principal Investigator</div>
                <input type="text" id="pi" class="form-input" placeholder="Enter principal investigator">
            </div>

            <div class="form-row">
                <div class="form-label">Co-Investigator</div>
                <input type="text" id="co_pi" class="form-input" placeholder="Enter co-investigator">
            </div>

            <div class="form-row">
                <div class="form-label">Funder</div>
                <input type="text" id="funder" class="form-input" placeholder="Enter funder">
            </div>

            <div class="form-row">
                <div class="form-label">Study Sites</div>
                <input type="text" id="study_sites" class="form-input" placeholder="Enter study sites">
            </div>

            <div class="form-row">
                <div class="form-label">Report (PDF)</div>
                <div>
                    <button type="button" class="btn-upload" id="upload-pdf-btn">
                        <span class="dashicons dashicons-upload"></span> Upload PDF
                    </button>
                    <input type="hidden" id="pdf_url">
                    <div id="pdf-preview" style="display:none;" class="pdf-preview-item">
                        <span class="dashicons dashicons-pdf" style="color: #ef4444;"></span>
                        <a id="pdf-link" href="#" target="_blank" style="color:#2563eb; text-decoration:none;"><span id="pdf-name"></span></a>
                        <span class="remove-pdf dashicons dashicons-no-alt" id="remove-pdf-btn" title="Remove"></span>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Summary</div>
                <textarea id="summary" class="form-input" style="max-width: 600px; height: 100px;" placeholder="Enter project summary"></textarea>
            </div>

            <div style="margin-top: 40px; border-top: 1px solid #f3f4f6; padding-top: 20px;">
                <button type="button" class="btn-save" id="save-btn">Save Project</button>
                <button type="button" class="btn-clear" id="clear-btn">Clear Form</button>
            </div>
        </div>

        <h2 style="margin-top: 40px;">All Completed Projects</h2>
        <table class="wp-list-table widefat fixed striped" style="border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
            <thead>
                <tr>
                    <th style="padding: 15px; width: 50px;">Sr No</th>
                    <th style="padding: 15px;">Project Title</th>
                    <th style="padding: 15px;">Year</th>
                    <th style="padding: 15px;">PI</th>
                    <th style="padding: 15px;">Co-PI</th>
                    <th style="padding: 15px;">Report</th>
                    <th style="padding: 15px; width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody id="projects-list"></tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        const API_BASE = '<?php echo rest_url('inclen-completed/v1'); ?>';
        const WP_NONCE = '<?php echo wp_create_nonce('wp_rest'); ?>';
        let projectsMap = {};

        function loadProjects() {
            $.get(API_BASE + '/all', function(res) {
                let html = '';
                projectsMap = {};
                const list = res.value || [];
                if (list.length === 0) {
                    $('#projects-list').html('<tr><td colspan="7" style="padding:20px; text-align:center; color:#888;">No completed projects found.</td></tr>');
                    return;
                }
                list.forEach((p, index) => {
                    projectsMap[p.id] = p;
                    html += `<tr>
                        <td style="padding: 15px;">${index + 1}</td>
                        <td style="padding: 15px;"><strong>${$('<div>').text(p.title || '').html()}</strong></td>
                        <td style="padding: 15px;">${$('<div>').text(p.year || '-').html()}</td>
                        <td style="padding: 15px;">${$('<div>').text(p.principal_investigator || '-').html()}</td>
                        <td style="padding: 15px;">${$('<div>').text(p.co_investigator || '-').html()}</td>
                        <td style="padding: 15px;">
                            ${p.pdf_url ? `<a href="${p.pdf_url}" target="_blank" style="color: #ef4444;"><span class="dashicons dashicons-pdf"></span> View PDF</a>` : '-'}
                        </td>
                        <td style="padding: 15px;">
                            <button class="button edit-btn" data-id="${p.id}">Edit</button>
                            <button class="button delete-btn" data-id="${p.id}" style="color: #ef4444; border-color:#fca5a5;">Delete</button>
                        </td>
                    </tr>`;
                });
                $('#projects-list').html(html);
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
                        $('#pdf_url').val(res.url);
                        $('#pdf-name').text(file.name);
                        $('#pdf-link').attr('href', res.url);
                        $('#pdf-preview').show();
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
            $('#pdf_url').val('');
            $('#pdf-preview').hide();
        });

        $('#save-btn').click(function() {
            const editId = $('#project-id').val();
            const data = {
                title: $('#title').val().trim(),
                year: $('#year').val().trim(),
                principal_investigator: $('#pi').val().trim(),
                co_investigator: $('#co_pi').val().trim(),
                funder: $('#funder').val().trim(),
                study_sites: $('#study_sites').val().trim(),
                summary: $('#summary').val().trim(),
                pdf_url: $('#pdf_url').val()
            };

            if (!data.title) {
                alert('Title is required');
                $('#title').focus();
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
                    alert(editId ? 'Project updated successfully!' : 'Project added successfully!');
                    clearForm();
                    loadProjects();
                    btn.prop('disabled', false).text(editId ? 'Update Project' : 'Save Project');
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || xhr.responseText || 'Failed to save'));
                    btn.prop('disabled', false).text(editId ? 'Update Project' : 'Save Project');
                }
            });
        });

        function clearForm() {
            $('#project-id').val('');
            $('#title').val('');
            $('#year').val('');
            $('#pi').val('');
            $('#co_pi').val('');
            $('#funder').val('');
            $('#study_sites').val('');
            $('#summary').val('');
            $('#pdf_url').val('');
            $('#pdf-preview').hide();
            $('#form-heading').text('Add New Completed Project');
            $('#save-btn').text('Save Project');
        }

        $('#clear-btn').click(clearForm);

        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            const p = projectsMap[id];
            if (!p) return;

            $('#project-id').val(p.id);
            $('#title').val(p.title || '');
            $('#year').val(p.year || '');
            $('#pi').val(p.principal_investigator || '');
            $('#co_pi').val(p.co_investigator || '');
            $('#funder').val(p.funder || '');
            $('#study_sites').val(p.study_sites || '');
            $('#summary').val(p.summary || '');
            $('#pdf_url').val(p.pdf_url || '');
            
            if (p.pdf_url) {
                const parts = p.pdf_url.split('/');
                $('#pdf-name').text(parts[parts.length - 1] || 'Current PDF');
                $('#pdf-link').attr('href', p.pdf_url);
                $('#pdf-preview').show();
            } else {
                $('#pdf-preview').hide();
            }

            $('#form-heading').text('Edit Completed Project (ID: ' + p.id + ')');
            $('#save-btn').text('Update Project');
            $('html, body').animate({ scrollTop: $('#project-form-container').offset().top - 40 }, 300);
        });

        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            if(!confirm('Are you sure you want to delete this completed project?')) return;
            $.ajax({
                url: API_BASE + '/delete/' + id,
                method: 'POST',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                success: function() {
                    loadProjects();
                    if ($('#project-id').val() == id) {
                        clearForm();
                    }
                },
                error: function() {
                    alert('Failed to delete project');
                }
            });
        });

        loadProjects();
    });
    </script>
    <?php
}
