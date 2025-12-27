<?php
/** spa-meta-boxes.php
 * SPA Meta Boxes - Admin formuláre pre CPT
 * @package Samuel Piasecký ACADEMY
 * @version 3.1.0 - OPRAVA: Pridaný meta box pre programy (spa_group)
 */

if (!defined('ABSPATH')) {
    exit;
}


/* ============================================================
   PRIDANIE VŠETKÝCH META BOXOV
   ============================================================ */
add_action('add_meta_boxes', 'spa_add_all_meta_boxes');
function spa_add_all_meta_boxes() {
    
    // PROGRAMY (spa_group)
    add_meta_box('spa_group_details', '🤸 Detaily programu', 'spa_group_meta_box', 'spa_group', 'normal', 'high');
    add_meta_box('spa_group_schedule', '📅 Rozvrh programu', 'spa_group_schedule_meta_box', 'spa_group', 'normal', 'high');
    add_meta_box('spa_group_pricing', '💳 Cenník programu', 'spa_group_pricing_meta_box', 'spa_group', 'normal', 'high');
    
    // REGISTRÁCIE
    
    add_meta_box('spa_registration_details', '📋 Detaily registrácie', 'spa_registration_details_callback', 'spa_registration', 'normal', 'high');
    
    // MIESTA (spa_place)
    add_meta_box('spa_place_details', '📍 Detaily miesta', 'spa_place_meta_box', 'spa_place', 'normal', 'high');
    add_meta_box('spa_place_schedule', '📅 Rozvrh miesta', 'spa_place_schedule_meta_box', 'spa_place', 'normal', 'default');
    
    // UDALOSTI (spa_event)
    add_meta_box('spa_event_details', '📅 Detaily udalosti', 'spa_event_meta_box', 'spa_event', 'normal', 'high');
    
    // DOCHÁDZKA (spa_attendance)
    add_meta_box('spa_attendance_details', '✅ Záznam dochádzky', 'spa_attendance_meta_box', 'spa_attendance', 'normal', 'high');

    // MIESTA - ROZVRH
    add_meta_box(
        'spa_place_schedule',
        '📅 Rozvrh miesta',
        'spa_place_schedule_callback',
        'spa_place',
        'normal',
        'high'
    );    
}



