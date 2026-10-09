<?php
/**
 * Fellowship Manager & REST API Controller
 */

// Register Admin Submenu
add_action('admin_menu', function () {
    add_submenu_page(
        'group-careers',
        'Fellowships',
        'Fellowships',
        'manage_options',
        'fellowship-manager',
        'fellowship_manager_page'
    );
});

// Setup Table on Activation/Load
function custom_setup_fellowship_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'fellowships';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            short_description text DEFAULT '',
            subtitle varchar(255) DEFAULT '',
            description longtext DEFAULT '',
            duration varchar(100) DEFAULT '',
            who_apply varchar(255) DEFAULT '',
            image varchar(500) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
add_action('admin_init', 'custom_setup_fellowship_table');

// REST API setup
add_action('rest_api_init', function () {
    register_rest_route('fellowship/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_fellowships',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('fellowship/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_fellowship',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('fellowship/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_fellowship',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('fellowship/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_fellowship',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('fellowship/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_image_to_r2_fellowship',
        'permission_callback' => '__return_true'
    ]);
});

// UI Page
function fellowship_manager_page() {
    $nonce = wp_create_nonce('fellowship_manager_nonce');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Fellowships Manager</h1>
        <hr class="wp-header-end">

        <!-- Add / Edit Form -->
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px; max-width:900px;">
            <h2 id="form-heading">Add New Fellowship Entry</h2>
           
            <form id="fellowship-form">
                <input type="hidden" id="fellowship_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="title">Title <span style="color:red;">*</span></label></th>
                        <td><input type="text" id="title" class="regular-text" style="width:100%;" placeholder="e.g. Post-Doctoral Fellowship in Epidemiology" required></td>
                    </tr>
                    <tr>
                        <th><label for="short_description">Short Description <span style="color:red;">*</span></label></th>
                        <td><input type="text" id="short_description" class="regular-text" style="width:100%;" placeholder="Brief overview..." required></td>
                    </tr>
                    <tr>
                        <th><label for="subtitle">Subtitle / Tag</label></th>
                        <td><input type="text" id="subtitle" class="regular-text" style="width:100%;" placeholder="e.g. Research Track"></td>
                    </tr>
                    <tr>
                        <th><label for="description">Description</label></th>
                        <td>
                            <?php
                            wp_editor('', 'description', [
                                'textarea_name' => 'description',
                                'textarea_rows' => 8,
                                'media_buttons' => true,
                                'teeny' => false
                            ]);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="duration">Duration</label></th>
                        <td><input type="text" id="duration" class="regular-text" style="width:300px;" placeholder="e.g. 1 Year / 2 Years"></td>
                    </tr>
                    <tr>
                        <th><label for="who_apply">Who Should Apply</label></th>
                        <td><input type="text" id="who_apply" class="regular-text" style="width:100%;" placeholder="Eligibility criteria..."></td>
                    </tr>
                    <tr>
                        <th><label>Image</label></th>
                        <td>
                            <input type="file" id="image-upload" accept="image/*">
                            <input type="hidden" id="image-url">
                            <div id="image-preview" style="margin-top:10px;"></div>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" id="save-btn" class="button button-primary">Add Fellowship</button>
                    <button type="button" id="cancel-btn" class="button button-secondary" style="display:none; margin-left:10px;">Cancel</button>
                </p>
            </form>
        </div>

        <!-- Table Listing -->
        <h2>All Fellowships</h2>
        <table class="wp-list-table widefat fixed striped" style="max-width:1000px;">
            <thead>
                <tr>
                    <th style="width:60px;">Image</th>
                    <th style="width:200px;">Title</th>
                    <th>Short Description</th>
                    <th style="width:100px;">Duration</th>
                    <th style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody id="fellowships-list">
                <tr><td colspan="5">Loading...</td></tr>
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let editId = 0;

        function loadFellowships() {
            $.get('<?php echo rest_url('fellowship/v1/all'); ?>', function(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html = '<tr><td colspan="5">No fellowships found.</td></tr>';
                } else {
                    data.forEach(function(item) {
                        const img = item.image ? `<img src="${item.image}" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">` : '—';
                        html += `
                            <tr>
                                <td>${img}</td>
                                <td><strong>${item.title}</strong></td>
                                <td>${item.short_description || ''}</td>
                                <td>${item.duration || ''}</td>
                                <td>
                                    <button class="button button-small edit-btn" data-id="${item.id}">Edit</button>
                                    <button class="button button-small button-link-delete delete-btn" data-id="${item.id}">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#fellowships-list').html(html);
            });
        }

        // Image upload
        $('#image-upload').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: '<?php echo rest_url('fellowship/v1/upload-image'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.url) {
                        $('#image-url').val(res.url);
                        $('#image-preview').html(`<img src="${res.url}" style="max-height:160px;border-radius:6px;">`);
                    }
                },
                error: function() {
                    alert('Image upload failed.');
                }
            });
        });

        // Form Submit
        $('#fellowship-form').on('submit', function(e) {
            e.preventDefault();
            const title = $('#title').val().trim();
            if (!title) {
                alert('Please enter a title.');
                return;
            }

            const data = {
                title: title,
                short_description: $('#short_description').val().trim(),
                subtitle: $('#subtitle').val().trim(),
                description: tinymce.get('description') ? tinymce.get('description').getContent() : $('#description').val(),
                duration: $('#duration').val().trim(),
                who_apply: $('#who_apply').val().trim(),
                image: $('#image-url').val(),
                nonce: $('#nonce').val()
            };

            const url = editId 
                ? '<?php echo rest_url('fellowship/v1/update/'); ?>' + editId 
                : '<?php echo rest_url('fellowship/v1/add'); ?>';

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function() {
                    alert(editId ? 'Fellowship updated!' : 'Fellowship added successfully!');
                    resetForm();
                    loadFellowships();
                },
                error: function() {
                    alert('Failed to save.');
                }
            });
        });

        // Edit Button
        $(document).on('click', '.edit-btn', function() {
            editId = $(this).data('id');
            $('#form-heading').text('Edit Fellowship Entry');
            $('#save-btn').text('Update Fellowship');
            $('#cancel-btn').show();

            $.get('<?php echo rest_url('fellowship/v1/all'); ?>', function(fellowships) {
                const item = fellowships.find(i => i.id == editId);
                if (item) {
                    $('#title').val(item.title);
                    $('#short_description').val(item.short_description || '');
                    $('#subtitle').val(item.subtitle || '');
                    $('#duration').val(item.duration || '');
                    $('#who_apply').val(item.who_apply || '');
                    $('#image-url').val(item.image || '');
                    $('#image-preview').html(item.image ? `<img src="${item.image}" style="max-height:160px;border-radius:6px;">` : '');

                    if (tinymce.get('description')) {
                        tinymce.get('description').setContent(item.description || '');
                    }
                }
            });
        });

        // Cancel
        $('#cancel-btn').on('click', resetForm);

        function resetForm() {
            editId = 0;
            $('#fellowship-form')[0].reset();
            $('#image-url').val('');
            $('#image-preview').html('');
            $('#form-heading').text('Add New Fellowship Entry');
            $('#save-btn').text('Add Fellowship');
            $('#cancel-btn').hide();
            if (tinymce.get('description')) tinymce.get('description').setContent('');
        }

        // Delete
        $(document).on('click', '.delete-btn', function() {
            if (!confirm('Delete this fellowship entry?')) return;
            const id = $(this).data('id');
            $.ajax({
                url: '<?php echo rest_url('fellowship/v1/delete/'); ?>' + id,
                method: 'DELETE',
                success: function() {
                    loadFellowships();
                }
            });
        });

        loadFellowships();
    });
    </script>
    <?php
}

// REST Callbacks
function get_all_fellowships() {
    global $wpdb;
    $table = $wpdb->prefix . 'fellowships';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_fellowship($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'fellowships';
    $params = $request->get_json_params();

    $wpdb->insert($table, [
        'title'             => sanitize_text_field($params['title'] ?? ''),
        'short_description' => sanitize_text_field($params['short_description'] ?? ''),
        'subtitle'          => sanitize_text_field($params['subtitle'] ?? ''),
        'description'       => wp_kses_post($params['description'] ?? ''),
        'duration'          => sanitize_text_field($params['duration'] ?? ''),
        'who_apply'         => sanitize_text_field($params['who_apply'] ?? ''),
        'image'             => esc_url_raw($params['image'] ?? ''),
        'created_at'        => current_time('mysql')
    ]);

    return ['status' => 'success', 'id' => $wpdb->insert_id];
}

function update_fellowship($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'fellowships';
    $params = $request->get_json_params();
    $id = absint($request['id']);

    $wpdb->update($table, [
        'title'             => sanitize_text_field($params['title'] ?? ''),
        'short_description' => sanitize_text_field($params['short_description'] ?? ''),
        'subtitle'          => sanitize_text_field($params['subtitle'] ?? ''),
        'description'       => wp_kses_post($params['description'] ?? ''),
        'duration'          => sanitize_text_field($params['duration'] ?? ''),
        'who_apply'         => sanitize_text_field($params['who_apply'] ?? ''),
        'image'             => esc_url_raw($params['image'] ?? ''),
    ], ['id' => $id]);

    return ['status' => 'updated'];
}

function delete_fellowship($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'fellowships';
    $id = absint($request['id']);
    $wpdb->delete($table, ['id' => $id]);
    return ['status' => 'deleted'];
}

function upload_image_to_r2_fellowship() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
    }

    $fileTmp = $_FILES['file']['tmp_name'];
    $fileName = time() . '-fellowship-' . sanitize_file_name($_FILES['file']['name']);
    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/fellowships";

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
            'Key'         => 'admin/fellowships/' . $fileName,
            'SourceFile'  => $fileTmp,
            'ContentType' => $_FILES['file']['type']
        ]);

        return ['url' => $publicUrlBase . '/' . $fileName];
    } catch (Exception $e) {
        return new WP_Error('upload_failed', $e->getMessage(), ['status' => 500]);
    }
}
