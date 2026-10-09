<?php
// Manager for INCLEN Tools (Open Access INCLEN Tools)

// Registration for REST API
add_action('rest_api_init', function () {
    // Migration: Ensure 'year' column exists
    global $wpdb;
    $table_name = $wpdb->prefix . 'inclen_tools';
    $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `$table_name` LIKE %s", 'year'));
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE `$table_name` ADD `year` VARCHAR(50) AFTER `tool_name` ");
    }

    register_rest_route('inclen-tools/v1', '/all', [
        'methods' => 'GET',
        'callback' => 'get_all_inclen_tools',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('inclen-tools/v1', '/add', [
        'methods' => 'POST',
        'callback' => 'add_inclen_tool',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route('inclen-tools/v1', '/update/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'update_inclen_tool',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route('inclen-tools/v1', '/delete/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'delete_inclen_tool',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);

    register_rest_route('inclen-tools/v1', '/upload-pdf', [
        'methods' => 'POST',
        'callback' => 'upload_pdf_to_r2_tools',
        'permission_callback' => function() { return current_user_can('manage_options'); }
    ]);
});

// PDF Upload to R2
function upload_pdf_to_r2_tools() {
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
            'Key' => 'admin/inclen-tools/pdfs/' . $fileName,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $file['type'],
            'ACL' => 'public-read',
        ]);

        return ['url' => R2_PUBLIC_URL . '/inclen-tools/pdfs/' . $fileName];
    } catch (Throwable $e) {
        return new WP_Error('upload_error', $e->getMessage(), ['status' => 500]);
    }
}

function get_all_inclen_tools() {
    global $wpdb;
    $table = $wpdb->prefix . 'inclen_tools';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC") ?: [];
    return ['value' => $results];
}

function add_inclen_tool($request) {
    try {
        global $wpdb;
        $table = $wpdb->prefix . 'inclen_tools';
        $params = $request->get_json_params();

        if (!$params) return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);

        $wpdb->insert($table, [
            'project_name' => sanitize_text_field($params['project_name'] ?? ''),
            'tool_name'    => sanitize_text_field($params['tool_name'] ?? ''),
            'year'         => sanitize_text_field($params['year'] ?? ''),
            'modules'      => is_string($params['modules'] ?? '') ? ($params['modules'] ?? '[]') : json_encode($params['modules'] ?? []),
            'cover_image'  => esc_url_raw($params['cover_image'] ?? ''),
            'pdfs'         => is_string($params['pdfs'] ?? '') ? ($params['pdfs'] ?? '[]') : json_encode($params['pdfs'] ?? [])
        ]);

        return ['status' => 'success', 'id' => $wpdb->insert_id];
    } catch (Throwable $e) {
        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
    }
}

function update_inclen_tool($request) {
    try {
        global $wpdb;
        $table = $wpdb->prefix . 'inclen_tools';
        $id = intval($request['id']);
        $params = $request->get_json_params();

        if (!$params) return new WP_Error('invalid_json', 'Invalid JSON body', ['status' => 400]);

        $wpdb->update($table, [
            'project_name' => sanitize_text_field($params['project_name'] ?? ''),
            'tool_name'    => sanitize_text_field($params['tool_name'] ?? ''),
            'year'         => sanitize_text_field($params['year'] ?? ''),
            'modules'      => is_string($params['modules'] ?? '') ? ($params['modules'] ?? '[]') : json_encode($params['modules'] ?? []),
            'cover_image'  => esc_url_raw($params['cover_image'] ?? ''),
            'pdfs'         => is_string($params['pdfs'] ?? '') ? ($params['pdfs'] ?? '[]') : json_encode($params['pdfs'] ?? [])
        ], ['id' => $id]);

        return ['status' => 'success'];
    } catch (Throwable $e) {
        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
    }
}

function delete_inclen_tool($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'inclen_tools';
    $wpdb->delete($table, ['id' => intval($request['id'])]);
    return ['status' => 'deleted'];
}

// Admin Menu and UI
add_action('admin_menu', function () {
    add_submenu_page(
        'group-resources',
        'Research Tools',
        'Research Tools',
        'manage_options',
        'inclen-tools',
        'inclen_tools_admin_page'
    );
});