/* ==========================
   REGISTRACIA - META BOX CALLBACK
   Pridaj túto funkciu za spa_add_all_meta_boxes()
   ========================== */

   function spa_registration_details_callback($post) {
        wp_nonce_field('spa_save_registration', 'spa_registration_nonce');
        
        // Načítaj CSS
        wp_enqueue_style('spa-admin-metaboxes', get_stylesheet_directory_uri() . '/assets/css/admin-metaboxes.css', [], '1.0.0');
        
        // Získaj meta údaje z AKTUÁLNEJ štruktúry
        $client_id = get_post_meta($post->ID, 'client_user_id', true);
        $parent_id = get_post_meta($post->ID, 'parent_user_id', true);
        $program_id = get_post_meta($post->ID, 'program_id', true);
        $status = get_post_meta($post->ID, 'status', true);
        
        // Získaj user data
        $client = $client_id ? get_userdata($client_id) : null;
        $parent = $parent_id ? get_userdata($parent_id) : null;
        $program = $program_id ? get_post($program_id) : null;
        
        // VS a PIN sú v user_meta
        $vs = $client_id ? get_user_meta($client_id, 'variabilny_symbol', true) : '';
        $pin = $client_id ? get_user_meta($client_id, 'spa_pin_plain', true) : '';
        $birthdate = $client_id ? get_user_meta($client_id, 'birthdate', true) : '';
        $rodne_cislo = $client_id ? get_user_meta($client_id, 'rodne_cislo', true) : '';
        $phone = $parent_id ? get_user_meta($parent_id, 'phone', true) : '';
        
        // Zoznam programov
        $all_programs = get_posts([
            'post_type' => 'spa_group',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        // Miesto z programu
        $place_str = '';
        if ($program_id) {
            $places = get_the_terms($program_id, 'spa_place');
            if ($places && !is_wp_error($places)) {
                $place_names = wp_list_pluck($places, 'name');
                $place_str = implode(', ', $place_names);
            }
        }
        
        // Statusy
        $statuses = [
            'pending' => '⏳ Čaká na schválenie',
            'approved' => '✅ Schválené',
            'active' => '🟢 Aktívny',
            'cancelled' => '❌ Zrušené',
            'completed' => '✔ Zaregistrované'
        ];
        
        $client_name = '';
        if ($client) {
            $client_name = trim($client->first_name . ' ' . $client->last_name);
            if (empty($client_name)) $client_name = $client->display_name;
        }
        
        $parent_name = '';
        if ($parent) {
            $parent_name = trim($parent->first_name . ' ' . $parent->last_name);
            if (empty($parent_name)) $parent_name = $parent->display_name;
        }
        
        ?>
        <table class="spa-meta-box-table">
            
            <!-- DIEŤA / KLIENT -->
            <tr>
                <th>👶 Dieťa/Klient</th>
                <td>
                    <?php if ($client): ?>
                        <strong><?php echo esc_html($client_name); ?></strong>
                        <?php if ($birthdate): ?>
                            <br><small>Dátum narodenia: <strong><?php echo esc_html(date('d.m.Y', strtotime($birthdate))); ?></strong></small>
                        <?php endif; ?>
                        <?php if ($rodne_cislo): ?>
                            <br><small>Rodné číslo: <strong><?php echo esc_html($rodne_cislo); ?></strong></small>
                        <?php endif; ?>
                        <br><a href="<?php echo get_edit_user_link($client_id); ?>" target="_blank" class="button button-small spa-btn-edit">Upraviť profil →</a>
                    <?php else: ?>
                        <span class="spa-no-data">Nie je priradený</span>
                    <?php endif; ?>
                </td>
            </tr>
            
            <!-- VS A PIN -->
            <tr>
                <th>🔢 VS / 🔐 PIN</th>
                <td>
                    <strong class="spa-credential">VS: <?php echo $vs ?: '—'; ?></strong>
                    <span class="spa-separator">|</span>
                    <strong class="spa-credential">PIN: <?php echo $pin ?: '—'; ?></strong>
                </td>
            </tr>
            
            <!-- RODIČ -->
            <tr>
                <th>👨‍👩‍👧 Rodič</th>
                <td>
                    <?php if ($parent): ?>
                        <strong><?php echo esc_html($parent_name); ?></strong><br>
                        <small>E-mail: <a href="mailto:<?php echo esc_html($parent->user_email); ?>"><?php echo esc_html($parent->user_email); ?></a></small>
                        <?php if ($phone): ?>
                            <br><small>Telefón: <a href="tel:<?php echo esc_html($phone); ?>"><?php echo esc_html($phone); ?></a></small>
                        <?php endif; ?>
                        <br><a href="<?php echo get_edit_user_link($parent_id); ?>" target="_blank" class="button button-small spa-btn-edit">Upraviť profil →</a>
                    <?php else: ?>
                        <span class="spa-no-data">Nie je priradený</span>
                    <?php endif; ?>
                </td>
            </tr>
            
            <!-- PROGRAM (readonly) -->
            <tr>
                <th>🏋️ Aktuálny program</th>
                <td>
                    <strong><?php echo $program ? esc_html($program->post_title) : '—'; ?></strong>
                    <?php if ($place_str): ?>
                        <br><small>📍 <?php echo esc_html($place_str); ?></small>
                    <?php endif; ?>
                </td>
            </tr>
            
        </table>
        
        <!-- EDITOVATEĽNÉ POLIA -->
        <div class="spa-edit-box">
            <h4>⚙️ Úprava registrácie - tréningového programu</h4>
            
            <p>
                <label><strong>Program:</strong></label><br>
                <select name="program_id" id="program_id" class="widefat spa-select-program">
                    <option value="">-- Vyberte program --</option>
                    <?php foreach ($all_programs as $prog): ?>
                        <option value="<?php echo $prog->ID; ?>" <?php selected($program_id, $prog->ID); ?>>
                            <?php echo esc_html($prog->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <label><strong>Status:</strong></label><br>
                <select name="status" id="status" class="widefat spa-select-status">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
        </div>
        
        <input type="hidden" name="client_user_id" value="<?php echo esc_attr($client_id); ?>">
        <input type="hidden" name="parent_user_id" value="<?php echo esc_attr($parent_id); ?>">
        <?php
    }

    /* ==========================
    REGISTRACIA - SAVE META
    ========================== */

    add_action('save_post_spa_registration', 'spa_save_registration_meta', 10, 2);
function spa_save_registration_meta($post_id, $post) {
    
    // Verifikácia nonce
    if (!isset($_POST['spa_registration_nonce']) || !wp_verify_nonce($_POST['spa_registration_nonce'], 'spa_save_registration')) {
        return;
    }
    
    // Autosave check
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Oprávnenia
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    $title_changed = false;
    
    // Ulož program_id
    if (isset($_POST['program_id'])) {
        $new_program_id = intval($_POST['program_id']);
        $old_program_id = get_post_meta($post_id, 'program_id', true);
        
        if ($new_program_id != $old_program_id) {
            update_post_meta($post_id, 'program_id', $new_program_id);
            $title_changed = true;
        }
    }
    
    // Ulož status
    if (isset($_POST['status'])) {
        update_post_meta($post_id, 'status', sanitize_text_field($_POST['status']));
    }
    
    // Ak sa zmenil program, aktualizuj post_title (LEN MENO)
    if ($title_changed) {
        $client_id = get_post_meta($post_id, 'client_user_id', true);
        
        $client = get_userdata($client_id);
        
        if ($client) {
            $client_name = trim($client->first_name . ' ' . $client->last_name);
            if (empty($client_name)) $client_name = $client->display_name;
            
            // ✅ OPRAVENÉ: post_title = LEN meno dieťaťa/klienta
            $new_title = $client_name;
            
            // Odpoj hook aby sa predišlo nekonečnej slučke
            remove_action('save_post_spa_registration', 'spa_save_registration_meta', 10);
            
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $new_title
            ]);
            
            // Znovu pripoj hook
            add_action('save_post_spa_registration', 'spa_save_registration_meta', 10, 2);
        }
    }
}

/* ============================================================
   META BOX: DETAILY PROGRAMU (spa_group) - NOVÝ
   ============================================================ */
   function spa_group_meta_box($post) {
    wp_nonce_field('spa_save_group_details', 'spa_group_nonce');
    
    $place_id = get_post_meta($post->ID, 'spa_place_id', true);
    $trainers = get_post_meta($post->ID, 'spa_trainers', true);
    $trainers = is_array($trainers) ? $trainers : (empty($trainers) ? [] : [$trainers]);
    $capacity = get_post_meta($post->ID, 'spa_capacity', true);
    $registration_type = get_post_meta($post->ID, 'spa_registration_type', true);
    $age_from = get_post_meta($post->ID, 'spa_age_from', true);
    $age_to = get_post_meta($post->ID, 'spa_age_to', true);
    $level = get_post_meta($post->ID, 'spa_level', true);
    $icon = get_post_meta($post->ID, 'spa_icon', true);
    $icon_primary = get_post_meta($post->ID, 'spa_icon_primary_color', true);
    $icon_secondary = get_post_meta($post->ID, 'spa_icon_secondary_color', true);
    
    // SPA Color Palette
    $spa_colors = array(
        '#005de8' => 'Olympic blue',
        '#00C853' => 'Olympic green',
        '#FF1439' => 'Olympic red',
        '#FFB81C' => 'Olympic yellow',
        '#000000' => 'Olympic black',
        '#f2f5f7' => 'Light grey',
        '#FAFBFC' => 'Smart grey',
        '#ffffff' => 'White',
        '#A7E9E9' => 'Tyrkys',
        '#E3F2FD' => 'Pastel blue',
        '#87C9FF' => 'Light blue',
        '#FF9AA2' => 'Pinky',
        '#a855f7' => 'Lila'
    );
    
    // Načítaj dostupné SVG ikony
    $svg_files = [];
    $icons_dir = WP_CONTENT_DIR . '/uploads/spa-icons/';
    if (is_dir($icons_dir)) {
        $files = scandir($icons_dir);
        $svg_files = array_filter($files, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'svg';
        });
        sort($svg_files);
    }
    
    // Získaj všetky miesta
    $places = get_posts([
        'post_type' => 'spa_place',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Získaj všetkých trénerov
    $all_trainers = get_users(['role' => 'spa_trainer', 'orderby' => 'display_name']);
    
    ?>
    
    <div class="spa-section">
        <h4>💥 Vyberte ikonu programu</h4>
        <div class="spa-meta-row">
            <div class="spa-field" style="display: flex; align-items: center; gap: 15px;">
                <?php if (empty($svg_files)) : ?>
                    <p style="color: #d63638; margin: 0;">Žiadne ikony v /uploads/spa-icons/</p>
                    <input type="hidden" name="spa_icon" value="">
                <?php else : ?>
                    <select name="spa_icon" id="spa_icon_select" style="width: 250px;">
                        <option value="">-- Bez ikony --</option>
                        <?php foreach ($svg_files as $file) : 
                            $name = pathinfo($file, PATHINFO_FILENAME);
                        ?>
                            <option value="<?php echo esc_attr($file); ?>" <?php selected($icon, $file); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="spa-icon-preview" id="spa_icon_preview">
                        <?php if ($icon && file_exists($icons_dir . $icon)) : ?>
                            <?php echo file_get_contents($icons_dir . $icon); ?>
                        <?php else : ?>
                            <span style="color:#999;">--</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- FAREBNÁ PALETA -->                        
                    <?php if (!empty($svg_files)) : ?>
                    <div class="spa-color-section">
                        <div class="spa-color-group">
                            <label>🎨 Primárna farba</label>
                            <div class="spa-color-palette">
                                <?php foreach ($spa_colors as $hex => $name): ?>
                                    <div class="spa-color-option <?php echo ($icon_primary === $hex) ? 'selected' : ''; ?>" 
                                        style="background-color: <?php echo esc_attr($hex); ?>;"
                                        data-color="<?php echo esc_attr($hex); ?>"
                                        title="<?php echo esc_attr($name); ?>">
                                        <input type="radio" 
                                            name="spa_icon_primary_color" 
                                            value="<?php echo esc_attr($hex); ?>" 
                                            <?php checked($icon_primary, $hex); ?>>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="spa-color-group">
                            <label>🎨 Sekundárna farba</label>
                            <div class="spa-color-palette">
                                <?php foreach ($spa_colors as $hex => $name): ?>
                                    <div class="spa-color-option <?php echo ($icon_secondary === $hex) ? 'selected' : ''; ?>" 
                                        style="background-color: <?php echo esc_attr($hex); ?>;"
                                        data-color="<?php echo esc_attr($hex); ?>"
                                        title="<?php echo esc_attr($name); ?>">
                                        <input type="radio" 
                                            name="spa_icon_secondary_color" 
                                            value="<?php echo esc_attr($hex); ?>" 
                                            <?php checked($icon_secondary, $hex); ?>>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="spa-section">
        <h4>🤸 Základné informácie</h4>
        
        <div class="spa-meta-row">
            <label for="spa_place_id">Adresa miesta:</label>
            <div class="spa-field">
                <select name="spa_place_id" id="spa_place_id" required>
                    <option value="">-- Vyberte miesto --</option>
                    <?php foreach ($places as $place) : ?>
                        <option value="<?php echo $place->ID; ?>" <?php selected($place_id, $place->ID); ?>>
                            <?php echo esc_html($place->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="spa-help">Tréningy sa budú konať na tomto mieste</p>
            </div>  
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_capacity">Kapacita skupiny:</label>
            <div class="spa-field">
                <input type="number" name="spa_capacity" id="spa_capacity" value="<?php echo esc_attr($capacity); ?>" min="1" max="100" style="max-width: 100px;">
                <p class="spa-help">Maximálny počet detí v jednej skupine</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_registration_type">Typ registrácie:</label>
            <div class="spa-field">
                <select name="spa_registration_type" id="spa_registration_type">
                    <option value="new" <?php selected($registration_type, 'new'); ?>>Nová registrácia</option>
                    <option value="existing" <?php selected($registration_type, 'existing'); ?>>Len pre už prihlásených</option>
                    <option value="both" <?php selected($registration_type, 'both'); ?>>Oboje</option>
                </select>
                <p class="spa-help">Kto sa môže registrovať do tohto programu</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label>Vekové rozpätie (rokov):</label>
            <div class="spa-field" style="display: flex; gap: 10px; align-items: center;">
                <div style="flex: 1;">
                    <label style="width: auto; font-weight: 600;">OD:</label>
                    <input type="number" name="spa_age_from" value="<?php echo esc_attr($age_from); ?>" step="0.1" min="0" max="100" placeholder="napr. 3 alebo 3.5" style="max-width: 120px;">
                </div>
                <div style="flex: 1;">
                    <label style="width: auto; font-weight: 600;">DO:</label>
                    <input type="number" name="spa_age_to" value="<?php echo esc_attr($age_to); ?>" step="0.1" min="0" max="100" placeholder="napr. 7 alebo 7.5" style="max-width: 120px;">
                </div>
            </div>
            <p class="spa-help">Odporúčaný vek účastníkov (napr. 5-7 rokov). Lze zadat aj s desatinou (5,5 alebo 5.5)</p>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_level">Úroveň:</label>
            <div class="spa-field">
                <select name="spa_level" id="spa_level">
                    <option value="">-- Vyberte úroveň --</option>
                    <option value="beginner" <?php selected($level, 'beginner'); ?>>🟢 Začiatočník</option>
                    <option value="intermediate" <?php selected($level, 'intermediate'); ?>>🟡 Mierne pokročilý</option>
                    <option value="advanced" <?php selected($level, 'advanced'); ?>>🟠 Pokročilý</option>
                    <option value="professional" <?php selected($level, 'professional'); ?>>🔴 Profesionál</option>
                </select>
                <p class="spa-help">Úroveň obtiažnosti/skúsenosti</p>
            </div>
        </div>
    </div>
    
    <div class="spa-section">
        <h4>👟 Tréneri</h4>
        <div class="spa-trainers-list">
            <?php foreach ($all_trainers as $trainer) : ?>
                <div class="spa-trainer-item">
                    <label>
                        <input type="checkbox" name="spa_trainers[]" value="<?php echo $trainer->ID; ?>" 
                            <?php echo in_array($trainer->ID, $trainers) ? 'checked' : ''; ?>>
                        <?php echo esc_html($trainer->display_name); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="spa-help" style="margin-top: 10px;">Vyberte trénerov, ktorí vedú tento program</p>
    </div>   
    
    
    <script>
        (function() {
            var select = document.getElementById('spa_icon_select');
            var preview = document.getElementById('spa_icon_preview');
            
            if (!select || !preview) return;
            
            select.addEventListener('change', function() {
                if (!this.value) {
                    preview.innerHTML = '<span style="color:#999; font-size:12px;">--</span>';
                    return;
                }
                
                var iconFile = this.value;
                var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=spa_load_icon&icon=' + encodeURIComponent(iconFile)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.svg) {
                        preview.innerHTML = data.svg;
                    } else {
                        preview.innerHTML = '<span style="color:#d63638; font-size:12px;">Chyba</span>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    preview.innerHTML = '<span style="color:#d63638; font-size:12px;">Chyba</span>';
                });
            });
            
            // COLOR PICKER
            var colorOptions = document.querySelectorAll('.spa-color-option');
            colorOptions.forEach(function(option) {
                option.addEventListener('click', function() {
                    var siblings = this.parentElement.querySelectorAll('.spa-color-option');
                    siblings.forEach(function(sib) { sib.classList.remove('selected'); });
                    this.classList.add('selected');
                    var radio = this.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                });
            });
        })();
    </script> 
    <?php
}

/* ============================================================
   META BOX: ROZVRH PROGRAMU (spa_group) - NOVÝ
   Dynamické pridávanie viacerých termínov
   ============================================================ */
function spa_group_schedule_meta_box($post) {
    wp_nonce_field('spa_save_group_schedule', 'spa_group_schedule_nonce');
    
    $schedule_json = get_post_meta($post->ID, 'spa_schedule', true);
    $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
    
    $days = [
        'monday' => 'Pondelok',
        'tuesday' => 'Utorok',
        'wednesday' => 'Streda',
        'thursday' => 'Štvrtok',
        'friday' => 'Piatok',
        'saturday' => 'Sobota',
        'sunday' => 'Nedeľa'
    ];
    
    ?>
    <style>
    .spa-schedule-box { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; }
    .spa-schedule-item { background: #fff; padding: 15px; border: 1px solid #ddd; margin-bottom: 15px; border-radius: 4px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
    .spa-schedule-item .day-select { min-width: 120px; }
    .spa-schedule-item .time-input { width: 80px; }
    .spa-schedule-item .remove-btn { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
    .spa-schedule-item .remove-btn:hover { background: #c82333; }
    .spa-add-btn { background: #0066FF; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; margin-top: 10px; }
    .spa-add-btn:hover { background: #0052cc; }
    .spa-help { color: #666; font-size: 12px; margin-top: 10px; }
    </style>
    
    <!-- ČASOVÝ ROZSAH PROGRAMU -->
    <div style="background: #f0f6fc; padding: 20px; border: 1px solid #0969da; border-radius: 4px; margin-bottom: 20px;">
        <h4 style="margin: 0 0 15px 0; color: #0969da;">🕘 Časový rozsah programu</h4>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">📅 Dátum začiatku:</label>
                <?php 
                $start_date = get_post_meta($post->ID, 'spa_program_start_date', true);
                $start_date_display = $start_date ? date('d.m.Y', strtotime($start_date)) : '';
                ?>
                <input 
                    type="date" 
                    name="spa_program_start_date" 
                    value="<?php echo esc_attr($start_date); ?>"
                    style="width: 100%;"
                >
                <?php if ($start_date_display): ?>
                    <p class="spa-help" style="margin: 5px 0 0 0; font-size: 11px; color: #666;">Zobrazí sa ako: <?php echo esc_html($start_date_display); ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">📅 Dátum ukončenia:</label>
                <?php 
                $end_date = get_post_meta($post->ID, 'spa_program_end_date', true);
                $end_date_display = $end_date ? date('d.m.Y', strtotime($end_date)) : '';
                ?>
                <input 
                    type="date" 
                    name="spa_program_end_date" 
                    value="<?php echo esc_attr($end_date); ?>"
                    style="width: 100%;"
                >
                <?php if ($end_date_display): ?>
                    <p class="spa-help" style="margin: 5px 0 0 0; font-size: 11px; color: #666;">Zobrazí sa ako: <?php echo esc_html($end_date_display); ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">📆 Kalendárne týždne:</label>
                <?php $weeks = get_post_meta($post->ID, 'spa_program_calendar_weeks', true); ?>
                <input 
                    type="text" 
                    name="spa_program_calendar_weeks" 
                    value="<?php echo esc_attr($weeks); ?>" 
                    placeholder="napr. 31,32,33"
                    style="width: 100%;"
                >
                <p class="spa-help" style="margin: 5px 0 0 0; font-size: 11px; color: #666;">Pre tábory. Oddeľuj čiarkou.</p>
            </div>
        </div>
        
        <p style="margin: 15px 0 0 0; padding: 10px; background: #fff; border-left: 3px solid #0969da; font-size: 12px;">
            ℹ️ <strong>Poznámka:</strong> Časový rozsah určuje, v akom období je program aktívny.<ul>
                <li>Ak vyplníš dátum začiatku a ukončenia, program platí len v tomto období.</li>
                <li>Ak zadáš kalendárne týždne, program platí len v uvedených týždňoch (napr. tábory).</li>
                <li>Ak polia <strong>nevyplníš</strong>, program sa považuje za <strong>celoročný</strong>.</li>
            </ul>
        </p>
    </div>
    <!-- DNI TÝŽDŇA PROGRAMU -->
    <div class="spa-schedule-box">
        <h4>📅 Tréningové dni týždňa a časy</h4>
        <p style="color: #666; margin-bottom: 15px;">Pridajte všetky dni a časy, kedy sa tento program koná.</p>
        
        <div id="spa-schedule-container">
            <?php if (!empty($schedule)) : ?>
                <?php foreach ($schedule as $index => $item) : ?>
                    <div class="spa-schedule-item">
                        <select name="spa_schedule[<?php echo $index; ?>][day]" class="day-select">
                            <option value="">-- Vyber deň --</option>
                            <?php foreach ($days as $key => $label) : ?>
                                <option value="<?php echo $key; ?>" <?php selected($item['day'] ?? '', $key); ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <span>od</span>
                        <input type="time" name="spa_schedule[<?php echo $index; ?>][from]" value="<?php echo esc_attr($item['from'] ?? ''); ?>" class="time-input">
                        
                        <span>do</span>
                        <input type="time" name="spa_schedule[<?php echo $index; ?>][to]" value="<?php echo esc_attr($item['to'] ?? ''); ?>" class="time-input">
                        
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove();">Odstrániť</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" class="spa-add-btn" onclick="spa_add_schedule_row();">+ Pridať ďalší termín</button>
        
        <p class="spa-help">Príklad: Utorok 10:00-11:00, Štvrtok 10:00-11:00 = 2x týždenne tréningy</p>
    </div>
    
    <script>
    var scheduleIndex = <?php echo !empty($schedule) ? max(array_keys($schedule)) + 1 : 0; ?>;
    
    function spa_add_schedule_row() {
        var days = <?php echo json_encode($days); ?>;
        var html = '<div class="spa-schedule-item">' +
            '<select name="spa_schedule[' + scheduleIndex + '][day]" class="day-select">' +
            '<option value="">-- Vyber deň --</option>';
        
        for (var key in days) {
            html += '<option value="' + key + '">' + days[key] + '</option>';
        }
        
        html += '</select>' +
            '<span>od</span>' +
            '<input type="time" name="spa_schedule[' + scheduleIndex + '][from]" class="time-input">' +
            '<span>do</span>' +
            '<input type="time" name="spa_schedule[' + scheduleIndex + '][to]" class="time-input">' +
            '<button type="button" class="remove-btn" onclick="this.parentElement.remove();">Odstrániť</button>' +
            '</div>';
        
        document.getElementById('spa-schedule-container').insertAdjacentHTML('beforeend', html);
        scheduleIndex++;
    }
    </script>
    <?php
}

/* ============================================================
   META BOX: CENNÍK PROGRAMU (spa_group)
   ============================================================ */
   function spa_group_pricing_meta_box($post) {
    wp_nonce_field('spa_save_group_pricing', 'spa_group_pricing_nonce');
    
    $price_1x = get_post_meta($post->ID, 'spa_price_1x_weekly', true);
    $price_2x = get_post_meta($post->ID, 'spa_price_2x_weekly', true);
    $price_monthly = get_post_meta($post->ID, 'spa_price_monthly', true);
    $price_semester = get_post_meta($post->ID, 'spa_price_semester', true);
    $external_surcharge = get_post_meta($post->ID, 'spa_external_surcharge', true);
    ?>
    
    <div class="spa-pricing-grid">
        <div class="spa-price-box">
            <h5>💳 Cena za 1x týždenne</h5>
            <input type="number" name="spa_price_1x_weekly" value="<?php echo esc_attr($price_1x); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Mesačná cena pri jednom tréningu týždenne</p>
        </div>
        
        <div class="spa-price-box">
            <h5>💳 Cena za 2x týždenne</h5>
            <input type="number" name="spa_price_2x_weekly" value="<?php echo esc_attr($price_2x); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Mesačná cena pri dvoch tréningoch týždenne (zvýhodnená)</p>
        </div>
        
        <div class="spa-price-box">
            <h5>📅 Cena mesačne (paušál)</h5>
            <input type="number" name="spa_price_monthly" value="<?php echo esc_attr($price_monthly); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Voliteľné - fixná mesačná cena</p>
        </div>
        
        <div class="spa-price-box">
            <h5>🎓 Cena za semester</h5>
            <input type="number" name="spa_price_semester" value="<?php echo esc_attr($price_semester); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Voliteľné - cena za celý školský polrok</p>
        </div>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
        <h5 style="margin: 0 0 10px 0;">🏫 Príplatok za externé priestory</h5>
        <input type="number" name="spa_external_surcharge" value="<?php echo esc_attr($external_surcharge); ?>" step="0.01" min="0" style="width: 80px;">
        <span class="currency">€</span>
        <p class="spa-help" style="margin-top: 5px;">Príplatok k cene ak sa tréning koná v externých priestoroch (prenájom)</p>
    </div>
    <?php
}


   /* ============================================================
   META BOX: UDALOSŤ (spa_event)
   Samostatná doménová entita s voliteľnými väzbami
   ============================================================ */

function spa_event_meta_box($post) {
    wp_nonce_field('spa_save_event', 'spa_event_nonce');
    
    // Načítaj existujúce dáta
    $type = get_post_meta($post->ID, 'spa_event_type', true);
    $date_from = get_post_meta($post->ID, 'spa_event_date_from', true);
    $date_to = get_post_meta($post->ID, 'spa_event_date_to', true);
    $time_from = get_post_meta($post->ID, 'spa_event_time_from', true);
    $time_to = get_post_meta($post->ID, 'spa_event_time_to', true);
    $all_day = get_post_meta($post->ID, 'spa_event_all_day', true);
    $recurring = get_post_meta($post->ID, 'spa_event_recurring', true);
    
    $program_ids = get_post_meta($post->ID, 'spa_event_program_ids', true);
    $place_ids = get_post_meta($post->ID, 'spa_event_place_ids', true);
    
    // Ensure arrays
    $program_ids = is_array($program_ids) ? $program_ids : [];
    $place_ids = is_array($place_ids) ? $place_ids : [];
    
    // Získaj dostupné programy a miesta
    $programs = get_posts([
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish'
    ]);
    
    $places = get_posts([
        'post_type' => 'spa_place',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish'
    ]);
    
    ?>
    
    <!-- EXPLAINER BOX -->
    <div style="background: #e7f3ff; border-left: 4px solid #0969da; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <h4 style="margin: 0 0 10px 0; color: #0969da;">ℹ️ Čo je SPA Udalosť?</h4>
        <p style="margin: 0; font-size: 13px; line-height: 1.6;">
            <strong>SPA Udalosť</strong> reprezentuje <strong>výnimku</strong> alebo <strong>špeciálnu situáciu</strong> v rozvrhu.<br>
            Môže ovplyvniť:<br>
            • konkrétne programy<br>
            • konkrétne miesta<br>
            • alebo celý systém (globálna udalosť)<br>            
            <strong>Väzby sú VOLITEĽNÉ</strong> – ak nevyberiete ani program, ani miesto, udalosť sa považuje za <strong>globálnu</strong>.
        </p>
    </div>
    
    <!-- TYP UDALOSTI -->
    <div class="spa-section">
        <h4>🎯 Typ udalosti</h4>
        
        <div class="spa-meta-row">
            <label for="spa_event_type">Typ udalosti: <span style="color:#d63638;">*</span></label>
            <div class="spa-field">
                <select name="spa_event_type" id="spa_event_type" required>
                    <option value="">-- Vyberte typ --</option>
                    <option value="holiday" <?php selected($type, 'holiday'); ?>>🎄 Sviatky / Prázdniny (zrušenie tréningov)</option>
                    <option value="closure" <?php selected($type, 'closure'); ?>>🚫 Zatvorené (miesto nedostupné)</option>
                    <option value="camp" <?php selected($type, 'camp'); ?>>⛺ Tábor (špeciálny program)</option>
                    <option value="event" <?php selected($type, 'event'); ?>>🎉 Špeciálna udalosť (informačná)</option>
                    <option value="schedule_change" <?php selected($type, 'schedule_change'); ?>>📅 Zmena rozvrhu</option>
                </select>
                <p class="spa-help">
                    <strong>Sviatky/Prázdniny:</strong> zruší tréningy v danom období<br>
                    <strong>Zatvorené:</strong> miesto je nedostupné<br>
                    <strong>Tábor:</strong> špeciálny program mimo bežného rozvrhu<br>
                    <strong>Špeciálna udalosť:</strong> len informačná, neovplyvňuje rozvrh<br>
                    <strong>Zmena rozvrhu:</strong> dočasná zmena času/miesta
                </p>
            </div>
        </div>
    </div>
    
    <!-- DÁTUM A ČAS -->
    <div class="spa-section">
        <h4>📆 Dátum a čas</h4>
        
        <div class="spa-meta-row">
            <label for="spa_event_date_from">Dátum od: <span style="color:#d63638;">*</span></label>
            <div class="spa-field">
                <input type="date" name="spa_event_date_from" id="spa_event_date_from" 
                    value="<?php echo esc_attr($date_from); ?>" required style="width: 200px;">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_event_date_to">Dátum do:</label>
            <div class="spa-field">
                <input type="date" name="spa_event_date_to" id="spa_event_date_to" 
                    value="<?php echo esc_attr($date_to); ?>" style="width: 200px;">
                <p class="spa-help">Voliteľné - pre viacdenné udalosti. Ak nevyplníte, udalosť trvá jeden deň.</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label>
                <input type="checkbox" name="spa_event_all_day" value="1" <?php checked($all_day, 1); ?> id="spa_event_all_day">
                Celý deň
            </label>
        </div>
        
        <div class="spa-meta-row" id="spa-time-fields" style="<?php echo $all_day ? 'display:none;' : ''; ?>">
            <label>Čas:</label>
            <div class="spa-field" style="display: flex; gap: 10px; align-items: center;">
                <input type="time" name="spa_event_time_from" value="<?php echo esc_attr($time_from); ?>" 
                    style="width: 120px;">
                <span>do</span>
                <input type="time" name="spa_event_time_to" value="<?php echo esc_attr($time_to); ?>" 
                    style="width: 120px;">
                <p class="spa-help" style="margin: 0 0 0 10px;">Voliteľné - len ak udalosť trvá konkrétny čas</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_event_recurring">Opakovanie:</label>
            <div class="spa-field">
                <select name="spa_event_recurring" id="spa_event_recurring">
                    <option value="once" <?php selected($recurring, 'once'); ?>>Jednorazovo</option>
                    <option value="weekly" <?php selected($recurring, 'weekly'); ?>>Každý týždeň</option>
                    <option value="yearly" <?php selected($recurring, 'yearly'); ?>>Každý rok (napr. vianočné sviatky)</option>
                </select>
                <p class="spa-help">Použite pre pravidelné udalosti (napr. letné prázdniny každý rok).</p>
            </div>
        </div>
    </div>
    
    <!-- VÄZBY (VOLITEĽNÉ) -->
    <div class="spa-section" style="background: #fff8e5; border: 1px solid #ffc107;">
        <h4>🔗 Väzby udalosti (VOLITEĽNÉ)</h4>
        
        <p style="background: #fff; padding: 12px; border-left: 3px solid #ffc107; margin-bottom: 15px; font-size: 13px;">
            <strong>⚠️ Ako fungujú väzby udalosti:</strong><br>
            • Ak NEVYBERIETE ani program ani miesto → udalosť je <strong>GLOBÁLNA</strong> (ovplyvní celý systém)<br>
            • Ak vyberiete LEN miesto → ovplyvní <strong>všetky programy</strong> na tomto mieste<br>
            • Ak vyberiete LEN program → ovplyvní tento program <strong>na všetkých miestach</strong><br>
            • Ak vyberiete oboje → ovplyvní <strong>konkrétnu kombináciu</strong> program + miesto
        </p>
        
        <!-- PROGRAMY -->
        <div class="spa-meta-row">
            <label>🤸 Ovplyvnené programy:</label>
            <div class="spa-field">
                <?php if (empty($programs)): ?>
                    <p style="color: #666;">Zatiaľ nemáte vytvorené žiadne programy.</p>
                <?php else: ?>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; border-radius: 4px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                            <input type="checkbox" id="spa_select_all_programs" style="margin-right: 5px;">
                            Vybrať všetky programy
                        </label>
                        <hr style="margin: 10px 0; border: none; border-top: 1px solid #ddd;">
                        <?php foreach ($programs as $program): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="spa_event_program_ids[]" value="<?php echo $program->ID; ?>" 
                                    class="spa-program-checkbox"
                                    <?php echo in_array($program->ID, $program_ids) ? 'checked' : ''; ?>>
                                <?php echo esc_html($program->post_title); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="spa-help" style="margin-top: 8px;">Nechajte prázdne pre globálnu udalosť</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- MIESTA -->
        <div class="spa-meta-row" style="margin-top: 20px;">
            <label>📍 Ovplyvnené miesta:</label>
            <div class="spa-field">
                <?php if (empty($places)): ?>
                    <p style="color: #666;">Zatiaľ nemáte vytvorené žiadne miesta.</p>
                <?php else: ?>
                    <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; border-radius: 4px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                            <input type="checkbox" id="spa_select_all_places" style="margin-right: 5px;">
                            Vybrať všetky miesta
                        </label>
                        <hr style="margin: 10px 0; border: none; border-top: 1px solid #ddd;">
                        <?php foreach ($places as $place): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="spa_event_place_ids[]" value="<?php echo $place->ID; ?>" 
                                    class="spa-place-checkbox"
                                    <?php echo in_array($place->ID, $place_ids) ? 'checked' : ''; ?>>
                                <?php echo esc_html($place->post_title); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="spa-help" style="margin-top: 8px;">Nechajte prázdne pre globálnu udalosť</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- JAVASCRIPT -->
    <script>
    (function() {
        // Toggle time fields
        var allDayCheckbox = document.getElementById('spa_event_all_day');
        var timeFields = document.getElementById('spa-time-fields');
        if (allDayCheckbox && timeFields) {
            allDayCheckbox.addEventListener('change', function() {
                timeFields.style.display = this.checked ? 'none' : 'block';
            });
        }
        
        // Select all programs
        var selectAllPrograms = document.getElementById('spa_select_all_programs');
        if (selectAllPrograms) {
            selectAllPrograms.addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('.spa-program-checkbox');
                checkboxes.forEach(function(cb) {
                    cb.checked = selectAllPrograms.checked;
                });
            });
        }
        
        // Select all places
        var selectAllPlaces = document.getElementById('spa_select_all_places');
        if (selectAllPlaces) {
            selectAllPlaces.addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('.spa-place-checkbox');
                checkboxes.forEach(function(cb) {
                    cb.checked = selectAllPlaces.checked;
                });
            });
        }
    })();
    </script>
    <?php
}

/* ============================================================
   META BOX: DOCHÁDZKA (spa_attendance)
   ============================================================ */

function spa_attendance_meta_box($post) {
    wp_nonce_field('spa_attendance_save', 'spa_attendance_nonce');
    
    echo '<p style="padding:20px;background:#f0f6fc;border-left:3px solid #0969da;">Meta box pre dochádzku zatiaľ nie je implementovaný.</p>';
}

/* ============================================================
   META BOX: MIESTO (spa_place)
   ============================================================ */
function spa_place_meta_box($post) {
    wp_nonce_field('spa_save_place', 'spa_place_nonce');
    
    $type = get_post_meta($post->ID, 'spa_place_type', true);
    $address = get_post_meta($post->ID, 'spa_place_address', true);
    $city = get_post_meta($post->ID, 'spa_place_city', true);
    $gps_lat = get_post_meta($post->ID, 'spa_place_gps_lat', true);
    $gps_lng = get_post_meta($post->ID, 'spa_place_gps_lng', true);
    $contact = get_post_meta($post->ID, 'spa_place_contact', true);
    $notes = get_post_meta($post->ID, 'spa_place_notes', true);
    
    ?>
    
    <div class="spa-section">
        <h4>📍 Základné informácie</h4>
        
        <div class="spa-meta-row">
            <label for="spa_place_type">Typ priestoru:</label>
            <div class="spa-field">
                <select name="spa_place_type" id="spa_place_type">
                    <option value="">-- Vyberte typ --</option>
                    <option value="spa" <?php selected($type, 'spa'); ?>>🏠 Priestory SPA (vlastné)</option>
                    <option value="external" <?php selected($type, 'external'); ?>>🏫 Externé priestory (prenájom)</option>
                </select>
                <p class="spa-help">Externé priestory môžu mať príplatok v cene programu</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_city">Mesto:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_city" id="spa_place_city" value="<?php echo esc_attr($city); ?>" placeholder="napr. Malacky, Košice">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_address">Adresa:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_address" id="spa_place_address" value="<?php echo esc_attr($address); ?>" placeholder="napr. Športová hala Basso, Sasinkova 2">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label><span class="dashicons dashicons-location" style="color: var(--theme-palette-color-1);"></span> GPS súradnice:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_gps_lat" value="<?php echo esc_attr($gps_lat); ?>" placeholder="Lat" style="width: 150px; margin-right: 10px;">
                <input type="text" name="spa_place_gps_lng" value="<?php echo esc_attr($gps_lng); ?>" placeholder="Lng" style="width: 150px;">
                <p class="spa-help">Voliteľné - pre zobrazenie na mape</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_contact">Kontakt:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_contact" id="spa_place_contact" value="<?php echo esc_attr($contact); ?>" placeholder="Telefón alebo email na správcu">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_notes">Poznámky:</label>
            <div class="spa-field">
                <textarea name="spa_place_notes" id="spa_place_notes" rows="3" placeholder="Interné poznámky k miestu..."><?php echo esc_textarea($notes); ?></textarea>
            </div>
        </div>
    </div>
    <?php
}

// ROZVRH PROGRAMU (spa_group)
add_action('save_post_spa_group', 'spa_save_group_schedule_meta', 11, 2);
function spa_save_group_schedule_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_group_schedule_nonce']) || !wp_verify_nonce($_POST['spa_group_schedule_nonce'], 'spa_save_group_schedule')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    if (isset($_POST['spa_schedule']) && is_array($_POST['spa_schedule'])) {
        $schedule = [];
        foreach ($_POST['spa_schedule'] as $index => $item) {
            if (!empty($item['day'])) {
                $schedule[$index] = [
                    'day' => sanitize_text_field($item['day']),
                    'from' => sanitize_text_field($item['from'] ?? ''),
                    'to' => sanitize_text_field($item['to'] ?? '')
                ];
            }
        }
        update_post_meta($post_id, 'spa_schedule', wp_json_encode($schedule));
    }
}

// CENNÍK PROGRAMU (spa_group)
add_action('save_post_spa_group', 'spa_save_group_pricing_meta', 12, 2);
function spa_save_group_pricing_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_group_pricing_nonce']) || !wp_verify_nonce($_POST['spa_group_pricing_nonce'], 'spa_save_group_pricing')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $price_fields = [
        'spa_price_1x_weekly',
        'spa_price_2x_weekly',
        'spa_price_monthly',
        'spa_price_semester',
        'spa_external_surcharge'
    ];
    
    foreach ($price_fields as $field) {
        if (isset($_POST[$field])) {
            $value = floatval(str_replace(',', '.', $_POST[$field]));
            update_post_meta($post_id, $field, $value);
        }
    }
    
    if (isset($_POST['spa_price_1x_weekly'])) {
        $price = floatval(str_replace(',', '.', $_POST['spa_price_1x_weekly']));
        update_post_meta($post_id, 'spa_price', $price);
    }
}

