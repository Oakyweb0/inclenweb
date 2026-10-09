<?php
/**
 * Academic Association Manager & REST API Controller
 */

// Register Admin Submenu
add_action('admin_menu', function () {
    add_submenu_page(
        'group-get-involved',
        'Academic Association',
        'Academic Association',
        'manage_options',
        'academic-association-manager',
        'academic_association_page'
    );
});

// Setup Table on Activation/Load
function custom_setup_academic_association_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_association';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            program_type varchar(100) NOT NULL DEFAULT 'phd',
            title varchar(255) NOT NULL,
            tag varchar(255) DEFAULT '',
            description longtext DEFAULT '',
            eligibility longtext DEFAULT '',
            duration varchar(100) DEFAULT '',
            pdf_url varchar(500) DEFAULT '',
            image_url varchar(500) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
add_action('admin_init', 'custom_setup_academic_association_table');

// REST API
add_action('rest_api_init', function () {
    register_rest_route('academic-association/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_academic_associations',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('academic-association/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_academic_association',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('academic-association/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_academic_association',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('academic-association/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_academic_association',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('academic-association/v1', '/upload-file', [
        'methods'  => 'POST',
        'callback' => 'upload_academic_assoc_file',
        'permission_callback' => '__return_true'
    ]);
});

function academic_association_page() {
    $nonce = wp_create_nonce('academic_assoc_nonce');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Academic Association Manager</h1>
        <hr class="wp-header-end">

        <!-- Add / Edit Form -->
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px; max-width:900px;">
            <h2 id="form-heading">Add New Academic Association Item</h2>
            
            <form id="assoc-form">
                <input type="hidden" id="assoc_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="program_type">Program Category <span style="color:red;">*</span></label></th>
                        <td>
                            <select id="program_type" style="min-width:240px;">
                                <option value="phd">PhD Program</option>
                                <option value="masters">Masters</option>
                                <option value="internship">Internship</option>
                                <option value="courses">Courses / Training</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="title">Title / Name <span style="color:red;">*</span></label></th>
                        <td><input type="text" id="title" class="regular-text" style="width:100%;" placeholder="e.g. Clinical Epidemiology Track" required></td>
                    </tr>
                    <tr>
                        <th><label for="tag">Tag / Badge</label></th>
                        <td><input type="text" id="tag" class="regular-text" style="width:100%;" placeholder="e.g. Full-Time / Part-Time"></td>
                    </tr>
                    <tr>
                        <th><label for="duration">Duration</label></th>
                        <td><input type="text" id="duration" class="regular-text" style="width:300px;" placeholder="e.g. 3 Years / 6 Months"></td>
                    </tr>
                    <tr>
                        <th><label for="description">Description</label></th>
                        <td>
                            <?php
                            wp_editor('', 'description', [
                                'textarea_name' => 'description',
                                'textarea_rows' => 6,
                                'media_buttons' => true,
                                'teeny' => false
                            ]);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="eligibility">Eligibility / Who Should Apply</label></th>
                        <td>
                            <textarea id="eligibility" rows="4" style="width:100%;" placeholder="Requirements and prerequisites..."></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Brochure / File</label></th>
                        <td>
                            <input type="file" id="file-upload">
                            <input type="hidden" id="file-url">
                            <div id="file-preview" style="margin-top:8px;"></div>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" id="save-btn" class="button button-primary">Save Item</button>
                    <button type="button" id="cancel-btn" class="button button-secondary" style="display:none; margin-left:10px;">Cancel</button>
                </p>
            </form>
        </div>

        <!-- Table Listing -->
        <h2>All Academic Association Items</h2>
        <table class="wp-list-table widefat fixed striped" style="max-width:1000px;">
            <thead>
                <tr>
                    <th style="width:120px;">Category</th>
                    <th>Title</th>
                    <th style="width:100px;">Duration</th>
                    <th style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody id="assoc-list">
                <tr><td colspan="4">Loading...</td></tr>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let editId = 0;

        function loadItems() {
            $.get('<?php echo rest_url('academic-association/v1/all'); ?>', function(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html = '<tr><td colspan="4">No items found.</td></tr>';
                } else {
                    data.forEach(function(item) {
                        html += `
                            <tr>
                                <td><span class="badge" style="background:#e0e7ff;color:#3730a3;padding:3px 8px;border-radius:4px;font-size:11px;text-transform:uppercase;font-weight:600;">${item.program_type}</span></td>
                                <td><strong>${item.title}</strong></td>
                                <td>${item.duration || '—'}</td>
                                <td>
                                    <button class="button button-small edit-btn" data-id="${item.id}">Edit</button>
                                    <button class="button button-small button-link-delete delete-btn" data-id="${item.id}">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#assoc-list').html(html);
            });
        }

        $('#file-upload').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: '<?php echo rest_url('academic-association/v1/upload-file'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.url) {
                        $('#file-url').val(res.url);
                        $('#file-preview').html(`<a href="${res.url}" target="_blank">View Uploaded File</a>`);
                    }
                },
                error: function() {
                    alert('File upload failed.');
                }
            });
        });

        $('#assoc-form').on('submit', function(e) {
            e.preventDefault();
            const title = $('#title').val().trim();
            if (!title) {
                alert('Please enter a title.');
                return;
            }

            const data = {
                program_type: $('#program_type').val(),
                title: title,
                tag: $('#tag').val().trim(),
                duration: $('#duration').val().trim(),
                description: tinymce.get('description') ? tinymce.get('description').getContent() : $('#description').val(),
                eligibility: $('#eligibility').val().trim(),
                pdf_url: $('#file-url').val(),
                nonce: $('#nonce').val()
            };

            const url = editId 
                ? '<?php echo rest_url('academic-association/v1/update/'); ?>' + editId 
                : '<?php echo rest_url('academic-association/v1/add'); ?>';

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function() {
                    alert(editId ? 'Item updated!' : 'Item added successfully!');
                    resetForm();
                    loadItems();
                },
                error: function() {
                    alert('Failed to save.');
                }
            });
        });

        $(document).on('click', '.edit-btn', function() {
            editId = $(this).data('id');
            $('#form-heading').text('Edit Academic Association Item');
            $('#save-btn').text('Update Item');
            $('#cancel-btn').show();

            $.get('<?php echo rest_url('academic-association/v1/all'); ?>', function(items) {
                const item = items.find(i => i.id == editId);
                if (item) {
                    $('#program_type').val(item.program_type || 'phd');
                    $('#title').val(item.title);
                    $('#tag').val(item.tag || '');
                    $('#duration').val(item.duration || '');
                    $('#eligibility').val(item.eligibility || '');
                    $('#file-url').val(item.pdf_url || '');
                    $('#file-preview').html(item.pdf_url ? `<a href="${item.pdf_url}" target="_blank">View File</a>` : '');

                    if (tinymce.get('description')) {
                        tinymce.get('description').setContent(item.description || '');
                    }
                }
            });
        });

        $('#cancel-btn').on('click', resetForm);

        function resetForm() {
            editId = 0;
            $('#assoc-form')[0].reset();
            $('#file-url').val('');
            $('#file-preview').html('');
            $('#form-heading').text('Add New Academic Association Item');
            $('#save-btn').text('Save Item');
            $('#cancel-btn').hide();
            if (tinymce.get('description')) tinymce.get('description').setContent('');
        }

        $(document).on('click', '.delete-btn', function() {
            if (!confirm('Delete this item?')) return;
            const id = $(this).data('id');
            $.ajax({
                url: '<?php echo rest_url('academic-association/v1/delete/'); ?>' + id,
                method: 'DELETE',
                success: function() {
                    loadItems();
                }
            });
        });

        loadItems();
    });
    </script>
    <?php
}

function get_all_academic_associations() {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_association';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_academic_association($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_association';
    $params = $request->get_json_params();

    $wpdb->insert($table, [
        'program_type' => sanitize_text_field($params['program_type'] ?? 'phd'),
        'title'        => sanitize_text_field($params['title'] ?? ''),
        'tag'          => sanitize_text_field($params['tag'] ?? ''),
        'duration'     => sanitize_text_field($params['duration'] ?? ''),
        'description'  => wp_kses_post($params['description'] ?? ''),
        'eligibility'  => sanitize_textarea_field($params['eligibility'] ?? ''),
        'pdf_url'      => esc_url_raw($params['pdf_url'] ?? ''),
        'created_at'   => current_time('mysql')
    ]);

    return ['status' => 'success', 'id' => $wpdb->insert_id];
}

function update_academic_association($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_association';
    $params = $request->get_json_params();
    $id = absint($request['id']);

    $wpdb->update($table, [
        'program_type' => sanitize_text_field($params['program_type'] ?? 'phd'),
        'title'        => sanitize_text_field($params['title'] ?? ''),
        'tag'          => sanitize_text_field($params['tag'] ?? ''),
        'duration'     => sanitize_text_field($params['duration'] ?? ''),
        'description'  => wp_kses_post($params['description'] ?? ''),
        'eligibility'  => sanitize_textarea_field($params['eligibility'] ?? ''),
        'pdf_url'      => esc_url_raw($params['pdf_url'] ?? ''),
    ], ['id' => $id]);

    return ['status' => 'updated'];
}

function delete_academic_association($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_association';
    $id = absint($request['id']);
    $wpdb->delete($table, ['id' => $id]);
    return ['status' => 'deleted'];
}

function upload_academic_assoc_file() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
    }

    $fileTmp = $_FILES['file']['tmp_name'];
    $fileName = time() . '-academic-' . sanitize_file_name($_FILES['file']['name']);
    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/academic";

    try {
        if (!class_exists('\Aws\S3\S3Client')) {
            require_once __DIR__ . '/vendor/autoload.php';
        }
        $client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);

        $client->putObject([
            'Bucket'      => $bucket,
            'Key'         => 'admin/academic/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return new WP_Error('upload_failed', $e->getMessage(), ['status' => 500]);
    }
}
