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

    register_rest_route('impact-sub/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => function() {
            global $wpdb;
            $table_findings = $wpdb->prefix . 'key_research_findings';
            $findings_data = $wpdb->get_row("SELECT * FROM $table_findings LIMIT 1", ARRAY_A);
            if ($findings_data && !empty($findings_data['findings']) && is_string($findings_data['findings'])) {
                $findings_data['findings'] = json_decode($findings_data['findings'], true);
            }
            return rest_ensure_response([
                'findings'     => $findings_data ?: get_option('key_research_findings_data', []),
                'policy'       => get_option('policy_influence_data', []),
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
                update_option('policy_influence_data', $body);
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

function policy_influence_page() {
    $data = get_option('policy_influence_data', [
        'title' => 'Translating Evidence into National Health Guidelines',
        'description' => 'Collaborating directly with Ministry of Health, ICMR, NITI Aayog, and WHO to shape clinical standards.',
        'policies' => "1. National Immunization Guidelines & Surveillance\n2. Child Survival & Neonatal Care Frameworks\n3. Anti-Microbial Resistance (AMR) Stewardship"
    ]);
    ?>
    <div class="wrap" style="max-width: 1000px;">
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 22px 28px; border-radius: 12px; margin-bottom: 25px;">
            <h1 style="color: #fff; margin: 0 0 6px 0; font-size: 24px;">🏛️ Policy Influence Manager</h1>
            <p style="margin: 0; color: #94a3b8; font-size: 14px;">Manage health policy contributions, advisory roles, and regulatory impact for <code>/policy-influence</code></p>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;">
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Heading</label>
                    <input type="text" id="policy_title" value="<?php echo esc_attr($data['title'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Policy Impact Summary</label>
                    <textarea id="policy_desc" rows="3" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;"><?php echo esc_textarea($data['description'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Key National Policies Influenced</label>
                    <textarea id="policy_list" rows="5" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;"><?php echo esc_textarea($data['policies'] ?? ''); ?></textarea>
                </div>
                <button type="button" id="save-policy-btn" style="background: #ea580c; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; align-self: flex-start;">
                    💾 Save Policy Data
                </button>
                <div id="policy-msg" style="display:none; padding: 10px; border-radius: 6px; font-weight: 600;"></div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#save-policy-btn').on('click', function() {
            var btn = $(this);
            btn.text('Saving...').prop('disabled', true);
            var payload = {
                title: $('#policy_title').val(),
                description: $('#policy_desc').val(),
                policies: $('#policy_list').val()
            };
            fetch('<?php echo esc_url_raw(rest_url('impact-sub/v1/save-policy')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function() {
                btn.text('💾 Save Policy Data').prop('disabled', false);
                $('#policy-msg').css({ display: 'block', background: '#dcfce7', color: '#15803d' }).text('✓ Policy data updated!').fadeIn();
                setTimeout(function() { $('#policy-msg').fadeOut(); }, 3500);
            });
        });
    });
    </script>
    <?php
}

function transforming_lives_page() {
    $data = get_option('transforming_lives_data', [
        'title' => 'Transforming Lives Across India',
        'subtitle' => 'See how our research translates into real-world health solutions.',
        'cta_label' => 'Explore Impact',
        'cta_link' => '/our-impact'
    ]);
    ?>
    <div class="wrap" style="max-width: 1000px;">
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 22px 28px; border-radius: 12px; margin-bottom: 25px;">
            <h1 style="color: #fff; margin: 0 0 6px 0; font-size: 24px;">✨ Transforming Lives Banner Manager</h1>
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
                cta_link: $('#trans_link').val()
            };
            fetch('<?php echo esc_url_raw(rest_url('impact-sub/v1/save-transforming')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function() {
                btn.text('💾 Save Transforming Lives Banner').prop('disabled', false);
                $('#trans-msg').css({ display: 'block', background: '#dcfce7', color: '#15803d' }).text('✓ Updated successfully!').fadeIn();
                setTimeout(function() { $('#trans-msg').fadeOut(); }, 3500);
            });
        });
    });
    </script>
    <?php
}