// MIESTO (spa_place)
add_action('save_post_spa_place', 'spa_save_place_meta', 10, 2);
function spa_save_place_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_place_nonce']) || !wp_verify_nonce($_POST['spa_place_nonce'], 'spa_save_place')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = ['spa_place_type', 'spa_place_city', 'spa_place_address', 'spa_place_gps_lat', 'spa_place_gps_lng', 'spa_place_contact', 'spa_place_notes'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    
    if (isset($_POST['spa_place_schedule']) && is_array($_POST['spa_place_schedule'])) {
        $schedule = [];
        foreach ($_POST['spa_place_schedule'] as $day => $data) {
            if (!empty($data['from']) || !empty($data['to'])) {
                $schedule[$day] = [
                    'from' => sanitize_text_field($data['from']),
                    'to' => sanitize_text_field($data['to']),
                    'capacity' => intval($data['capacity'] ?? 0),
                    'active' => !empty($data['active'])
                ];
            }
        }
        update_post_meta($post_id, 'spa_place_schedule', wp_json_encode($schedule));
    }
}

// UDALOSŤ (spa_event)
add_action('save_post_spa_event', 'spa_save_event_meta', 10, 2);
    function spa_save_event_meta($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['spa_event_nonce']) || !wp_verify_nonce($_POST['spa_event_nonce'], 'spa_save_event')) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        // Základné polia
        $fields = [
            'spa_event_type' => 'sanitize_text_field',
            'spa_event_date_from' => 'sanitize_text_field',
            'spa_event_date_to' => 'sanitize_text_field',
            'spa_event_time_from' => 'sanitize_text_field',
            'spa_event_time_to' => 'sanitize_text_field',
            'spa_event_recurring' => 'sanitize_text_field'
        ];
        
        foreach ($fields as $key => $sanitize) {
            if (isset($_POST[$key])) {
                $value = sanitize_text_field($_POST[$key]);
                update_post_meta($post_id, $key, $value);
            }
        }
        
        // All day checkbox
        update_post_meta($post_id, 'spa_event_all_day', isset($_POST['spa_event_all_day']) ? 1 : 0);
        
        // VÄZBY (arrays)
        $program_ids = isset($_POST['spa_event_program_ids']) && is_array($_POST['spa_event_program_ids']) 
            ? array_map('intval', $_POST['spa_event_program_ids']) 
            : [];
        update_post_meta($post_id, 'spa_event_program_ids', $program_ids);
        
        $place_ids = isset($_POST['spa_event_place_ids']) && is_array($_POST['spa_event_place_ids']) 
            ? array_map('intval', $_POST['spa_event_place_ids']) 
            : [];
        update_post_meta($post_id, 'spa_event_place_ids', $place_ids);
    }

