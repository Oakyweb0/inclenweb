<?php
/**
 * Setup and Update Database Tables for INCLEN Managers
 */

function custom_setup_database_tables() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();

    // 1. INCLEN Tools Table
    $table_inclen = $wpdb->prefix . 'inclen_tools';
    $sql_inclen = "CREATE TABLE $table_inclen (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        project_name varchar(255) NOT NULL,
        tool_name varchar(255) DEFAULT '',
        modules text DEFAULT '[]',
        cover_image varchar(255) DEFAULT '',
        pdfs text DEFAULT '[]',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_inclen);

    // 2. Research Projects (Original)
    $table_research = $wpdb->prefix . 'research_projects';
    $sql_research = "CREATE TABLE $table_research (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        year varchar(50) DEFAULT '',
        principal_investigator varchar(255) DEFAULT '',
        funder varchar(255) DEFAULT '',
        study_sites varchar(255) DEFAULT '',
        image_url varchar(255) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        summary text DEFAULT '',
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_research);

    // Make sure sort_order column exists
    $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_research' AND COLUMN_NAME = 'sort_order'");
    if(empty($row)) {
        $wpdb->query("ALTER TABLE $table_research ADD COLUMN sort_order int(11) DEFAULT 0");
    }

    // 3. Completed Projects (New)
    $table_completed = $wpdb->prefix . 'completed_projects';
    $sql_completed = "CREATE TABLE $table_completed (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        year varchar(50) DEFAULT '',
        principal_investigator varchar(255) DEFAULT '',
        co_investigator varchar(255) DEFAULT '',
        funder varchar(255) DEFAULT '',
        study_sites varchar(255) DEFAULT '',
        image_url varchar(255) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        summary text DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_completed);

    // 4. Research Priority Settings (New)
    $table_priority = $wpdb->prefix . 'priority_settings';
    $sql_priority = "CREATE TABLE $table_priority (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        category varchar(100) DEFAULT '',
        duration varchar(100) DEFAULT '',
        file_url varchar(255) DEFAULT '',
        file_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_priority);

    // 5. Document Library (Original)
    $table_docs = $wpdb->prefix . 'document_library';
    $sql_docs = "CREATE TABLE $table_docs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        category varchar(100) DEFAULT '',
        duration varchar(100) DEFAULT '',
        file_url varchar(255) DEFAULT '',
        file_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_docs);

    // 6. Download Requests Table
    $table_requests = $wpdb->prefix . 'download_requests';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_requests'") != $table_requests) {
        $sql_requests = "CREATE TABLE $table_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            full_name varchar(255) NOT NULL,
            email varchar(100) NOT NULL,
            location varchar(255) DEFAULT '',
            speciality varchar(255) DEFAULT '',
            project_title varchar(255) DEFAULT '',
            pdf_url varchar(255) DEFAULT '',
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_requests);
    }

    // 7. Partners Table
    $table_partners = $wpdb->prefix . 'partners';
    $sql_partners = "CREATE TABLE $table_partners (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        company_logo varchar(255) DEFAULT '',
        about text DEFAULT '',
        description longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_partners);

    // 8. Industry Partnerships Table
    $table_industry = $wpdb->prefix . 'industry_partnerships';
    $sql_industry = "CREATE TABLE $table_industry (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        company_logo varchar(255) DEFAULT '',
        para longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_industry);

    // 9. Research Partnerships Table
    $table_research_part = $wpdb->prefix . 'research_partnerships';
    $sql_research_part = "CREATE TABLE $table_research_part (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        company_logo varchar(255) DEFAULT '',
        para longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_research_part);

    // 10. Annual Reports Table
    $table_annual_reports = $wpdb->prefix . 'annual_reports';
    $sql_annual_reports = "CREATE TABLE $table_annual_reports (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cover_image varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        highlights longtext DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        year varchar(50) DEFAULT '',
        is_featured tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_annual_reports);

    // 11. Newsletters Table
    $table_newsletters = $wpdb->prefix . 'newsletters';
    $sql_newsletters = "CREATE TABLE $table_newsletters (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cover_image varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        highlights longtext DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        year varchar(50) DEFAULT '',
        is_featured tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_newsletters);

    // 12. Device Products Table
    $table_device_products = $wpdb->prefix . 'device_products';
    $sql_device_products = "CREATE TABLE $table_device_products (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cover_image varchar(255) DEFAULT '',
        tag varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        short_description text DEFAULT '',
        para longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_device_products);

    // 13. FCRA & Registration Table
    $table_fcra = $wpdb->prefix . 'fcra_registration';
    $sql_fcra = "CREATE TABLE $table_fcra (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        declared_year varchar(50) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_fcra);

    // 14. Training Materials Table
    $table_training = $wpdb->prefix . 'training_materials';
    $sql_training = "CREATE TABLE $table_training (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        heading varchar(255) NOT NULL,
        tag varchar(255) DEFAULT '',
        module varchar(255) DEFAULT '',
        level varchar(255) DEFAULT '',
        duration varchar(255) DEFAULT '',
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_training);

    // 15. Data Repository Table
    $table_data_repo = $wpdb->prefix . 'data_repository';
    $sql_data_repo = "CREATE TABLE $table_data_repo (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        category varchar(255) DEFAULT '',
        title varchar(255) NOT NULL,
        pdf_url varchar(255) DEFAULT '',
        pdf_size varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_data_repo);

    // 16. Home Page Hero Section Table
    $table_home_hero = $wpdb->prefix . 'home_hero';
    $sql_home_hero = "CREATE TABLE $table_home_hero (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        images longtext,
        heading varchar(255) NOT NULL,
        paragraph longtext DEFAULT '',
        latest_updates longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_hero);

    // 17. Home Page About Section Table
    $table_home_about = $wpdb->prefix . 'home_about';
    $sql_home_about = "CREATE TABLE $table_home_about (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) NOT NULL,
        description longtext DEFAULT '',
        main_image varchar(255) DEFAULT '',
        quote_text varchar(255) DEFAULT '',
        quote_author varchar(255) DEFAULT '',
        stat_number varchar(255) DEFAULT '',
        stat_text varchar(255) DEFAULT '',
        feature_blocks longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_about);

    // 18. Home Page Presence Section Table
    $table_home_presence = $wpdb->prefix . 'home_presence';
    $sql_home_presence = "CREATE TABLE $table_home_presence (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        heading varchar(255) NOT NULL,
        subheading text DEFAULT '',
        stats_data longtext,
        countries_data longtext,
        institutions_data longtext,
        networks_data longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_presence);

    // 19. Home Page Collaborators Section Table
    $table_home_collaborators = $wpdb->prefix . 'home_collaborators';
    $sql_home_collaborators = "CREATE TABLE $table_home_collaborators (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        heading varchar(255) NOT NULL,
        subheading text DEFAULT '',
        logos longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_collaborators);

    // 20. PDF Download Leads Table
    $table_download_leads = $wpdb->prefix . 'download_leads';
    $sql_download_leads = "CREATE TABLE $table_download_leads (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        full_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        location varchar(255) NOT NULL,
        speciality varchar(255) NOT NULL,
        pdf_url varchar(1000) NOT NULL,
        source_section varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_download_leads);

    // 21. Home Page Impact Statistics Table
    $table_home_impact = $wpdb->prefix . 'home_impact';
    $sql_home_impact = "CREATE TABLE $table_home_impact (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        stats longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_impact);

    // 22. Home Page Key Research Areas Table
    $table_home_research_areas = $wpdb->prefix . 'home_research_areas';
    $sql_home_research_areas = "CREATE TABLE $table_home_research_areas (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        areas longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_home_research_areas);

    // 23. About Page Who We Are Table
    $table_about_who_we_are = $wpdb->prefix . 'about_who_we_are';
    $sql_about_who_we_are = "CREATE TABLE $table_about_who_we_are (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) DEFAULT '',
        description longtext,
        image_url varchar(1000) DEFAULT '',
        quote_text text,
        quote_subtext varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_about_who_we_are);

    // 24. About Page Our Journey Table
    $table_about_our_journey = $wpdb->prefix . 'about_our_journey';
    $sql_about_our_journey = "CREATE TABLE $table_about_our_journey (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) DEFAULT '',
        subheading text DEFAULT '',
        milestones longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_about_our_journey);

    // 25. Website Navigation Menu Visibility Settings
    $table_site_navigation = $wpdb->prefix . 'site_navigation';
    $sql_site_navigation = "CREATE TABLE $table_site_navigation (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        menu_structure longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_site_navigation);
    // 26. About Page Mission Table
    $table_about_mission = $wpdb->prefix . 'about_mission';
    $sql_about_mission = "CREATE TABLE $table_about_mission (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tag_text varchar(255) DEFAULT '',
        heading varchar(255) DEFAULT '',
        description text DEFAULT '',
        cards longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_about_mission);

    // 27. Blogs Table
    $table_blogs = $wpdb->prefix . 'blogs';
    $sql_blogs = "CREATE TABLE $table_blogs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        slug varchar(255) DEFAULT '',
        content longtext DEFAULT '',
        author varchar(255) DEFAULT '',
        image varchar(255) DEFAULT '',
        banner_image varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_blogs);

    // 28. News Table
    $table_news = $wpdb->prefix . 'news';
    $sql_news = "CREATE TABLE $table_news (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        slug varchar(255) DEFAULT '',
        content longtext DEFAULT '',
        author varchar(255) DEFAULT '',
        image varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_news);

    // 29. Contact & Site Information Table
    $table_contact = $wpdb->prefix . 'contact_info';
    $sql_contact = "CREATE TABLE $table_contact (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        office_title varchar(255) DEFAULT 'Executive Office',
        address_line1 text DEFAULT '',
        address_line2 text DEFAULT '',
        phone varchar(100) DEFAULT '',
        phone_footer varchar(100) DEFAULT '',
        email varchar(150) DEFAULT '',
        map_query text DEFAULT '',
        map_embed_url text DEFAULT '',
        hero_title varchar(255) DEFAULT '',
        hero_subtitle text DEFAULT '',
        research_title varchar(255) DEFAULT '',
        research_description text DEFAULT '',
        stat1_value varchar(100) DEFAULT '',
        stat1_label varchar(255) DEFAULT '',
        stat2_value varchar(100) DEFAULT '',
        stat2_label varchar(255) DEFAULT '',
        stat3_value varchar(100) DEFAULT '',
        stat3_label varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_contact);

    // Seed default if empty
    $contact_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_contact");
    if (!$contact_count || $contact_count == 0) {
        $wpdb->insert($table_contact, [
            'office_title'         => 'Executive Office',
            'address_line1'        => 'A-157–158, 3rd Floor, DDA Shed, Okhla Phase-II',
            'address_line2'        => 'New Delhi – 110020',
            'phone'                => '+91-11-47730000',
            'phone_footer'         => '+91 11 47730000 - 99',
            'email'                => 'ieodelhi@inclentrust.org',
            'map_query'            => 'A-157 DDA Shed, Okhla Phase-II, New Delhi 110020',
            'map_embed_url'        => 'https://maps.google.com/maps?q=A-157%20DDA%20Shed%2C%20Okhla%20Phase-II%2C%20New%20Delhi%20110020&t=&z=16&ie=UTF8&iwloc=&output=embed',
            'hero_title'           => "Let's start a Conversation",
            'hero_subtitle'        => "Connect with the INCLEN Executive Office. Whether it's data access, institutional partnership, or global research inquiries.",
            'research_title'       => 'A Global Research Infrastructure',
            'research_description' => 'With 89 Clinical Epidemiology Units across 34 countries, our network provides a unique platform for high-impact multicentric studies.',
            'stat1_value'          => '34',
            'stat1_label'          => 'Countries Connected',
            'stat2_value'          => '89',
            'stat2_label'          => 'Partner Institutes',
            'stat3_value'          => '400k+',
            'stat3_label'          => 'Population Monitored'
        ]);
    }

    // 30. Impact Summary Table
    $table_impact_summary = $wpdb->prefix . 'impact_summary';
    $sql_impact_summary = "CREATE TABLE $table_impact_summary (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        hero_title varchar(255) DEFAULT 'Our Impact',
        hero_description longtext DEFAULT '',
        key_areas_title varchar(255) DEFAULT 'Key Impact Areas',
        key_areas longtext DEFAULT '[]',
        approach_tag varchar(255) DEFAULT 'Our Approach',
        approach_title varchar(255) DEFAULT 'Bridging Evidence & Action',
        approach_description longtext DEFAULT '',
        pathway1_badge varchar(50) DEFAULT '01',
        pathway1_title varchar(255) DEFAULT 'Research to Policy & Program',
        pathway1_description longtext DEFAULT '',
        pathway1_items longtext DEFAULT '[]',
        pathway2_badge varchar(50) DEFAULT '02',
        pathway2_title varchar(255) DEFAULT 'Research to Practice',
        pathway2_description longtext DEFAULT '',
        pathway2_items longtext DEFAULT '[]',
        cta_title varchar(255) DEFAULT 'Join Us in Making a Difference',
        cta_description longtext DEFAULT '',
        cta_btn1_text varchar(255) DEFAULT 'Partner With Us',
        cta_btn1_link varchar(255) DEFAULT '/contact',
        cta_btn2_text varchar(255) DEFAULT 'Explore Our Work',
        cta_btn2_link varchar(255) DEFAULT '/our-work',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_impact_summary);

    // Seed default if empty
    $impact_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_impact_summary");
    if (!$impact_count || $impact_count == 0) {
        $default_key_areas = [
            [
                'id' => '1',
                'title' => 'Global Research Network',
                'description' => 'Operates across 34 countries with 89 academic institutions and over 1,800 members, creating a massive platform for interdisciplinary public health research.',
                'icon_color' => 'brand',
                'bg_color' => 'brand',
                'icon_name' => 'globe'
            ],
            [
                'id' => '2',
                'title' => 'SOMAARTH Surveillance',
                'description' => 'Established one of the world\'s largest surveillance sites in Palwal, Haryana, covering 51 villages and a population exceeding 200,000 to monitor environmental and health transitions.',
                'icon_color' => 'blue',
                'bg_color' => 'green',
                'icon_name' => 'beaker'
            ],
            [
                'id' => '3',
                'title' => 'Diagnostic Innovation',
                'description' => 'Developed the widely used INCLEN diagnostic tool for neurodevelopmental disorders, validated and modified by AIIMS for nationwide child health assessments.',
                'icon_color' => 'blue',
                'bg_color' => 'blue',
                'icon_name' => 'flask'
            ],
            [
                'id' => '4',
                'title' => 'Policy & Program Evaluation',
                'description' => 'Conducted critical evaluations of India\'s Universal Immunization Program (UIP) and implemented research on managing neonatal sepsis and pneumonia to guide national health policy.',
                'icon_color' => 'green',
                'bg_color' => 'green',
                'icon_name' => 'document'
            ],
            [
                'id' => '5',
                'title' => 'National Priority Setting',
                'description' => 'Led a massive \'crowd-sourced\' initiative involving over 2,000 experts and 250+ institutions to define public health research priorities for the Government of India.',
                'icon_color' => 'purple',
                'bg_color' => 'purple',
                'icon_name' => 'clipboard'
            ],
            [
                'id' => '6',
                'title' => 'Vaccine & COVID-19',
                'description' => 'Actively managed clinical trials for COVID-19 vaccines (including heterologous prime-boost combinations) and sero-surveillance for Dengue and Chikungunya.',
                'icon_color' => 'red',
                'bg_color' => 'red',
                'icon_name' => 'shield'
            ]
        ];

        $default_pathway1_items = [
            [
                'title' => 'National Research Priority Setting (RPS)',
                'desc' => 'Collaborated with the Indian Council of Medical Research (ICMR) to lead a nationwide crowd-sourced exercise involving 2,000+ experts to define research priorities for maternal and child health through 2025.'
            ],
            [
                'title' => 'Immunization Policy',
                'desc' => 'Evaluated the Universal Immunization Program (UIP) and provided the evidence base for the rollout of the Rotavirus Vaccine and the Intensified Mission Indradhanush (IMI).'
            ],
            [
                'title' => 'National Health Programs',
                'desc' => 'Actively assists the government in adopting strategies for the National Program for Prevention & Control of Cancer, Diabetes, Cardiovascular Diseases and Stroke (NPCDCD).'
            ],
            [
                'title' => 'Childhood Pneumonia & Sepsis',
                'desc' => 'Generated evidence to assist national governments in adopting context-sensitive strategies to reduce under-five mortality from pneumonia.'
            ]
        ];

        $default_pathway2_items = [
            [
                'title' => 'INCLEN Diagnostic Tools (INDT)',
                'desc' => 'Developed validated diagnostic instruments for neurodevelopmental disorders (e.g., ADHD, Neuromotor Impairments) that primary care physicians can use with minimal training.'
            ],
            [
                'title' => 'Community Interventions',
                'desc' => 'Translates research into practice at the SOMAARTH site by engaging ASHA workers and Panchayati officers in dialogues about high-risk pregnancies and heart attack symptoms.'
            ],
            [
                'title' => 'Knowledge Translation Units',
                'desc' => 'Established the International Institute of Global Health (IIGH) which houses a \'Policy Unit\' dedicated to turning network-generated evidence into clinical care tools and practice guidelines.'
            ],
            [
                'title' => 'Diagnostic Validation',
                'desc' => 'Partnered with AIIMS to modify and validate INCLEN tools for a wider age range (1 month to 18 years), ensuring they are practical for diverse clinical settings.'
            ]
        ];

        $wpdb->insert($table_impact_summary, [
            'hero_title' => 'Our Impact',
            'hero_description' => "The INCLEN Trust measures its impact through large-scale research surveillance, policy translation, and the development of diagnostic tools that influence national health programs.\n\nKey Impact Areas: Global Research Network, SOMAARTH Surveillance Site, Diagnostic Innovation, Policy & Program Evaluation, National Research Priority Setting, Vaccine Research & COVID-19 Response.",
            'key_areas_title' => 'Key Impact Areas',
            'key_areas' => json_encode($default_key_areas),
            'approach_tag' => 'Our Approach',
            'approach_title' => 'Bridging Evidence & Action',
            'approach_description' => 'INCLEN Trust bridges the gap between scientific evidence and public health action through two primary pathways: translating research into national policies and programs, and converting findings into clinical practice.',
            'pathway1_badge' => '01',
            'pathway1_title' => 'Research to Policy & Program',
            'pathway1_description' => 'INCLEN acts as a strategic technical partner to the Government of India, ensuring that data drives national health strategies.',
            'pathway1_items' => json_encode($default_pathway1_items),
            'pathway2_badge' => '02',
            'pathway2_title' => 'Research to Practice',
            'pathway2_description' => 'The organization develops "actionable tools" that empower frontline health workers and primary care physicians to apply complex research in everyday settings.',
            'pathway2_items' => json_encode($default_pathway2_items),
            'cta_title' => 'Join Us in Making a Difference',
            'cta_description' => 'Partner with INCLEN to drive global health innovation and policy change. Together, we can build a healthier future.',
            'cta_btn1_text' => 'Partner With Us',
            'cta_btn1_link' => '/contact',
            'cta_btn2_text' => 'Explore Our Work',
            'cta_btn2_link' => '/our-work'
        ]);
    }
}

// Hook to run during theme load or admin init
add_action('after_switch_theme', 'custom_setup_database_tables');
add_action('admin_init', 'custom_setup_database_tables');
