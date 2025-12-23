<?php
/**
 * Theme Name: Blocksy Child - Samuel Piasecký ACADEMY
 * Description: Child theme pre Samuel Piasecký ACADEMY s kompletným training management systémom
 * Author: Artefactum
 * Version: 26.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/*Artefactum support*/
include_once( ARTEFACTUM_COMMON . 'Artefactum-supports.php' );
include_once( ARTEFACTUM_COMMON . 'a-wplogin.php' );

// Remove gravity forms nag
function remove_gravity_forms_nag() {
    update_option( 'rg_gforms_message', '' );
    remove_action( 'after_plugin_row_gravityforms/gravityforms.php', array( 'GFForms', 'plugin_row' ) );
}
add_action( 'admin_init', 'remove_gravity_forms_nag' );

/*=== Capabilities (len pre admin) ===*//*=== Capabilities (len pre admin) ===*/
if (is_admin()) {
    
    // Capabilities sa nastavia LEN RAZ, nie pri každom načítaní
    function allow_editors_capabilities() {
        // Kontrola či už boli capabilities nastavené
        $capabilities_set = get_option('custom_capabilities_initialized', false);
        
        if ($capabilities_set) {
            return; // Už boli nastavené, nerobíme nič
        }
        
        $role = get_role('editor');
        if ($role) {
            // Blocksy capabilities
            $role->add_cap('edit_ct_content_blocks');
            $role->add_cap('edit_ct_content_block');
            $role->add_cap('edit_others_ct_content_blocks');
            $role->add_cap('publish_ct_content_blocks');
            $role->add_cap('read_ct_content_block');
            $role->add_cap('delete_ct_content_blocks');
            
            // Gravity Forms capabilities
            $role->add_cap('gravityforms_view_entries');
            $role->add_cap('gravityforms_delete_entries');
            $role->add_cap('gravityforms_view_entry_notes');
            $role->add_cap('gravityforms_export_entries');
            $role->add_cap('gravityforms_edit_entries');
            
            // UPRAVENÉ: manage_options len ak naozaj treba
            // POZOR: Toto dáva editorom širšie práva, zvážte či je to potrebné
            if (!$role->has_cap('manage_options')) {
                $role->add_cap('manage_options');
            }
        }
        
        // Označíme že capabilities už boli nastavené
        update_option('custom_capabilities_initialized', true);
    }
    
    // Spustíme len raz pri aktivácii témy alebo manuálne
    add_action('after_switch_theme', 'allow_editors_capabilities');
    
    // Ak potrebujete resetovať capabilities, pridajte do URL: ?reset_capabilities=1
    if (isset($_GET['reset_capabilities']) && current_user_can('administrator')) {
        delete_option('custom_capabilities_initialized');
        allow_editors_capabilities();
        wp_die('Capabilities boli resetované. <a href="' . admin_url() . '">Späť na dashboard</a>');
    }

    // Obmedzenia len pre non-admin používateľov
    function restrict_blocksy_dashboard_for_editors() {
        // DÔLEŽITÉ: Nekontrolujeme počas AIOS bezpečnostných kontrol
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        if (!current_user_can('administrator')) {
            $page = $_GET['page'] ?? '';
            if (in_array($page, ['ct-dashboard', 'ct-dashboard-account'])) {
                wp_die('Nemáte oprávnenie pre vstup do tejto sekcie.');
            }
        }
    }
    add_action('admin_init', 'restrict_blocksy_dashboard_for_editors', 999); // Vysoká priorita

    // Menu obmedzenia len pre non-artefactum používateľov
    function my_remove_menu_pages() {
        global $current_user;
        wp_get_current_user();
        
        // Len pre non-artefactum používateľov
        if($current_user->user_login != 'artefactum'){
            $menus_to_remove = [
                'wp-mail-smtp','aiowpsec','litespeed','advanced_db_cleaner','tools.php','elementor','edit.php?post_type=elementor_library'];
            
            foreach($menus_to_remove as $menu) {
                remove_menu_page($menu);
            }
            
            $elementor_submenus = [
                'elementor', 'elementor-settings', 'elementor-role-manager','elementor-element-manager', 'elementor-tools', 'elementor-system-info', 'go_knowledge_base_site', 'e-form-submissions', 'elementor_custom_fonts','elementor_custom_icons', 'elementor_custom_code', 'elementor-apps','go_elementor_pro'
            ];
            
            foreach($elementor_submenus as $submenu) {
                remove_submenu_page('elementor', $submenu);
            }
            
            $options_submenus = ['options-permalink.php','options-media.php','cache-enabler','eml_settings'];
            foreach ($options_submenus as $optsubmenu) {
                remove_submenu_page('options-general.php', $optsubmenu);
            }
        }
    }
    add_action('admin_menu', 'my_remove_menu_pages', 201);

    // Dodatočná funkcia pre skrytie Blocksy menu s CSS ako záloha
    function hide_blocksy_menu_css() {
        global $current_user;
        wp_get_current_user();

        // Skrytie ďalších položiek pre všetkých okrem používateľa 'artefactum'
        if ($current_user->user_login != 'artefactum') {
            echo '<style>   
                a.page-title-action[href*="post-new.php?post_type=ct_content_block"],#adminmenu a[href*="ct-dashboard-account"], #adminmenu .wp-first-item a[href*="ct-dashboard"], #adminmenu a[href*="site-editor.php"], #adminmenu a[href*="customize.php?return=%2Fwp-admin%2Fthemes.php"], .theme-actions a[href*="wp-admin%2Fthemes.php"], a.hide-if-no-customize, .ab-submenu li a[href*="options-general.php?page=translate-press"], #wp-admin-bar-elementor-maintenance-on, #wp_mail_smtp_reports_widget_lite, #wp-admin-bar-litespeed-bar-manage, #wp-admin-bar-litespeed-bar-setting, #wp-admin-bar-litespeed-bar-imgoptm,#new_admin_email, #new_admin_email + p.description, label[for="new_admin_email"] {
                    display: none !important;
                }
            </style>';
        }
    }
    add_action('admin_head', 'hide_blocksy_menu_css');
    
    // NOVÁ FUNKCIA: Zamedzenie úpravy admin_email pre non-administrátorov (okrem artefactum)
    function restrict_admin_email_update($value, $option) {
        global $current_user;
        wp_get_current_user();

        if ($option === 'admin_email' && $current_user->user_login != 'artefactum' && !current_user_can('administrator')) {
            // Vráti pôvodnú hodnotu, aby sa zmena neuložila
            return get_option('admin_email');
        }
        return $value;
    }
    add_filter('pre_update_option_admin_email', 'restrict_admin_email_update', 10, 2);
    
    // NOVÁ FUNKCIA: Kompatibilita s AIOS
    function custom_aios_compatibility() {
        // Umožníme AIOS upravovať security settings bez konfliktov
        remove_action('admin_init', 'allow_editors_capabilities');
    }
    add_action('plugins_loaded', 'custom_aios_compatibility', 1);
}


