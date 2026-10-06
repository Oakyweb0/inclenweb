<?php
/**
 * Somaarth Sites Manager - WordPress Admin & REST API
 */

add_action('admin_menu', function () {
    add_submenu_page(
        'group-our-work',
        'Somaarth Sites',
        'Somaarth Sites',
        'manage_options',
        'somaarth-sites',
        'somaarth_sites_manager_page'
    );
});

// REST API setup
add_action('rest_api_init', function () {
    register_rest_route('somaarth-sites/v1', '/all', [
        'methods'             => 'GET',
        'callback'            => 'get_somaarth_sites_data',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('somaarth-sites/v1', '/save', [
        'methods'             => 'POST',
        'callback'            => 'save_somaarth_sites_data',
        'permission_callback' => '__return_true',
    ]);
});

function get_somaarth_sites_data() {
    $data = get_option('somaarth_sites_custom_data', null);
    if (!$data) {
        $data = [
            'palwal' => [
                'name' => 'SOMAARTH – Palwal',
                'state' => 'Haryana',
                'distance' => '60 Kms from New Delhi',
                'villages' => '51',
                'population' => '200K+',
                'address' => 'H. Khata No. 1460/1582, Village- Mitrol, NH 19, Aurangabad, Haryana 121105',
                'email' => 'oic.palwal@somaarth.org'
            ],
            'bareilly' => [
                'name' => 'SOMAARTH – Bareilly',
                'state' => 'Uttar Pradesh',
                'distance' => '250 Kms from New Delhi',
                'villages' => '45',
                'population' => '180K+',
                'address' => 'Bareilly Research Center, Uttar Pradesh',
                'email' => 'oic.bareilly@somaarth.org'
            ],
            'mawphlang' => [
                'name' => 'SOMAARTH – Mawphlang',
                'state' => 'Meghalaya',
                'distance' => '25 Kms from Shillong',
                'villages' => '38',
                'population' => '60K+',
                'address' => 'East Khasi Hills, Mawphlang, Meghalaya 793121',
                'email' => 'oic.mawphlang@somaarth.org'
            ]
        ];
    }
    return rest_ensure_response(['success' => true, 'data' => $data]);
}

function save_somaarth_sites_data($request) {
    $body = $request->get_json_params();
    if (!empty($body)) {
        update_option('somaarth_sites_custom_data', $body);
        return rest_ensure_response(['success' => true, 'message' => 'Somaarth Sites updated successfully']);
    }
    return new WP_Error('invalid_data', 'No data received', ['status' => 400]);
}

function somaarth_sites_manager_page() {
    $data = get_option('somaarth_sites_custom_data', null);
    if (!$data) {
        $data = [
            'palwal' => [
                'name' => 'SOMAARTH – Palwal',
                'state' => 'Haryana',
                'distance' => '60 Kms from New Delhi',
                'villages' => '51',
                'population' => '200K+',
                'address' => 'H. Khata No. 1460/1582, Village- Mitrol, NH 19, Aurangabad, Haryana 121105',
                'email' => 'oic.palwal@somaarth.org'
            ],
            'bareilly' => [
                'name' => 'SOMAARTH – Bareilly',
                'state' => 'Uttar Pradesh',
                'distance' => '250 Kms from New Delhi',
                'villages' => '45',
                'population' => '180K+',
                'address' => 'Bareilly Research Center, Uttar Pradesh',
                'email' => 'oic.bareilly@somaarth.org'
            ],
            'mawphlang' => [
                'name' => 'SOMAARTH – Mawphlang',
                'state' => 'Meghalaya',
                'distance' => '25 Kms from Shillong',
                'villages' => '38',
                'population' => '60K+',
                'address' => 'East Khasi Hills, Mawphlang, Meghalaya 793121',
                'email' => 'oic.mawphlang@somaarth.org'
            ]
        ];
    }
    ?>
    <div class="wrap" style="max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 24px 30px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="color: #fff; margin: 0 0 6px 0; font-size: 26px; font-weight: 700;">📍 Somaarth Sites Manager</h1>
                    <p style="margin: 0; color: #94a3b8; font-size: 14px;">Manage surveillance sites (Palwal, Bareilly, Mawphlang), demographic stats, and facility information for <code>/somaarth-sites</code></p>
                </div>
                <button type="button" id="save-all-sites-btn" style="background: #ea580c; color: #fff; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; box-shadow: 0 2px 8px rgba(234, 88, 12, 0.4); transition: all 0.2s;">
                    💾 Save Changes
                </button>
            </div>
        </div>

        <div id="save-status-msg" style="display: none; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"></div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px;">
            <?php foreach (['palwal' => 'Palwal (Haryana)', 'bareilly' => 'Bareilly (UP)', 'mawphlang' => 'Mawphlang (Meghalaya)'] as $k => $label): 
                $site = $data[$k] ?? [];
            ?>
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 style="margin: 0; color: #0f172a; font-size: 18px; font-weight: 700;">🏛️ <?php echo esc_html($label); ?></h3>
                    <span style="background: #ffedd5; color: #c2410c; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">Active Site</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">Site Name</label>
                        <input type="text" id="<?php echo $k; ?>_name" value="<?php echo esc_attr($site['name'] ?? ''); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">State</label>
                            <input type="text" id="<?php echo $k; ?>_state" value="<?php echo esc_attr($site['state'] ?? ''); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">Distance</label>
                            <input type="text" id="<?php echo $k; ?>_distance" value="<?php echo esc_attr($site['distance'] ?? ''); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">Villages Count</label>
                            <input type="text" id="<?php echo $k; ?>_villages" value="<?php echo esc_attr($site['villages'] ?? ''); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">Population Covered</label>
                            <input type="text" id="<?php echo $k; ?>_population" value="<?php echo esc_attr($site['population'] ?? ''); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">Address</label>
                        <textarea id="<?php echo $k; ?>_address" rows="2" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"><?php echo esc_textarea($site['address'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px;">Contact Email</label>
                        <input type="email" id="<?php echo $k; ?>_email" value="<?php echo esc_attr($site['email'] ?? ''); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#save-all-sites-btn').on('click', function() {
            var btn = $(this);
            btn.text('Saving...').prop('disabled', true);
            
            var payload = {
                palwal: {
                    name: $('#palwal_name').val(),
                    state: $('#palwal_state').val(),
                    distance: $('#palwal_distance').val(),
                    villages: $('#palwal_villages').val(),
                    population: $('#palwal_population').val(),
                    address: $('#palwal_address').val(),
                    email: $('#palwal_email').val()
                },
                bareilly: {
                    name: $('#bareilly_name').val(),
                    state: $('#bareilly_state').val(),
                    distance: $('#bareilly_distance').val(),
                    villages: $('#bareilly_villages').val(),
                    population: $('#bareilly_population').val(),
                    address: $('#bareilly_address').val(),
                    email: $('#bareilly_email').val()
                },
                mawphlang: {
                    name: $('#mawphlang_name').val(),
                    state: $('#mawphlang_state').val(),
                    distance: $('#mawphlang_distance').val(),
                    villages: $('#mawphlang_villages').val(),
                    population: $('#mawphlang_population').val(),
                    address: $('#mawphlang_address').val(),
                    email: $('#mawphlang_email').val()
                }
            };

            fetch('<?php echo esc_url_raw(rest_url('somaarth-sites/v1/save')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btn.text('💾 Save Changes').prop('disabled', false);
                $('#save-status-msg')
                    .css({ 'display': 'block', 'background': '#dcfce7', 'color': '#15803d', 'border': '1px solid #bbf7d0' })
                    .text('✓ Somaarth Sites updated successfully!')
                    .fadeIn();
                setTimeout(function() { $('#save-status-msg').fadeOut(); }, 4000);
            })
            .catch(function(err) {
                btn.text('💾 Save Changes').prop('disabled', false);
                $('#save-status-msg')
                    .css({ 'display': 'block', 'background': '#fee2e2', 'color': '#b91c1c', 'border': '1px solid #fecaca' })
                    .text('❌ Error saving data: ' + err)
                    .fadeIn();
            });
        });
    });
    </script>
    <?php
}
