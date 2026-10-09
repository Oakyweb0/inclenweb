<?php
// Register all Home Page submenus
add_action('admin_menu', function () {
    // Global Presence under About
    add_submenu_page(
        'group-about',
        'Global Presence',
        'Global Presence',
        'manage_options',
        'home-presence',
        'home_presence_page'
    );

    // Strategic Collaborators under About
    add_submenu_page(
        'group-about',
        'Strategic Collaborators',
        'Strategic Collaborators',
        'manage_options',
        'home-collaborators',
        'home_collaborators_page'
    );

    // Area of Work under Our Work
    add_submenu_page(
        'group-our-work',
        'Area of Work',
        'Area of Work',
        'manage_options',
        'home-research-areas',
        'home_research_areas_page'
    );
});

// REST API setup for Home Hero
add_action('rest_api_init', function () {
    register_rest_route('home-hero/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_home_heroes',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-hero/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_home_hero',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-hero/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_home_hero',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-hero/v1', '/delete/(?P<id>\d+)', [
        'methods'  => ['DELETE', 'POST'],
        'callback' => 'delete_home_hero',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-hero/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_home_hero_image_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function upload_home_hero_image_to_r2() {
    return upload_any_home_image_to_r2('hero');
}

function upload_home_about_image_to_r2() {
    return upload_any_home_image_to_r2('about');
}

function upload_home_collaborator_logo_to_r2() {
    return upload_any_home_image_to_r2('collaborator');
}

function upload_any_home_image_to_r2($prefix) {
    global $accountId, $accessKey, $secretKey, $bucket, $public_url;

    if (!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $cleanOriginal = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['file']['name']));
    $fileName = time() . '-' . $prefix . '-' . $cleanOriginal;
    $publicUrlBase = !empty($public_url) ? rtrim($public_url, '/') : "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin";
    
    // Try Cloudflare R2 Upload
    try {
        if (class_exists('\\Aws\\S3\\S3Client') && !empty($accountId) && !empty($accessKey) && !empty($secretKey)) {
            $client = new Aws\S3\S3Client([
                'version'  => 'latest',
                'region'   => 'auto',
                'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
                'credentials' => [
                    'key'    => $accessKey,
                    'secret' => $secretKey,
                ],
            ]);

            $mimeType = $_FILES['file']['type'] ?? 'image/jpeg';
            if (empty($mimeType) && function_exists('mime_content_type') && file_exists($fileTmp)) {
                $mimeType = mime_content_type($fileTmp);
            }
            if (empty($mimeType)) $mimeType = 'image/jpeg';

            $client->putObject([
                'Bucket'      => !empty($bucket) ? $bucket : 'inclen',
                'Key'         => 'admin/' . $fileName,
                'SourceFile'  => $fileTmp,
                'ContentType' => $mimeType
            ]);

            return ['url' => $publicUrlBase . '/' . $fileName];
        }
    } catch (Exception $e) {
        error_log('R2 Upload error in home manager: ' . $e->getMessage());
    }

    // Local upload fallback
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/home/';
    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }
    $target_file = $target_dir . $fileName;
    if (move_uploaded_file($fileTmp, $target_file)) {
        return ['url' => $upload_dir['baseurl'] . '/home/' . $fileName];
    }

    return ['error' => 'Upload failed'];
}

// REST API setup for Home About
add_action('rest_api_init', function () {
    register_rest_route('home-about/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_home_abouts',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-about/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_home_about',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-about/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_home_about',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-about/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_home_about',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-about/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_home_about_image_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function get_all_home_abouts() {
    global $wpdb;
    $table = $wpdb->prefix . 'home_about';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_home_about($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_about';
    $params = json_decode($request->get_body(), true);

    // Sanitize feature blocks (Array of objects)
    $blocks = [];
    if (!empty($params['feature_blocks']) && is_array($params['feature_blocks'])) {
        foreach ($params['feature_blocks'] as $b) {
            $blocks[] = [
                'logo_url'    => esc_url_raw($b['logo_url']),
                'title'       => sanitize_text_field($b['title']),
                'description' => sanitize_textarea_field($b['description'])
            ];
        }
    }

    $wpdb->insert($table, [
        'tag_text'       => sanitize_text_field($params['tag_text']),
        'heading'        => sanitize_text_field($params['heading']),
        'description'    => wp_kses_post($params['description']),
        'main_image'     => esc_url_raw($params['main_image']),
        'quote_text'     => sanitize_text_field($params['quote_text']),
        'quote_author'   => sanitize_text_field($params['quote_author']),
        'stat_number'    => sanitize_text_field($params['stat_number']),
        'stat_text'      => sanitize_text_field($params['stat_text']),
        'feature_blocks' => wp_json_encode($blocks)
    ]);

    return ['status' => 'success'];
}

function update_home_about($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_about';
    $params = json_decode($request->get_body(), true);

    $blocks = [];
    if (!empty($params['feature_blocks']) && is_array($params['feature_blocks'])) {
        foreach ($params['feature_blocks'] as $b) {
            $blocks[] = [
                'logo_url'    => esc_url_raw($b['logo_url']),
                'title'       => sanitize_text_field($b['title']),
                'description' => sanitize_textarea_field($b['description'])
            ];
        }
    }

    $wpdb->update($table, [
        'tag_text'       => sanitize_text_field($params['tag_text']),
        'heading'        => sanitize_text_field($params['heading']),
        'description'    => wp_kses_post($params['description']),
        'main_image'     => esc_url_raw($params['main_image']),
        'quote_text'     => sanitize_text_field($params['quote_text']),
        'quote_author'   => sanitize_text_field($params['quote_author']),
        'stat_number'    => sanitize_text_field($params['stat_number']),
        'stat_text'      => sanitize_text_field($params['stat_text']),
        'feature_blocks' => wp_json_encode($blocks)
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_home_about($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_about';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

// REST API setup for Home Presence
add_action('rest_api_init', function () {
    register_rest_route('home-presence/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_home_presences',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-presence/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_home_presence',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-presence/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_home_presence',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-presence/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_home_presence',
        'permission_callback' => '__return_true'
    ]);
});

function get_all_home_presences() {
    global $wpdb;
    $table = $wpdb->prefix . 'home_presence';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function process_presence_repeater($items, $fields) {
    $out = [];
    if (!empty($items) && is_array($items)) {
        foreach ($items as $item) {
            $parsed = [];
            foreach ($fields as $f) {
                $parsed[$f] = sanitize_text_field($item[$f] ?? '');
            }
            $out[] = $parsed;
        }
    }
    return wp_json_encode($out);
}

function add_home_presence($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_presence';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'heading'           => sanitize_text_field($params['heading']),
        'subheading'        => sanitize_textarea_field($params['subheading']),
        'stats_data'        => process_presence_repeater($params['stats_data'], ['number', 'title', 'subtitle']),
        'countries_data'    => process_presence_repeater($params['countries_data'], ['name', 'lat', 'lng']),
        'institutions_data' => process_presence_repeater($params['institutions_data'], ['name', 'lat', 'lng']),
        'networks_data'     => process_presence_repeater($params['networks_data'], ['name', 'lat', 'lng'])
    ]);

    return ['status' => 'success'];
}

function update_home_presence($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_presence';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'heading'           => sanitize_text_field($params['heading']),
        'subheading'        => sanitize_textarea_field($params['subheading']),
        'stats_data'        => process_presence_repeater($params['stats_data'], ['number', 'title', 'subtitle']),
        'countries_data'    => process_presence_repeater($params['countries_data'], ['name', 'lat', 'lng']),
        'institutions_data' => process_presence_repeater($params['institutions_data'], ['name', 'lat', 'lng']),
        'networks_data'     => process_presence_repeater($params['networks_data'], ['name', 'lat', 'lng'])
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_home_presence($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_presence';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

// REST API setup for Home Collaborators
add_action('rest_api_init', function () {
    register_rest_route('home-collaborators/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_home_collaborators',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-collaborators/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_home_collaborators',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-collaborators/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_home_collaborators',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-collaborators/v1', '/delete/(?P<id>\d+)', [
        'methods'  => ['DELETE', 'POST'],
        'callback' => 'delete_home_collaborators',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-collaborators/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_home_collaborator_logo_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function get_all_home_collaborators() {
    global $wpdb;
    $table = $wpdb->prefix . 'home_collaborators';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    if (empty($results)) {
        $default_logos = [
            '/images/collabortor_logo/who.webp',
            '/images/collabortor_logo/icmr_logo_new.webp',
            '/images/collabortor_logo/phfi.webp',
            '/images/collabortor_logo/bill-melinda-gates-foundation-logo.webp',
            '/images/collabortor_logo/World_Bank-Logo.wine.webp',
            '/images/collabortor_logo/UNICEF-Logo.wine.webp'
        ];
        $wpdb->insert($table, [
            'heading'    => 'Strategic Collaborators',
            'subheading' => 'Empowering global healthcare through multi-disciplinary research and high-impact partnerships.',
            'logos'      => wp_json_encode($default_logos)
        ]);
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    }
    return $results;
}

function add_home_collaborators($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_collaborators';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'heading'    => sanitize_text_field($params['heading']),
        'subheading' => sanitize_textarea_field($params['subheading']),
        'logos'      => wp_json_encode(array_map('esc_url_raw', (array)$params['logos']))
    ]);

    return ['status' => 'success'];
}

function update_home_collaborators($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_collaborators';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'heading'    => sanitize_text_field($params['heading']),
        'subheading' => sanitize_textarea_field($params['subheading']),
        'logos'      => wp_json_encode(array_map('esc_url_raw', (array)$params['logos']))
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_home_collaborators($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_collaborators';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

// REST API setup for Home Impact Statistics
add_action('rest_api_init', function () {
    register_rest_route('home-impact/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_home_impacts',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-impact/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_home_impact',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-impact/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_home_impact',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-impact/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_home_impact',
        'permission_callback' => '__return_true'
    ]);
});

function get_all_home_impacts() {
    global $wpdb;
    $table = $wpdb->prefix . 'home_impact';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_home_impact($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_impact';
    $params = json_decode($request->get_body(), true);

    $stats = [];
    if (!empty($params['stats']) && is_array($params['stats'])) {
        foreach ($params['stats'] as $s) {
            $stats[] = [
                'number' => sanitize_text_field($s['number']),
                'label'  => sanitize_text_field($s['label'])
            ];
        }
    }

    $wpdb->insert($table, [
        'stats' => wp_json_encode($stats)
    ]);

    return ['status' => 'success'];
}

function update_home_impact($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_impact';
    $params = json_decode($request->get_body(), true);

    $stats = [];
    if (!empty($params['stats']) && is_array($params['stats'])) {
        foreach ($params['stats'] as $s) {
            $stats[] = [
                'number' => sanitize_text_field($s['number']),
                'label'  => sanitize_text_field($s['label'])
            ];
        }
    }

    $wpdb->update($table, [
        'stats' => wp_json_encode($stats)
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_home_impact($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_impact';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

// REST API setup for Home Research Areas
add_action('rest_api_init', function () {
    register_rest_route('home-research-areas/v1', '/all', [
        'methods'  => 'GET',
        'callback' => 'get_all_home_research_areas',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-research-areas/v1', '/add', [
        'methods'  => 'POST',
        'callback' => 'add_home_research_area',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-research-areas/v1', '/update/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'update_home_research_area',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-research-areas/v1', '/delete/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'delete_home_research_area',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('home-research-areas/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'upload_home_research_area_image_to_r2',
        'permission_callback' => '__return_true'
    ]);
});

function get_all_home_research_areas() {
    global $wpdb;
    $table = $wpdb->prefix . 'home_research_areas';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_home_research_area($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_research_areas';
    $params = json_decode($request->get_body(), true);

    $areas = [];
    if (!empty($params['areas']) && is_array($params['areas'])) {
        foreach ($params['areas'] as $a) {
            $areas[] = [
                'badge_text'  => sanitize_text_field($a['badge_text']),
                'badge_color' => sanitize_text_field($a['badge_color']),
                'image_url'   => esc_url_raw($a['image_url']),
                'title'       => sanitize_text_field($a['title']),
                'description' => sanitize_textarea_field($a['description']),
                'footer_text' => sanitize_text_field($a['footer_text'])
            ];
        }
    }

    $wpdb->insert($table, [
        'areas' => wp_json_encode($areas)
    ]);

    return ['status' => 'success'];
}

function update_home_research_area($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_research_areas';
    $params = json_decode($request->get_body(), true);

    $areas = [];
    if (!empty($params['areas']) && is_array($params['areas'])) {
        foreach ($params['areas'] as $a) {
            $areas[] = [
                'badge_text'  => sanitize_text_field($a['badge_text']),
                'badge_color' => sanitize_text_field($a['badge_color']),
                'image_url'   => esc_url_raw($a['image_url']),
                'title'       => sanitize_text_field($a['title']),
                'description' => sanitize_textarea_field($a['description']),
                'footer_text' => sanitize_text_field($a['footer_text'])
            ];
        }
    }

    $wpdb->update($table, [
        'areas' => wp_json_encode($areas)
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_home_research_area($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_research_areas';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

function get_all_home_heroes() {
    global $wpdb;
    $table = $wpdb->prefix . 'home_hero';
    return $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
}

function add_home_hero($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_hero';
    $params = json_decode($request->get_body(), true);

    $wpdb->insert($table, [
        'heading'        => sanitize_text_field($params['heading']),
        'paragraph'      => sanitize_textarea_field($params['paragraph']),
        'latest_updates' => wp_json_encode(array_map('sanitize_text_field', (array)$params['latest_updates'])),
        'images'         => wp_json_encode(array_map('esc_url_raw', (array)$params['images']))
    ]);

    return ['status' => 'success'];
}

function update_home_hero($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_hero';
    $params = json_decode($request->get_body(), true);

    $wpdb->update($table, [
        'heading'        => sanitize_text_field($params['heading']),
        'paragraph'      => sanitize_textarea_field($params['paragraph']),
        'latest_updates' => wp_json_encode(array_map('sanitize_text_field', (array)$params['latest_updates'])),
        'images'         => wp_json_encode(array_map('esc_url_raw', (array)$params['images']))
    ], ['id' => $request['id']]);

    return ['status' => 'updated'];
}

function delete_home_hero($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'home_hero';
    $wpdb->delete($table, ['id' => $request['id']]);
    return ['status' => 'deleted'];
}

function home_hero_page() { ?>
    <div class="wrap">
        <h1>Hero Section Manager</h1>
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Hero Slide</h2>

            <table class="form-table">
                <tr>
                    <th>Heading</th>
                    <td>
                        <input type="text" id="hh_heading" class="regular-text" required placeholder="e.g. Empowering Health Research">
                        <span id="hh_heading_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>Paragraph</th>
                    <td>
                        <textarea id="hh_paragraph" class="large-text" rows="3" placeholder="A short description..."></textarea>
                    </td>
                </tr>
                <tr>
                    <th>Latest Update Slider Texts</th>
                    <td>
                        <div id="hh_updates_container"></div>
                        <button type="button" class="button" onclick="addUpdateField()">+ Add Update Text</button>
                    </td>
                </tr>
                <tr>
                    <th>Images</th>
                    <td>
                        <p class="description">Recommended rendered size: <strong>1496 × 604px</strong></p>
                        <div id="hh_images_container"></div>
                        <button type="button" class="button" onclick="addImageField()">+ Add Image</button>
                    </td>
                </tr>
            </table>

            <p>
                <button class="button button-primary" onclick="saveHomeHero()">Save Hero Slide</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Hero Slides</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Images</th>
                        <th>Heading</th>
                        <th>Latest Updates</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="homeHeroList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const HH_API_BASE = "<?php echo site_url('/index.php?rest_route=/home-hero/v1'); ?>";
    let hhEditingId = null;

    function addUpdateField(val = '') {
        const container = document.getElementById('hh_updates_container');
        const count = container.children.length + 1;
        const div = document.createElement('div');
        div.style.marginBottom = '10px';
        div.innerHTML = `
            <label style="display:inline-block; width:50px;">Text ${count}:</label>
            <input type="text" class="regular-text hh-update-input" value="${val.replace(/"/g, '&quot;')}">
            <button type="button" class="button" onclick="this.parentElement.remove(); updateCounts('hh_updates_container', 'Text');">Remove</button>
        `;
        container.appendChild(div);
    }

    function addImageField(url = '') {
        const container = document.getElementById('hh_images_container');
        const count = container.children.length + 1;
        const div = document.createElement('div');
        div.style.marginBottom = '15px';
        div.style.padding = '10px';
        div.style.border = '1px solid #ddd';
        div.style.background = '#fafafa';
        
        let imgHtml = url ? `<img src="${url}" style="max-width:150px; display:block; margin-top:5px; border-radius:4px;">` : `<img style="max-width:150px; display:none; margin-top:5px; border-radius:4px;">`;
        
        div.innerHTML = `
            <label style="display:inline-block; font-weight:bold; margin-bottom:5px;" class="img-label">Image ${count}:</label><br>
            <input type="file" accept="image/*" onchange="uploadHeroImage(this)">
            <input type="hidden" class="hh-image-url" value="${url}">
            <button type="button" class="button" style="color:#b32d2e;" onclick="this.parentElement.remove(); updateCounts('hh_images_container', 'Image');">Remove</button>
            ${imgHtml}
        `;
        container.appendChild(div);
    }

    function updateCounts(containerId, labelPrefix) {
        const container = document.getElementById(containerId);
        Array.from(container.children).forEach((child, index) => {
            const label = child.querySelector('label');
            if(label) label.innerText = `${labelPrefix} ${index + 1}:`;
        });
    }

    function uploadHeroImage(inputEl) {
        let file = inputEl.files[0];
        if(!file) return;
        let formData = new FormData();
        formData.append("file", file);
        fetch(HH_API_BASE + "/upload-image", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                inputEl.parentElement.querySelector('.hh-image-url').value = data.url;
                let imgEl = inputEl.parentElement.querySelector('img');
                imgEl.src = data.url;
                imgEl.style.display = 'block';
            } else {
                alert("Upload Failed");
            }
        });
    }

    function loadHomeHeroes() {
        fetch(HH_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                let images = [];
                let updates = [];
                try { images = JSON.parse(item.images) || []; } catch(e){}
                try { updates = JSON.parse(item.latest_updates) || []; } catch(e){}
                
                let imgHtml = images.length > 0 ? `<img src="${images[0]}" width="100" style="border-radius:4px;"><br><small>${images.length} images</small>` : '';
                let updatesHtml = updates.length > 0 ? `<ul style="margin:0;padding-left:15px;"><li>${updates.join('</li><li>')}</li></ul>` : '';

                html += `
                <tr>
                    <td>${imgHtml}</td>
                    <td><strong>${item.heading}</strong></td>
                    <td>${updatesHtml}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editHomeHero(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteHomeHero(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("homeHeroList").innerHTML = html;
        });
    }

    function clearHhErrors() {
        document.querySelectorAll('.error-msg').forEach(el => el.innerText = '');
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    function saveHomeHero() {
        clearHhErrors();
        
        const headingVal = document.getElementById("hh_heading").value.trim();
        const paraVal = document.getElementById("hh_paragraph").value.trim();
        
        let updatesArr = [];
        document.querySelectorAll('.hh-update-input').forEach(input => {
            if (input.value.trim() !== '') updatesArr.push(input.value.trim());
        });

        let imagesArr = [];
        document.querySelectorAll('.hh-image-url').forEach(input => {
            if (input.value.trim() !== '') imagesArr.push(input.value.trim());
        });
        
        let isValid = true;
        if (!headingVal) {
            document.getElementById("hh_heading_error").innerText = "Heading is required.";
            document.getElementById("hh_heading").classList.add("input-error");
            isValid = false;
        }
        
        if (!isValid) return;

        const data = {
            heading: headingVal,
            paragraph: paraVal,
            latest_updates: updatesArr,
            images: imagesArr
        };
        
        let url = HH_API_BASE + "/add";
        if (hhEditingId) url = HH_API_BASE + "/update/" + hhEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            hhEditingId = null; 
            document.getElementById("hh_heading").value = '';
            document.getElementById("hh_paragraph").value = '';
            document.getElementById("hh_updates_container").innerHTML = '';
            document.getElementById("hh_images_container").innerHTML = '';
            loadHomeHeroes(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function editHomeHero(item) {
        clearHhErrors();
        hhEditingId = item.id;
        document.getElementById("hh_heading").value = item.heading;
        document.getElementById("hh_paragraph").value = item.paragraph || '';
        
        document.getElementById("hh_updates_container").innerHTML = '';
        let updates = [];
        try { updates = JSON.parse(item.latest_updates) || []; } catch(e){}
        updates.forEach(u => addUpdateField(u));
        if(updates.length === 0) addUpdateField();

        document.getElementById("hh_images_container").innerHTML = '';
        let images = [];
        try { images = JSON.parse(item.images) || []; } catch(e){}
        images.forEach(img => addImageField(img));
        if(images.length === 0) addImageField();
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteHomeHero(id) {
        if (!confirm("Are you sure you want to delete this Hero Slide?")) return;
        fetch(HH_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadHomeHeroes());
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadHomeHeroes();
        if(document.getElementById("hh_updates_container").children.length === 0) {
            addUpdateField();
        }
        if(document.getElementById("hh_images_container").children.length === 0) {
            addImageField();
        }
    });
    </script>
<?php }

function home_updates_page() {
    echo '<div class="wrap"><h1>Latest Update Slider Manager</h1><p>Waiting for table details to build this section...</p></div>';
}

function home_about_page() { ?>
    <div class="wrap">
        <h1>About Section Manager</h1>
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
            .feature-block-item { border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; background: #fafafa; border-radius: 4px; }
            .feature-block-item label { font-weight: bold; display: block; margin-bottom: 5px; margin-top: 10px; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit About Section</h2>

            <table class="form-table">
                <tr>
                    <th>Tag Text</th>
                    <td>
                        <input type="text" id="ha_tag_text" class="regular-text" placeholder="e.g. ABOUT INCLEN">
                    </td>
                </tr>
                <tr>
                    <th>Heading</th>
                    <td>
                        <input type="text" id="ha_heading" class="regular-text" required placeholder="e.g. Global Health Research Organization">
                        <span id="ha_heading_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>Description Paragraphs</th>
                    <td>
                        <?php 
                        wp_editor('', 'ha_description', array(
                            'textarea_name' => 'ha_description',
                            'media_buttons' => false,
                            'textarea_rows' => 10,
                            'teeny'         => true,
                        )); 
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Main Left Image</th>
                    <td>
                        <input type="file" id="ha_main_image_file" accept="image/*">
                        <input type="hidden" id="ha_main_image">
                        <br><br>
                        <img id="ha_preview_image" style="max-width:200px;display:none;border:1px solid #ddd;padding:3px;border-radius:4px;">
                    </td>
                </tr>
                <tr>
                    <th>Quote Over Image</th>
                    <td>
                        <textarea id="ha_quote_text" class="large-text" rows="2" placeholder="e.g. Global health solutions must be rooted in local realities."></textarea>
                    </td>
                </tr>
                <tr>
                    <th>Quote Author/Subtext</th>
                    <td>
                        <input type="text" id="ha_quote_author" class="regular-text" placeholder="e.g. – ESTABLISHED 1980">
                    </td>
                </tr>
                <tr>
                    <th>Stats Box Number</th>
                    <td>
                        <input type="text" id="ha_stat_number" class="small-text" placeholder="e.g. 4+">
                    </td>
                </tr>
                <tr>
                    <th>Stats Box Text</th>
                    <td>
                        <input type="text" id="ha_stat_text" class="regular-text" placeholder="e.g. DECADES OF EXCELLENCE">
                    </td>
                </tr>
                <tr>
                    <th>Feature Blocks (Bottom Right)</th>
                    <td>
                        <div id="ha_feature_blocks_container"></div>
                        <button type="button" class="button" onclick="addFeatureBlock()">+ Add Feature Block</button>
                    </td>
                </tr>
            </table>

            <p>
                <button class="button button-primary" onclick="saveHomeAbout()">Save About Section</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All About Sections</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Heading</th>
                        <th>Tag</th>
                        <th>Features</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="homeAboutList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const HA_API_BASE = "<?php echo site_url('/index.php?rest_route=/home-about/v1'); ?>";
    let haEditingId = null;

    function addFeatureBlock(block = {logo_url:'', title:'', description:''}) {
        const container = document.getElementById('ha_feature_blocks_container');
        const count = container.children.length + 1;
        const div = document.createElement('div');
        div.className = 'feature-block-item';
        
        let imgHtml = block.logo_url ? `<img src="${block.logo_url}" style="max-width:80px; display:block; margin-top:5px; border-radius:4px;">` : `<img style="max-width:80px; display:none; margin-top:5px; border-radius:4px;">`;

        div.innerHTML = `
            <div style="float:right;">
                <button type="button" class="button" style="color:#b32d2e;" onclick="this.closest('.feature-block-item').remove();">X Remove</button>
            </div>
            <h4>Feature ${count}</h4>
            
            <label>Logo / Icon Image:</label>
            <input type="file" accept="image/*" onchange="uploadFeatureLogo(this)">
            <input type="hidden" class="fb-logo-url" value="${block.logo_url}">
            ${imgHtml}

            <label>Title:</label>
            <input type="text" class="regular-text fb-title" value="${block.title.replace(/"/g, '&quot;')}">

            <label>Description:</label>
            <textarea class="large-text fb-description" rows="2">${block.description.replace(/</g, '&lt;')}</textarea>
        `;
        container.appendChild(div);
    }

    function uploadFeatureLogo(inputEl) {
        let file = inputEl.files[0];
        if(!file) return;
        let formData = new FormData();
        formData.append("file", file);
        fetch(HA_API_BASE + "/upload-image", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.url) {
                inputEl.parentElement.querySelector('.fb-logo-url').value = data.url;
                let imgEl = inputEl.parentElement.querySelector('img');
                imgEl.src = data.url;
                imgEl.style.display = 'block';
            } else {
                alert("Upload Failed");
            }
        });
    }

    function loadHomeAbouts() {
        fetch(HA_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                let blocks = [];
                try { blocks = JSON.parse(item.feature_blocks) || []; } catch(e){}
                
                html += `
                <tr>
                    <td>${item.main_image ? '<img src="' + item.main_image + '" width="80" style="border-radius:4px;">' : ''}</td>
                    <td><strong>${item.heading}</strong></td>
                    <td>${item.tag_text}</td>
                    <td>${blocks.length} blocks</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editHomeAbout(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteHomeAbout(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("homeAboutList").innerHTML = html;
        });
    }

    function saveHomeAbout() {
        document.getElementById("ha_heading_error").innerText = '';
        document.getElementById("ha_heading").classList.remove('input-error');
        
        const headingVal = document.getElementById("ha_heading").value.trim();
        
        if (!headingVal) {
            document.getElementById("ha_heading_error").innerText = "Heading is required.";
            document.getElementById("ha_heading").classList.add("input-error");
            return;
        }

        let wpEditorContent = '';
        if(typeof tinymce !== 'undefined' && tinymce.get('ha_description')) {
            wpEditorContent = tinymce.get('ha_description').getContent();
        } else {
            wpEditorContent = document.getElementById('ha_description').value;
        }

        let blocksArr = [];
        document.querySelectorAll('.feature-block-item').forEach(el => {
            blocksArr.push({
                logo_url: el.querySelector('.fb-logo-url').value,
                title: el.querySelector('.fb-title').value,
                description: el.querySelector('.fb-description').value
            });
        });

        const data = {
            tag_text: document.getElementById("ha_tag_text").value,
            heading: headingVal,
            description: wpEditorContent,
            main_image: document.getElementById("ha_main_image").value,
            quote_text: document.getElementById("ha_quote_text").value,
            quote_author: document.getElementById("ha_quote_author").value,
            stat_number: document.getElementById("ha_stat_number").value,
            stat_text: document.getElementById("ha_stat_text").value,
            feature_blocks: blocksArr
        };
        
        let url = HA_API_BASE + "/add";
        if (haEditingId) url = HA_API_BASE + "/update/" + haEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            haEditingId = null; 
            document.getElementById("ha_tag_text").value = '';
            document.getElementById("ha_heading").value = '';
            if(typeof tinymce !== 'undefined' && tinymce.get('ha_description')) tinymce.get('ha_description').setContent('');
            else document.getElementById('ha_description').value = '';
            document.getElementById("ha_main_image").value = '';
            document.getElementById("ha_preview_image").style.display = 'none';
            document.getElementById("ha_quote_text").value = '';
            document.getElementById("ha_quote_author").value = '';
            document.getElementById("ha_stat_number").value = '';
            document.getElementById("ha_stat_text").value = '';
            document.getElementById("ha_feature_blocks_container").innerHTML = '';
            loadHomeAbouts(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function editHomeAbout(item) {
        haEditingId = item.id;
        document.getElementById("ha_tag_text").value = item.tag_text || '';
        document.getElementById("ha_heading").value = item.heading || '';
        
        if(typeof tinymce !== 'undefined' && tinymce.get('ha_description')) {
            tinymce.get('ha_description').setContent(item.description || '');
        } else {
            document.getElementById('ha_description').value = item.description || '';
        }

        document.getElementById("ha_main_image").value = item.main_image || '';
        if(item.main_image) {
            document.getElementById("ha_preview_image").src = item.main_image;
            document.getElementById("ha_preview_image").style.display = 'block';
        } else {
            document.getElementById("ha_preview_image").style.display = 'none';
        }

        document.getElementById("ha_quote_text").value = item.quote_text || '';
        document.getElementById("ha_quote_author").value = item.quote_author || '';
        document.getElementById("ha_stat_number").value = item.stat_number || '';
        document.getElementById("ha_stat_text").value = item.stat_text || '';

        document.getElementById("ha_feature_blocks_container").innerHTML = '';
        let blocks = [];
        try { blocks = JSON.parse(item.feature_blocks) || []; } catch(e){}
        blocks.forEach(b => addFeatureBlock(b));
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteHomeAbout(id) {
        if (!confirm("Are you sure you want to delete this About Section?")) return;
        fetch(HA_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadHomeAbouts());
    }

    if (document.getElementById("ha_main_image_file")) {
        document.getElementById("ha_main_image_file").addEventListener("change", function() {
            let file = this.files[0];
            if(!file) return;
            let formData = new FormData();
            formData.append("file", file);
            fetch(HA_API_BASE + "/upload-image", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.url) {
                    document.getElementById("ha_main_image").value = data.url;
                    document.getElementById("ha_preview_image").src = data.url;
                    document.getElementById("ha_preview_image").style.display = "block";
                } else {
                    alert("Upload Failed");
                }
            });
        });

        document.addEventListener("DOMContentLoaded", loadHomeAbouts);
    }
    </script>
<?php }

function home_presence_page() { ?>
    <div class="wrap">
        <h1>Our Presence Manager</h1>
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
            .repeater-item { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; background: #fafafa; border-radius: 4px; display: flex; gap: 10px; align-items: center; }
            .repeater-item input { flex: 1; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Presence Section</h2>

            <table class="form-table">
                <tr>
                    <th>Heading</th>
                    <td>
                        <input type="text" id="hp_heading" class="regular-text" required placeholder="e.g. Our Presence">
                        <span id="hp_heading_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th>Subheading</th>
                    <td>
                        <textarea id="hp_subheading" class="large-text" rows="2" placeholder="e.g. With a decentralized network structure..."></textarea>
                    </td>
                </tr>
                
                <tr><th colspan="2" style="background:#f0f0f1; padding:10px;">Stats Data (e.g. 34 Countries Global Coverage)</th></tr>
                <tr>
                    <th>Stats Blocks</th>
                    <td>
                        <div id="hp_stats_container"></div>
                        <button type="button" class="button" onclick="addStatBlock()">+ Add Stat</button>
                    </td>
                </tr>

                <tr><th colspan="2" style="background:#f0f0f1; padding:10px;">Map Markers Data</th></tr>
                
                <tr>
                    <th>Countries Tab</th>
                    <td>
                        <div id="hp_countries_container"></div>
                        <button type="button" class="button" onclick="addMapMarker('hp_countries_container', 'Country Name')">+ Add Country Marker</button>
                    </td>
                </tr>

                <tr>
                    <th>Institutions Tab</th>
                    <td>
                        <div id="hp_institutions_container"></div>
                        <button type="button" class="button" onclick="addMapMarker('hp_institutions_container', 'Institution Name')">+ Add Institution Marker</button>
                    </td>
                </tr>

                <tr>
                    <th>Networks Tab</th>
                    <td>
                        <div id="hp_networks_container"></div>
                        <button type="button" class="button" onclick="addMapMarker('hp_networks_container', 'Network Name')">+ Add Network Marker</button>
                    </td>
                </tr>

            </table>

            <p>
                <button class="button button-primary" onclick="saveHomePresence()">Save Presence Section</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Presence Sections</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Heading</th>
                        <th>Subheading</th>
                        <th>Stats Count</th>
                        <th>Markers Count</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="homePresenceList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const HP_API_BASE = "<?php echo site_url('/index.php?rest_route=/home-presence/v1'); ?>";
    let hpEditingId = null;
    let allHomePresences = [];

    function safeParseJSON(data) {
        if (!data) return [];
        let parsed = [];
        try { parsed = JSON.parse(data); } catch(e){ return []; }
        if (typeof parsed === 'string') {
            try { parsed = JSON.parse(parsed); } catch(e) { return []; }
        }
        return Array.isArray(parsed) ? parsed : [];
    }

    function addStatBlock(num='', title='', sub='') {
        const container = document.getElementById('hp_stats_container');
        if (container.children.length >= 3) {
            alert("You cannot add more than 3 Stat Blocks.");
            return;
        }
        const div = document.createElement('div');
        div.className = 'repeater-item hp-stat-item';
        div.innerHTML = `
            <input type="text" class="stat-num" placeholder="Number (e.g. 34)" value="${String(num).replace(/"/g, '&quot;')}">
            <input type="text" class="stat-title" placeholder="Title (e.g. Countries)" value="${String(title).replace(/"/g, '&quot;')}">
            <input type="text" class="stat-sub" placeholder="Subtitle (e.g. Global Coverage)" value="${String(sub).replace(/"/g, '&quot;')}">
            <button type="button" class="button" style="color:#b32d2e;" onclick="this.parentElement.remove()">X</button>
        `;
        container.appendChild(div);
    }

    function addMapMarker(containerId, placeholderName, name='', lat='', lng='') {
        const container = document.getElementById(containerId);
        const div = document.createElement('div');
        div.className = `repeater-item map-marker-item ${containerId}-item`;
        div.innerHTML = `
            <input type="text" class="marker-name" placeholder="${placeholderName}" value="${String(name).replace(/"/g, '&quot;')}">
            <input type="text" class="marker-lat" placeholder="Latitude (e.g. 28.6139)" value="${String(lat).replace(/"/g, '&quot;')}">
            <input type="text" class="marker-lng" placeholder="Longitude (e.g. 77.2090)" value="${String(lng).replace(/"/g, '&quot;')}">
            <button type="button" class="button" style="color:#b32d2e;" onclick="this.parentElement.remove()">X</button>
        `;
        container.appendChild(div);
    }

    function loadHomePresences() {
        fetch(HP_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            allHomePresences = data;
            let html = '';
            data.forEach((item, index) => {
                let stats = safeParseJSON(item.stats_data);
                let c = safeParseJSON(item.countries_data);
                let i = safeParseJSON(item.institutions_data);
                let n = safeParseJSON(item.networks_data);
                
                let totalMarkers = c.length + i.length + n.length;

                html += `
                <tr>
                    <td><strong>${item.heading}</strong></td>
                    <td>${item.subheading}</td>
                    <td>${stats.length} stats</td>
                    <td>${totalMarkers} markers</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editHomePresence(${index})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteHomePresence(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("homePresenceList").innerHTML = html;
        });
    }

    function saveHomePresence() {
        document.getElementById("hp_heading_error").innerText = '';
        document.getElementById("hp_heading").classList.remove('input-error');
        
        const headingVal = document.getElementById("hp_heading").value.trim();
        
        if (!headingVal) {
            document.getElementById("hp_heading_error").innerText = "Heading is required.";
            document.getElementById("hp_heading").classList.add("input-error");
            return;
        }

        let statsArr = [];
        document.querySelectorAll('.hp-stat-item').forEach(el => {
            const numVal = el.querySelector('.stat-num').value.trim();
            const titleVal = el.querySelector('.stat-title').value.trim();
            const subVal = el.querySelector('.stat-sub').value.trim();
            if (numVal || titleVal || subVal) {
                statsArr.push({
                    number: numVal,
                    title: titleVal,
                    subtitle: subVal
                });
            }
        });

        const getMarkers = (className) => {
            let arr = [];
            document.querySelectorAll(`.${className}`).forEach(el => {
                const nVal = el.querySelector('.marker-name').value.trim();
                const latVal = el.querySelector('.marker-lat').value.trim();
                const lngVal = el.querySelector('.marker-lng').value.trim();
                if (nVal || latVal || lngVal) {
                    arr.push({
                        name: nVal,
                        lat: latVal,
                        lng: lngVal
                    });
                }
            });
            return arr;
        };

        const data = {
            heading: headingVal,
            subheading: document.getElementById("hp_subheading").value,
            stats_data: statsArr,
            countries_data: getMarkers('hp_countries_container-item'),
            institutions_data: getMarkers('hp_institutions_container-item'),
            networks_data: getMarkers('hp_networks_container-item')
        };
        
        let url = HP_API_BASE + "/add";
        if (hpEditingId) url = HP_API_BASE + "/update/" + hpEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            hpEditingId = null; 
            document.getElementById("hp_heading").value = '';
            document.getElementById("hp_subheading").value = '';
            document.getElementById("hp_stats_container").innerHTML = '';
            document.getElementById("hp_countries_container").innerHTML = '';
            document.getElementById("hp_institutions_container").innerHTML = '';
            document.getElementById("hp_networks_container").innerHTML = '';
            loadHomePresences(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function editHomePresence(index) {
        const item = allHomePresences[index];
        hpEditingId = item.id;
        document.getElementById("hp_heading").value = item.heading || '';
        document.getElementById("hp_subheading").value = item.subheading || '';
        
        document.getElementById("hp_stats_container").innerHTML = '';
        let stats = safeParseJSON(item.stats_data);
        stats.forEach(s => addStatBlock(s.number, s.title, s.subtitle));

        document.getElementById("hp_countries_container").innerHTML = '';
        let c = safeParseJSON(item.countries_data);
        c.forEach(m => addMapMarker('hp_countries_container', 'Country Name', m.name, m.lat, m.lng));

        document.getElementById("hp_institutions_container").innerHTML = '';
        let i = safeParseJSON(item.institutions_data);
        i.forEach(m => addMapMarker('hp_institutions_container', 'Institution Name', m.name, m.lat, m.lng));

        document.getElementById("hp_networks_container").innerHTML = '';
        let n = safeParseJSON(item.networks_data);
        n.forEach(m => addMapMarker('hp_networks_container', 'Network Name', m.name, m.lat, m.lng));
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteHomePresence(id) {
        if (!confirm("Are you sure you want to delete this Presence Section?")) return;
        fetch(HP_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadHomePresences());
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadHomePresences();
        if(document.getElementById("hp_stats_container").children.length === 0) {
            addStatBlock();
        }
        if(document.getElementById("hp_countries_container").children.length === 0) {
            addMapMarker('hp_countries_container', 'Country Name');
        }
    });
    </script>
<?php }

function home_collaborators_page() { ?>
    <div class="wrap">
        <h1 style="font-weight: 700; color: #1e293b; margin-bottom: 20px;">
            <span class="dashicons dashicons-networking" style="font-size:32px; width:32px; height:32px; margin-right:8px; vertical-align:middle; color:#f7610c;"></span>
            Strategic Collaborators & Logo Slider Manager
        </h1>
        <p style="color: #64748b; font-size: 14px; margin-top:-10px; margin-bottom: 20px;">
            Manage the partner logos marquee slider and section heading displayed on the Homepage.
        </p>

        <style>
            .collab-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 24px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                margin-bottom: 30px;
            }
            .error-msg { color: #dc2626; font-size: 13px; margin-top: 4px; display: block; font-weight: 500; }
            .input-error { border-color: #dc2626 !important; }
            .logo-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 16px;
                margin-top: 10px;
                margin-bottom: 15px;
            }
            .logo-card-item {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 10px;
                position: relative;
                transition: all 0.2s ease;
            }
            .logo-card-item:hover {
                border-color: #94a3b8;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08);
            }
            .logo-preview-box {
                width: 100%;
                height: 70px;
                background: #ffffff;
                border: 1px dashed #cbd5e1;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                padding: 8px;
            }
            .logo-preview-box img {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }
            .logo-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .logo-card-header strong {
                font-size: 13px;
                color: #334155;
            }
            .logo-actions {
                display: flex;
                gap: 4px;
            }
            .logo-upload-btn {
                position: relative;
                overflow: hidden;
                display: inline-block;
            }
            .upload-spinner {
                display: inline-block;
                color: #f7610c;
                font-weight: bold;
                font-size: 12px;
            }
            .table-logo-gallery {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: center;
            }
            .table-logo-thumb {
                width: 48px;
                height: 36px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 4px;
                padding: 3px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .table-logo-thumb img {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }
        </style>

        <div class="collab-card">
            <h2 id="hc_form_title" style="margin-top:0; font-size:18px; font-weight:600; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
                Add / Edit Collaborators Section
            </h2>

            <table class="form-table" style="margin-top:0;">
                <tr>
                    <th style="width:160px;"><label for="hc_heading"><strong>Section Heading</strong></label></th>
                    <td>
                        <input type="text" id="hc_heading" class="regular-text" style="width:100%; max-width:550px;" required placeholder="e.g. Strategic Collaborators" value="Strategic Collaborators">
                        <span id="hc_heading_error" class="error-msg"></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="hc_subheading"><strong>Subheading</strong></label></th>
                    <td>
                        <textarea id="hc_subheading" class="large-text" rows="2" style="width:100%; max-width:550px;" placeholder="e.g. Empowering global healthcare through multi-disciplinary research and high-impact partnerships.">Empowering global healthcare through multi-disciplinary research and high-impact partnerships.</textarea>
                    </td>
                </tr>
                <tr>
                    <th><label><strong>Partner Logos</strong></label></th>
                    <td>
                        <div style="margin-bottom:10px;">
                            <span style="color:#64748b; font-size:13px;">Upload logo files (.png, .webp, .svg, .jpg) or enter file URLs. Use arrow buttons to adjust slider order.</span>
                        </div>
                        <div id="hc_logos_container" class="logo-grid"></div>
                        <button type="button" class="button" style="background:#f1f5f9; color:#0f172a; border-color:#cbd5e1; font-weight:600;" onclick="addCollaboratorLogo()">
                            + Add Another Logo
                        </button>
                    </td>
                </tr>
            </table>

            <div style="margin-top:24px; padding-top:16px; border-top:1px solid #f1f5f9; display:flex; gap:10px; align-items:center;">
                <button class="button button-primary button-large" id="hc_save_btn" onclick="saveHomeCollaborators()" style="background:#f7610c; border-color:#f7610c; font-weight:600; padding:4px 20px;">
                    Save Collaborators Section
                </button>
                <button type="button" class="button button-large" id="hc_cancel_btn" style="display:none;" onclick="cancelEditCollaborators()">
                    Cancel Edit
                </button>
                <span id="hc_status_msg" style="margin-left:10px; font-weight:500; font-size:13px;"></span>
            </div>
        </div>

        <div style="margin-top:35px;">
            <h2 style="font-size:18px; font-weight:600; color:#0f172a;">All Collaborators Sections</h2>
            <table class="wp-list-table widefat fixed striped" style="background:#fff; border-radius:6px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <thead>
                    <tr>
                        <th style="width: 220px;">Heading</th>
                        <th>Subheading</th>
                        <th style="width: 260px;">Logos Preview</th>
                        <th style="width: 100px;">Total Logos</th>
                        <th style="width: 140px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="homeCollaboratorsList">
                    <tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">Loading collaborator sections...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    const HC_API_BASE = "<?php echo site_url('/index.php?rest_route=/home-collaborators/v1'); ?>";
    let hcEditingId = null;

    function addCollaboratorLogo(url = '') {
        const container = document.getElementById('hc_logos_container');
        const count = container.children.length + 1;
        const div = document.createElement('div');
        div.className = 'logo-card-item';
        
        let hasImg = url && url.trim() !== '';
        let displayUrl = url ? url.trim() : '';

        div.innerHTML = `
            <div class="logo-card-header">
                <strong class="logo-num-label">Logo #${count}</strong>
                <div class="logo-actions">
                    <button type="button" class="button button-small" title="Move Up" onclick="moveLogoItem(this, -1)">↑</button>
                    <button type="button" class="button button-small" title="Move Down" onclick="moveLogoItem(this, 1)">↓</button>
                    <button type="button" class="button button-small" style="color:#b32d2e; border-color:#fca5a5;" title="Remove" onclick="this.closest('.logo-card-item').remove(); updateHcCounts();">✕</button>
                </div>
            </div>

            <div class="logo-preview-box">
                <img src="${displayUrl}" style="display:${hasImg ? 'block' : 'none'};" alt="Logo Preview" onerror="this.style.display='none';">
                <span class="no-img-text" style="display:${hasImg ? 'none' : 'block'}; color:#94a3b8; font-size:12px;">No Logo Selected</span>
            </div>

            <div>
                <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Upload File:</label>
                <input type="file" accept="image/*" style="width:100%; font-size:12px;" onchange="uploadCollaboratorLogo(this)">
                <span class="upload-status" style="font-size:11px; color:#f7610c; display:none; margin-top:2px;">Uploading...</span>
            </div>

            <div>
                <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Or Logo URL / Relative Path:</label>
                <input type="text" class="regular-text hc-logo-url" style="width:100%; font-size:12px; padding:4px 8px;" placeholder="/images/collabortor_logo/who.webp" value="${displayUrl.replace(/"/g, '&quot;')}" oninput="updateLogoPreview(this)">
            </div>
        `;
        container.appendChild(div);
        updateHcCounts();
    }

    function updateLogoPreview(inputEl) {
        const card = inputEl.closest('.logo-card-item');
        const img = card.querySelector('.logo-preview-box img');
        const noText = card.querySelector('.no-img-text');
        const val = inputEl.value.trim();
        if (val) {
            img.src = val;
            img.style.display = 'block';
            if (noText) noText.style.display = 'none';
        } else {
            img.style.display = 'none';
            if (noText) noText.style.display = 'block';
        }
    }

    function moveLogoItem(btn, direction) {
        const item = btn.closest('.logo-card-item');
        const container = document.getElementById('hc_logos_container');
        if (direction === -1 && item.previousElementSibling) {
            container.insertBefore(item, item.previousElementSibling);
        } else if (direction === 1 && item.nextElementSibling) {
            container.insertBefore(item.nextElementSibling, item);
        }
        updateHcCounts();
    }

    function updateHcCounts() {
        const container = document.getElementById('hc_logos_container');
        Array.from(container.children).forEach((child, index) => {
            const label = child.querySelector('.logo-num-label');
            if(label) label.innerText = `Logo #${index + 1}`;
        });
    }

    function uploadCollaboratorLogo(inputEl) {
        let file = inputEl.files[0];
        if(!file) return;
        
        let card = inputEl.closest('.logo-card-item');
        let statusEl = card.querySelector('.upload-status');
        if(statusEl) {
            statusEl.innerText = "Uploading logo...";
            statusEl.style.display = "block";
        }

        let formData = new FormData();
        formData.append("file", file);
        
        fetch(HC_API_BASE + "/upload-image", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if(statusEl) statusEl.style.display = "none";
            if (data.url) {
                const urlInput = card.querySelector('.hc-logo-url');
                urlInput.value = data.url;
                updateLogoPreview(urlInput);
            } else {
                alert("Upload failed: " + (data.error || "Unknown error"));
            }
        })
        .catch(err => {
            if(statusEl) statusEl.style.display = "none";
            alert("Upload error: " + err.message);
        });
    }

    function loadHomeCollaborators() {
        fetch(HC_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            if (!Array.isArray(data) || data.length === 0) {
                html = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">No collaborator sections found. Add one above.</td></tr>';
            } else {
                data.forEach(item => {
                    let logos = [];
                    try { logos = JSON.parse(item.logos) || []; } catch(e){}
                    if (typeof logos === 'string') {
                        try { logos = JSON.parse(logos) || []; } catch(e){}
                    }
                    if (!Array.isArray(logos)) logos = [];

                    let previewThumbs = '';
                    logos.slice(0, 6).forEach(src => {
                        previewThumbs += `<div class="table-logo-thumb"><img src="${src}" alt="logo"></div>`;
                    });
                    if (logos.length > 6) {
                        previewThumbs += `<span style="font-size:12px; color:#64748b; font-weight:600;">+${logos.length - 6} more</span>`;
                    }

                    html += `
                    <tr>
                        <td><strong style="color:#0f172a; font-size:14px;">${item.heading || 'Strategic Collaborators'}</strong></td>
                        <td style="color:#475569; font-size:13px;">${item.subheading || ''}</td>
                        <td><div class="table-logo-gallery">${previewThumbs || '<span style="color:#94a3b8; font-size:12px;">No logos</span>'}</div></td>
                        <td><span style="background:#e2e8f0; color:#334155; padding:2px 8px; border-radius:12px; font-size:12px; font-weight:600;">${logos.length} logos</span></td>
                        <td style="text-align:right;">
                            <button class="button button-small" style="background:#f8fafc; color:#0f172a; font-weight:600;" onclick='editHomeCollaborators(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                            <button class="button button-small" style="color:#b32d2e; border-color:#fecaca;" onclick='deleteHomeCollaborators(${item.id})'>Delete</button>
                        </td>
                    </tr>`;
                });

                // If not currently editing a specific ID, automatically load the active record
                if (!hcEditingId && data.length > 0) {
                    editHomeCollaborators(data[0]);
                }
            }
            document.getElementById("homeCollaboratorsList").innerHTML = html;
        })
        .catch(() => {
            document.getElementById("homeCollaboratorsList").innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#ef4444;">Failed to load data. Please refresh.</td></tr>';
        });
    }

    function saveHomeCollaborators() {
        document.getElementById("hc_heading_error").innerText = '';
        document.getElementById("hc_heading").classList.remove('input-error');
        
        const headingVal = document.getElementById("hc_heading").value.trim();
        
        if (!headingVal) {
            document.getElementById("hc_heading_error").innerText = "Heading is required.";
            document.getElementById("hc_heading").classList.add("input-error");
            return;
        }

        let logosArr = [];
        document.querySelectorAll('.hc-logo-url').forEach(input => {
            if (input.value.trim() !== '') logosArr.push(input.value.trim());
        });

        const data = {
            heading: headingVal,
            subheading: document.getElementById("hc_subheading").value.trim(),
            logos: logosArr
        };
        
        const saveBtn = document.getElementById("hc_save_btn");
        const statusMsg = document.getElementById("hc_status_msg");
        saveBtn.disabled = true;
        saveBtn.innerText = "Saving...";

        let url = HC_API_BASE + "/add";
        if (hcEditingId) url = HC_API_BASE + "/update/" + hcEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(() => { 
            saveBtn.disabled = false;
            saveBtn.innerText = "Save Collaborators Section";
            statusMsg.style.color = "#16a34a";
            statusMsg.innerText = "✓ Saved successfully!";
            setTimeout(() => { statusMsg.innerText = ''; }, 3000);

            loadHomeCollaborators();
        })
        .catch(err => {
            saveBtn.disabled = false;
            saveBtn.innerText = "Save Collaborators Section";
            statusMsg.style.color = "#dc2626";
            statusMsg.innerText = "Error: " + err.message;
        });
    }

    function editHomeCollaborators(item) {
        hcEditingId = item.id;
        document.getElementById("hc_form_title").innerText = "Edit Active Collaborators Section (ID #" + item.id + ")";
        document.getElementById("hc_cancel_btn").style.display = "none";
        document.getElementById("hc_heading").value = item.heading || '';
        document.getElementById("hc_subheading").value = item.subheading || '';
        
        document.getElementById("hc_logos_container").innerHTML = '';
        let logos = [];
        try { logos = JSON.parse(item.logos) || []; } catch(e){}
        if (typeof logos === 'string') {
            try { logos = JSON.parse(logos) || []; } catch(e){}
        }
        if (!Array.isArray(logos)) logos = [];

        logos.forEach(url => addCollaboratorLogo(url));
        if(logos.length === 0) addCollaboratorLogo();
    }

    function cancelEditCollaborators() {
        hcEditingId = null;
        document.getElementById("hc_form_title").innerText = "Add / Edit Collaborators Section";
        document.getElementById("hc_cancel_btn").style.display = "none";
        document.getElementById("hc_heading").value = 'Strategic Collaborators';
        document.getElementById("hc_subheading").value = 'Empowering global healthcare through multi-disciplinary research and high-impact partnerships.';
        document.getElementById("hc_logos_container").innerHTML = '';
        
        const defaultLogos = [
            '/images/collabortor_logo/who.webp',
            '/images/collabortor_logo/icmr_logo_new.webp',
            '/images/collabortor_logo/phfi.webp',
            '/images/collabortor_logo/bill-melinda-gates-foundation-logo.webp',
            '/images/collabortor_logo/World_Bank-Logo.wine.webp',
            '/images/collabortor_logo/UNICEF-Logo.wine.webp'
        ];
        defaultLogos.forEach(logo => addCollaboratorLogo(logo));
    }

    function deleteHomeCollaborators(id) {
        if (!confirm("Are you sure you want to delete this Collaborators Section?")) return;
        fetch(HC_API_BASE + "/delete/" + id, { method: "POST" })
        .then(() => {
            hcEditingId = null;
            loadHomeCollaborators();
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadHomeCollaborators();
    });
    </script>
<?php }

function home_impact_page() { ?>
    <div class="wrap">
        <h1>Impact Statistics Manager</h1>
        <style>
            .error-msg { color: red; font-size: 13px; margin-top: 5px; display: block; font-weight: 500; }
            .input-error { border-color: red !important; }
            .stat-item { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; background: #fafafa; border-radius: 4px; display: flex; gap: 15px; align-items: center; }
            .stat-item input { flex: 1; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Impact Statistics Section</h2>

            <table class="form-table">
                <tr>
                    <th>Statistics</th>
                    <td>
                        <div id="hi_stats_container"></div>
                        <button type="button" class="button" onclick="addImpactStat()">+ Add Statistic</button>
                    </td>
                </tr>
            </table>

            <p>
                <button class="button button-primary" onclick="saveHomeImpact()">Save Statistics Section</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Impact Statistics Sections</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Stats Count</th>
                        <th>Preview</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="homeImpactList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const HI_API_BASE = "<?php echo site_url('/index.php?rest_route=/home-impact/v1'); ?>";
    let hiEditingId = null;

    function addImpactStat(number = '', label = '') {
        const container = document.getElementById('hi_stats_container');
        const count = container.children.length + 1;
        const div = document.createElement('div');
        div.className = 'stat-item';
        
        div.innerHTML = `
            <label>Stat ${count}:</label>
            <input type="text" class="hi-stat-number regular-text" placeholder="Number (e.g. 34)" value="${number.replace(/"/g, '&quot;')}">
            <input type="text" class="hi-stat-label regular-text" placeholder="Label (e.g. COUNTRIES CONNECTED)" value="${label.replace(/"/g, '&quot;')}">
            <button type="button" class="button" style="color:#b32d2e;" onclick="this.parentElement.remove(); updateHiCounts();">X</button>
        `;
        container.appendChild(div);
    }

    function updateHiCounts() {
        const container = document.getElementById('hi_stats_container');
        Array.from(container.children).forEach((child, index) => {
            const label = child.querySelector('label');
            if(label) label.innerText = `Stat ${index + 1}:`;
        });
    }

    function loadHomeImpacts() {
        fetch(HI_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                let stats = [];
                try { stats = JSON.parse(item.stats) || []; } catch(e){}
                
                let previewText = stats.map(s => `<strong>${s.number}</strong> ${s.label}`).join(' | ');

                html += `
                <tr>
                    <td>${stats.length} stats</td>
                    <td>${previewText}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editHomeImpact(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteHomeImpact(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("homeImpactList").innerHTML = html;
        });
    }

    function saveHomeImpact() {
        let statsArr = [];
        document.querySelectorAll('.stat-item').forEach(item => {
            let num = item.querySelector('.hi-stat-number').value.trim();
            let lbl = item.querySelector('.hi-stat-label').value.trim();
            if (num !== '' || lbl !== '') {
                statsArr.push({ number: num, label: lbl });
            }
        });

        const data = { stats: statsArr };
        
        let url = HI_API_BASE + "/add";
        if (hiEditingId) url = HI_API_BASE + "/update/" + hiEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            hiEditingId = null; 
            document.getElementById("hi_stats_container").innerHTML = '';
            loadHomeImpacts(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function editHomeImpact(item) {
        hiEditingId = item.id;
        
        document.getElementById("hi_stats_container").innerHTML = '';
        let stats = [];
        try { stats = JSON.parse(item.stats) || []; } catch(e){}
        stats.forEach(s => addImpactStat(s.number, s.label));
        if(stats.length === 0) addImpactStat();
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteHomeImpact(id) {
        if (!confirm("Are you sure you want to delete this Impact Statistics Section?")) return;
        fetch(HI_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadHomeImpacts());
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadHomeImpacts();
        if(document.getElementById("hi_stats_container").children.length === 0) {
            addImpactStat();
            addImpactStat();
            addImpactStat();
            addImpactStat();
        }
    });
    </script>
<?php }

function home_research_areas_page() { ?>
    <div class="wrap">
        <h1>Key Research Areas Manager</h1>
        <style>
            .area-item { border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; background: #fafafa; border-radius: 4px; position: relative; }
            .area-item input, .area-item textarea { width: 100%; margin-bottom: 10px; padding: 8px; }
            .remove-area { position: absolute; top: 10px; right: 10px; color: #b32d2e; cursor: pointer; border: none; background: none; font-weight: bold; font-size: 16px; }
            .area-image-preview { max-width: 200px; max-height: 150px; display: block; margin-bottom: 10px; border-radius: 4px; }
            .badge-row { display: flex; gap: 10px; }
            .badge-row input { flex: 1; }
        </style>

        <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #ccc;border-radius:6px;">
            <h2>Add / Edit Key Research Areas Section</h2>

            <div id="hra_areas_container"></div>
            <button type="button" class="button" onclick="addResearchArea()">+ Add Research Area Card</button>

            <p style="margin-top: 30px;">
                <button class="button button-primary" onclick="saveHomeResearchAreas()">Save Key Research Areas Section</button>
            </p>
        </div>

        <div style="margin-top:30px;">
            <h2>All Key Research Areas Sections</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Cards Count</th>
                        <th>Preview Titles</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="homeResearchAreasList"></tbody>
            </table>
        </div>
    </div>

    <script>
    const HRA_API_BASE = "<?php echo site_url('/index.php?rest_route=/home-research-areas/v1'); ?>";
    const HRA_WP_NONCE = "<?php echo wp_create_nonce('wp_rest'); ?>";
    let hraEditingId = null;

    function addResearchArea(data = {}) {
        const container = document.getElementById('hra_areas_container');
        const index = container.children.length;
        const div = document.createElement('div');
        div.className = 'area-item';
        div.dataset.index = index;
        
        let badgeText = data.badge_text || '';
        let badgeColor = data.badge_color || '#ff0000';
        let imageUrl = data.image_url || '';
        let title = data.title || '';
        let desc = data.description || '';
        let footerText = data.footer_text || '';

        div.innerHTML = `
            <button type="button" class="remove-area" onclick="this.parentElement.remove()">X</button>
            <h3 style="margin-top:0;">Research Area Card</h3>
            
            <label><strong>Badge</strong></label>
            <div class="badge-row">
                <input type="text" class="hra-badge-text" placeholder="Badge Text (e.g. REPRODUCTIVE HEALTH)" value="${badgeText.replace(/"/g, '&quot;')}">
                <input type="color" class="hra-badge-color" value="${badgeColor}" style="width: 50px; padding: 0; cursor: pointer;">
            </div>

            <label><strong>Image</strong></label>
            <img src="${imageUrl}" class="area-image-preview" style="display: ${imageUrl ? 'block' : 'none'};">
            <input type="hidden" class="hra-image-url" value="${imageUrl}">
            <input type="file" class="hra-image-file" accept="image/*" style="margin-bottom: 15px;" onchange="uploadResearchImage(this)">

            <label><strong>Title</strong></label>
            <input type="text" class="hra-title" placeholder="Card Title" value="${title.replace(/"/g, '&quot;')}">

            <label><strong>Description</strong></label>
            <textarea class="hra-desc" rows="3" placeholder="Card Description">${desc.replace(/</g, '&lt;')}</textarea>

            <label><strong>Footer Text</strong></label>
            <input type="text" class="hra-footer-text" placeholder="Footer (e.g. Field Report • Updated Nov 2025)" value="${footerText.replace(/"/g, '&quot;')}">
        `;
        container.appendChild(div);
    }

    function uploadResearchImage(fileInput) {
        const file = fileInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        
        fileInput.disabled = true;

        fetch(HRA_API_BASE + "/upload-image", {
            method: "POST",
            headers: { 'X-WP-Nonce': HRA_WP_NONCE },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            fileInput.disabled = false;
            if (data.url) {
                const parent = fileInput.parentElement;
                parent.querySelector('.hra-image-url').value = data.url;
                const preview = parent.querySelector('.area-image-preview');
                preview.src = data.url;
                preview.style.display = 'block';
            } else {
                alert("Image upload failed.");
            }
        })
        .catch(e => {
            fileInput.disabled = false;
            alert("Upload error.");
        });
    }

    function loadHomeResearchAreas() {
        fetch(HRA_API_BASE + "/all")
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                let areas = [];
                try { areas = JSON.parse(item.areas) || []; } catch(e){}
                
                let previewTitles = areas.map(a => a.title).join(', ');

                html += `
                <tr>
                    <td>${areas.length} cards</td>
                    <td>${previewTitles}</td>
                    <td>
                        <button class="button button-small edit-btn" onclick='editHomeResearchAreas(${JSON.stringify(item).replace(/'/g, "\\'")})'>Edit</button>
                        <button class="button button-small delete-btn" style="color:#b32d2e;" onclick='deleteHomeResearchAreas(${item.id})'>Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById("homeResearchAreasList").innerHTML = html;
        });
    }

    function saveHomeResearchAreas() {
        let areasArr = [];
        document.querySelectorAll('.area-item').forEach(item => {
            areasArr.push({
                badge_text: item.querySelector('.hra-badge-text').value.trim(),
                badge_color: item.querySelector('.hra-badge-color').value,
                image_url: item.querySelector('.hra-image-url').value,
                title: item.querySelector('.hra-title').value.trim(),
                description: item.querySelector('.hra-desc').value.trim(),
                footer_text: item.querySelector('.hra-footer-text').value.trim()
            });
        });

        const data = { areas: areasArr };
        
        let url = HRA_API_BASE + "/add";
        if (hraEditingId) url = HRA_API_BASE + "/update/" + hraEditingId;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(() => { 
            alert("Saved successfully!"); 
            hraEditingId = null; 
            document.getElementById("hra_areas_container").innerHTML = '';
            loadHomeResearchAreas(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function editHomeResearchAreas(item) {
        hraEditingId = item.id;
        document.getElementById("hra_areas_container").innerHTML = '';
        
        let areas = [];
        try { areas = JSON.parse(item.areas) || []; } catch(e){}
        
        areas.forEach(a => addResearchArea(a));
        if(areas.length === 0) addResearchArea();
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteHomeResearchAreas(id) {
        if (!confirm("Are you sure you want to delete this Research Areas Section?")) return;
        fetch(HRA_API_BASE + "/delete/" + id, { method: "DELETE" })
        .then(() => loadHomeResearchAreas());
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadHomeResearchAreas();
        if(document.getElementById("hra_areas_container").children.length === 0) {
            addResearchArea();
        }
    });
    </script>
<?php }
