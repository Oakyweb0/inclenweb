<?php
/**
 * Impact Sub-Pages Manager (Key Research Findings, Policy Influence, Transforming Lives)
 */

add_action('admin_menu', function () {
    // Key Research Findings
    add_submenu_page(
        'group-our-impact',
        'Key Research Findings',
        'Key Research Findings',
        'manage_options',
        'key-research-findings',
        'key_research_findings_page'
    );

    // Policy Influence
    add_submenu_page(
        'group-our-impact',
        'Policy Influence',
        'Policy Influence',
        'manage_options',
        'policy-influence',
        'policy_influence_page'
    );

    // Transforming Lives
    add_submenu_page(
        'group-our-impact',
        'Transforming Lives',
        'Transforming Lives',
        'manage_options',
        'transforming-lives',
        'transforming_lives_page'
    );
});

// REST API setup
add_action('rest_api_init', function () {
    // Key Research Findings
    register_rest_route('key-research-findings/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_key_research_findings_data',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('key-research-findings/v1', '/save', [
        'methods'             => 'POST',
        'callback'            => 'save_key_research_findings_data',
        'permission_callback' => '__return_true',
    ]);

    // Policy Influence
    register_rest_route('policy-influence/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_policy_influence_data',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('policy-influence/v1', '/save', [
        'methods'             => 'POST',
        'callback'            => 'save_policy_influence_data',
        'permission_callback' => '__return_true',
    ]);

    // Transforming Lives
    register_rest_route('transforming-lives/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => function() {
            $data = get_option('transforming_lives_data', [
                'title' => 'Transforming Lives Across India',
                'subtitle' => 'See how our research translates into real-world health solutions.',
                'cta_label' => 'Explore Impact',
                'cta_link' => '/our-impact',
                'image' => 'https://images.pexels.com/photos/6120214/pexels-photo-6120214.jpeg'
            ]);
            return rest_ensure_response($data);
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('transforming-lives/v1', '/save', [
        'methods'             => 'POST',
        'callback'            => function($request) {
            $body = $request->get_json_params();
            update_option('transforming_lives_data', $body);
            return rest_ensure_response(['success' => true, 'message' => 'Saved successfully']);
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('impact-sub/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => function() {
            global $wpdb;
            $table_findings = $wpdb->prefix . 'key_research_findings';
            $findings_data = $wpdb->get_row("SELECT * FROM $table_findings LIMIT 1", ARRAY_A);
            if ($findings_data && !empty($findings_data['findings']) && is_string($findings_data['findings'])) {
                $findings_data['findings'] = json_decode($findings_data['findings'], true);
            }

            $table_policy = $wpdb->prefix . 'policy_influence';
            $policy_data = $wpdb->get_row("SELECT * FROM $table_policy LIMIT 1", ARRAY_A);
            if ($policy_data) {
                foreach (['stats', 'pillars', 'thematic_cards', 'timeline_items', 'collaborators'] as $f) {
                    if (!empty($policy_data[$f]) && is_string($policy_data[$f])) {
                        $policy_data[$f] = json_decode($policy_data[$f], true);
                    }
                }
            }

            return rest_ensure_response([
                'findings'     => $findings_data ?: get_option('key_research_findings_data', []),
                'policy'       => $policy_data ?: get_option('policy_influence_data', []),
                'transforming' => get_option('transforming_lives_data', [])
            ]);
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('impact-sub/v1', '/save-(?P<section>[a-zA-Z0-9_-]+)', [
        'methods'             => 'POST',
        'callback'            => function($request) {
            $section = $request->get_param('section');
            $body = $request->get_json_params();
            if ($section === 'findings') {
                return save_key_research_findings_data($request);
            } elseif ($section === 'policy') {
                return save_policy_influence_data($request);
            } elseif ($section === 'transforming') {
                update_option('transforming_lives_data', $body);
            }
            return rest_ensure_response(['success' => true, 'message' => 'Saved successfully']);
        },
        'permission_callback' => '__return_true',
    ]);
});

function get_key_research_findings_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'key_research_findings';
    if (function_exists('custom_setup_database_tables')) {
        custom_setup_database_tables();
    }
    $row = $wpdb->get_row("SELECT * FROM $table ORDER BY id ASC LIMIT 1", ARRAY_A);
    if (!$row) {
        return new WP_REST_Response(['status' => 'not_found', 'data' => null], 404);
    }

    if (!empty($row['findings']) && is_string($row['findings'])) {
        $row['findings'] = json_decode($row['findings'], true);
    }

    return new WP_REST_Response(['status' => 'success', 'data' => $row], 200);
}

function save_key_research_findings_data($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'key_research_findings';
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

    $findings = isset($params['findings']) ? (is_array($params['findings']) ? json_encode($params['findings']) : $params['findings']) : '[]';

    $data = [
        'hero_badge'          => sanitize_text_field($params['hero_badge'] ?? 'Research • Evidence • Impact'),
        'hero_heading'        => sanitize_text_field($params['hero_heading'] ?? 'Key Research Findings'),
        'hero_description'    => sanitize_textarea_field($params['hero_description'] ?? ''),
        'hero_subdescription' => sanitize_textarea_field($params['hero_subdescription'] ?? ''),
        'intro_heading'       => sanitize_textarea_field($params['intro_heading'] ?? ''),
        'intro_highlight'     => sanitize_text_field($params['intro_highlight'] ?? ''),
        'intro_subtext'       => sanitize_textarea_field($params['intro_subtext'] ?? ''),
        'findings'            => $findings,
    ];

    $existing = $wpdb->get_var("SELECT id FROM $table LIMIT 1");
    if ($existing) {
        $wpdb->update($table, $data, ['id' => $existing]);
        return new WP_REST_Response(['status' => 'success', 'message' => 'Key Research Findings updated successfully!'], 200);
    } else {
        $wpdb->insert($table, $data);
        return new WP_REST_Response(['status' => 'success', 'message' => 'Key Research Findings saved successfully!'], 200);
    }
}

function key_research_findings_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'key_research_findings';
    if (function_exists('custom_setup_database_tables')) {
        custom_setup_database_tables();
    }
    $data = $wpdb->get_row("SELECT * FROM $table LIMIT 1", ARRAY_A);

    $findings = !empty($data['findings']) ? json_decode($data['findings'], true) : [];
    if (!is_array($findings)) $findings = [];
    ?>
    <div class="wrap" style="max-width: 1200px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #0f172a; margin: 0;">🔬 Key Research Findings Manager</h1>
                <p style="color: #64748b; margin: 5px 0 0 0; font-size: 14px;">Manage hero, introductory public health statement, and dynamic findings cards with charts for <code>/key-research-findings</code></p>
            </div>
            <div>
                <button type="button" id="save-krf-btn-top" class="button button-primary" style="background:#ea580c; border-color:#ea580c; padding: 6px 20px; font-size: 15px; height:auto; border-radius:6px; font-weight:600;">💾 Save Changes</button>
            </div>
        </div>

        <div id="krf-notice" style="display:none; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:600; font-size:14px;"></div>

        <form id="krf-form" onsubmit="event.preventDefault(); saveKrfData();">
            <!-- HERO SECTION -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">1. Hero Banner</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Top Tag / Badge</label>
                        <input type="text" id="krf_hero_badge" value="<?php echo esc_attr($data['hero_badge'] ?? 'Research • Evidence • Impact'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Main Heading</label>
                        <input type="text" id="krf_hero_heading" value="<?php echo esc_attr($data['hero_heading'] ?? 'Key Research Findings'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Subtitle (e.g. Evidence that Drives Change)</label>
                        <textarea id="krf_hero_description" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['hero_description'] ?? 'Evidence that Drives Change'); ?></textarea>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Sub-Description (Italic line)</label>
                        <textarea id="krf_hero_subdescription" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['hero_subdescription'] ?? 'Data-Led Insights for Better Health Outcomes.'); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- INTRO STATEMENT SECTION -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">2. Intro Statement Section</h3>
                <div style="margin-top:15px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Main Statement Heading</label>
                    <textarea id="krf_intro_heading" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['intro_heading'] ?? 'Our research translates complex public health challenges into actionable, evidence-based solutions.'); ?></textarea>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 2fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Highlighted Words in Heading</label>
                        <input type="text" id="krf_intro_highlight" value="<?php echo esc_attr($data['intro_highlight'] ?? 'public health challenges'); ?>" placeholder="e.g. public health challenges" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                        <small style="color:#64748b;">These words will be colored in orange accent in the heading</small>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Intro Subtext / Bridge Explanation</label>
                        <textarea id="krf_intro_subtext" rows="2" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo esc_textarea($data['intro_subtext'] ?? 'We bridge the gap between scientific investigation and implementation to ensure national health priorities are met with data-driven precision.'); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- FINDINGS CARDS REPEATER -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:15px;">
                    <div>
                        <h3 style="font-size:18px; color:#0f172a; margin:0;">3. Research Findings Cards (<span id="findings-count"><?php echo count($findings); ?></span>)</h3>
                        <p style="color:#64748b; font-size:13px; margin:4px 0 0 0;">Add, reorder, edit findings with dynamic Chart.js charts (Pie, Line, Bar, Stacked Bar) and statistics.</p>
                    </div>
                    <button type="button" onclick="addFindingCard()" class="button button-primary" style="background:#ea580c; border:none; padding:5px 15px; border-radius:6px; font-weight:600;">+ Add Finding Card</button>
                </div>

                <div id="findings-container" style="display:flex; flex-direction:column; gap:20px;"></div>
            </div>

            <div style="margin-top:20px; padding:15px 0; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                <button type="submit" id="save-krf-btn" class="button button-primary" style="background:#ea580c; border-color:#ea580c; padding: 8px 30px; font-size: 16px; height:auto; border-radius:6px; font-weight:700;">💾 Save All Changes</button>
            </div>
        </form>
    </div>

    <script>
    let findingsData = <?php echo json_encode($findings); ?>;

    function renderFindings() {
        const container = document.getElementById('findings-container');
        container.innerHTML = '';
        document.getElementById('findings-count').innerText = findingsData.length;

        findingsData.forEach((item, index) => {
            const card = document.createElement('div');
            card.style.cssText = 'background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04);';
            card.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:8px; margin-bottom:12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="background:#ea580c; color:#fff; font-size:12px; font-weight:700; padding:2px 8px; border-radius:12px;">#${index + 1}</span>
                        <strong style="font-size:16px; color:#0f172a;">${item.category_tag || 'Finding'} — ${item.title_highlight || item.title || 'Untitled'}</strong>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <button type="button" onclick="moveFinding(${index}, -1)" ${index === 0 ? 'disabled' : ''} style="background:#cbd5e1; color:#1e293b; border:none; padding:4px 8px; border-radius:4px; font-size:12px; cursor:pointer;">▲</button>
                        <button type="button" onclick="moveFinding(${index}, 1)" ${index === findingsData.length - 1 ? 'disabled' : ''} style="background:#cbd5e1; color:#1e293b; border:none; padding:4px 8px; border-radius:4px; font-size:12px; cursor:pointer;">▼</button>
                        <button type="button" onclick="removeFinding(${index})" style="background:#ef4444; color:#fff; border:none; padding:4px 10px; border-radius:4px; font-size:12px; cursor:pointer;">Delete</button>
                    </div>
                </div>

                <!-- ROW 1: TAG, TITLE, TITLE HIGHLIGHT, ACCENT COLOR, LAYOUT -->
                <div style="display:grid; grid-template-columns: 1.2fr 1fr 1.5fr 1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="display:block; font-weight:600; font-size:12px; margin-bottom:4px; color:#475569;">Category Tag</label>
                        <input type="text" value="${escapeHtml(item.category_tag || '')}" onchange="updateFinding(${index}, 'category_tag', this.value)" placeholder="e.g. Child Health Research" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:12px; margin-bottom:4px; color:#475569;">Title Prefix</label>
                        <input type="text" value="${escapeHtml(item.title || '')}" onchange="updateFinding(${index}, 'title', this.value)" placeholder="e.g. High Burden of" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:12px; margin-bottom:4px; color:#475569;">Title Highlight (Main Subject)</label>
                        <input type="text" value="${escapeHtml(item.title_highlight || '')}" onchange="updateFinding(${index}, 'title_highlight', this.value)" placeholder="e.g. Neurodevelopmental Disorders" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:12px; margin-bottom:4px; color:#475569;">Accent Color</label>
                        <select onchange="updateFinding(${index}, 'accent_color', this.value)" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                            <option value="accent" ${item.accent_color === 'accent' ? 'selected' : ''}>Accent Orange (#f7610c)</option>
                            <option value="brand" ${item.accent_color === 'brand' ? 'selected' : ''}>Brand Blue (#00558F)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:12px; margin-bottom:4px; color:#475569;">Layout Side</label>
                        <select onchange="updateFinding(${index}, 'layout_position', this.value)" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                            <option value="chart_right" ${item.layout_position === 'chart_right' ? 'selected' : ''}>Text Left / Chart Right</option>
                            <option value="chart_left" ${item.layout_position === 'chart_left' ? 'selected' : ''}>Chart Left / Text Right</option>
                        </select>
                    </div>
                </div>

                <!-- ROW 2: DESCRIPTION -->
                <div style="margin-bottom:12px;">
                    <label style="display:block; font-weight:600; font-size:12px; margin-bottom:4px; color:#475569;">Description / Evidence Paragraph</label>
                    <textarea rows="2" onchange="updateFinding(${index}, 'description', this.value)" placeholder="Detailed research description..." style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">${escapeHtml(item.description || '')}</textarea>
                </div>

                <!-- ROW 3: STAT DISPLAY STYLE & STAT VALUES -->
                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:12px;">
                    <div style="font-weight:700; font-size:13px; color:#0f172a; margin-bottom:8px;">📊 Statistics & Highlight Elements</div>
                    <div style="display:grid; grid-template-columns: 1.5fr 1fr 1.2fr 1fr 1.2fr; gap:10px;">
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Stat Display Style</label>
                            <select onchange="updateFinding(${index}, 'stat_type', this.value)" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                                <option value="single_stat" ${item.stat_type === 'single_stat' ? 'selected' : ''}>Single Stat + Note (e.g. 12% Incidence)</option>
                                <option value="dual_stat" ${item.stat_type === 'dual_stat' ? 'selected' : ''}>Dual Stat Box (e.g. 63% Unsafe + Critical Alert)</option>
                                <option value="tag_list" ${item.stat_type === 'tag_list' ? 'selected' : ''}>Tag Badges List (e.g. Leptospirosis, Scrub Typhus)</option>
                                <option value="banner_stat" ${item.stat_type === 'banner_stat' ? 'selected' : ''}>Large Banner Stat Box (e.g. 24% Children Missed)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Stat 1 Value</label>
                            <input type="text" value="${escapeHtml(item.stat_value || '')}" onchange="updateFinding(${index}, 'stat_value', this.value)" placeholder="e.g. 12% or 63%" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Stat 1 Label</label>
                            <input type="text" value="${escapeHtml(item.stat_label || '')}" onchange="updateFinding(${index}, 'stat_label', this.value)" placeholder="e.g. Incidence Rate" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Stat 2 Value (Dual Stat)</label>
                            <input type="text" value="${escapeHtml(item.stat_secondary_value || '')}" onchange="updateFinding(${index}, 'stat_secondary_value', this.value)" placeholder="e.g. Critical" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Stat 2 Label (Dual Stat)</label>
                            <input type="text" value="${escapeHtml(item.stat_secondary_label || '')}" onchange="updateFinding(${index}, 'stat_secondary_label', this.value)" placeholder="e.g. Public Health Alert" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:8px;">
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Stat Footer Note (Single Stat style)</label>
                            <input type="text" value="${escapeHtml(item.stat_note || '')}" onchange="updateFinding(${index}, 'stat_note', this.value)" placeholder="e.g. Underlining the need for scaled screening infrastructure." style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Tag Badges List (comma separated for Tag List style)</label>
                            <input type="text" value="${escapeHtml(item.tags || '')}" onchange="updateFinding(${index}, 'tags', this.value)" placeholder="e.g. Leptospirosis, Scrub Typhus" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                    </div>
                </div>

                <!-- ROW 4: CHART CONFIGURATION -->
                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px;">
                    <div style="font-weight:700; font-size:13px; color:#0f172a; margin-bottom:8px;">📈 Chart Configuration (Chart.js)</div>
                    <div style="display:grid; grid-template-columns: 1.2fr 2fr 1.5fr; gap:10px;">
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Chart Type</label>
                            <select onchange="updateFinding(${index}, 'chart_type', this.value)" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                                <option value="pie" ${item.chart_type === 'pie' ? 'selected' : ''}>Pie Chart</option>
                                <option value="line" ${item.chart_type === 'line' ? 'selected' : ''}>Line Chart (Smooth fill)</option>
                                <option value="bar" ${item.chart_type === 'bar' ? 'selected' : ''}>Bar Chart</option>
                                <option value="stacked_bar" ${item.chart_type === 'stacked_bar' ? 'selected' : ''}>Stacked Bar Chart</option>
                                <option value="none" ${item.chart_type === 'none' ? 'selected' : ''}>None (No chart)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Chart Labels (Comma separated)</label>
                            <input type="text" value="${escapeHtml(item.chart_labels || '')}" onchange="updateFinding(${index}, 'chart_labels', this.value)" placeholder="e.g. Impacted, Others or North, Central, South, East, West" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Dataset 1 Values (Numbers)</label>
                            <input type="text" value="${escapeHtml(item.chart_data || '')}" onchange="updateFinding(${index}, 'chart_data', this.value)" placeholder="e.g. 12, 88 or 45, 78, 52, 63" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; margin-top:8px;">
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Dataset 1 Label</label>
                            <input type="text" value="${escapeHtml(item.chart_dataset_label1 || '')}" onchange="updateFinding(${index}, 'chart_dataset_label1', this.value)" placeholder="e.g. Unsafe Practices % or Reached" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Dataset 2 Values (For Stacked Bar)</label>
                            <input type="text" value="${escapeHtml(item.chart_data_secondary || '')}" onchange="updateFinding(${index}, 'chart_data_secondary', this.value)" placeholder="e.g. 28, 22, 15, 32, 23" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:11px; margin-bottom:3px; color:#64748b;">Dataset 2 Label</label>
                            <input type="text" value="${escapeHtml(item.chart_dataset_label2 || '')}" onchange="updateFinding(${index}, 'chart_dataset_label2', this.value)" placeholder="e.g. Missed" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function addFindingCard() {
        findingsData.push({
            id: 'finding_' + Date.now(),
            category_tag: 'New Research Area',
            title: 'Key Finding in',
            title_highlight: 'Health Domain',
            description: 'Enter finding details and evidence-based clinical discovery here.',
            accent_color: 'accent',
            layout_position: 'chart_right',
            stat_type: 'single_stat',
            stat_value: '50%',
            stat_label: 'Impact Metric',
            stat_secondary_value: '',
            stat_secondary_label: '',
            stat_note: 'Validated via multicentric study.',
            tags: '',
            chart_type: 'bar',
            chart_labels: 'Category A, Category B, Category C',
            chart_data: '30, 50, 20',
            chart_data_secondary: '',
            chart_dataset_label1: 'Metric %',
            chart_dataset_label2: ''
        });
        renderFindings();
    }

    function removeFinding(index) {
        if (confirm('Are you sure you want to delete this research finding card?')) {
            findingsData.splice(index, 1);
            renderFindings();
        }
    }

    function moveFinding(index, delta) {
        const newIndex = index + delta;
        if (newIndex < 0 || newIndex >= findingsData.length) return;
        const temp = findingsData[index];
        findingsData[index] = findingsData[newIndex];
        findingsData[newIndex] = temp;
        renderFindings();
    }

    function updateFinding(index, field, value) {
        if (findingsData[index]) {
            findingsData[index][field] = value;
        }
    }

    function showNotice(msg, isError) {
        const notice = document.getElementById('krf-notice');
        notice.style.display = 'block';
        notice.style.background = isError ? '#fee2e2' : '#dcfce7';
        notice.style.color = isError ? '#991b1b' : '#166534';
        notice.style.border = isError ? '1px solid #f87171' : '1px solid #86efac';
        notice.innerText = msg;
        setTimeout(() => { notice.style.display = 'none'; }, 4000);
    }

    function saveKrfData() {
        const btn = document.getElementById('save-krf-btn');
        const btnTop = document.getElementById('save-krf-btn-top');
        btn.innerText = 'Saving...';
        btn.disabled = true;
        if (btnTop) { btnTop.innerText = 'Saving...'; btnTop.disabled = true; }

        const payload = {
            hero_badge: document.getElementById('krf_hero_badge').value.trim(),
            hero_heading: document.getElementById('krf_hero_heading').value.trim(),
            hero_description: document.getElementById('krf_hero_description').value.trim(),
            hero_subdescription: document.getElementById('krf_hero_subdescription').value.trim(),
            intro_heading: document.getElementById('krf_intro_heading').value.trim(),
            intro_highlight: document.getElementById('krf_intro_highlight').value.trim(),
            intro_subtext: document.getElementById('krf_intro_subtext').value.trim(),
            findings: findingsData
        };

        fetch('<?php echo esc_url_raw(rest_url('key-research-findings/v1/save')); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.innerText = '💾 Save All Changes';
            btn.disabled = false;
            if (btnTop) { btnTop.innerText = '💾 Save Changes'; btnTop.disabled = false; }

            if (data.status === 'success' || data.success) {
                showNotice('✓ Key Research Findings updated successfully!', false);
            } else {
                showNotice('Error saving: ' + (data.message || 'Unknown error'), true);
            }
        })
        .catch(err => {
            btn.innerText = '💾 Save All Changes';
            btn.disabled = false;
            if (btnTop) { btnTop.innerText = '💾 Save Changes'; btnTop.disabled = false; }
            showNotice('Failed to connect to server.', true);
        });
    }

    document.getElementById('save-krf-btn-top').addEventListener('click', saveKrfData);
    document.addEventListener('DOMContentLoaded', renderFindings);
    </script>
    <?php
}

function get_policy_influence_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'policy_influence';
    if (function_exists('custom_setup_database_tables')) {
        custom_setup_database_tables();
    }
    $row = $wpdb->get_row("SELECT * FROM $table ORDER BY id ASC LIMIT 1", ARRAY_A);
    if (!$row) {
        return new WP_REST_Response(['status' => 'not_found', 'data' => null], 404);
    }

    foreach (['stats', 'pillars', 'thematic_cards', 'timeline_items', 'collaborators'] as $f) {
        if (!empty($row[$f]) && is_string($row[$f])) {
            $row[$f] = json_decode($row[$f], true);
        }
    }

    return new WP_REST_Response(['status' => 'success', 'data' => $row], 200);
}

function save_policy_influence_data($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'policy_influence';
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

    $stats = isset($params['stats']) ? (is_array($params['stats']) ? json_encode($params['stats']) : $params['stats']) : '[]';
    $pillars = isset($params['pillars']) ? (is_array($params['pillars']) ? json_encode($params['pillars']) : $params['pillars']) : '[]';
    $thematic_cards = isset($params['thematic_cards']) ? (is_array($params['thematic_cards']) ? json_encode($params['thematic_cards']) : $params['thematic_cards']) : '[]';
    $timeline_items = isset($params['timeline_items']) ? (is_array($params['timeline_items']) ? json_encode($params['timeline_items']) : $params['timeline_items']) : '[]';
    $collaborators = isset($params['collaborators']) ? (is_array($params['collaborators']) ? json_encode($params['collaborators']) : $params['collaborators']) : '[]';

    $data = [
        'hero_badge'            => sanitize_text_field($params['hero_badge'] ?? 'EVIDENCE TO POLICY'),
        'hero_title_prefix'     => sanitize_text_field($params['hero_title_prefix'] ?? 'Policy'),
        'hero_title_highlight'  => sanitize_text_field($params['hero_title_highlight'] ?? 'Influence'),
        'hero_description'      => sanitize_textarea_field($params['hero_description'] ?? ''),
        'stats'                 => $stats,
        'approach_tag'          => sanitize_text_field($params['approach_tag'] ?? 'How we make an impact'),
        'approach_heading'      => sanitize_text_field($params['approach_heading'] ?? 'Our Approach'),
        'approach_description'  => sanitize_textarea_field($params['approach_description'] ?? ''),
        'pillars'               => $pillars,
        'thematic_tag'          => sanitize_text_field($params['thematic_tag'] ?? 'Research Streams'),
        'thematic_heading'      => sanitize_text_field($params['thematic_heading'] ?? 'Thematic Focus'),
        'thematic_description'  => sanitize_textarea_field($params['thematic_description'] ?? ''),
        'thematic_cards'        => $thematic_cards,
        'timeline_tag'          => sanitize_text_field($params['timeline_tag'] ?? 'Chronology of Impact'),
        'timeline_heading'      => sanitize_text_field($params['timeline_heading'] ?? 'Track Record'),
        'timeline_description'  => sanitize_textarea_field($params['timeline_description'] ?? ''),
        'timeline_items'        => $timeline_items,
        'partners_tag'          => sanitize_text_field($params['partners_tag'] ?? 'Global Translation Network'),
        'partners_heading'      => sanitize_text_field($params['partners_heading'] ?? 'Partners & Collaborators'),
        'partners_description'  => sanitize_textarea_field($params['partners_description'] ?? ''),
        'collaborators'         => $collaborators,
        'alliances_tag'         => sanitize_text_field($params['alliances_tag'] ?? 'Regional Alliances'),
        'alliances_text'        => sanitize_text_field($params['alliances_text'] ?? 'IndiaCLEN · ChinaCLEN · LatinCLEN · INCLEN Africa')
    ];

    $existing = $wpdb->get_var("SELECT id FROM $table LIMIT 1");
    if ($existing) {
        $wpdb->update($table, $data, ['id' => $existing]);
        return new WP_REST_Response(['status' => 'success', 'message' => 'Policy Influence data updated successfully!'], 200);
    } else {
        $wpdb->insert($table, $data);
        return new WP_REST_Response(['status' => 'success', 'message' => 'Policy Influence data saved successfully!'], 200);
    }
}

function policy_influence_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'policy_influence';
    if (function_exists('custom_setup_database_tables')) {
        custom_setup_database_tables();
    }
    $data = $wpdb->get_row("SELECT * FROM $table LIMIT 1", ARRAY_A);

    $stats = !empty($data['stats']) ? json_decode($data['stats'], true) : [];
    if (!is_array($stats) || empty($stats)) {
        $stats = [
            ['id' => 's1', 'value' => '89', 'label' => 'Academic Institutions'],
            ['id' => 's2', 'value' => '34', 'label' => 'Countries'],
            ['id' => 's3', 'value' => '218+', 'label' => 'Partners in India'],
            ['id' => 's4', 'value' => '200K+', 'label' => 'Population under surveillance']
        ];
    }

    $pillars = !empty($data['pillars']) ? json_decode($data['pillars'], true) : [];
    if (!is_array($pillars) || empty($pillars)) {
        $pillars = [
            ['id' => 'p1', 'title' => 'Evidence Generation', 'description' => 'Multi-site, collaborative research producing nationally representative data on high-priority health issues.', 'icon' => 'evidence'],
            ['id' => 'p2', 'title' => 'Capacity Building', 'description' => 'Training future leaders in clinical epidemiology and health research who carry evidence into policy roles.', 'icon' => 'capacity'],
            ['id' => 'p3', 'title' => 'Government Engagement', 'description' => 'Direct partnerships with MoHFW, ICMR, and state governments to align research with national health priorities.', 'icon' => 'government'],
            ['id' => 'p4', 'title' => 'Surveillance & Monitoring', 'description' => 'SOMAARTH-DDESS provides continuous demographic and health data from over 200,000 people to track policy outcomes.', 'icon' => 'surveillance']
        ];
    }

    $thematic_cards = !empty($data['thematic_cards']) ? json_decode($data['thematic_cards'], true) : [];
    if (!is_array($thematic_cards) || empty($thematic_cards)) {
        $thematic_cards = [
            ['id' => 't1', 'title' => 'Child Health', 'description' => 'Neurodevelopmental disabilities, low birth weight, pneumonia treatment, and vaccine-preventable disease.', 'color' => 'purple'],
            ['id' => 't2', 'title' => 'Maternal & Reproductive Health', 'description' => 'Evidence for improving maternal care quality, neonatal outcomes, and reproductive health access.', 'color' => 'rose'],
            ['id' => 't3', 'title' => 'Nutrition & NCDs', 'description' => 'Social determinants of undernutrition, childhood obesity, and metabolic syndrome across LMICs.', 'color' => 'emerald'],
            ['id' => 't4', 'title' => 'Mental Health', 'description' => 'Digital and primary-care-based screening and management for depression, anxiety, and alcohol use.', 'color' => 'sky'],
            ['id' => 't5', 'title' => 'Injuries & Violence', 'description' => 'Multi-country data on childhood injuries to support prevention policies and health system responses.', 'color' => 'orange']
        ];
    }

    $timeline_items = !empty($data['timeline_items']) ? json_decode($data['timeline_items'], true) : [];
    if (!is_array($timeline_items) || empty($timeline_items)) {
        $timeline_items = [
            ['id' => 'tl1', 'year' => '2019', 'tag' => 'Neonatal Health', 'title' => 'Management of Possible Serious Bacterial Infection (PSBI)', 'description' => 'Implementation research in Palwal, Haryana on managing newborn sepsis where hospital referral is not feasible — directly informing frontline health worker protocols.'],
            ['id' => 'tl2', 'year' => '2018', 'tag' => 'Vaccines', 'title' => 'Rollout of Rotavirus Vaccine & Active AEFI Surveillance', 'description' => 'Multi-centre surveillance of adverse events following immunisation (MAASS-India) and post-introduction evaluation of rotavirus vaccine to support national immunisation policy.'],
            ['id' => 'tl3', 'year' => '2016', 'tag' => 'Child Health', 'title' => 'Task Force on Childhood Obesity', 'description' => 'Nationally coordinated evidence synthesis used to develop India\'s policy framework for prevention and management of childhood and adolescent obesity.'],
            ['id' => 'tl4', 'year' => '2014', 'tag' => 'Neurodevelopment', 'title' => 'Neurodevelopmental Disabilities study (NDD-India)', 'description' => 'Landmark multi-site study establishing prevalence of neurodevelopmental disabilities among Indian children — a critical evidence base for national disability policy.'],
            ['id' => 'tl5', 'year' => '2009', 'tag' => 'Immunisation', 'title' => 'Evaluation of Integrated Management of Neonatal & Childhood Illness (IMNCI)', 'description' => 'Comprehensive programme evaluation informing India\'s IMNCI scale-up strategy and influencing WHO guidelines for LMIC settings.'],
            ['id' => 'tl6', 'year' => '2005', 'tag' => 'Universal Immunisation', 'title' => 'Evaluation of Universal Immunization Program (UIP)', 'description' => 'Multi-site evaluation of India\'s UIP, alongside repeated pulse polio programme evaluations from 1998 onwards, directly shaping national immunisation strategy.']
        ];
    }

    $collaborators = !empty($data['collaborators']) ? json_decode($data['collaborators'], true) : [];
    if (!is_array($collaborators) || empty($collaborators)) {
        $collaborators = [
            'Ministry of Health & Family Welfare',
            'Indian Council of Medical Research (ICMR)',
            'Government of Haryana',
            'World Health Organization (WHO)',
            'Bill & Melinda Gates Foundation',
            'UNICEF'
        ];
    }
    ?>
    <div class="wrap" style="max-width: 1200px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #0f172a; margin: 0;">🏛️ Policy Influence Manager</h1>
                <p style="color: #64748b; margin: 5px 0 0 0; font-size: 14px;">Manage hero, impact stats, 4 pillars, thematic focus streams, timeline track records, and partners for <code>/policy-influence</code></p>
            </div>
            <div>
                <button type="button" id="save-policy-btn-top" class="button button-primary" style="background:#ea580c; border-color:#ea580c; padding: 6px 20px; font-size: 15px; height:auto; border-radius:6px; font-weight:600;">💾 Save Changes</button>
            </div>
        </div>

        <!-- Floating Toast Notification -->
        <div id="policy-toast" style="position:fixed; top:50px; right:30px; z-index:99999; display:none; min-width:340px; padding:16px 22px; border-radius:10px; font-weight:600; font-size:15px; box-shadow:0 12px 30px rgba(0,0,0,0.18); transition:all 0.3s ease; transform:translateY(-15px); opacity:0;"></div>

        <div id="policy-notice" style="display:none; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:600; font-size:14px;"></div>

        <form id="policy-form" onsubmit="event.preventDefault(); savePolicyData();">
            <!-- 1. HERO SECTION -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">1. Hero Banner</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Badge</label>
                        <input type="text" id="pi_hero_badge" value="<?php echo esc_attr(!empty($data['hero_badge']) ? $data['hero_badge'] : 'EVIDENCE TO POLICY'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Title (First Word)</label>
                        <input type="text" id="pi_hero_title_prefix" value="<?php echo esc_attr(!empty($data['hero_title_prefix']) ? $data['hero_title_prefix'] : 'Policy'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Title Highlight (Italic / Blue)</label>
                        <input type="text" id="pi_hero_title_highlight" value="<?php echo esc_attr(!empty($data['hero_title_highlight']) ? $data['hero_title_highlight'] : 'Influence'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-top:15px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Hero Description</label>
                    <textarea id="pi_hero_description" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; line-height:1.5;"><?php echo esc_textarea(!empty($data['hero_description']) ? $data['hero_description'] : 'INCLEN bridges the gap between rigorous evidence and real-world policy, working alongside governments, international agencies, and health ministries to shape decisions that improve health outcomes for underserved populations.'); ?></textarea>
                </div>
            </div>

            <!-- 2. FLOATING IMPACT STATS SECTION -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:15px;">
                    <div>
                        <h3 style="font-size:18px; color:#0f172a; margin:0;">2. Floating Impact Stats (<span id="stats-count"><?php echo count($stats); ?></span>)</h3>
                        <p style="color:#64748b; font-size:13px; margin:4px 0 0 0;">Numbers displayed in the floating card right below the hero banner.</p>
                    </div>
                    <button type="button" onclick="addStatItem()" class="button button-primary" style="background:#ea580c; border:none; padding:5px 15px; border-radius:6px; font-weight:600;">+ Add Stat</button>
                </div>
                <div id="stats-container" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap:15px;"></div>
            </div>

            <!-- 3. OUR APPROACH SECTION -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">3. Our Approach Section</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Approach Tag / Subtitle</label>
                        <input type="text" id="pi_approach_tag" value="<?php echo esc_attr(!empty($data['approach_tag']) ? $data['approach_tag'] : 'How we make an impact'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Approach Main Heading</label>
                        <input type="text" id="pi_approach_heading" value="<?php echo esc_attr(!empty($data['approach_heading']) ? $data['approach_heading'] : 'Our Approach'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-top:15px; margin-bottom:20px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Approach Description Paragraph</label>
                    <textarea id="pi_approach_description" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; line-height:1.5;"><?php echo esc_textarea(!empty($data['approach_description']) ? $data['approach_description'] : "INCLEN's policy influence rests on four pillars — generating credible multi-site evidence, building a network of trained researchers, engaging directly with decision-makers, and sustaining long-term surveillance to track impact."); ?></textarea>
                </div>

                <!-- Pillars Repeater -->
                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed #cbd5e1; padding-top:15px; margin-bottom:12px;">
                    <strong style="font-size:15px; color:#0f172a;">Approach Pillars (<span id="pillars-count"><?php echo count($pillars); ?></span>)</strong>
                    <button type="button" onclick="addPillarItem()" class="button button-secondary" style="font-weight:600;">+ Add Pillar</button>
                </div>
                <div id="pillars-container" style="display:flex; flex-direction:column; gap:15px;"></div>
            </div>

            <!-- 4. THEMATIC FOCUS SECTION -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">4. Thematic Focus Section</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Thematic Tag</label>
                        <input type="text" id="pi_thematic_tag" value="<?php echo esc_attr(!empty($data['thematic_tag']) ? $data['thematic_tag'] : 'Research Streams'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Thematic Main Heading</label>
                        <input type="text" id="pi_thematic_heading" value="<?php echo esc_attr(!empty($data['thematic_heading']) ? $data['thematic_heading'] : 'Thematic Focus'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-top:15px; margin-bottom:20px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Thematic Description</label>
                    <textarea id="pi_thematic_description" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; line-height:1.5;"><?php echo esc_textarea(!empty($data['thematic_description']) ? $data['thematic_description'] : "INCLEN's five thematic groups generate evidence that directly informs national programmes and international guidelines."); ?></textarea>
                </div>

                <!-- Thematic Cards Repeater -->
                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed #cbd5e1; padding-top:15px; margin-bottom:12px;">
                    <strong style="font-size:15px; color:#0f172a;">Thematic Focus Cards (<span id="thematic-count"><?php echo count($thematic_cards); ?></span>)</strong>
                    <button type="button" onclick="addThematicItem()" class="button button-secondary" style="font-weight:600;">+ Add Thematic Card</button>
                </div>
                <div id="thematic-container" style="display:flex; flex-direction:column; gap:15px;"></div>
            </div>

            <!-- 5. TRACK RECORD (TIMELINE) SECTION -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">5. Track Record (Chronological Timeline)</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Timeline Tag</label>
                        <input type="text" id="pi_timeline_tag" value="<?php echo esc_attr(!empty($data['timeline_tag']) ? $data['timeline_tag'] : 'Chronology of Impact'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Timeline Main Heading</label>
                        <input type="text" id="pi_timeline_heading" value="<?php echo esc_attr(!empty($data['timeline_heading']) ? $data['timeline_heading'] : 'Track Record'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-top:15px; margin-bottom:20px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Timeline Description</label>
                    <textarea id="pi_timeline_description" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; line-height:1.5;"><?php echo esc_textarea(!empty($data['timeline_description']) ? $data['timeline_description'] : 'Selected research studies that have directly informed national programmes and government decision-making.'); ?></textarea>
                </div>

                <!-- Timeline Items Repeater -->
                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed #cbd5e1; padding-top:15px; margin-bottom:12px;">
                    <strong style="font-size:15px; color:#0f172a;">Timeline Milestones (<span id="timeline-count"><?php echo count($timeline_items); ?></span>)</strong>
                    <button type="button" onclick="addTimelineItem()" class="button button-primary" style="background:#ea580c; border:none; padding:5px 15px; border-radius:6px; font-weight:600;">+ Add Milestone</button>
                </div>
                <div id="timeline-container" style="display:flex; flex-direction:column; gap:15px;"></div>
            </div>

            <!-- 6. PARTNERS & COLLABORATORS SECTION -->
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:18px; color:#0f172a; margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">6. Partners & Collaborators Section</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Partners Tag</label>
                        <input type="text" id="pi_partners_tag" value="<?php echo esc_attr(!empty($data['partners_tag']) ? $data['partners_tag'] : 'Global Translation Network'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Partners Heading</label>
                        <input type="text" id="pi_partners_heading" value="<?php echo esc_attr(!empty($data['partners_heading']) ? $data['partners_heading'] : 'Partners & Collaborators'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-top:15px; margin-bottom:20px;">
                    <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Partners Description</label>
                    <textarea id="pi_partners_description" rows="3" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; line-height:1.5;"><?php echo esc_textarea(!empty($data['partners_description']) ? $data['partners_description'] : 'INCLEN maintains active strategic relationships with government bodies and international agencies to ensure research findings reach those who make policy.'); ?></textarea>
                </div>

                <!-- Collaborator Badges Repeater -->
                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed #cbd5e1; padding-top:15px; margin-bottom:12px;">
                    <strong style="font-size:15px; color:#0f172a;">Partner Organizations (<span id="collab-count"><?php echo count($collaborators); ?></span>)</strong>
                    <button type="button" onclick="addCollaboratorItem()" class="button button-secondary" style="font-weight:600;">+ Add Partner</button>
                </div>
                <div id="collab-container" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:20px;"></div>

                <!-- Regional Alliances -->
                <div style="border-top:1px solid #e2e8f0; padding-top:15px; display:grid; grid-template-columns: 1fr 2fr; gap:15px;">
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Regional Alliances Tag</label>
                        <input type="text" id="pi_alliances_tag" value="<?php echo esc_attr(!empty($data['alliances_tag']) ? $data['alliances_tag'] : 'Regional Alliances'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; margin-bottom:5px; color:#334155;">Regional Alliances List / Text</label>
                        <input type="text" id="pi_alliances_text" value="<?php echo esc_attr(!empty($data['alliances_text']) ? $data['alliances_text'] : 'IndiaCLEN · ChinaCLEN · LatinCLEN · INCLEN Africa'); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div style="margin-top:20px; padding:15px 0; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                <button type="submit" id="save-policy-btn" class="button button-primary" style="background:#ea580c; border-color:#ea580c; padding: 10px 32px; font-size: 16px; height:auto; border-radius:6px; font-weight:700; cursor:pointer;">💾 Save All Changes</button>
            </div>
        </form>
    </div>

    <script>
    let statsData = <?php echo json_encode($stats); ?>;
    let pillarsData = <?php echo json_encode($pillars); ?>;
    let thematicData = <?php echo json_encode($thematic_cards); ?>;
    let timelineData = <?php echo json_encode($timeline_items); ?>;
    let collabData = <?php echo json_encode($collaborators); ?>;

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // 1. STATS
    function renderStats() {
        const container = document.getElementById('stats-container');
        container.innerHTML = '';
        document.getElementById('stats-count').innerText = statsData.length;

        statsData.forEach((item, index) => {
            const el = document.createElement('div');
            el.style.cssText = 'background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:12px; position:relative;';
            el.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <strong style="font-size:12px; color:#64748b;">Stat #${index + 1}</strong>
                    <button type="button" onclick="removeStat(${index})" style="background:#fee2e2; color:#ef4444; border:none; padding:2px 6px; border-radius:4px; font-size:11px; cursor:pointer; font-weight:700;">✕</button>
                </div>
                <div style="margin-bottom:6px;">
                    <label style="display:block; font-size:11px; font-weight:600; color:#475569;">Value (e.g. 89 or 218+)</label>
                    <input type="text" value="${escapeHtml(item.value || '')}" onchange="statsData[${index}].value = this.value" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:13px; font-weight:700;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; color:#475569;">Label (e.g. Countries)</label>
                    <input type="text" value="${escapeHtml(item.label || '')}" onchange="statsData[${index}].label = this.value" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px;">
                </div>
            `;
            container.appendChild(el);
        });
    }

    function addStatItem() {
        statsData.push({ id: 's_' + Date.now(), value: '100+', label: 'New Metric' });
        renderStats();
    }
    function removeStat(idx) {
        statsData.splice(idx, 1);
        renderStats();
    }

    // 2. PILLARS
    function renderPillars() {
        const container = document.getElementById('pillars-container');
        container.innerHTML = '';
        document.getElementById('pillars-count').innerText = pillarsData.length;

        pillarsData.forEach((item, index) => {
            const el = document.createElement('div');
            el.style.cssText = 'background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:15px;';
            el.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:6px; margin-bottom:10px;">
                    <span style="font-weight:700; color:#0f172a; font-size:14px;">Pillar #${index + 1}: ${escapeHtml(item.title || 'Untitled')}</span>
                    <div style="display:flex; gap:6px;">
                        <button type="button" onclick="movePillar(${index}, -1)" ${index === 0 ? 'disabled' : ''} style="background:#cbd5e1; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer;">▲</button>
                        <button type="button" onclick="movePillar(${index}, 1)" ${index === pillarsData.length - 1 ? 'disabled' : ''} style="background:#cbd5e1; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer;">▼</button>
                        <button type="button" onclick="removePillar(${index})" style="background:#ef4444; color:#fff; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer;">Delete</button>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:12px; margin-bottom:10px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Pillar Title</label>
                        <input type="text" value="${escapeHtml(item.title || '')}" onchange="pillarsData[${index}].title = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:600;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Icon Preset</label>
                        <select onchange="pillarsData[${index}].icon = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                            <option value="evidence" ${item.icon === 'evidence' ? 'selected' : ''}>Evidence / Document Icon</option>
                            <option value="capacity" ${item.icon === 'capacity' ? 'selected' : ''}>Capacity / Book Icon</option>
                            <option value="government" ${item.icon === 'government' ? 'selected' : ''}>Government / People Icon</option>
                            <option value="surveillance" ${item.icon === 'surveillance' ? 'selected' : ''}>Surveillance / Map Icon</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Description</label>
                    <textarea rows="2" onchange="pillarsData[${index}].description = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">${escapeHtml(item.description || '')}</textarea>
                </div>
            `;
            container.appendChild(el);
        });
    }

    function addPillarItem() {
        pillarsData.push({ id: 'p_' + Date.now(), title: 'New Pillar', description: 'Description of the strategic pillar...', icon: 'evidence' });
        renderPillars();
    }
    function removePillar(idx) {
        pillarsData.splice(idx, 1);
        renderPillars();
    }
    function movePillar(idx, delta) {
        const newIdx = idx + delta;
        if (newIdx < 0 || newIdx >= pillarsData.length) return;
        const temp = pillarsData[idx];
        pillarsData[idx] = pillarsData[newIdx];
        pillarsData[newIdx] = temp;
        renderPillars();
    }

    // 3. THEMATIC CARDS
    function renderThematic() {
        const container = document.getElementById('thematic-container');
        container.innerHTML = '';
        document.getElementById('thematic-count').innerText = thematicData.length;

        thematicData.forEach((item, index) => {
            const el = document.createElement('div');
            el.style.cssText = 'background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:15px;';
            el.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:6px; margin-bottom:10px;">
                    <span style="font-weight:700; color:#0f172a; font-size:14px;">Thematic Stream #${index + 1}: ${escapeHtml(item.title || 'Untitled')}</span>
                    <div style="display:flex; gap:6px;">
                        <button type="button" onclick="moveThematic(${index}, -1)" ${index === 0 ? 'disabled' : ''} style="background:#cbd5e1; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer;">▲</button>
                        <button type="button" onclick="moveThematic(${index}, 1)" ${index === thematicData.length - 1 ? 'disabled' : ''} style="background:#cbd5e1; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer;">▼</button>
                        <button type="button" onclick="removeThematic(${index})" style="background:#ef4444; color:#fff; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer;">Delete</button>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:12px; margin-bottom:10px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Stream Title</label>
                        <input type="text" value="${escapeHtml(item.title || '')}" onchange="thematicData[${index}].title = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:600;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Accent Left Strip Color</label>
                        <select onchange="thematicData[${index}].color = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                            <option value="purple" ${item.color === 'purple' ? 'selected' : ''}>Purple</option>
                            <option value="rose" ${item.color === 'rose' ? 'selected' : ''}>Rose / Red</option>
                            <option value="emerald" ${item.color === 'emerald' ? 'selected' : ''}>Emerald / Green</option>
                            <option value="sky" ${item.color === 'sky' ? 'selected' : ''}>Sky / Blue</option>
                            <option value="orange" ${item.color === 'orange' ? 'selected' : ''}>Orange</option>
                            <option value="indigo" ${item.color === 'indigo' ? 'selected' : ''}>Indigo</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Description</label>
                    <textarea rows="2" onchange="thematicData[${index}].description = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">${escapeHtml(item.description || '')}</textarea>
                </div>
            `;
            container.appendChild(el);
        });
    }

    function addThematicItem() {
        thematicData.push({ id: 't_' + Date.now(), title: 'New Research Stream', description: 'Stream research focus area...', color: 'sky' });
        renderThematic();
    }
    function removeThematic(idx) {
        thematicData.splice(idx, 1);
        renderThematic();
    }
    function moveThematic(idx, delta) {
        const newIdx = idx + delta;
        if (newIdx < 0 || newIdx >= thematicData.length) return;
        const temp = thematicData[idx];
        thematicData[idx] = thematicData[newIdx];
        thematicData[newIdx] = temp;
        renderThematic();
    }

    // 4. TIMELINE
    function renderTimeline() {
        const container = document.getElementById('timeline-container');
        container.innerHTML = '';
        document.getElementById('timeline-count').innerText = timelineData.length;

        timelineData.forEach((item, index) => {
            const el = document.createElement('div');
            el.style.cssText = 'background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:15px;';
            el.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:6px; margin-bottom:10px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="background:#00558F; color:#fff; font-size:11px; font-weight:700; padding:2px 8px; border-radius:10px;">${escapeHtml(item.year || 'Year')}</span>
                        <strong style="color:#0f172a; font-size:14px;">${escapeHtml(item.title || 'Untitled')}</strong>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <button type="button" onclick="moveTimeline(${index}, -1)" ${index === 0 ? 'disabled' : ''} style="background:#cbd5e1; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer;">▲</button>
                        <button type="button" onclick="moveTimeline(${index}, 1)" ${index === timelineData.length - 1 ? 'disabled' : ''} style="background:#cbd5e1; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer;">▼</button>
                        <button type="button" onclick="removeTimeline(${index})" style="background:#ef4444; color:#fff; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer;">Delete</button>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns: 100px 180px 1fr; gap:12px; margin-bottom:10px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Year</label>
                        <input type="text" value="${escapeHtml(item.year || '')}" onchange="timelineData[${index}].year = this.value" placeholder="e.g. 2024" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:700;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Tag / Category</label>
                        <input type="text" value="${escapeHtml(item.tag || '')}" onchange="timelineData[${index}].tag = this.value" placeholder="e.g. Neonatal Health" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Study / Guideline Title</label>
                        <input type="text" value="${escapeHtml(item.title || '')}" onchange="timelineData[${index}].title = this.value" placeholder="Title of the research study" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:600;">
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">Policy Translation / Impact Description</label>
                    <textarea rows="2" onchange="timelineData[${index}].description = this.value" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">${escapeHtml(item.description || '')}</textarea>
                </div>
            `;
            container.appendChild(el);
        });
    }

    function addTimelineItem() {
        timelineData.push({ id: 'tl_' + Date.now(), year: '2024', tag: 'Public Health', title: 'New Impact Study', description: 'Details on how this study informed national health frameworks...' });
        renderTimeline();
    }
    function removeTimeline(idx) {
        timelineData.splice(idx, 1);
        renderTimeline();
    }
    function moveTimeline(idx, delta) {
        const newIdx = idx + delta;
        if (newIdx < 0 || newIdx >= timelineData.length) return;
        const temp = timelineData[idx];
        timelineData[idx] = timelineData[newIdx];
        timelineData[newIdx] = temp;
        renderTimeline();
    }

    // 5. COLLABORATORS
    function renderCollab() {
        const container = document.getElementById('collab-container');
        container.innerHTML = '';
        document.getElementById('collab-count').innerText = collabData.length;

        collabData.forEach((item, index) => {
            const strVal = typeof item === 'string' ? item : (item.name || '');
            const el = document.createElement('div');
            el.style.cssText = 'display:flex; gap:8px; align-items:center; background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:6px 10px;';
            el.innerHTML = `
                <input type="text" value="${escapeHtml(strVal)}" onchange="collabData[${index}] = this.value" style="flex:1; border:none; background:transparent; font-size:13px; font-weight:600; color:#1e293b; outline:none;">
                <button type="button" onclick="removeCollab(${index})" style="background:#fee2e2; color:#ef4444; border:none; padding:3px 7px; border-radius:4px; font-size:11px; cursor:pointer; font-weight:700;">✕</button>
            `;
            container.appendChild(el);
        });
    }

    function addCollaboratorItem() {
        collabData.push('New Strategic Partner Organization');
        renderCollab();
    }
    function removeCollab(idx) {
        collabData.splice(idx, 1);
        renderCollab();
    }

    function showNotice(msg, isError) {
        // Top banner notice
        const notice = document.getElementById('policy-notice');
        if (notice) {
            notice.style.display = 'block';
            notice.style.background = isError ? '#fee2e2' : '#dcfce7';
            notice.style.color = isError ? '#991b1b' : '#166534';
            notice.style.border = isError ? '1px solid #f87171' : '1px solid #86efac';
            notice.innerHTML = (isError ? '⚠️ ' : '✅ ') + msg;
            notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            setTimeout(() => { notice.style.display = 'none'; }, 4500);
        }

        // Floating Toast Notification
        const toast = document.getElementById('policy-toast');
        if (toast) {
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '10px';
            toast.style.background = isError ? '#ef4444' : '#16a34a';
            toast.style.color = '#ffffff';
            toast.style.border = isError ? '1px solid #b91c1c' : '1px solid #15803d';
            toast.innerHTML = `<span style="font-size:18px;">${isError ? '❌' : '🎉'}</span> <span>${msg}</span>`;
            setTimeout(() => {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            }, 10);
            setTimeout(() => {
                toast.style.transform = 'translateY(-15px)';
                toast.style.opacity = '0';
                setTimeout(() => { toast.style.display = 'none'; }, 350);
            }, 4000);
        }
    }

    function savePolicyData() {
        const btn = document.getElementById('save-policy-btn');
        const btnTop = document.getElementById('save-policy-btn-top');
        const originalText = '💾 Save All Changes';
        const originalTopText = '💾 Save Changes';
        
        if (btn) { btn.innerText = '⏳ Saving...'; btn.disabled = true; }
        if (btnTop) { btnTop.innerText = '⏳ Saving...'; btnTop.disabled = true; }

        const payload = {
            hero_badge: document.getElementById('pi_hero_badge').value.trim(),
            hero_title_prefix: document.getElementById('pi_hero_title_prefix').value.trim(),
            hero_title_highlight: document.getElementById('pi_hero_title_highlight').value.trim(),
            hero_description: document.getElementById('pi_hero_description').value.trim(),
            stats: statsData,
            approach_tag: document.getElementById('pi_approach_tag').value.trim(),
            approach_heading: document.getElementById('pi_approach_heading').value.trim(),
            approach_description: document.getElementById('pi_approach_description').value.trim(),
            pillars: pillarsData,
            thematic_tag: document.getElementById('pi_thematic_tag').value.trim(),
            thematic_heading: document.getElementById('pi_thematic_heading').value.trim(),
            thematic_description: document.getElementById('pi_thematic_description').value.trim(),
            thematic_cards: thematicData,
            timeline_tag: document.getElementById('pi_timeline_tag').value.trim(),
            timeline_heading: document.getElementById('pi_timeline_heading').value.trim(),
            timeline_description: document.getElementById('pi_timeline_description').value.trim(),
            timeline_items: timelineData,
            partners_tag: document.getElementById('pi_partners_tag').value.trim(),
            partners_heading: document.getElementById('pi_partners_heading').value.trim(),
            partners_description: document.getElementById('pi_partners_description').value.trim(),
            collaborators: collabData,
            alliances_tag: document.getElementById('pi_alliances_tag').value.trim(),
            alliances_text: document.getElementById('pi_alliances_text').value.trim()
        };

        fetch('<?php echo esc_url_raw(rest_url('policy-influence/v1/save')); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                if (btn) {
                    btn.innerText = '✅ Saved Successfully!';
                    btn.style.background = '#16a34a';
                    btn.style.borderColor = '#16a34a';
                }
                if (btnTop) {
                    btnTop.innerText = '✅ Saved!';
                    btnTop.style.background = '#16a34a';
                    btnTop.style.borderColor = '#16a34a';
                }
                showNotice('Policy Influence data updated successfully!', false);
                
                setTimeout(() => {
                    if (btn) {
                        btn.innerText = originalText;
                        btn.style.background = '#ea580c';
                        btn.style.borderColor = '#ea580c';
                        btn.disabled = false;
                    }
                    if (btnTop) {
                        btnTop.innerText = originalTopText;
                        btnTop.style.background = '#ea580c';
                        btnTop.style.borderColor = '#ea580c';
                        btnTop.disabled = false;
                    }
                }, 3000);
            } else {
                if (btn) { btn.innerText = originalText; btn.disabled = false; }
                if (btnTop) { btnTop.innerText = originalTopText; btnTop.disabled = false; }
                showNotice('Error saving: ' + (data.message || 'Unknown error'), true);
            }
        })
        .catch(err => {
            if (btn) { btn.innerText = originalText; btn.disabled = false; }
            if (btnTop) { btnTop.innerText = originalTopText; btnTop.disabled = false; }
            showNotice('Failed to connect to server: ' + err.message, true);
        });
    }

    document.getElementById('save-policy-btn-top').addEventListener('click', savePolicyData);
    document.addEventListener('DOMContentLoaded', () => {
        renderStats();
        renderPillars();
        renderThematic();
        renderTimeline();
        renderCollab();
    });
    </script>
    <?php
}

function transforming_lives_page() {
    $data = get_option('transforming_lives_data', [
        'title' => 'Transforming Lives Across India',
        'subtitle' => 'See how our research translates into real-world health solutions.',
        'cta_label' => 'Explore Impact',
        'cta_link' => '/our-impact',
        'image' => 'https://images.pexels.com/photos/6120214/pexels-photo-6120214.jpeg'
    ]);
    ?>
    <div class="wrap" style="max-width: 1000px;">
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 22px 28px; border-radius: 12px; margin-bottom: 25px;">
            <h1 style="color: #fff; margin: 0 0 6px 0; font-size: 24px;">Transforming Lives Banner Manager</h1>
            <p style="margin: 0; color: #94a3b8; font-size: 14px;">Manage promotional card and spotlight stories for Our Impact dropdown & pages</p>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;">
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Title</label>
                    <input type="text" id="trans_title" value="<?php echo esc_attr($data['title'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Subtitle / Tagline</label>
                    <textarea id="trans_sub" rows="3" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;"><?php echo esc_textarea($data['subtitle'] ?? ''); ?></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Button Label</label>
                        <input type="text" id="trans_btn" value="<?php echo esc_attr($data['cta_label'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Button Link</label>
                        <input type="text" id="trans_link" value="<?php echo esc_attr($data['cta_link'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    </div>
                </div>
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Image URL</label>
                    <input type="text" id="trans_image" value="<?php echo esc_attr($data['image'] ?? 'https://images.pexels.com/photos/6120214/pexels-photo-6120214.jpeg'); ?>" placeholder="https://images.pexels.com/..." style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <button type="button" id="save-trans-btn" style="background: #ea580c; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; align-self: flex-start;">
                    💾 Save Transforming Lives Banner
                </button>
                <div id="trans-msg" style="display:none; padding: 10px; border-radius: 6px; font-weight: 600;"></div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#save-trans-btn').on('click', function() {
            var btn = $(this);
            btn.text('Saving...').prop('disabled', true);
            var payload = {
                title: $('#trans_title').val(),
                subtitle: $('#trans_sub').val(),
                cta_label: $('#trans_btn').val(),
                cta_link: $('#trans_link').val(),
                image: $('#trans_image').val()
            };
            fetch('<?php echo esc_url_raw(rest_url('transforming-lives/v1/save')); ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify(payload)
            }).then(function(res) {
                return res.json();
            }).then(function() {
                btn.text('💾 Save Transforming Lives Banner').prop('disabled', false);
                $('#trans-msg').css({ display: 'block', background: '#dcfce7', color: '#15803d' }).text('✓ Updated successfully!').fadeIn();
                setTimeout(function() { $('#trans-msg').fadeOut(); }, 3500);
            }).catch(function(err) {
                btn.text('💾 Save Transforming Lives Banner').prop('disabled', false);
                $('#trans-msg').css({ display: 'block', background: '#fee2e2', color: '#b91c1c' }).text('Failed to save. Please try again.').fadeIn();
                setTimeout(function() { $('#trans-msg').fadeOut(); }, 3500);
            });
        });
    });
    </script>
    <?php
}
