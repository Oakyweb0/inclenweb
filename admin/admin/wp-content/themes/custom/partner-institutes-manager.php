<?php
/**
 * Partner Institutes Manager (Footer / Collaborating Institutes)
 */

// Submenu registration under Footer removed as requested


// Setup Table
function custom_setup_partner_institutes_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'partner_institutes';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            institute_name varchar(255) NOT NULL,
            institute_logo varchar(500) DEFAULT '',
            website_url varchar(500) DEFAULT '',
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
add_action('admin_init', 'custom_setup_partner_institutes_table');

// REST API
add_action('rest_api_init', function () {
    register_rest_route('partner-institutes/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_partner_institutes',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('partner-institutes/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_partner_institute',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('partner-institutes/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_partner_institute',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('partner-institutes/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_partner_institute',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('partner-institutes/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_partner_institute_image',
        'permission_callback' => '__return_true'
    ]);
});

function partner_institutes_manager_page() {
    $nonce = wp_create_nonce('partner_institutes_nonce');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Partner Institutes Manager</h1>
        <hr class="wp-header-end">

        <!-- Add / Edit Form -->
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px; max-width:800px;">
            <h2 id="form-heading">Add New Partner Institute</h2>
            
            <form id="institute-form">
                <input type="hidden" id="inst_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="institute_name">Institute Name <span style="color:red;">*</span></label></th>
                        <td><input type="text" id="institute_name" class="regular-text" style="width:100%;" placeholder="e.g. All India Institute of Medical Sciences" required></td>
                    </tr>
                    <tr>
                        <th><label for="website_url">Website URL</label></th>
                        <td><input type="url" id="website_url" class="regular-text" style="width:100%;" placeholder="https://..."></td>
                    </tr>
                    <tr>
                        <th><label>Logo Image</label></th>
                        <td>
                            <input type="file" id="logo-upload" accept="image/*">
                            <input type="hidden" id="logo-url">
                            <div id="logo-preview" style="margin-top:8px;"></div>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" id="save-btn" class="button button-primary">Save Institute</button>
                    <button type="button" id="cancel-btn" class="button button-secondary" style="display:none; margin-left:10px;">Cancel</button>
                </p>
            </form>
        </div>

        <!-- Listing -->
        <h2>All Partner Institutes</h2>
        <table class="wp-list-table widefat fixed striped" style="max-width:900px;">
            <thead>
                <tr>
                    <th style="width:80px;">Logo</th>
                    <th>Institute Name</th>
                    <th>Website</th>
                    <th style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody id="institutes-list">
                <tr><td colspan="4">Loading...</td></tr>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let editId = 0;

        function loadInstitutes() {
            $.get('<?php echo rest_url('partner-institutes/v1/all'); ?>', function(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html = '<tr><td colspan="4">No partner institutes found.</td></tr>';
                } else {
                    data.forEach(function(item) {
                        const img = item.institute_logo ? `<img src="${item.institute_logo}" style="max-height:40px;max-width:70px;object-fit:contain;">` : '—';
                        const link = item.website_url ? `<a href="${item.website_url}" target="_blank">${item.website_url}</a>` : '—';
                        html += `
                            <tr>
                                <td>${img}</td>
                                <td><strong>${item.institute_name}</strong></td>
                                <td>${link}</td>
                                <td>
                                    <button class="button button-small edit-btn" data-id="${item.id}">Edit</button>
                                    <button class="button button-small button-link-delete delete-btn" data-id="${item.id}">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#institutes-list').html(html);
            });
        }

        $('#logo-upload').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: '<?php echo rest_url('partner-institutes/v1/upload-image'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.url) {
                        $('#logo-url').val(res.url);
                        $('#logo-preview').html(`<img src="${res.url}" style="max-height:80px;border-radius:4px;padding:4px;background:#f9fafb;border:1px solid #e5e7eb;">`);
                    }
                },
                error: function() {
                    alert('Upload failed.');
                }
            });
        });

        $('#institute-form').on('submit', function(e) {
            e.preventDefault();
            const name = $('#institute_name').val().trim();
            if (!name) {
                alert('Please enter institute name.');
                return;
            }

            const data = {
                institute_name: name,
                website_url: $('#website_url').val().trim(),
                institute_logo: $('#logo-url').val(),
                nonce: $('#nonce').val()
            };

            const url = editId 
                ? '<?php echo rest_url('partner-institutes/v1/update/'); ?>' + editId 
                : '<?php echo rest_url('partner-institutes/v1/add'); ?>';

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function() {
                    alert(editId ? 'Institute updated!' : 'Institute added!');
                    resetForm();
                    loadInstitutes();
                },
                error: function() {
                    alert('Failed to save.');
                }
            });
        });

        $(document).on('click', '.edit-btn', function() {
            editId = $(this).data('id');
            $('#form-heading').text('Edit Partner Institute');
            $('#save-btn').text('Update Institute');
            $('#cancel-btn').show();

            $.get('<?php echo rest_url('partner-institutes/v1/all'); ?>', function(items) {
                const item = items.find(i => i.id == editId);
                if (item) {
                    $('#institute_name').val(item.institute_name);
                    $('#website_url').val(item.website_url || '');
                    $('#logo-url').val(item.institute_logo || '');
                    $('#logo-preview').html(item.institute_logo ? `<img src="${item.institute_logo}" style="max-height:80px;border-radius:4px;padding:4px;background:#f9fafb;border:1px solid #e5e7eb;">` : '');
                }
            });
        });

        $('#cancel-btn').on('click', resetForm);

        function resetForm() {
            editId = 0;
            $('#institute-form')[0].reset();
            $('#logo-url').val('');
            $('#logo-preview').html('');
            $('#form-heading').text('Add New Partner Institute');
            $('#save-btn').text('Save Institute');
            $('#cancel-btn').hide();
        }

        $(document).on('click', '.delete-btn', function() {
            if (!confirm('Delete this partner institute?')) return;
            const id = $(this).data('id');
            $.ajax({
                url: '<?php echo rest_url('partner-institutes/v1/delete/'); ?>' + id,
                method: 'DELETE',
                success: function() {
                    loadInstitutes();
                }
            });
        });

        loadInstitutes();
    });
    </script>
    <?php
}

function get_all_partner_institutes() {
    global $wpdb;
    $table = $wpdb->prefix . 'partner_institutes';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_partner_institute($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'partner_institutes';
    $params = $request->get_json_params();

    $wpdb->insert($table, [
        'institute_name' => sanitize_text_field($params['institute_name'] ?? ''),
        'website_url'    => esc_url_raw($params['website_url'] ?? ''),
        'institute_logo' => esc_url_raw($params['institute_logo'] ?? ''),
        'created_at'     => current_time('mysql')
    ]);

    return ['status' => 'success', 'id' => $wpdb->insert_id];
}

function update_partner_institute($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'partner_institutes';
    $params = $request->get_json_params();
    $id = absint($request['id']);

    $wpdb->update($table, [
        'institute_name' => sanitize_text_field($params['institute_name'] ?? ''),
        'website_url'    => esc_url_raw($params['website_url'] ?? ''),
        'institute_logo' => esc_url_raw($params['institute_logo'] ?? ''),
    ], ['id' => $id]);

    return ['status' => 'updated'];
}

function delete_partner_institute($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'partner_institutes';
    $id = absint($request['id']);
    $wpdb->delete($table, ['id' => $id]);
    return ['status' => 'deleted'];
}

function upload_partner_institute_image() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
    }

    $fileTmp = $_FILES['file']['tmp_name'];
    $fileName = time() . '-partner-inst-' . sanitize_file_name($_FILES['file']['name']);
    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/partners";

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
            'Key'         => 'admin/partners/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return new WP_Error('upload_failed', $e->getMessage(), ['status' => 500]);
    }
}