// DOCHÁDZKA (spa_attendance)
add_action('save_post_spa_attendance', 'spa_save_attendance_meta', 10, 2);
function spa_save_attendance_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_attendance_nonce']) || !wp_verify_nonce($_POST['spa_attendance_nonce'], 'spa_save_attendance')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = [
        'spa_att_client_id' => 'intval',
        'spa_att_program_id' => 'intval',
        'spa_att_registration_id' => 'intval',
        'spa_att_date' => 'sanitize_text_field',
        'spa_att_status' => 'sanitize_text_field',
        'spa_att_stars' => 'intval',
        'spa_att_points' => 'intval',
        'spa_att_rating' => 'sanitize_textarea_field',
        'spa_att_note' => 'sanitize_textarea_field'
    ];
    
    foreach ($fields as $key => $sanitize) {
        if (isset($_POST[$key])) {
            if ($sanitize === 'intval') {
                $value = intval($_POST[$key]);
            } elseif ($sanitize === 'sanitize_textarea_field') {
                $value = sanitize_textarea_field($_POST[$key]);
            } else {
                $value = sanitize_text_field($_POST[$key]);
            }
            update_post_meta($post_id, $key, $value);
        }
    }
    
    $client_id = intval($_POST['spa_att_client_id'] ?? 0);
    $date = sanitize_text_field($_POST['spa_att_date'] ?? '');
    
    if ($client_id && $date) {
        $user = get_userdata($client_id);
        if ($user) {
            $name = trim($user->first_name . ' ' . $user->last_name);
            if (empty($name)) $name = $user->display_name;
            $new_title = $name . ' - ' . date_i18n('j.n.Y', strtotime($date));
            
            remove_action('save_post_spa_attendance', 'spa_save_attendance_meta', 10);
            wp_update_post(['ID' => $post_id, 'post_title' => $new_title]);
            add_action('save_post_spa_attendance', 'spa_save_attendance_meta', 10, 2);
        }
    }
}

