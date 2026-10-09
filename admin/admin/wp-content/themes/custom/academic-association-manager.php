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

// Setup Table and Initial Seed Data
function custom_setup_academic_association_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_association';
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        program_type varchar(100) NOT NULL DEFAULT 'phd',
        title varchar(255) NOT NULL,
        tag varchar(255) DEFAULT '',
        duration varchar(100) DEFAULT '',
        description longtext DEFAULT '',
        eligibility longtext DEFAULT '',
        pdf_url varchar(500) DEFAULT '',
        image_url varchar(500) DEFAULT '',
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql);

    // Make sure sort_order column exists
    $has_sort_order = $wpdb->get_results("SHOW COLUMNS FROM `$table` LIKE 'sort_order'");
    if (empty($has_sort_order)) {
        $wpdb->query("ALTER TABLE `$table` ADD COLUMN sort_order int(11) DEFAULT 0");
    }

    // Check if table is empty; if so, seed initial data from website
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    if ($count == 0) {
        custom_seed_academic_association_data();
    }
}
add_action('init', 'custom_setup_academic_association_table');

// Seed Function
function custom_seed_academic_association_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_association';

    $default_items = [
        // PhD Programs
        [
            'program_type' => 'phd',
            'title'        => 'PhD & Postdoctoral Research Program',
            'tag'          => 'Full-Time / JRF',
            'duration'     => '3 - 5 Years',
            'description'  => '<p>IIGH through its collaborating partners facilitates PhD programs. Interested candidates are encouraged to visit INCLEN research themes and identify an area of their interest. Students are linked to any one of the ongoing research projects of IIGH and are encouraged to frame their thesis aligned with project objectives.</p><p>In addition, we are also recognized as a research center (RRC) by Indira Gandhi National Open University (IGNOU) and will be supporting student thesis.</p>',
            'eligibility'  => 'Those who are interested in pursuing a PhD must work in one of INCLEN research projects for a minimum of One Year as a Research Staff. Students with Junior Research Fellowships (JRF) are encouraged to develop a synopsis/research idea in agreement with IIGH objectives.',
            'pdf_url'      => '',
            'sort_order'   => 1
        ],
        [
            'program_type' => 'phd',
            'title'        => 'Jamia Hamdard University, New Delhi',
            'tag'          => 'Collaborating University',
            'duration'     => 'Doctoral Program',
            'description'  => '<p>Presently in India, IIGH is collaborating with Jamia Hamdard University, New Delhi for hosting PhD or postdoctoral programs across clinical epidemiology and public health themes.</p>',
            'eligibility'  => 'Postgraduates in Medicine, Public Health, Pharmacy, or Allied Health Sciences qualifying university entrance / fellowship requirements.',
            'pdf_url'      => '',
            'sort_order'   => 2
        ],
        [
            'program_type' => 'phd',
            'title'        => 'Sri Ram Chandra University, Chennai',
            'tag'          => 'Collaborating University',
            'duration'     => 'Doctoral Program',
            'description'  => '<p>Collaborative PhD and postdoctoral training in clinical epidemiology, community health, and biostatistics with Sri Ramachandra Institute of Higher Education and Research.</p>',
            'eligibility'  => 'Qualified candidates with masters/doctoral aspirations aligned with INCLEN research priorities.',
            'pdf_url'      => '',
            'sort_order'   => 3
        ],

        // Masters Programs
        [
            'program_type' => 'masters',
            'title'        => 'MPH Dissertation',
            'tag'          => 'Dissertation',
            'duration'     => '6 - 12 Months',
            'description'  => '<p>Complete your Master of Public Health dissertation with guidance from our experienced mentors. INCLEN collaborates with various universities to offer dissertation support and practical training for Masters students in Public Health, Epidemiology, and Biostatistics.</p>',
            'eligibility'  => 'Enrolled Master of Public Health (MPH) students in recognized Indian or international universities.',
            'pdf_url'      => '',
            'sort_order'   => 1
        ],
        [
            'program_type' => 'masters',
            'title'        => 'Field Attachments',
            'tag'          => 'Field Exposure',
            'duration'     => '1 - 3 Months',
            'description'  => '<p>Short-term field exposures to understand primary community data collection, demographic surveillance, quality assurance, and community engagement in global health research.</p>',
            'eligibility'  => 'Postgraduate students in Public Health, Social Work, Statistics, or Community Medicine.',
            'pdf_url'      => '',
            'sort_order'   => 2
        ],

        // Internship
        [
            'program_type' => 'internship',
            'title'        => 'Work Experience Internship (Mid Career Internship)',
            'tag'          => 'Mid Career',
            'duration'     => '3 to 24 Months',
            'description'  => '<p>The placement can be from 3 months to 24 months. During this period the candidate is expected to enhance the skills he/she acquired in their career (work experience) and tailor it towards real life global health situations. This program helps candidates to identify their personal development plan and equip them with skills to pursue independent research in future. This program is also ideal for candidates who want to establish a career as faculty members.</p>',
            'eligibility'  => 'Early to mid-career professionals in health sciences, public health, medicine, or social sciences seeking applied research competency.',
            'pdf_url'      => '',
            'sort_order'   => 1
        ],
        [
            'program_type' => 'internship',
            'title'        => 'Dissertation Internship',
            'tag'          => 'Student Internship',
            'duration'     => '4 to 12 Weeks',
            'description'  => '<p>The placement can be from 4 weeks to 12 weeks. This internship is primarily oriented for students from different academic backgrounds. During this internship a student is expected to do research under supervision as per the priority of the IIGH activities. The student can choose a topic within the thematic areas or on an area outside the purview of IIGH, with prior approval.</p>',
            'eligibility'  => 'Graduates, post graduates or doctoral students from any discipline related to health or public health are eligible to apply under this stream.',
            'pdf_url'      => '',
            'sort_order'   => 2
        ],

        // Courses / Training
        [
            'program_type' => 'courses',
            'title'        => 'LAMP (Leadership, Applied Epidemiology, Management and Practice)',
            'tag'          => 'Flagship Course',
            'duration'     => 'Modular / 6 Months',
            'description'  => '<p>A flagship leadership program designed for public health professionals and managers to develop leadership, management, and field epidemiology competencies in health program implementation.</p>',
            'eligibility'  => 'Public health professionals, program officers, medical officers, and researchers.',
            'pdf_url'      => '',
            'sort_order'   => 1
        ],
        [
            'program_type' => 'courses',
            'title'        => 'Field Epidemiology',
            'tag'          => 'Specialized Training',
            'duration'     => '3 Months',
            'description'  => '<p>Comprehensive training focused on the practical application of epidemiological methods in field settings. This program is designed for health professionals involved in disease surveillance and outbreak investigation.</p>',
            'eligibility'  => 'Health professionals, district surveillance officers, and epidemiologists.',
            'pdf_url'      => '',
            'sort_order'   => 2
        ],
        [
            'program_type' => 'courses',
            'title'        => 'Geospatial Epidemiology',
            'tag'          => 'Spatial Analysis',
            'duration'     => '4 Weeks',
            'description'  => '<p>Training in GIS mapping, spatial analysis of epidemiological data, hotspot detection, and resource allocation modeling for public health decision-makers.</p>',
            'eligibility'  => 'Researchers, health geographers, data analysts, and postgraduates.',
            'pdf_url'      => '',
            'sort_order'   => 3
        ],
        [
            'program_type' => 'courses',
            'title'        => 'Niche Training',
            'tag'          => 'Short Courses',
            'duration'     => '1 to 2 Weeks',
            'description'  => '<p>Focused short modules on systematic reviews, meta-analysis, biostatistics with R/Stata, protocol development, and Good Clinical Practice (GCP).</p>',
            'eligibility'  => 'Researchers, postgraduate medical students, clinicians, and faculty.',
            'pdf_url'      => '',
            'sort_order'   => 4
        ],
        [
            'program_type' => 'courses',
            'title'        => 'Qualitative Research in Health',
            'tag'          => 'Methodology',
            'duration'     => '4 Weeks',
            'description'  => '<p>Hands-on focus group facilitation, in-depth interviews, thematic analysis, and mixed methods design for health system and community studies.</p>',
            'eligibility'  => 'Social scientists, public health professionals, and qualitative researchers.',
            'pdf_url'      => '',
            'sort_order'   => 5
        ],
        [
            'program_type' => 'courses',
            'title'        => 'Fellowships',
            'tag'          => 'Research Fellowship',
            'duration'     => '1 - 2 Years',
            'description'  => '<p>Intensive research fellowship embedded in live multicentric global health studies under direct mentorship of senior INCLEN scientists.</p>',
            'eligibility'  => 'Postgraduates (MD, MPH, PhD) with strong quantitative or qualitative research aptitude.',
            'pdf_url'      => '',
            'sort_order'   => 6
        ]
    ];

    foreach ($default_items as $item) {
        $wpdb->insert($table, [
            'program_type' => $item['program_type'],
            'title'        => $item['title'],
            'tag'          => $item['tag'],
            'duration'     => $item['duration'],
            'description'  => $item['description'],
            'eligibility'  => $item['eligibility'],
            'pdf_url'      => $item['pdf_url'],
            'sort_order'   => $item['sort_order'],
            'created_at'   => current_time('mysql')
        ]);
    }
}

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

    register_rest_route('academic-association/v1', '/reset-defaults', [
        'methods'  => 'POST',
        'callback' => 'reset_academic_associations_to_default',
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
        <div style="background:#fff; padding:25px; border:1px solid #c3c4c7; border-radius:8px; margin-bottom:30px; max-width:960px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h2 id="form-heading" style="margin:0; font-size:18px;">Add New Academic Association Item</h2>
                <button type="button" id="seed-btn" class="button button-secondary" style="font-size:12px;" title="Reset or populate default items from the website">
                    ↺ Seed / Reset Default Data
                </button>
            </div>
            
            <form id="assoc-form">
                <input type="hidden" id="assoc_id" value="">
                <input type="hidden" id="nonce" value="<?php echo esc_attr($nonce); ?>">

                <table class="form-table">
                    <tr>
                        <th style="width:200px;"><label for="program_type">Program Category <span style="color:red;">*</span></label></th>
                        <td>
                            <select id="program_type" style="min-width:260px; padding:5px 8px; font-weight:600;">
                                <option value="phd">PhD Program</option>
                                <option value="masters">Masters</option>
                                <option value="internship">Internship</option>
                                <option value="courses">Courses / Training</option>
                            </select>
                            <p class="description">Select which tab on the website this item belongs to.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="title">Title / Name <span style="color:red;">*</span></label></th>
                        <td><input type="text" id="title" class="regular-text" style="width:100%;" placeholder="e.g. Clinical Epidemiology Track" required></td>
                    </tr>
                    <tr>
                        <th><label for="tag">Tag / Badge</label></th>
                        <td><input type="text" id="tag" class="regular-text" style="width:100%;" placeholder="e.g. Full-Time / Part-Time / Flagship Course"></td>
                    </tr>
                    <tr>
                        <th><label for="duration">Duration</label></th>
                        <td><input type="text" id="duration" class="regular-text" style="width:320px;" placeholder="e.g. 3 - 5 Years / 6 Months"></td>
                    </tr>
                    <tr>
                        <th><label for="sort_order">Sort Order</label></th>
                        <td>
                            <input type="number" id="sort_order" class="small-text" value="0" min="0" step="1">
                            <span class="description">Lower numbers appear first.</span>
                        </td>
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
                            <textarea id="eligibility" rows="4" style="width:100%;" placeholder="Requirements, qualifications, and prerequisites..."></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Brochure / Attachment (PDF/Doc)</label></th>
                        <td>
                            <input type="file" id="file-upload">
                            <input type="hidden" id="file-url">
                            <div id="file-upload-status" style="margin-top:6px; color:#2563eb; font-weight:600; display:none;">Uploading...</div>
                            <div id="file-preview" style="margin-top:8px;"></div>
                        </td>
                    </tr>
                </table>

                <p class="submit" style="margin-top:20px; padding-top:15px; border-top:1px solid #eee;">
                    <button type="submit" id="save-btn" class="button button-primary button-large">Save Item</button>
                    <button type="button" id="cancel-btn" class="button button-secondary button-large" style="display:none; margin-left:10px;">Cancel</button>
                </p>
            </form>
        </div>

        <!-- Table Listing with Filter & Search -->
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:15px; max-width:1000px; flex-wrap:wrap; gap:10px;">
            <div>
                <h2 style="margin:0 0 10px 0;">All Academic Association Items</h2>
                <div id="category-filter-buttons" style="display:flex; gap:6px; flex-wrap:wrap;">
                    <button type="button" class="button cat-filter active" data-cat="all">All (<span id="count-all">0</span>)</button>
                    <button type="button" class="button cat-filter" data-cat="phd">PhD (<span id="count-phd">0</span>)</button>
                    <button type="button" class="button cat-filter" data-cat="masters">Masters (<span id="count-masters">0</span>)</button>
                    <button type="button" class="button cat-filter" data-cat="internship">Internship (<span id="count-internship">0</span>)</button>
                    <button type="button" class="button cat-filter" data-cat="courses">Courses / Training (<span id="count-courses">0</span>)</button>
                </div>
            </div>
            <div>
                <input type="search" id="search-box" placeholder="Search items..." style="padding:4px 8px; min-width:200px;">
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped" style="max-width:1000px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <thead>
                <tr>
                    <th style="width:130px;">Category</th>
                    <th>Title / Name</th>
                    <th style="width:130px;">Tag</th>
                    <th style="width:110px;">Duration</th>
                    <th style="width:70px; text-align:center;">Order</th>
                    <th style="width:130px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody id="assoc-list">
                <tr><td colspan="6" style="text-align:center; padding:20px;">Loading items...</td></tr>
            </tbody>
        </table>
    </div>

    <style>
        .cat-badge {
            display:inline-block;
            padding:3px 8px;
            border-radius:4px;
            font-size:11px;
            text-transform:uppercase;
            font-weight:700;
            letter-spacing:0.5px;
        }
        .cat-badge-phd { background:#f3e8ff; color:#7e22ce; }
        .cat-badge-masters { background:#d1fae5; color:#065f46; }
        .cat-badge-internship { background:#fef3c7; color:#92400e; }
        .cat-badge-courses { background:#dbeafe; color:#1e40af; }
        .cat-filter.active { background:#2271b1; color:#fff; border-color:#2271b1; font-weight:600; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        let editId = 0;
        let allItems = [];
        let currentCat = 'all';

        function getCatBadge(type) {
            type = type || 'phd';
            let label = type.toUpperCase();
            if (type === 'courses') label = 'COURSES';
            return `<span class="cat-badge cat-badge-${type}">${label}</span>`;
        }

        function renderItems() {
            let filtered = allItems;
            if (currentCat !== 'all') {
                filtered = filtered.filter(i => i.program_type === currentCat);
            }

            const search = $('#search-box').val().trim().toLowerCase();
            if (search) {
                filtered = filtered.filter(i => 
                    (i.title && i.title.toLowerCase().includes(search)) ||
                    (i.tag && i.tag.toLowerCase().includes(search)) ||
                    (i.duration && i.duration.toLowerCase().includes(search))
                );
            }

            // Update Counts
            $('#count-all').text(allItems.length);
            $('#count-phd').text(allItems.filter(i => i.program_type === 'phd').length);
            $('#count-masters').text(allItems.filter(i => i.program_type === 'masters').length);
            $('#count-internship').text(allItems.filter(i => i.program_type === 'internship').length);
            $('#count-courses').text(allItems.filter(i => i.program_type === 'courses').length);

            if (filtered.length === 0) {
                $('#assoc-list').html('<tr><td colspan="6" style="text-align:center; padding:20px;">No items found.</td></tr>');
                return;
            }

            let html = '';
            filtered.forEach(function(item) {
                html += `
                    <tr>
                        <td>${getCatBadge(item.program_type)}</td>
                        <td>
                            <strong>${escapeHtml(item.title)}</strong>
                            ${item.pdf_url ? `<br><a href="${item.pdf_url}" target="_blank" style="font-size:11px; color:#2563eb;">📄 View File</a>` : ''}
                        </td>
                        <td><span style="color:#64748b; font-size:12px;">${escapeHtml(item.tag || '—')}</span></td>
                        <td><span style="color:#475569; font-size:12px;">${escapeHtml(item.duration || '—')}</span></td>
                        <td style="text-align:center; font-weight:600;">${item.sort_order || 0}</td>
                        <td style="text-align:center;">
                            <button class="button button-small edit-btn" data-id="${item.id}">Edit</button>
                            <button class="button button-small button-link-delete delete-btn" data-id="${item.id}" style="color:#b32d2e; margin-left:4px;">Delete</button>
                        </td>
                    </tr>
                `;
            });
            $('#assoc-list').html(html);
        }

        function loadItems() {
            $.get('<?php echo rest_url('academic-association/v1/all'); ?>', function(data) {
                allItems = Array.isArray(data) ? data : [];
                renderItems();
            }).fail(function() {
                $('#assoc-list').html('<tr><td colspan="6" style="text-align:center; color:red; padding:20px;">Failed to load items.</td></tr>');
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            return $('<div>').text(text).html();
        }

        $('.cat-filter').on('click', function() {
            $('.cat-filter').removeClass('active');
            $(this).addClass('active');
            currentCat = $(this).data('cat');
            renderItems();
        });

        $('#search-box').on('input', function() {
            renderItems();
        });

        // Seed / Reset Default Data
        $('#seed-btn').on('click', function() {
            if (!confirm('This will seed the database with the default Academic Association items. Existing items will remain or be re-populated if empty. Proceed?')) return;
            const $btn = $(this);
            $btn.prop('disabled', true).text('Seeding...');

            $.ajax({
                url: '<?php echo rest_url('academic-association/v1/reset-defaults'); ?>',
                method: 'POST',
                success: function(res) {
                    alert(res.message || 'Default data seeded successfully!');
                    loadItems();
                },
                error: function() {
                    alert('Failed to seed default data.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('↺ Seed / Reset Default Data');
                }
            });
        });

        // File upload
        $('#file-upload').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);
            $('#file-upload-status').show();

            $.ajax({
                url: '<?php echo rest_url('academic-association/v1/upload-file'); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $('#file-upload-status').hide();
                    if (res.url) {
                        $('#file-url').val(res.url);
                        $('#file-preview').html(`<a href="${res.url}" target="_blank" style="font-weight:600; color:#2563eb;">✓ File Attached: View File</a> <button type="button" id="remove-file-btn" class="button button-small" style="margin-left:6px;">Remove</button>`);
                    }
                },
                error: function(err) {
                    $('#file-upload-status').hide();
                    alert('File upload failed: ' + (err.responseJSON?.message || 'Server error'));
                }
            });
        });

        $(document).on('click', '#remove-file-btn', function() {
            $('#file-url').val('');
            $('#file-preview').html('');
            $('#file-upload').val('');
        });

        // Form Submit
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
                sort_order: parseInt($('#sort_order').val()) || 0,
                description: tinymce.get('description') ? tinymce.get('description').getContent() : $('#description').val(),
                eligibility: $('#eligibility').val().trim(),
                pdf_url: $('#file-url').val(),
                nonce: $('#nonce').val()
            };

            const url = editId 
                ? '<?php echo rest_url('academic-association/v1/update/'); ?>' + editId 
                : '<?php echo rest_url('academic-association/v1/add'); ?>';

            const $btn = $('#save-btn');
            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function() {
                    alert(editId ? 'Item updated successfully!' : 'Item added successfully!');
                    resetForm();
                    loadItems();
                },
                error: function() {
                    alert('Failed to save item.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(editId ? 'Update Item' : 'Save Item');
                }
            });
        });

        // Edit
        $(document).on('click', '.edit-btn', function() {
            editId = $(this).data('id');
            const item = allItems.find(i => i.id == editId);
            if (item) {
                $('#form-heading').text('Edit Academic Association Item (ID: ' + editId + ')');
                $('#save-btn').text('Update Item');
                $('#cancel-btn').show();

                $('#program_type').val(item.program_type || 'phd');
                $('#title').val(item.title || '');
                $('#tag').val(item.tag || '');
                $('#duration').val(item.duration || '');
                $('#sort_order').val(item.sort_order || 0);
                $('#eligibility').val(item.eligibility || '');
                $('#file-url').val(item.pdf_url || '');

                if (item.pdf_url) {
                    $('#file-preview').html(`<a href="${item.pdf_url}" target="_blank" style="font-weight:600; color:#2563eb;">✓ Current File: View Attachment</a> <button type="button" id="remove-file-btn" class="button button-small" style="margin-left:6px;">Remove</button>`);
                } else {
                    $('#file-preview').html('');
                }

                if (tinymce.get('description')) {
                    tinymce.get('description').setContent(item.description || '');
                } else {
                    $('#description').val(item.description || '');
                }

                $('html, body').animate({ scrollTop: $('#assoc-form').offset().top - 60 }, 300);
            }
        });

        $('#cancel-btn').on('click', resetForm);

        function resetForm() {
            editId = 0;
            $('#assoc-form')[0].reset();
            $('#file-url').val('');
            $('#file-preview').html('');
            $('#file-upload').val('');
            $('#sort_order').val('0');
            $('#form-heading').text('Add New Academic Association Item');
            $('#save-btn').text('Save Item');
            $('#cancel-btn').hide();
            if (tinymce.get('description')) {
                tinymce.get('description').setContent('');
            }
        }

        // Delete
        $(document).on('click', '.delete-btn', function() {
            if (!confirm('Are you sure you want to delete this item?')) return;
            const id = $(this).data('id');
            $.ajax({
                url: '<?php echo rest_url('academic-association/v1/delete/'); ?>' + id,
                method: 'DELETE',
                success: function() {
                    loadItems();
                },
                error: function() {
                    alert('Failed to delete item.');
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
    return $wpdb->get_results("SELECT * FROM $table ORDER BY sort_order ASC, id ASC");
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
        'sort_order'   => intval($params['sort_order'] ?? 0),
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
        'sort_order'   => intval($params['sort_order'] ?? 0),
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

function reset_academic_associations_to_default() {
    global $wpdb;
    $table = $wpdb->prefix . 'academic_association';
    
    // Clear and re-seed
    $wpdb->query("TRUNCATE TABLE $table");
    custom_seed_academic_association_data();

    return ['status' => 'success', 'message' => 'Default academic association items populated successfully.'];
}

function upload_academic_assoc_file() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
    }

    $fileTmp = $_FILES['file']['tmp_name'];
    $fileName = time() . '-academic-' . sanitize_file_name($_FILES['file']['name']);
    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/academic";

    // Attempt Cloudflare R2 / S3 upload
    if (!empty($accountId) && !empty($accessKey) && !empty($secretKey) && !empty($bucket)) {
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
            // Fallback to standard WP upload if R2 fails
        }
    }

    // Standard WordPress Upload Fallback
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $uploaded = wp_handle_upload($_FILES['file'], ['test_form' => false]);
    if (isset($uploaded['url'])) {
        return ['url' => $uploaded['url']];
    }

    return new WP_Error('upload_failed', $uploaded['error'] ?? 'Upload failed', ['status' => 500]);
}