function inclen_tools_admin_page() {
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
        .module-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 20px;
        }
        .module-item {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #f3f4f6;
            position: relative;
        }
        .remove-module {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #9ca3af;
            cursor: pointer;
        }
        .remove-module:hover { color: #ef4444; }
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
        .btn-add-module {
            background: #fff !important;
            border: 1px solid #00558f !important;
            color: #00558f !important;
            border-radius: 6px !important;
            padding: 8px 16px !important;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
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
        <h1>Research Tools Manager (Open Access INCLEN Tools)</h1>

        <div class="inclen-card" id="tool-form-container">
            <h2 id="tool-form-title" style="font-size: 18px; margin-bottom: 30px;">Add New INCLEN Tool</h2>
            
            <input type="hidden" id="tool-id" value="">

            <div class="form-row">
                <div class="form-label">Project Name <span>*</span></div>
                <input type="text" id="project-name" class="form-input" placeholder="Enter project name">
            </div>

            <div class="form-row">
                <div class="form-label">Tool Name</div>
                <input type="text" id="tool-name" class="form-input" placeholder="Enter tool name">
            </div>

            <div class="form-row">
                <div class="form-label">Year of Completion</div>
                <input type="text" id="tool-year" class="form-input" placeholder="Enter year (e.g. 2024)">
            </div>

            <div class="form-row">
                <div class="form-label">Tool PDF Documents <span>*</span></div>
                <div>
                    <button type="button" class="btn-upload" id="upload-main-pdf">
                        <span class="dashicons dashicons-upload"></span> Upload PDF
                    </button>
                    <div id="main-pdf-list"></div>
                </div>
            </div>

            <div class="module-section-header">
                <div style="font-size: 16px; font-weight: 500; color: #374151;">Modules / Sections</div>
                <button type="button" class="btn-add-module" id="add-module-btn">
                    <span class="dashicons dashicons-plus"></span> Add Module
                </button>
            </div>

            <div id="modules-container">
                <!-- Modules will be added here -->
            </div>

            <div style="margin-top: 40px; border-top: 1px solid #f3f4f6; padding-top: 20px;">
                <button type="button" class="btn-save" id="save-tool-btn">Save Tool</button>
                <button type="button" class="btn-clear" id="clear-form-btn">Clear Form</button>
            </div>
        </div>

        <h2 style="margin-top: 40px;">All INCLEN Tools</h2>
        <table class="wp-list-table widefat fixed striped" style="border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
            <thead>
                <tr>
                    <th style="padding: 15px; width: 60px;">Sr No</th>
                    <th style="padding: 15px;">Project Name</th>
                    <th style="padding: 15px;">Tool Name</th>
                    <th style="padding: 15px;">Year</th>
                    <th style="padding: 15px;">PDFs / Modules</th>
                    <th style="padding: 15px; width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tools-list">
                <!-- Data loaded via JS -->
            </tbody>
        </table>
    </div>

    <script>
    jQuery(document).ready(function($) {
        const API_BASE = '<?php echo rest_url('inclen-tools/v1'); ?>';
        const WP_NONCE = '<?php echo wp_create_nonce('wp_rest'); ?>';
        
        let toolsMap = {};
        let mainPdfs = [];
        let modules = [];

        function renderMainPdfs() {
            const container = $('#main-pdf-list');
            container.empty();
            mainPdfs.forEach((pdf, index) => {
                container.append(`
                    <div class="pdf-preview-item">
                        <span class="dashicons dashicons-pdf" style="color: #ef4444;"></span>
                        <a href="${pdf.url}" target="_blank" style="text-decoration:none; color:#2563eb;">${pdf.name || 'PDF Document'}</a>
                        <span class="remove-pdf dashicons dashicons-no-alt" data-index="${index}" style="cursor:pointer;" title="Remove"></span>
                    </div>
                `);
            });
        }

        function renderModules() {
            const container = $('#modules-container');
            container.empty();
            modules.forEach((module, mIndex) => {
                let pdfHtml = '';
                const modPdfs = Array.isArray(module.pdfs) ? module.pdfs : [];
                modPdfs.forEach((pdf, pIndex) => {
                    pdfHtml += `
                        <div class="pdf-preview-item">
                            <span class="dashicons dashicons-pdf" style="color: #ef4444;"></span>
                            <a href="${pdf.url}" target="_blank" style="text-decoration:none; color:#2563eb;">${pdf.name || 'Module PDF'}</a>
                            <span class="remove-module-pdf dashicons dashicons-no-alt" data-mindex="${mIndex}" data-pindex="${pIndex}" style="cursor:pointer;" title="Remove"></span>
                        </div>
                    `;
                });

                container.append(`
                    <div class="module-item" data-index="${mIndex}">
                        <span class="remove-module dashicons dashicons-no-alt" data-index="${mIndex}" title="Remove Module"></span>
                        <div class="form-row">
                            <div class="form-label">Module Name</div>
                            <input type="text" class="form-input module-name" value="${(module.name || '').replace(/"/g, '&quot;')}" placeholder="Enter module name" data-index="${mIndex}">
                        </div>
                        <div class="form-row" style="margin-bottom: 0;">
                            <div class="form-label">Module PDF</div>
                            <div>
                                <button type="button" class="btn-upload upload-module-pdf" data-index="${mIndex}">
                                    <span class="dashicons dashicons-upload"></span> Upload PDF
                                </button>
                                <div class="module-pdf-list">${pdfHtml}</div>
                            </div>
                        </div>
                    </div>
                `);
            });
        }

        // Add Module
        $('#add-module-btn').click(function() {
            // sync current inputs before adding
            $('.module-name').each(function() {
                const idx = $(this).data('index');
                if (modules[idx]) modules[idx].name = $(this).val();
            });
            modules.push({ name: '', pdfs: [] });
            renderModules();
        });

        // Remove Module
        $(document).on('click', '.remove-module', function() {
            const index = $(this).data('index');
            $('.module-name').each(function() {
                const idx = $(this).data('index');
                if (modules[idx]) modules[idx].name = $(this).val();
            });
            modules.splice(index, 1);
            renderModules();
        });

        // Update Module Name
        $(document).on('input', '.module-name', function() {
            const index = $(this).data('index');
            if (modules[index]) modules[index].name = $(this).val();
        });

        // Main PDF Upload
        $('#upload-main-pdf').click(function() {
            const fileInput = $('<input type="file" accept="application/pdf">');
            fileInput.on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);

                const btn = $('#upload-main-pdf');
                btn.prop('disabled', true).text('Uploading...');

                $.ajax({
                    url: API_BASE + '/upload-pdf',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                    success: function(res) {
                        mainPdfs.push({ name: file.name, url: res.url });
                        renderMainPdfs();
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

        // Module PDF Upload
        $(document).on('click', '.upload-module-pdf', function() {
            const mIndex = $(this).data('index');
            const fileInput = $('<input type="file" accept="application/pdf">');
            fileInput.on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                // sync names first
                $('.module-name').each(function() {
                    const idx = $(this).data('index');
                    if (modules[idx]) modules[idx].name = $(this).val();
                });

                const formData = new FormData();
                formData.append('file', file);

                const btn = $(e.target).closest('.btn-upload');
                btn.prop('disabled', true).text('Uploading...');

                $.ajax({
                    url: API_BASE + '/upload-pdf',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                    success: function(res) {
                        if (!modules[mIndex].pdfs) modules[mIndex].pdfs = [];
                        modules[mIndex].pdfs.push({ name: file.name, url: res.url });
                        renderModules();
                    },
                    error: function(xhr) {
                        alert('Upload failed: ' + (xhr.responseJSON?.message || 'Server error'));
                        renderModules();
                    }
                });
            });
            fileInput.click();
        });

        // Remove Main PDF
        $(document).on('click', '.remove-pdf', function() {
            const index = $(this).data('index');
            mainPdfs.splice(index, 1);
            renderMainPdfs();
        });

        // Remove Module PDF
        $(document).on('click', '.remove-module-pdf', function() {
            const mIndex = $(this).data('mindex');
            const pIndex = $(this).data('pindex');
            if (modules[mIndex] && modules[mIndex].pdfs) {
                modules[mIndex].pdfs.splice(pIndex, 1);
                renderModules();
            }
        });

        // Load Tools
        function loadTools() {
            $.get(API_BASE + '/all', function(response) {
                const list = $('#tools-list');
                list.empty();
                toolsMap = {};
                const data = response.value || [];
                if (data.length === 0) {
                    list.append('<tr><td colspan="6" style="padding:20px; text-align:center; color:#888;">No tools found.</td></tr>');
                    return;
                }
                data.forEach((tool, index) => {
                    toolsMap[tool.id] = tool;
                    let pdfCount = 0;
                    let modCount = 0;
                    try {
                        const parsedPdfs = typeof tool.pdfs === 'string' ? JSON.parse(tool.pdfs || '[]') : (tool.pdfs || []);
                        pdfCount = parsedPdfs.length;
                    } catch(e) {}
                    try {
                        const parsedMods = typeof tool.modules === 'string' ? JSON.parse(tool.modules || '[]') : (tool.modules || []);
                        modCount = parsedMods.length;
                    } catch(e) {}

                    list.append(`
                        <tr>
                            <td style="padding: 15px;">${index + 1}</td>
                            <td style="padding: 15px;"><strong>${$('<div>').text(tool.project_name || '').html()}</strong></td>
                            <td style="padding: 15px;">${$('<div>').text(tool.tool_name || '-').html()}</td>
                            <td style="padding: 15px;">${$('<div>').text(tool.year || '-').html()}</td>
                            <td style="padding: 15px;">
                                <span class="badge" style="background:#e0f2fe; color:#0369a1; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600;">${pdfCount} PDFs</span>
                                <span class="badge" style="background:#f1f5f9; color:#475569; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600; margin-left:4px;">${modCount} Modules</span>
                            </td>
                            <td style="padding: 15px;">
                                <button class="button edit-btn" data-id="${tool.id}">Edit</button>
                                <button class="button delete-btn" data-id="${tool.id}" style="color: #ef4444; border-color:#fca5a5;">Delete</button>
                            </td>
                        </tr>
                    `);
                });
            });
        }

        loadTools();

        // Save Tool (Add or Update)
        $('#save-tool-btn').click(function() {
            // sync module names
            $('.module-name').each(function() {
                const idx = $(this).data('index');
                if (modules[idx]) modules[idx].name = $(this).val();
            });

            const id = $('#tool-id').val();
            const data = {
                project_name: $('#project-name').val().trim(),
                tool_name: $('#tool-name').val().trim(),
                year: $('#tool-year').val().trim(),
                pdfs: JSON.stringify(mainPdfs),
                modules: JSON.stringify(modules)
            };

            if (!data.project_name) {
                alert('Project Name is required');
                $('#project-name').focus();
                return;
            }

            const url = id ? API_BASE + '/update/' + id : API_BASE + '/add';
            const btn = $(this);
            btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                success: function() {
                    alert(id ? 'Tool updated successfully!' : 'Tool added successfully!');
                    clearForm();
                    loadTools();
                    btn.prop('disabled', false).text(id ? 'Update Tool' : 'Save Tool');
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || xhr.responseText || 'Failed to save'));
                    btn.prop('disabled', false).text(id ? 'Update Tool' : 'Save Tool');
                }
            });
        });

        function clearForm() {
            $('#tool-id').val('');
            $('#project-name').val('');
            $('#tool-name').val('');
            $('#tool-year').val('');
            mainPdfs = [];
            modules = [];
            renderMainPdfs();
            renderModules();
            $('#tool-form-title').text('Add New INCLEN Tool');
            $('#save-tool-btn').text('Save Tool');
        }

        $('#clear-form-btn').click(clearForm);

        // Edit
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            const tool = toolsMap[id];
            if (!tool) return;

            $('#tool-id').val(tool.id);
            $('#project-name').val(tool.project_name || '');
            $('#tool-name').val(tool.tool_name || '');
            $('#tool-year').val(tool.year || '');
            
            try {
                mainPdfs = typeof tool.pdfs === 'string' ? JSON.parse(tool.pdfs || '[]') : (tool.pdfs || []);
                if (!Array.isArray(mainPdfs)) mainPdfs = [];
            } catch(e) {
                mainPdfs = [];
            }

            try {
                modules = typeof tool.modules === 'string' ? JSON.parse(tool.modules || '[]') : (tool.modules || []);
                if (!Array.isArray(modules)) modules = [];
            } catch(e) {
                modules = [];
            }
            
            renderMainPdfs();
            renderModules();

            $('#tool-form-title').text('Edit INCLEN Tool (ID: ' + tool.id + ')');
            $('#save-tool-btn').text('Update Tool');
            $('html, body').animate({ scrollTop: $('#tool-form-container').offset().top - 40 }, 300);
        });

        // Delete
        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            if (confirm('Are you sure you want to delete this tool?')) {
                $.ajax({
                    url: API_BASE + '/delete/' + id,
                    method: 'POST',
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', WP_NONCE); },
                    success: function() {
                        loadTools();
                        if ($('#tool-id').val() == id) {
                            clearForm();
                        }
                    },
                    error: function() {
                        alert('Failed to delete tool');
                    }
                });
            }
        });
    });
    </script>
    <?php
}