/* ============================================================
   AJAX: Dynamické načítanie ikony
   ============================================================ */

add_action('wp_ajax_spa_load_icon', 'spa_ajax_load_icon');
// add_action('wp_ajax_nopriv_spa_load_icon', 'spa_ajax_load_icon');
function spa_ajax_load_icon() {
    if (!isset($_POST['icon']) || empty($_POST['icon'])) {
        wp_send_json(['success' => false, 'error' => 'Ikona nie je zadaná']);
    }
    
    $icon_file = sanitize_file_name($_POST['icon']);
    $icon_path = WP_CONTENT_DIR . '/uploads/spa-icons/' . $icon_file;
    
    if (!file_exists($icon_path) || pathinfo($icon_path, PATHINFO_EXTENSION) !== 'svg') {
        wp_send_json(['success' => false, 'error' => 'Súbor neexistuje alebo nie je SVG']);
    }
    
    $svg_content = file_get_contents($icon_path);
    if (!$svg_content) {
        echo json_encode(['success' => false, 'error' => 'Nemôžem načítať súbor']);
        wp_die();
    }

    // Odstráň XML deklaráciu ak existuje
    $svg_content = preg_replace('/<\?xml[^?]*\?>/', '', $svg_content);

    echo json_encode(['success' => true, 'svg' => $svg_content]);
    wp_die();
}

