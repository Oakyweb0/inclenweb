<?php
/**
 * Engagement & Advocacy Manager - WordPress Admin & REST API
 */

add_action('admin_menu', function () {
    add_submenu_page(
        'group-our-work',
        'Engagement & Advocacy',
        'Engagement & Advocacy',
        'manage_options',
        'engagement-advocacy-manager',
        'engagement_advocacy_manager_page'
    );
});

add_action('rest_api_init', function () {
    register_rest_route('engagement-advocacy/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_engagement_advocacy_data',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('engagement-advocacy/v1', '/save', [
        'methods'             => 'POST',
        'callback'            => 'save_engagement_advocacy_data',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('engagement-advocacy/v1', '/upload-pdf', [
        'methods'             => 'POST',
        'callback'            => 'upload_engagement_pdf_file',
        'permission_callback' => '__return_true',
    ]);
});

function upload_engagement_pdf_file() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return new WP_REST_Response(['error' => 'No file uploaded'], 400);
    }

    $file = $_FILES['file'];
    $fileTmp  = $file['tmp_name'];
    $fileName = time() . '-brief-' . sanitize_file_name($file['name']);
    $fileSize = $file['size'];

    $sizeMB = round($fileSize / 1048576, 1);
    $sizeStr = $sizeMB > 0 ? $sizeMB . ' MB' : round($fileSize / 1024, 1) . ' KB';

    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin/documents";

    if (!empty($accountId) && !empty($accessKey) && !empty($secretKey) && !empty($bucket)) {
        try {
            $client = new Aws\S3\S3Client([
                'version'     => 'latest',
                'region'      => 'auto',
                'endpoint'    => "https://$accountId.r2.cloudflarestorage.com",
                'credentials' => [
                    'key'    => $accessKey,
                    'secret' => $secretKey,
                ],
            ]);

            $client->putObject([
                'Bucket'      => $bucket,
                'Key'         => 'admin/documents/' . $fileName,
                'SourceFile'  => $fileTmp,
                'ContentType' => $file['type'],
                'ACL'         => 'public-read'
            ]);

            return new WP_REST_Response([
                'status'   => 'success',
                'url'      => $publicUrlBase . '/' . $fileName,
                'size_str' => $sizeStr,
                'filename' => $file['name']
            ], 200);
        } catch (Exception $e) {
            // fallback to local wp upload
        }
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload = wp_handle_upload($file, ['test_form' => false]);
    if (isset($upload['url'])) {
        return new WP_REST_Response([
            'status'   => 'success',
            'url'      => $upload['url'],
            'size_str' => $sizeStr,
            'filename' => $file['name']
        ], 200);
    }

    return new WP_REST_Response(['error' => 'Upload failed'], 500);
}

function get_engagement_advocacy_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'engagement_advocacy';
    $row = $wpdb->get_row("SELECT * FROM $table ORDER BY id ASC LIMIT 1", ARRAY_A);
    if (!$row) {
        return new WP_REST_Response(['status' => 'not_found', 'data' => null], 404);
    }

    if (!empty($row['approach_items']) && is_string($row['approach_items'])) {
        $row['approach_items'] = json_decode($row['approach_items'], true);
    }
    if (!empty($row['pillars_items']) && is_string($row['pillars_items'])) {
        $row['pillars_items'] = json_decode($row['pillars_items'], true);
    }
    if (!empty($row['resources_items']) && is_string($row['resources_items'])) {
        $row['resources_items'] = json_decode($row['resources_items'], true);
    }

    return new WP_REST_Response(['status' => 'success', 'data' => $row], 200);
}

function save_engagement_advocacy_data($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'engagement_advocacy';
    if (function_exists('custom_setup_database_tables')) {
        custom_setup_database_tables();
    }

    $params = $request->get_json_params();
    if (empty($params)) {
        $params = json_decode($request->get_body(), true);
    }

    if (empty($params)) {
        return new WP_REST_Response(['status' => 'error', 'message' => 'Invalid parameters'], 400);
    }

    $approach_items = isset($params['approach_items']) ? (is_array($params['approach_items']) ? json_encode($params['approach_items']) : $params['approach_items']) : '[]';
    $pillars_items = isset($params['pillars_items']) ? (is_array($params['pillars_items']) ? json_encode($params['pillars_items']) : $params['pillars_items']) : '[]';
    $resources_items = isset($params['resources_items']) ? (is_array($params['resources_items']) ? json_encode($params['resources_items']) : $params['resources_items']) : '[]';

    $data = [
        'hero_badge'            => sanitize_text_field($params['hero_badge'] ?? ''),
        'hero_title'            => sanitize_text_field($params['hero_title'] ?? ''),
        'hero_highlight'        => sanitize_text_field($params['hero_highlight'] ?? ''),
        'hero_description'      => sanitize_textarea_field($params['hero_description'] ?? ''),
        'belief_tag'            => sanitize_text_field($params['belief_tag'] ?? ''),
        'belief_heading'        => sanitize_text_field($params['belief_heading'] ?? ''),
        'belief_para1'          => sanitize_textarea_field($params['belief_para1'] ?? ''),
        'belief_para2'          => sanitize_textarea_field($params['belief_para2'] ?? ''),
        'belief_para3'          => sanitize_textarea_field($params['belief_para3'] ?? ''),
        'approach_tag'          => sanitize_text_field($params['approach_tag'] ?? ''),
        'approach_heading'      => sanitize_text_field($params['approach_heading'] ?? ''),
        'approach_description'  => sanitize_textarea_field($params['approach_description'] ?? ''),
        'approach_items'        => $approach_items,
        'pillars_tag'           => sanitize_text_field($params['pillars_tag'] ?? ''),
        'pillars_heading'       => sanitize_text_field($params['pillars_heading'] ?? ''),
        'pillars_description'   => sanitize_textarea_field($params['pillars_description'] ?? ''),
        'pillars_items'         => $pillars_items,
        'case_study_tag'        => sanitize_text_field($params['case_study_tag'] ?? ''),
        'case_study_heading'    => sanitize_text_field($params['case_study_heading'] ?? ''),
        'case_study_badge'      => sanitize_text_field($params['case_study_badge'] ?? ''),
        'case_study_title'      => sanitize_text_field($params['case_study_title'] ?? ''),
        'case_study_description'=> sanitize_textarea_field($params['case_study_description'] ?? ''),
        'case_study_image'      => esc_url_raw($params['case_study_image'] ?? ''),
        'resources_tag'         => sanitize_text_field($params['resources_tag'] ?? ''),
        'resources_heading'     => sanitize_text_field($params['resources_heading'] ?? ''),
        'resources_items'       => $resources_items,
    ];

    $existing = $wpdb->get_var("SELECT id FROM $table LIMIT 1");
    if ($existing) {
        $updated = $wpdb->update($table, $data, ['id' => $existing]);
        return new WP_REST_Response(['status' => 'success', 'message' => 'Engagement & Advocacy updated successfully!'], 200);
    } else {
        $inserted = $wpdb->insert($table, $data);
        return new WP_REST_Response(['status' => 'success', 'message' => 'Engagement & Advocacy saved successfully!'], 200);
    }
}

function engagement_advocacy_manager_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'engagement_advocacy';
    if (function_exists('custom_setup_database_tables')) {
        custom_setup_database_tables();
    }
    $data = $wpdb->get_row("SELECT * FROM $table LIMIT 1", ARRAY_A);

    $approach_items = !empty($data['approach_items']) ? json_decode($data['approach_items'], true) : [];
    if (!is_array($approach_items)) $approach_items = [];

    $pillars_items = !empty($data['pillars_items']) ? json_decode($data['pillars_items'], true) : [];
    if (!is_array($pillars_items)) $pillars_items = [];

    $resources_items = !empty($data['resources_items']) ? json_decode($data['resources_items'], true) : [];
    if (!is_array($resources_items)) $resources_items = [];
    ?>
    <div class="wrap" style="max-width: 1200px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #0f172a; margin: 0;">Engagement & Advocacy Manager</h1>
                <p style="color: #64748b; margin: 5px 0 0 0; font-size: 14px;">Manage all sections, strategic pillars, case studies, and <strong>PDF Policy Briefs & Publications</strong> for <code>/engagement-advocacy</code></p>
            </div>
            <div>
                <button type="button" id="save-ea-btn-top" class="button button-primary" style="background:#0284c7; border-color:#0284c7; padding: 6px 20px; font-size: 15px; height:auto; border-radius:6px; font-weight:600;">💾 Save Changes</button>
            </div>
        </div>

        <div id="ea-notice" style="display:none; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:600; font-size:14px;"></div>

        <!-- Tab Navigation -->
        <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; flex-wrap:wrap;">
            <button type="button" class="ea-tab-btn active-tab" onclick="openEaSection(event, 'ea-hero')" style="padding: 10px 18px; border:none; background:#0284c7; color:#fff; border-radius:6px; cursor:pointer; font-weight:600;">1. Hero Banner</button>
            <button type="button" class="ea-tab-btn" onclick="openEaSection(event, 'ea-belief')" style="padding: 10px 18px; border:none; background:#e2e8f0; color:#334155; border-radius:6px; cursor:pointer; font-weight:600;">2. Core Belief</button>
            <button type="button" class="ea-tab-btn" onclick="openEaSection(event, 'ea-approach')" style="padding: 10px 18px; border:none; background:#e2e8f0; color:#334155; border-radius:6px; cursor:pointer; font-weight:600;">3. Methodology (Approach)</button>
            <button type="button" class="ea-tab-btn" onclick="openEaSection(event, 'ea-pillars')" style="padding: 10px 18px; border:none; background:#e2e8f0; color:#334155; border-radius:6px; cursor:pointer; font-weight:600;">4. Strategic Pillars</button>
            <button type="button" class="ea-tab-btn" onclick="openEaSection(event, 'ea-case')" style="padding: 10px 18px; border:none; background:#e2e8f0; color:#334155; border-radius:6px; cursor:pointer; font-weight:600;">5. Case Study</button>
            <button type="button" class="ea-tab-btn" onclick="openEaSection(event, 'ea-resources')" style="padding: 10px 18px; border:none; background:#e2e8f0; color:#334155; border-radius:6px; cursor:pointer; font-weight:600;">📄 6. PDF Policy Briefs & Resources</button>
        </div>

        <form id="ea-form" onsubmit="event.preventDefault(); saveEaData();">
            <!-- TAB 1: HERO -->
            <div id="ea-hero" class="ea-section" style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Hero Section</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Badge</label>
                        <input type="text" id="ea_hero_badge" value="<?php echo esc_attr($data['hero_badge'] ?? 'Social Mobilization & Action'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Title (e.g. "Engagement &")</label>
                        <input type="text" id="ea_hero_title" value="<?php echo esc_attr($data['hero_title'] ?? 'Engagement &'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Highlight Word (e.g. "Advocacy")</label>
                        <input type="text" id="ea_hero_highlight" value="<?php echo esc_attr($data['hero_highlight'] ?? 'Advocacy'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-top:15px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Description</label>
                    <textarea id="ea_hero_description" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['hero_description'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- TAB 2: CORE BELIEF -->
            <div id="ea-belief" class="ea-section" style="display:none; background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Our Core Belief</h3>
                <div style="display:grid; grid-template-columns: 1fr 2fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Belief Tag</label>
                        <input type="text" id="ea_belief_tag" value="<?php echo esc_attr($data['belief_tag'] ?? 'Our Core Belief'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Belief Main Heading</label>
                        <input type="text" id="ea_belief_heading" value="<?php echo esc_attr($data['belief_heading'] ?? 'Sustainable health improvements are achieved when we work together.'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-top:15px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Paragraph 1</label>
                    <textarea id="ea_belief_para1" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['belief_para1'] ?? ''); ?></textarea>
                </div>
                <div style="margin-top:15px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Paragraph 2</label>
                    <textarea id="ea_belief_para2" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['belief_para2'] ?? ''); ?></textarea>
                </div>
                <div style="margin-top:15px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Paragraph 3</label>
                    <textarea id="ea_belief_para3" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['belief_para3'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- TAB 3: APPROACH / METHODOLOGY -->
            <div id="ea-approach" class="ea-section" style="display:none; background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Methodology & Approach Cards</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Tag</label>
                        <input type="text" id="ea_approach_tag" value="<?php echo esc_attr($data['approach_tag'] ?? 'Methodology'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Heading</label>
                        <input type="text" id="ea_approach_heading" value="<?php echo esc_attr($data['approach_heading'] ?? 'Our Approach'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Description</label>
                        <textarea id="ea_approach_description" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['approach_description'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h4 style="margin:0; font-size:15px; color:#1e293b;">Approach Cards (<span id="approach-count"><?php echo count($approach_items); ?></span>)</h4>
                    <button type="button" onclick="addApproachRow()" class="button" style="background:#0284c7; color:#fff; border:none; padding:4px 12px; border-radius:4px; font-weight:600;">+ Add Card</button>
                </div>
                <div id="approach-container" style="display:flex; flex-direction:column; gap:10px;"></div>
            </div>

            <!-- TAB 4: STRATEGIC PILLARS -->
            <div id="ea-pillars" class="ea-section" style="display:none; background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Strategic Pillars</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Pillars Tag</label>
                        <input type="text" id="ea_pillars_tag" value="<?php echo esc_attr($data['pillars_tag'] ?? 'Strategic Focus'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Pillars Heading</label>
                        <input type="text" id="ea_pillars_heading" value="<?php echo esc_attr($data['pillars_heading'] ?? 'Strategic Pillars'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Pillars Description</label>
                        <textarea id="ea_pillars_description" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['pillars_description'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h4 style="margin:0; font-size:15px; color:#1e293b;">Pillars List (<span id="pillars-count"><?php echo count($pillars_items); ?></span>)</h4>
                    <button type="button" onclick="addPillarRow()" class="button" style="background:#0284c7; color:#fff; border:none; padding:4px 12px; border-radius:4px; font-weight:600;">+ Add Pillar</button>
                </div>
                <div id="pillars-container" style="display:flex; flex-direction:column; gap:10px;"></div>
            </div>

            <!-- TAB 5: CASE STUDY -->
            <div id="ea-case" class="ea-section" style="display:none; background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Real-World Case Study (Impact Story)</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Section Tag</label>
                        <input type="text" id="ea_case_study_tag" value="<?php echo esc_attr($data['case_study_tag'] ?? 'Real-World Results'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Section Heading</label>
                        <input type="text" id="ea_case_study_heading" value="<?php echo esc_attr($data['case_study_heading'] ?? 'Impact Stories'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Case Study Badge / Category</label>
                        <input type="text" id="ea_case_study_badge" value="<?php echo esc_attr($data['case_study_badge'] ?? 'Case Study'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Case Study Image URL</label>
                        <input type="text" id="ea_case_study_image" value="<?php echo esc_attr($data['case_study_image'] ?? ''); ?>" placeholder="https://..." style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Case Study Headline / Title</label>
                        <input type="text" id="ea_case_study_title" value="<?php echo esc_attr($data['case_study_title'] ?? ''); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Case Study Description</label>
                        <textarea id="ea_case_study_description" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['case_study_description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- TAB 6: PDF POLICY BRIEFS & RESOURCES -->
            <div id="ea-resources" class="ea-section" style="display:none; background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
                    <div>
                        <h3 style="font-size:18px; color:#0f172a; margin:0;">📄 Publications & Policy Briefs (PDF Upload & Cards)</h3>
                        <p style="color:#64748b; font-size:13px; margin:4px 0 0 0;">Upload direct PDF documents or provide external links for Policy Briefs and Handbooks.</p>
                    </div>
                    <button type="button" onclick="addResourceRow()" class="button button-primary" style="background:#0284c7; border:none; padding:5px 15px; border-radius:6px; font-weight:600;">+ Add New Policy Brief / PDF</button>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Section Tag</label>
                        <input type="text" id="ea_resources_tag" value="<?php echo esc_attr($data['resources_tag'] ?? 'Resources'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Section Heading</label>
                        <input type="text" id="ea_resources_heading" value="<?php echo esc_attr($data['resources_heading'] ?? 'Publications & Policy Briefs'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                </div>

                <div id="resources-container" style="display:flex; flex-direction:column; gap:16px;"></div>
            </div>

            <div style="margin-top:20px; padding:15px 0; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                <button type="submit" id="save-ea-btn" class="button button-primary" style="background:#0284c7; border-color:#0284c7; padding: 8px 30px; font-size: 16px; height:auto; border-radius:6px; font-weight:700;">💾 Save All Changes</button>
            </div>
        </form>
    </div>

    <script>
    let approachesData = <?php echo json_encode($approach_items); ?>;
    let pillarsData = <?php echo json_encode($pillars_items); ?>;
    let resourcesData = <?php echo json_encode($resources_items); ?>;

    function openEaSection(evt, secId) {
        document.querySelectorAll('.ea-section').forEach(sec => sec.style.display = 'none');
        document.querySelectorAll('.ea-tab-btn').forEach(btn => {
            btn.style.background = '#e2e8f0';
            btn.style.color = '#334155';
        });
        document.getElementById(secId).style.display = 'block';
        evt.currentTarget.style.background = '#0284c7';
        evt.currentTarget.style.color = '#fff';
    }

    // Approaches render
    function renderApproaches() {
        const container = document.getElementById('approach-container');
        container.innerHTML = '';
        document.getElementById('approach-count').innerText = approachesData.length;
        approachesData.forEach((item, index) => {
            const row = document.createElement('div');
            row.style.cssText = 'background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:12px; display:grid; grid-template-columns: 1fr 2.5fr 50px; gap:10px; align-items:center;';
            row.innerHTML = `
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b;">Title</label>
                    <input type="text" value="${item.title || ''}" onchange="approachesData[${index}].title = this.value" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b;">Description</label>
                    <input type="text" value="${item.description || ''}" onchange="approachesData[${index}].description = this.value" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div style="padding-top:16px;">
                    <button type="button" onclick="removeApproach(${index})" style="background:#ef4444; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">✕</button>
                </div>
            `;
            container.appendChild(row);
        });
    }

    function addApproachRow() {
        approachesData.push({ title: 'New Approach Title', description: 'Approach description goes here.' });
        renderApproaches();
    }

    function removeApproach(idx) {
        approachesData.splice(idx, 1);
        renderApproaches();
    }

    // Pillars render
    function renderPillars() {
        const container = document.getElementById('pillars-container');
        container.innerHTML = '';
        document.getElementById('pillars-count').innerText = pillarsData.length;
        pillarsData.forEach((item, index) => {
            const row = document.createElement('div');
            row.style.cssText = 'background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:12px; display:grid; grid-template-columns: 1.2fr 1fr 2fr 50px; gap:10px; align-items:center;';
            row.innerHTML = `
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b;">Pillar Name</label>
                    <input type="text" value="${item.title || ''}" onchange="pillarsData[${index}].title = this.value" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b;">Icon (users/globe/document/book)</label>
                    <input type="text" value="${item.icon_name || 'users'}" onchange="pillarsData[${index}].icon_name = this.value" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b;">Description</label>
                    <input type="text" value="${item.description || ''}" onchange="pillarsData[${index}].description = this.value" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div style="padding-top:16px;">
                    <button type="button" onclick="removePillar(${index})" style="background:#ef4444; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">✕</button>
                </div>
            `;
            container.appendChild(row);
        });
    }

    function addPillarRow() {
        pillarsData.push({ title: 'New Pillar', description: 'Pillar description', icon_name: 'document' });
        renderPillars();
    }

    function removePillar(idx) {
        pillarsData.splice(idx, 1);
        renderPillars();
    }

    // Resources & PDF Upload render
    function renderResources() {
        const container = document.getElementById('resources-container');
        container.innerHTML = '';
        resourcesData.forEach((res, index) => {
            const card = document.createElement('div');
            card.style.cssText = 'background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:18px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);';
            card.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:8px; margin-bottom:12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:20px;">📄</span>
                        <strong style="color:#0f172a; font-size:15px;">Card #${index + 1}: ${res.title || 'Untitled Document'}</strong>
                    </div>
                    <button type="button" onclick="removeResource(${index})" style="background:#ef4444; color:#fff; border:none; padding:4px 10px; border-radius:4px; font-size:12px; cursor:pointer;">Delete</button>
                </div>
                <div style="display:grid; grid-template-columns: 1.5fr 1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Document Title <span style="color:red;">*</span></label>
                        <input type="text" value="${res.title || ''}" onchange="resourcesData[${index}].title = this.value" placeholder="e.g. Health Policy Brief 2024" style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Meta Info (e.g. Published: Date • PDF)</label>
                        <input type="text" id="meta-input-${index}" value="${res.meta || ''}" onchange="resourcesData[${index}].meta = this.value" placeholder="Published: 2024 • PDF (1.2 MB)" style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#334155;">Download Button Text</label>
                        <input type="text" value="${res.button_text || 'Download Brief'}" onchange="resourcesData[${index}].button_text = this.value" placeholder="Download Brief" style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                </div>
                <div style="background:#ffffff; border:1px dashed #94a3b8; border-radius:8px; padding:12px; margin-top:8px;">
                    <label style="font-size:12px; font-weight:700; color:#0f172a; display:block; margin-bottom:6px;">📁 Upload PDF File or Direct Link</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="file" id="pdf-file-picker-${index}" accept="application/pdf" style="display:none;" onchange="handlePdfUpload(${index}, this.files[0])">
                        <button type="button" onclick="document.getElementById('pdf-file-picker-${index}').click()" class="button" style="background:#0284c7; color:#fff; border:none; padding:6px 14px; border-radius:4px; font-weight:600; cursor:pointer;">
                            📤 Choose & Upload PDF
                        </button>
                        <input type="text" id="pdf-url-input-${index}" value="${res.pdf_url || ''}" onchange="resourcesData[${index}].pdf_url = this.value" placeholder="https://... or Upload above" style="flex:1; padding:7px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                        ${res.pdf_url && res.pdf_url !== '#' ? `<a href="${res.pdf_url}" target="_blank" class="button" style="background:#f1f5f9; color:#0f172a;">👁 View PDF</a>` : ''}
                    </div>
                    <div id="upload-status-${index}" style="margin-top:6px; font-size:12px; font-weight:600;"></div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function addResourceRow() {
        resourcesData.push({
            title: 'New Policy Brief',
            meta: 'Published: ' + new Date().getFullYear() + ' • PDF',
            pdf_url: '#',
            button_text: 'Download Brief'
        });
        renderResources();
    }

    function removeResource(idx) {
        if (confirm('Are you sure you want to remove this publication card?')) {
            resourcesData.splice(idx, 1);
            renderResources();
        }
    }

    function handlePdfUpload(index, file) {
        if (!file) return;
        if (file.type !== 'application/pdf') {
            alert('Please select a valid PDF file.');
            return;
        }

        const statusEl = document.getElementById(`upload-status-${index}`);
        statusEl.style.color = '#0369a1';
        statusEl.innerText = '⏳ Uploading PDF (' + (file.size / 1048576).toFixed(1) + ' MB)... Please wait.';

        const formData = new FormData();
        formData.append('file', file);

        fetch('<?php echo esc_url_raw(rest_url('engagement-advocacy/v1/upload-pdf')); ?>', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.url) {
                resourcesData[index].pdf_url = res.url;
                if (!resourcesData[index].meta || resourcesData[index].meta.includes('PDF')) {
                    resourcesData[index].meta = 'Published: ' + new Date().toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) + ' • PDF (' + res.size_str + ')';
                }
                statusEl.style.color = '#15803d';
                statusEl.innerText = '✅ PDF Uploaded Successfully! URL updated.';
                renderResources();
            } else {
                statusEl.style.color = '#b91c1c';
                statusEl.innerText = '❌ Upload failed: ' + (res.error || 'Server error');
            }
        })
        .catch(err => {
            statusEl.style.color = '#b91c1c';
            statusEl.innerText = '❌ Upload error: ' + err.message;
        });
    }

    function saveEaData() {
        const notice = document.getElementById('ea-notice');
        notice.style.display = 'block';
        notice.style.background = '#e0f2fe';
        notice.style.color = '#0369a1';
        notice.innerText = '⏳ Saving Engagement & Advocacy details...';

        const payload = {
            hero_badge: document.getElementById('ea_hero_badge').value,
            hero_title: document.getElementById('ea_hero_title').value,
            hero_highlight: document.getElementById('ea_hero_highlight').value,
            hero_description: document.getElementById('ea_hero_description').value,
            belief_tag: document.getElementById('ea_belief_tag').value,
            belief_heading: document.getElementById('ea_belief_heading').value,
            belief_para1: document.getElementById('ea_belief_para1').value,
            belief_para2: document.getElementById('ea_belief_para2').value,
            belief_para3: document.getElementById('ea_belief_para3').value,
            approach_tag: document.getElementById('ea_approach_tag').value,
            approach_heading: document.getElementById('ea_approach_heading').value,
            approach_description: document.getElementById('ea_approach_description').value,
            approach_items: approachesData,
            pillars_tag: document.getElementById('ea_pillars_tag').value,
            pillars_heading: document.getElementById('ea_pillars_heading').value,
            pillars_description: document.getElementById('ea_pillars_description').value,
            pillars_items: pillarsData,
            case_study_tag: document.getElementById('ea_case_study_tag').value,
            case_study_heading: document.getElementById('ea_case_study_heading').value,
            case_study_badge: document.getElementById('ea_case_study_badge').value,
            case_study_title: document.getElementById('ea_case_study_title').value,
            case_study_description: document.getElementById('ea_case_study_description').value,
            case_study_image: document.getElementById('ea_case_study_image').value,
            resources_tag: document.getElementById('ea_resources_tag').value,
            resources_heading: document.getElementById('ea_resources_heading').value,
            resources_items: resourcesData,
        };

        fetch('<?php echo esc_url_raw(rest_url('engagement-advocacy/v1/save')); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                notice.style.background = '#dcfce7';
                notice.style.color = '#15803d';
                notice.innerText = '✅ Saved successfully! All changes & PDFs are live.';
            } else {
                notice.style.background = '#fee2e2';
                notice.style.color = '#b91c1c';
                notice.innerText = '❌ Error saving: ' + (res.message || 'Unknown error');
            }
            setTimeout(() => { notice.style.display = 'none'; }, 4000);
        })
        .catch(err => {
            notice.style.background = '#fee2e2';
            notice.style.color = '#b91c1c';
            notice.innerText = '❌ Request failed: ' + err.message;
        });
    }

    document.getElementById('save-ea-btn-top').addEventListener('click', saveEaData);

    // Initial renders
    renderApproaches();
    renderPillars();
    renderResources();
    </script>
    <?php
}