/* ==========================
   ZÁKLADNÉ KONŠTANTY
   ========================== */

define('SPA_VERSION', '26.1.0');
define('SPA_PATH', get_stylesheet_directory());
define('SPA_URL', get_stylesheet_directory_uri());
define('SPA_INCLUDES', SPA_PATH . '/includes/');

/* ==========================
   NAČÍTANIE ADMIN ŠTÝLOV
   ========================== */

   add_action('admin_enqueue_scripts', 'spa_load_admin_styles');

   function spa_load_admin_styles($hook) {
       
       // Dashboard widget CSS - len na hlavnej stránke
       if ($hook === 'index.php') {
           
           // Len pre Tréner SPA a vyššie
           if (current_user_can('spa_trainer') || current_user_can('administrator')) {
               wp_enqueue_style(
                   'spa-dashboard',
                   SPA_URL . '/includes/admin_css/spa-dashboard.css',
                   [],
                   SPA_VERSION
               );
           }
       }
       
       // META BOXY CSS - na stránkach editácie CPT
       $allowed_screens = ['post.php', 'post-new.php'];
       
       if (in_array($hook, $allowed_screens)) {
           global $post_type;
           
           // Načítaj CSS len pre SPA post types
           $spa_post_types = ['spa_group', 'spa_place', 'spa_event', 'spa_registration', 'spa_attendance'];
           
           if (in_array($post_type, $spa_post_types)) {
               wp_enqueue_style(
                   'spa-metaboxes',
                   SPA_URL . '/includes/admin_css/spa-dashboard.css',
                   [],
                   SPA_VERSION
               );
           }
       }
   }
/* ==========================
   ARTEFACTUM SUPPORT
   ========================== */

if (defined('ARTEFACTUM_COMMON')) {
    include_once(ARTEFACTUM_COMMON . 'Artefactum-supports.php');
    include_once(ARTEFACTUM_COMMON . 'a-wplogin.php');
}

/**
 * URL systémovej ikony
 */
function spa_icon($name, $class = 'spa-icon') {
    $url = content_url('/uploads/spa-icons/system/' . $name . '.svg');
    return '<img src="' . esc_url($url) . '" class="' . esc_attr($class) . '" alt="">';
}

