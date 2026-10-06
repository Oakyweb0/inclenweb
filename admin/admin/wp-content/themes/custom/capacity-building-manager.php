<?php
/**
 * Capacity Building Manager - WordPress Admin & REST API
 */

add_action('admin_menu', function () {
    add_submenu_page(
        'group-our-work',
        'Capacity Building',
        'Capacity Building',
        'manage_options',
        'capacity-building-manager',
        'capacity_building_manager_page'
    );
});

add_action('rest_api_init', function () {
    register_rest_route('capacity-building/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_capacity_building_data',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('capacity-building/v1', '/save', [
        'methods'             => 'POST',
        'callback'            => 'save_capacity_building_data',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('capacity-building/v1', '/upload-file', [
        'methods'             => 'POST',
        'callback'            => 'upload_capacity_file_r2',
        'permission_callback' => '__return_true',
    ]);
});

function upload_capacity_file_r2() {
    global $accountId, $accessKey, $secretKey, $bucket;

    if (!isset($_FILES['file'])) {
        return ['error' => 'No file uploaded'];
    }

    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileName = time() . '-capacity-' . sanitize_file_name($_FILES['file']['name']);
    $fileSize = $_FILES['file']['size'];

    $sizeMB = round($fileSize / 1048576, 1);
    $sizeStr = $sizeMB > 0 ? $sizeMB . ' MB' : round($fileSize / 1024, 1) . ' KB';

    $publicUrlBase = "https://pub-0a4b820e73c14605a159d60ec5f71130.r2.dev/admin";

    if (!empty($accountId) && !empty($accessKey) && !empty($secretKey) && !empty($bucket)) {
        try {
            $client = new Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => 'auto',
                'endpoint' => "https://$accountId.r2.cloudflarestorage.com",
                'credentials' => [
                    'key'    => $accessKey,
                    'secret' => $secretKey,
                ],
            ]);

            $client->putObject([
                'Bucket'      => $bucket,
                'Key'         => 'admin/' . $fileName,
                'SourceFile'  => $fileTmp,
                'ContentType' => $_FILES['file']['type'],
                'ACL'         => 'public-read'
            ]);

            return [
                'url'  => $publicUrlBase . '/' . $fileName,
                'size' => $sizeStr
            ];
        } catch (Exception $e) {
            // fallback to local wp upload
        }
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload = wp_handle_upload($_FILES['file'], ['test_form' => false]);
    if (isset($upload['url'])) {
        return [
            'url'  => $upload['url'],
            'size' => $sizeStr
        ];
    }

    return ['error' => 'Upload failed'];
}

function get_capacity_building_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'capacity_building';
    $row = $wpdb->get_row("SELECT * FROM $table ORDER BY id ASC LIMIT 1", ARRAY_A);
    if (!$row) {
        return new WP_REST_Response(['status' => 'not_found', 'data' => null], 404);
    }

    if (!empty($row['focus_topics']) && is_string($row['focus_topics'])) {
        $row['focus_topics'] = json_decode($row['focus_topics'], true);
    }
    if (!empty($row['alumni_items']) && is_string($row['alumni_items'])) {
        $row['alumni_items'] = json_decode($row['alumni_items'], true);
    }

    return new WP_REST_Response(['status' => 'success', 'data' => $row], 200);
}

function save_capacity_building_data($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'capacity_building';
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

    $focus_topics = isset($params['focus_topics']) ? (is_array($params['focus_topics']) ? json_encode($params['focus_topics']) : $params['focus_topics']) : '[]';
    $alumni_items = isset($params['alumni_items']) ? (is_array($params['alumni_items']) ? json_encode($params['alumni_items']) : $params['alumni_items']) : '[]';

    $data = [
        'hero_badge'        => sanitize_text_field($params['hero_badge'] ?? ''),
        'hero_title'        => sanitize_text_field($params['hero_title'] ?? ''),
        'hero_highlight'    => sanitize_text_field($params['hero_highlight'] ?? ''),
        'hero_description'  => sanitize_textarea_field($params['hero_description'] ?? ''),
        'hero_btn_text'     => sanitize_text_field($params['hero_btn_text'] ?? ''),
        'hero_btn_link'     => sanitize_text_field($params['hero_btn_link'] ?? ''),
        'focus_tag'         => sanitize_text_field($params['focus_tag'] ?? ''),
        'focus_heading'     => sanitize_text_field($params['focus_heading'] ?? ''),
        'focus_description' => sanitize_textarea_field($params['focus_description'] ?? ''),
        'focus_topics'      => $focus_topics,
        'alumni_tag'        => sanitize_text_field($params['alumni_tag'] ?? ''),
        'alumni_heading'    => sanitize_text_field($params['alumni_heading'] ?? ''),
        'alumni_description'=> sanitize_textarea_field($params['alumni_description'] ?? ''),
        'alumni_items'      => $alumni_items,
        'cta_tag'           => sanitize_text_field($params['cta_tag'] ?? ''),
        'cta_heading'       => sanitize_text_field($params['cta_heading'] ?? ''),
        'cta_description'   => sanitize_textarea_field($params['cta_description'] ?? ''),
        'cta_btn1_text'     => sanitize_text_field($params['cta_btn1_text'] ?? ''),
        'cta_btn1_link'     => sanitize_text_field($params['cta_btn1_link'] ?? ''),
        'cta_btn2_text'     => sanitize_text_field($params['cta_btn2_text'] ?? ''),
        'cta_btn2_link'     => sanitize_text_field($params['cta_btn2_link'] ?? ''),
        'cta_btn3_text'     => sanitize_text_field($params['cta_btn3_text'] ?? ''),
        'cta_btn3_link'     => sanitize_text_field($params['cta_btn3_link'] ?? '')
    ];

    $existing = $wpdb->get_var("SELECT id FROM $table LIMIT 1");
    if ($existing) {
        $updated = $wpdb->update($table, $data, ['id' => $existing]);
        return new WP_REST_Response(['status' => 'success', 'message' => 'Capacity Building updated successfully!'], 200);
    } else {
        $inserted = $wpdb->insert($table, $data);
        return new WP_REST_Response(['status' => 'success', 'message' => 'Capacity Building saved successfully!'], 200);
    }
}

function capacity_building_manager_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'capacity_building';
    if (function_exists('custom_setup_database_tables')) {
        custom_setup_database_tables();
    }
    $data = $wpdb->get_row("SELECT * FROM $table LIMIT 1", ARRAY_A);

    $focus_topics = !empty($data['focus_topics']) ? json_decode($data['focus_topics'], true) : [];
    if (!is_array($focus_topics)) $focus_topics = [];

    $alumni_items = !empty($data['alumni_items']) ? json_decode($data['alumni_items'], true) : [];
    if (!is_array($alumni_items)) $alumni_items = [];
    ?>
    <div class="wrap" style="max-width: 1200px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #0f172a; margin: 0;">Capacity Building Manager</h1>
                <p style="color: #64748b; margin: 5px 0 0 0; font-size: 14px;">Manage all sections, training programs/topics, alumni records, and CTA banners dynamically for <code>/capacity-building</code></p>
            </div>
            <div>
                <button type="button" id="save-all-btn-top" class="button button-primary" style="background:#0284c7; border-color:#0284c7; padding: 6px 20px; font-size: 15px; height:auto; border-radius:6px; font-weight:600;">💾 Save Changes</button>
            </div>
        </div>

        <div id="notice-area" style="display:none; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:600; font-size:14px;"></div>

        <!-- Tab Navigation -->
        <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px;">
            <button type="button" class="tab-btn active-tab" onclick="openSection(event, 'sec-hero')" style="padding: 10px 18px; border:none; background:#0284c7; color:#fff; border-radius:6px; cursor:pointer; font-weight:600;">1. Hero Banner</button>
            <button type="button" class="tab-btn" onclick="openSection(event, 'sec-topics')" style="padding: 10px 18px; border:none; background:#e2e8f0; color:#334155; border-radius:6px; cursor:pointer; font-weight:600;">2. Core Training Programs (Topics)</button>
            <button type="button" class="tab-btn" onclick="openSection(event, 'sec-alumni')" style="padding: 10px 18px; border:none; background:#e2e8f0; color:#334155; border-radius:6px; cursor:pointer; font-weight:600;">3. Alumni & Participants Directory</button>
            <button type="button" class="tab-btn" onclick="openSection(event, 'sec-cta')" style="padding: 10px 18px; border:none; background:#e2e8f0; color:#334155; border-radius:6px; cursor:pointer; font-weight:600;">4. CTA Banner</button>
        </div>

        <form id="capacity-form" onsubmit="event.preventDefault(); saveCapacityData();">
            <!-- SECTION 1: HERO BANNER -->
            <div id="sec-hero" class="manager-section" style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Hero Banner Section</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Top Badge</label>
                        <input type="text" id="hero_badge" value="<?php echo esc_attr($data['hero_badge'] ?? 'Est. 2012 · Global Reach'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Main Title (e.g. "Capacity")</label>
                        <input type="text" id="hero_title" value="<?php echo esc_attr($data['hero_title'] ?? 'Capacity'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Highlight Word (e.g. "Building")</label>
                        <input type="text" id="hero_highlight" value="<?php echo esc_attr($data['hero_highlight'] ?? 'Building'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Button Text & Link</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" id="hero_btn_text" placeholder="Explore Programs" value="<?php echo esc_attr($data['hero_btn_text'] ?? 'Explore Programs'); ?>" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <input type="text" id="hero_btn_link" placeholder="#programs" value="<?php echo esc_attr($data['hero_btn_link'] ?? '#programs'); ?>" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                        </div>
                    </div>
                </div>
                <div style="margin-top:15px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Description</label>
                    <textarea id="hero_description" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['hero_description'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- SECTION 2: FOCUS TOPICS / PROGRAMS -->
            <div id="sec-topics" class="manager-section" style="display:none; background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Core Training Programs & Explorer Topics</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:15px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Section Tag</label>
                        <input type="text" id="focus_tag" value="<?php echo esc_attr($data['focus_tag'] ?? 'Core Training Programs'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Section Heading</label>
                        <input type="text" id="focus_heading" value="<?php echo esc_attr($data['focus_heading'] ?? 'Capacity Building Focus'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Section Subtitle / Description</label>
                        <textarea id="focus_description" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['focus_description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h4 style="font-size:16px; margin:0; color:#1e293b;">Training Programs / Topics List (<span id="topics-count"><?php echo count($focus_topics); ?></span>)</h4>
                    <button type="button" onclick="addTopicRow()" class="button" style="background:#0284c7; color:#fff; border:none; padding:5px 15px; border-radius:6px; font-weight:600;">+ Add New Topic</button>
                </div>

                <div id="topics-container" style="display:flex; flex-direction:column; gap:15px;">
                    <!-- JS will render dynamic cards -->
                </div>
            </div>

            <!-- SECTION 3: ALUMNI & PARTICIPANTS -->
            <div id="sec-alumni" class="manager-section" style="display:none; background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Alumni & Past Participants Directory</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:15px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Alumni Section Tag</label>
                        <input type="text" id="alumni_tag" value="<?php echo esc_attr($data['alumni_tag'] ?? 'Social Proof'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Alumni Heading</label>
                        <input type="text" id="alumni_heading" value="<?php echo esc_attr($data['alumni_heading'] ?? 'Alumni & Past Participants'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Alumni Subtitle / Description</label>
                        <textarea id="alumni_description" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['alumni_description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h4 style="font-size:16px; margin:0; color:#1e293b;">Alumni Records (<span id="alumni-count"><?php echo count($alumni_items); ?></span>)</h4>
                    <button type="button" onclick="addAlumnusRow()" class="button" style="background:#0284c7; color:#fff; border:none; padding:5px 15px; border-radius:6px; font-weight:600;">+ Add Alumnus</button>
                </div>

                <div id="alumni-container" style="display:flex; flex-direction:column; gap:10px;">
                    <!-- JS will render dynamic alumni rows -->
                </div>
            </div>

            <!-- SECTION 4: CTA BANNER -->
            <div id="sec-cta" class="manager-section" style="display:none; background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Get Involved (Bottom CTA Banner)</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">CTA Tag</label>
                        <input type="text" id="cta_tag" value="<?php echo esc_attr($data['cta_tag'] ?? 'Advance Your Health Career'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">CTA Heading</label>
                        <input type="text" id="cta_heading" value="<?php echo esc_attr($data['cta_heading'] ?? 'Get Involved Today'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">CTA Description</label>
                        <textarea id="cta_description" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['cta_description'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Button 1 (Primary - Orange)</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" id="cta_btn1_text" placeholder="Apply to LAMP ↗" value="<?php echo esc_attr($data['cta_btn1_text'] ?? 'Apply to LAMP ↗'); ?>" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <input type="text" id="cta_btn1_link" placeholder="#" value="<?php echo esc_attr($data['cta_btn1_link'] ?? '#'); ?>" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Button 2 (Outline)</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" id="cta_btn2_text" placeholder="Join as Institution" value="<?php echo esc_attr($data['cta_btn2_text'] ?? 'Join as Institution'); ?>" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <input type="text" id="cta_btn2_link" placeholder="#" value="<?php echo esc_attr($data['cta_btn2_link'] ?? '#'); ?>" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Button 3 (Outline)</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" id="cta_btn3_text" placeholder="Explore Internships" value="<?php echo esc_attr($data['cta_btn3_text'] ?? 'Explore Internships'); ?>" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <input type="text" id="cta_btn3_link" placeholder="#" value="<?php echo esc_attr($data['cta_btn3_link'] ?? '#'); ?>" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top:20px; padding:15px 0; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                <button type="submit" id="save-all-btn" class="button button-primary" style="background:#0284c7; border-color:#0284c7; padding: 8px 30px; font-size: 16px; height:auto; border-radius:6px; font-weight:700;">💾 Save All Changes</button>
            </div>
        </form>
    </div>

    <script>
    let topicsData = <?php echo json_encode($focus_topics); ?>;
    let alumniData = <?php echo json_encode($alumni_items); ?>;

    function openSection(evt, sectionId) {
        document.querySelectorAll('.manager-section').forEach(sec => sec.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.style.background = '#e2e8f0';
            btn.style.color = '#334155';
        });
        document.getElementById(sectionId).style.display = 'block';
        evt.currentTarget.style.background = '#0284c7';
        evt.currentTarget.style.color = '#fff';
    }

    function renderTopics() {
        const container = document.getElementById('topics-container');
        container.innerHTML = '';
        document.getElementById('topics-count').innerText = topicsData.length;

        topicsData.forEach((topic, index) => {
            const card = document.createElement('div');
            card.style.cssText = 'background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px; position:relative;';

            const curriculumText = Array.isArray(topic.curriculum) ? topic.curriculum.join("\n") : (topic.curriculum || '');

            card.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">
                    <span style="font-weight:700; color:#0f172a; font-size:15px;">#${index + 1}: ${topic.title || 'Untitled Topic'}</span>
                    <button type="button" onclick="removeTopic(${index})" style="background:#ef4444; color:#fff; border:none; padding:4px 10px; border-radius:4px; font-size:12px; cursor:pointer;">Delete</button>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569;">Key / Slug</label>
                        <input type="text" value="${topic.key || ''}" onchange="topicsData[${index}].key = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569;">Topic Title</label>
                        <input type="text" value="${topic.title || ''}" onchange="topicsData[${index}].title = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569;">Tag / Category</label>
                        <input type="text" value="${topic.tag || ''}" onchange="topicsData[${index}].tag = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:12px; font-weight:600; color:#475569;">Overview / Description</label>
                    <textarea rows="2" onchange="topicsData[${index}].overview = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">${topic.overview || ''}</textarea>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:12px; font-weight:600; color:#475569;">Curriculum Modules (1 item per line)</label>
                    <textarea rows="3" onchange="topicsData[${index}].curriculum = this.value.split('\\n').filter(Boolean)" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">${curriculumText}</textarea>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569;">Target Audience</label>
                        <input type="text" value="${topic.audience || ''}" onchange="topicsData[${index}].audience = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569;">Typical Duration</label>
                        <input type="text" value="${topic.duration || ''}" onchange="topicsData[${index}].duration = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function addTopicRow() {
        topicsData.push({
            key: 'topic-' + (topicsData.length + 1),
            title: 'New Program Title',
            tag: 'General Training',
            overview: 'Comprehensive training program description.',
            curriculum: ['Module 1 Overview', 'Module 2 Methodologies', 'Module 3 Practical Labs'],
            audience: 'Healthcare practitioners and researchers.',
            duration: '2-week intensive workshop'
        });
        renderTopics();
    }

    function removeTopic(index) {
        if (confirm('Are you sure you want to remove this topic?')) {
            topicsData.splice(index, 1);
            renderTopics();
        }
    }

    function renderAlumni() {
        const container = document.getElementById('alumni-container');
        container.innerHTML = '';
        document.getElementById('alumni-count').innerText = alumniData.length;

        alumniData.forEach((alumnus, index) => {
            const row = document.createElement('div');
            row.style.cssText = 'background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:12px; display:grid; grid-template-columns: 1.5fr 1fr 1.5fr 1fr 2fr 60px; gap:10px; align-items:center;';

            row.innerHTML = `
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b;">Name</label>
                    <input type="text" value="${alumnus.name || ''}" onchange="alumniData[${index}].name = this.value" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:13px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b;">Batch Year</label>
                    <input type="text" value="${alumnus.batch || ''}" onchange="alumniData[${index}].batch = this.value" placeholder="2015" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:13px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b;">Institution</label>
                    <input type="text" value="${alumnus.institution || ''}" onchange="alumniData[${index}].institution = this.value" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:13px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b;">Location</label>
                    <input type="text" value="${alumnus.location || ''}" onchange="alumniData[${index}].location = this.value" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:13px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b;">Focus Theme</label>
                    <input type="text" value="${alumnus.theme || ''}" onchange="alumniData[${index}].theme = this.value" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:13px;">
                </div>
                <div style="padding-top:16px;">
                    <button type="button" onclick="removeAlumnus(${index})" style="background:#ef4444; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; font-size:12px;">✕</button>
                </div>
            `;
            container.appendChild(row);
        });
    }

    function addAlumnusRow() {
        alumniData.unshift({
            name: 'Dr. New Participant',
            batch: '2024',
            institution: 'Medical University',
            location: 'New Delhi, India',
            theme: 'Clinical Epidemiology & Policy'
        });
        renderAlumni();
    }

    function removeAlumnus(index) {
        alumniData.splice(index, 1);
        renderAlumni();
    }

    function saveCapacityData() {
        const notice = document.getElementById('notice-area');
        notice.style.display = 'block';
        notice.style.background = '#e0f2fe';
        notice.style.color = '#0369a1';
        notice.innerText = '⏳ Saving Capacity Building details...';

        const payload = {
            hero_badge: document.getElementById('hero_badge').value,
            hero_title: document.getElementById('hero_title').value,
            hero_highlight: document.getElementById('hero_highlight').value,
            hero_description: document.getElementById('hero_description').value,
            hero_btn_text: document.getElementById('hero_btn_text').value,
            hero_btn_link: document.getElementById('hero_btn_link').value,
            focus_tag: document.getElementById('focus_tag').value,
            focus_heading: document.getElementById('focus_heading').value,
            focus_description: document.getElementById('focus_description').value,
            focus_topics: topicsData,
            alumni_tag: document.getElementById('alumni_tag').value,
            alumni_heading: document.getElementById('alumni_heading').value,
            alumni_description: document.getElementById('alumni_description').value,
            alumni_items: alumniData,
            cta_tag: document.getElementById('cta_tag').value,
            cta_heading: document.getElementById('cta_heading').value,
            cta_description: document.getElementById('cta_description').value,
            cta_btn1_text: document.getElementById('cta_btn1_text').value,
            cta_btn1_link: document.getElementById('cta_btn1_link').value,
            cta_btn2_text: document.getElementById('cta_btn2_text').value,
            cta_btn2_link: document.getElementById('cta_btn2_link').value,
            cta_btn3_text: document.getElementById('cta_btn3_text').value,
            cta_btn3_link: document.getElementById('cta_btn3_link').value,
        };

        fetch('<?php echo esc_url_raw(rest_url('capacity-building/v1/save')); ?>', {
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
                notice.innerText = '✅ Saved successfully! All changes are now live.';
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

    document.getElementById('save-all-btn-top').addEventListener('click', saveCapacityData);

    // Initial render
    renderTopics();
    renderAlumni();
    </script>
    <?php
}