// DETAILY PROGRAMU (spa_group)
add_action('save_post_spa_group', 'spa_save_group_details_meta', 10, 2);
function spa_save_group_details_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_group_nonce']) || !wp_verify_nonce($_POST['spa_group_nonce'], 'spa_save_group_details')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    // Základné polia
    $fields = ['spa_place_id', 'spa_capacity', 'spa_registration_type', 'spa_level', 'spa_icon'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = ($field === 'spa_place_id' || $field === 'spa_capacity') 
                ? intval($_POST[$field]) 
                : sanitize_text_field($_POST[$field]);
            update_post_meta($post_id, $field, $value);
        }
    }
    
    // Vekové hodnoty
    if (isset($_POST['spa_age_from'])) {
        $age = floatval(str_replace(',', '.', $_POST['spa_age_from']));
        update_post_meta($post_id, 'spa_age_from', $age);
    }
    if (isset($_POST['spa_age_to'])) {
        $age = floatval(str_replace(',', '.', $_POST['spa_age_to']));
        update_post_meta($post_id, 'spa_age_to', $age);
    }
    
    // Tréneri
    $trainers = isset($_POST['spa_trainers']) && is_array($_POST['spa_trainers']) 
        ? array_map('intval', $_POST['spa_trainers']) 
        : [];
    update_post_meta($post_id, 'spa_trainers', $trainers);
    
    // ✅ Primárna farba
    if (isset($_POST['spa_icon_primary_color'])) {
        update_post_meta($post_id, 'spa_icon_primary_color', sanitize_hex_color($_POST['spa_icon_primary_color']));
    }
    
    // ✅ Sekundárna farba
    if (isset($_POST['spa_icon_secondary_color'])) {
        update_post_meta($post_id, 'spa_icon_secondary_color', sanitize_hex_color($_POST['spa_icon_secondary_color']));
    }
}