/*IKONA SVG - napr. echo get_spa_svg_icon(39);*/ 
function get_spa_svg_icon($spasvgsize = 39) {
    $sizesvg = intval($spasvgsize);

    $spa_svg = <<<SVG
<svg class="spa-icon" width="{$sizesvg}" height="{$sizesvg}" viewBox="0 0 {$sizesvg} 100" preserveAspectRatio="xMidYMid meet" aria-hidden="true" style="vertical-align: middle; display: inline-block;">
    <path d="M36.29,0C-3.91,29.7.49,65.3,32.79,69.8-1.91,69-20.51,38.3,36.29,0Z" fill="var(--theme-palette-color-1, #FF1439)"></path>
    <path d="M16.99,60.2c2.5,1.8,5.1,1.8,5.6-.2s-1.1-5.1-3.7-7-5.1-1.8-5.6.2,1.1,5.1,3.7,7Z" fill="var(--theme-palette-color-1, #FF1439)"></path>
    <path d="M16.49,92.4c40.2-29.7,35.8-65.3,3.5-69.8,34.7.8,53.3,31.5-3.5,69.8Z" fill="var(--theme-palette-color-3, #ff1439)"></path>
    <path d="M48.39,30.5c2.6,1.9,5.1,1.8,5.6-.2s-1.1-5.1-3.7-7-5.1-1.8-5.6.2,1.1,5.1,3.7,7Z" fill="var(--theme-palette-color-3, #ff1439)"></path>
</svg>
SVG;

    return $spa_svg;
}

/* ==========================
   GRAVITY FORMS - Remove nag
   ========================== */

add_action('admin_init', function() {
    update_option('rg_gforms_message', '');
    remove_action('after_plugin_row_gravityforms/gravityforms.php', ['GFForms', 'plugin_row']);
});

/* ==========================
   NAČÍTANIE MODULOV
   ========================== */

$spa_modules = [
    'spa-core.php',           // Základné funkcie, role, capabilities
    'spa-helpers.php',        // Pomocné funkcie
    'spa-cpt.php',           // Custom Post Types
    'spa-admin-columns.php',  // Admin columns
    'spa-taxonomies.php',    // Taxonómie    
    'spa-shortcodes.php',    // Frontend shortcodes
    'spa-widgets.php',       // Widgety, bannery
    'spa-calendar.php',      // Kalendár, Obsadenosť hál
    'spa-user-fields.php',   // ✅ Rozšírené polia (rodné číslo, VS, adresa)
    'spa-login.php',         // ✅ Custom login systém (email+heslo / meno+priezvisko+PIN)
    'spa-login-popup.php',   // ✅ login popup
    'spa-registration.php',  // Registračný systém (FÁZA 2)
    'spa-meta-boxes.php',    // Admin meta boxy
    'spa-import.php',        // ✅ NOVÉ: Import nástroj
    'spa-trainer.php',       // ✅ NOVÉ: Správa trénerov
    // 'spa-dashboard.php',     // Dashboardy (FÁZA 2)
    // 'spa-payments.php',      // Platby (FÁZA 2)
    // 'spa-attendance.php',    // Dochádzka (FÁZA 3)
    // 'spa-gamification.php',  // Gamifikácia (FÁZA 3)
    // 'spa-messaging.php',     // Správy (FÁZA 3)
    // 'spa-notifications.php', // Notifikácie (FÁZA 3)
];

foreach ($spa_modules as $module) {
    $file = SPA_INCLUDES . $module;
    if (file_exists($file)) {
        require_once $file;
    }
}

/* ==========================
   DEBUG MODE (vývojové)
   ========================== */

if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('administrator')) {
    
    // Zobraz načítané moduly
    add_action('admin_notices', function() {
        global $spa_modules;
        
        echo '<div class="notice notice-info" style="border-left-color:#f60;"><p><strong>Programové moduly SPA:</strong><span style="color:#f60;"> ';
        echo count($spa_modules) . '</span> načítaných';
        echo '</p></div>';
    });
}

/**
 * SPA Dashboard Widget - Upravený
 */

// Odstráň starý widget (ak existuje)
add_action('wp_dashboard_setup', 'spa_remove_old_dashboard_widget', 0);
function spa_remove_old_dashboard_widget() {
    remove_meta_box('spa_dashboard_widget', 'dashboard', 'normal');
}

// Pridaj nový upravený widget
add_action('wp_dashboard_setup', 'spa_dashboard_widget_enhanced');

function spa_dashboard_widget_enhanced() {
    wp_add_dashboard_widget(
        'spa_dashboard_enhanced',
        get_spa_svg_icon(39).'Samuel Piasecký ACADEMY - Stav systému',
        'spa_dashboard_widget_enhanced_display'
    );
}

function spa_dashboard_widget_enhanced_display() {
    global $wpdb;
    
    // Načítaj počty z DB
    $stats = [
        'programs' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spa_programs WHERE active = 1"),
        'registrations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spa_registrations WHERE status = 'active'"),
        'places' => $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}terms t
            INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'spa_place'
        "),
        'events' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'spa_event' AND post_status = 'publish'"),
        'attendance' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spa_attendance"),
        'payments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spa_payments WHERE status = 'paid'"),
        'training_units' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spa_training_units"),
        'trainers' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spa_trainers WHERE active = 1"),
    ];
    
    ?>
    <div class="spa-dashboard-widget">
        <p><strong>Verzia:</strong> 26.1.0</p>
        <p><strong>Načítané moduly SPA:</strong> 15</p>
        
        <h3 style="margin-top: 20px; margin-bottom: 15px;">🚀  Rýchle linky:</h3>
        
        <div class="spa-stats-grid">
            
            <a href="<?php echo admin_url('edit.php?post_type=spa_group'); ?>" class="spa-stat-link">
                <span class="dashicons dashicons-groups dashicons-universal-access-alt" style="color: var(--theme-palette-color-1);"></span>
                <strong>Programy SPA</strong>
                <span class="spa-count"><?php echo $stats['programs']; ?></span>
            </a>
            
            <a href="<?php echo admin_url('edit.php?post_type=spa_registration'); ?>" class="spa-stat-link">
                <span class="dashicons dashicons-clipboard" style="color: var(--theme-palette-color-1);"></span>
                <strong>Registrácie SPA</strong>
                <span class="spa-count"><?php echo $stats['registrations']; ?></span>
            </a>
            
            <a href="<?php echo admin_url('edit.php?post_type=spa_place'); ?>" class="spa-stat-link">
                <span class="dashicons dashicons-location" style="color: var(--theme-palette-color-1);"></span>
                <strong>Miesta SPA</strong>
                <span class="spa-count"><?php echo $stats['places']; ?></span>
            </a>
            
            <a href="<?php echo admin_url('edit.php?post_type=spa_event'); ?>" class="spa-stat-link">
                <span class="dashicons dashicons-calendar" style="color: var(--theme-palette-color-1);"></span>
                <strong>Udalosti SPA</strong>
                <span class="spa-count"><?php echo $stats['events']; ?></span>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=spa-attendance'); ?>" class="spa-stat-link">
                <span class="dashicons dashicons-yes-alt" style="color: var(--theme-palette-color-1);"></span>
                <strong>Dochádzka</strong>
                <span class="spa-count"><?php echo $stats['attendance']; ?></span>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=spa-payments'); ?>" class="spa-stat-link">
                <span class="dashicons dashicons-money-alt" style="color: var(--theme-palette-color-1);"></span>
                <strong>Prehľad platieb</strong>
                <span class="spa-count"><?php echo $stats['payments']; ?></span>
            </a>
            
        </div>
        
        <hr style="margin: 20px 0;">
        
        <div class="spa-stats-grid">
            <div style="text-align: center;">
                <strong>⌚ Tréningové jednotky</strong><br>
                <span style="font-size: 24px; color: var(--theme-palette-color-3);"><?php echo $stats['training_units']; ?></span>
            </div>
            <div style="text-align: center;">
                <strong>👟 Aktívni tréneri</strong><br>
                <span style="font-size: 24px; color: var(--theme-palette-color-3);"><?php echo $stats['trainers']; ?></span>
            </div>
        </div>
        
        <p style="margin-top: 20px; padding: 10px; background: #f0f6fc; border-left: 4px solid #fc6600;border-radius: 4px;">
            💡 <strong>Potrebuješ pomoc?</strong> → 
            <a href="mailto:support@artefactum.sk">support@artefactum.sk</a>
        </p>
    </div>
    <?php
}
// BLOKOVANIE EMAILOV NA TESTOVACEJ DOMÉNE
add_filter('pre_wp_mail', 'spa_block_test_emails', 10, 2);
function spa_block_test_emails($null, $atts) {
    $current_host = $_SERVER['HTTP_HOST'] ?? '';
    
    if (strpos($current_host, 'spa.artepaint.eu') !== false) {
        error_log('EMAIL BLOCKED on test domain: To=' . ($atts['to'] ?? 'unknown'));
        return true; // Vráti true = email sa neodošle, ale nespôsobí chybu
    }
    
    return $null; // Normálne pokračovanie
}