/* ==========================
   MIESTO - ROZVRH MIESTA
   ========================== */

   function spa_place_schedule_callback($post) {
    
    // Načítaj programy priradené k tomuto miestu cez meta_query
    $programs = get_posts([
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
        'post_status' => 'publish',
        'meta_query' => [[
            'key' => 'spa_place_id',
            'value' => $post->ID,
            'compare' => '='
        ]]
    ]);
    
    ?>
    <style>
    .spa-schedule-table { width: 100%; border-collapse: collapse; }
    .spa-schedule-table th { padding: 10px; background: #F9F9F9; border: 1px solid #DDD; text-align: left; font-weight: 600; }
    .spa-schedule-table td { padding: 10px; border: 1px solid #DDD; }
    .spa-schedule-day { display: inline-block; padding: 3px 8px; background: #E3F2FD; border-radius: 3px; margin-right: 5px; font-size: 11px; }
    </style>
    
    <?php if (empty($programs)): ?>
        <div style="padding:20px;text-align:center;background:#FFF3CD;border:1px solid #FFE69C;border-radius:4px;">
            <p style="margin:0;font-size:14px;color:#856404;">
                ⚠️ Pre toto miesto nie sú priradené žiadne programy.<br>
                <small>Programy musia mať nastavené pole "Adresa miesta" na <strong><?php echo esc_html($post->post_title); ?></strong>.</small>
            </p>
        </div>
    <?php else: ?>
        <table class="spa-schedule-table">
            <thead>
                <tr>
                    <th>Program</th>
                    <th>Kategória</th>
                    <th>Rozvrh</th>
                    <th>Kapacita</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($programs as $program): 
                    $schedule_json = get_post_meta($program->ID, 'spa_schedule', true);
                    $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
                    $capacity = get_post_meta($program->ID, 'spa_capacity', true);
                    
                    $categories = get_the_terms($program->ID, 'spa_group_category');
                    $cat_name = $categories && !is_wp_error($categories) ? $categories[0]->name : '—';
                    
                    $days_sk = [
                        'monday' => 'Po',
                        'tuesday' => 'Ut',
                        'wednesday' => 'St',
                        'thursday' => 'Št',
                        'friday' => 'Pi',
                        'saturday' => 'So',
                        'sunday' => 'Ne'
                    ];
                ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_edit_post_link($program->ID); ?>" target="_blank">
                                <strong><?php echo esc_html($program->post_title); ?></strong>
                            </a>
                        </td>
                        <td><?php echo esc_html($cat_name); ?></td>
                        <td>
                            <?php if ($schedule): ?>
                                <?php foreach ($schedule as $item): ?>
                                    <span class="spa-schedule-day">
                                        <?php echo $days_sk[$item['day']] ?? '?'; ?> <?php echo esc_html($item['time']); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color:#999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $capacity ? esc_html($capacity) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php
}

/* ==========================
   PROGRAMY - ULOŽENIE ČASOVÉHO ROZSAHU
   ========================== */

add_action('save_post_spa_group', 'spa_save_program_timeframe', 10, 2);
function spa_save_program_timeframe($post_id, $post) {
    
    // Nonce check
    if (!isset($_POST['spa_program_timeframe_nonce']) || 
        !wp_verify_nonce($_POST['spa_program_timeframe_nonce'], 'spa_save_program_timeframe')) {
        return;
    }
    
    // Autosave check
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Permission check
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Uložiť DÁTUM ZAČIATKU (ISO formát Y-m-d)
    if (isset($_POST['spa_program_start_date'])) {
        $start_date = sanitize_text_field($_POST['spa_program_start_date']);
        
        if (!empty($start_date)) {
            // Validácia dátumu
            $parsed = date_parse($start_date);
            if ($parsed['error_count'] === 0 && checkdate($parsed['month'], $parsed['day'], $parsed['year'])) {
                update_post_meta($post_id, 'spa_program_start_date', $start_date);
            }
        } else {
            delete_post_meta($post_id, 'spa_program_start_date');
        }
    }
    
    // Uložiť DÁTUM UKONČENIA (ISO formát Y-m-d)
    if (isset($_POST['spa_program_end_date'])) {
        $end_date = sanitize_text_field($_POST['spa_program_end_date']);
        
        if (!empty($end_date)) {
            // Validácia dátumu
            $parsed = date_parse($end_date);
            if ($parsed['error_count'] === 0 && checkdate($parsed['month'], $parsed['day'], $parsed['year'])) {
                update_post_meta($post_id, 'spa_program_end_date', $end_date);
            }
        } else {
            delete_post_meta($post_id, 'spa_program_end_date');
        }
    }
    
    // Uložiť KALENDÁRNE TÝŽDNE
    if (isset($_POST['spa_program_calendar_weeks'])) {
        $weeks = sanitize_text_field($_POST['spa_program_calendar_weeks']);
        
        if (!empty($weeks)) {
            // Validácia (len čísla a čiarky)
            $weeks = preg_replace('/[^0-9,]/', '', $weeks);
            update_post_meta($post_id, 'spa_program_calendar_weeks', $weeks);
        } else {
            delete_post_meta($post_id, 'spa_program_calendar_weeks');
        }
    }
}