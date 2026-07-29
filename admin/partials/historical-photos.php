<?php
/**
 * Historical Photos Page
 * Allows clinicians to search customers, view all their photos across appointments,
 * and compare lesions over time.
 * 
 * @since 9.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure table exists
ensure_patient_photos_table();

$selected_customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$selected_lesion_id = isset($_GET['lesion_id']) ? sanitize_text_field($_GET['lesion_id']) : '';
$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'search';

// Get customer details if selected
$customer_info = null;
if ($selected_customer_id) {
    global $wpdb;
    $customer_info = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}amelia_users WHERE id = %d AND type = 'customer'",
        $selected_customer_id
    ));
}
?>

<style>
/* Historical Photos Page Styles */
.historical-photos-wrap {
    background: #D4F1E8;
    padding: 30px;
    border-radius: 16px;
    font-family: 'Quicksand', sans-serif;
    max-width: 1400px;
    margin-top: 20px;
}

.historical-photos-wrap h1 {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 28px;
    color: #5A4A5A;
    margin-bottom: 24px;
    display: inline-flex;
    align-items: center;
    gap: 12px;
}

.hp-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(255, 181, 197, 0.12);
    margin-bottom: 20px;
}

.hp-card h2 {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 18px;
    color: #5A4A5A;
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.hp-card h2 .icon-dot {
    width: 10px;
    height: 10px;
    background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%);
    border-radius: 50%;
}

/* Search Box */
.hp-search-box {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.hp-search-box input[type="text"] {
    flex: 1;
    min-width: 250px;
    padding: 12px 16px;
    border: 2px solid #98D9C2;
    border-radius: 25px;
    font-family: 'Quicksand', sans-serif;
    font-size: 14px;
    outline: none;
    transition: all 0.3s ease;
}

.hp-search-box input[type="text"]:focus {
    border-color: #5FBDA0;
    box-shadow: 0 0 0 3px rgba(152, 217, 194, 0.3);
}

.hp-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #98D9C2 0%, #5FBDA0 100%);
    color: white;
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    border-radius: 25px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(152, 217, 194, 0.4);
}

.hp-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(152, 217, 194, 0.5);
    color: white;
}

.hp-btn-secondary {
    background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%);
    box-shadow: 0 4px 16px rgba(255, 181, 197, 0.4);
}

.hp-btn-secondary:hover {
    box-shadow: 0 6px 20px rgba(255, 181, 197, 0.5);
}

/* Customer Results */
.hp-customer-results {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.hp-customer-card {
    background: #FFF9F5;
    border: 2px solid #FFE4EC;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.hp-customer-card:hover {
    border-color: #FFB5C5;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 181, 197, 0.2);
}

.hp-customer-card h3 {
    font-family: 'Quicksand', sans-serif;
    font-size: 16px;
    font-weight: 700;
    color: #5A4A5A;
    margin: 0 0 8px 0;
}

.hp-customer-card p {
    font-family: 'Quicksand', sans-serif;
    font-size: 13px;
    color: #8A7A8A;
    margin: 0;
}

.hp-customer-card .photo-count {
    display: inline-block;
    background: #98D9C2;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 10px;
}

/* Customer Profile Header */
.hp-profile-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.hp-profile-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #98D9C2 0%, #5FBDA0 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 700;
    color: white;
}

.hp-profile-info h2 {
    margin: 0 0 4px 0;
    font-size: 24px;
}

.hp-profile-info p {
    color: #8A7A8A;
    margin: 0;
    font-size: 14px;
}

/* Body Map Navigator */
.hp-body-map {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
    margin: 20px 0;
}

.hp-body-region {
    text-align: center;
    cursor: pointer;
    padding: 15px;
    border-radius: 12px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    min-width: 100px;
}

.hp-body-region:hover,
.hp-body-region.active {
    background: #FFF9F5;
    border-color: #FFB5C5;
}

.hp-body-region img {
    width: 60px;
    height: 80px;
    object-fit: contain;
    margin-bottom: 8px;
}

.hp-body-region span {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #5A4A5A;
}

.hp-body-region .lesion-count {
    background: #E8829A;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    margin-top: 4px;
    display: inline-block;
}

/* Timeline View */
.hp-timeline {
    position: relative;
    padding-left: 30px;
}

.hp-timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(180deg, #98D9C2 0%, #FFB5C5 100%);
    border-radius: 3px;
}

.hp-timeline-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 30px;
}

.hp-timeline-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 5px;
    width: 14px;
    height: 14px;
    background: white;
    border: 3px solid #5FBDA0;
    border-radius: 50%;
}

.hp-timeline-date {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 14px;
    color: #5FBDA0;
    margin-bottom: 10px;
}

.hp-timeline-photos {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.hp-photo-thumb {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 12px;
    overflow: hidden;
    border: 3px solid #98D9C2;
    cursor: pointer;
    transition: all 0.3s ease;
}

.hp-photo-thumb:hover {
    transform: scale(1.05);
    border-color: #5FBDA0;
    box-shadow: 0 4px 12px rgba(152, 217, 194, 0.4);
}

.hp-photo-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hp-photo-thumb .location-badge {
    position: absolute;
    bottom: 4px;
    left: 4px;
    background: rgba(90, 74, 90, 0.85);
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
}

.hp-photo-thumb .compare-checkbox {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    background: white;
    border: 2px solid #98D9C2;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hp-photo-thumb .compare-checkbox.selected {
    background: #5FBDA0;
    border-color: #5FBDA0;
}

.hp-photo-thumb .compare-checkbox.selected::after {
    content: '✓';
    color: white;
    font-size: 14px;
    font-weight: bold;
}

/* Comparison View */
.hp-comparison-container {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.hp-comparison-slot {
    flex: 1;
    min-width: 300px;
    max-width: 400px;
    background: #FFF9F5;
    border: 2px solid #FFE4EC;
    border-radius: 16px;
    padding: 16px;
    text-align: center;
}

.hp-comparison-slot h4 {
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
    font-size: 14px;
    color: #8A7A8A;
    margin: 0 0 12px 0;
}

.hp-comparison-slot img {
    width: 100%;
    max-height: 300px;
    object-fit: contain;
    border-radius: 12px;
    border: 3px solid #98D9C2;
}

.hp-comparison-slot .photo-date {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 16px;
    color: #5A4A5A;
    margin-top: 12px;
}

.hp-comparison-slot .photo-notes {
    font-size: 13px;
    color: #8A7A8A;
    margin-top: 8px;
    text-align: left;
    background: white;
    padding: 10px;
    border-radius: 8px;
}

/* Lesion Tracker */
.hp-lesion-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.hp-lesion-card {
    background: #FFF9F5;
    border: 2px solid #FFE4EC;
    border-radius: 12px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.hp-lesion-card:hover {
    border-color: #E8829A;
}

.hp-lesion-card .lesion-id {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 14px;
    color: #E8829A;
    margin-bottom: 4px;
}

.hp-lesion-card .lesion-location {
    font-size: 12px;
    color: #5A4A5A;
}

.hp-lesion-card .lesion-visits {
    font-size: 11px;
    color: #8A7A8A;
    margin-top: 8px;
}

/* AI Analysis Styles */
.hp-ai-section {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 2px solid #FFE4EC;
}

.hp-ai-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 14px;
    border-radius: 25px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
}

.hp-ai-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}

.hp-ai-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.hp-ai-result {
    margin-top: 20px;
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    border: 2px solid #c4b5fd;
    border-radius: 16px;
    padding: 20px;
}

.hp-ai-result h4 {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 16px;
    color: #5A4A5A;
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.hp-ai-result .risk-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.hp-ai-result .risk-low {
    background: #D4EDDA;
    color: #155724;
}

.hp-ai-result .risk-medium {
    background: #FFF3CD;
    color: #856404;
}

.hp-ai-result .risk-high {
    background: #F8D7DA;
    color: #721C24;
}

.hp-ai-result .analysis-content {
    font-size: 14px;
    line-height: 1.8;
    color: #5A4A5A;
    white-space: pre-wrap;
}

.hp-ai-result .analysis-content strong {
    color: #764ba2;
}

.hp-ai-loading {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    background: #f5f3ff;
    border-radius: 12px;
    margin-top: 16px;
}

.hp-ai-loading .spinner {
    width: 24px;
    height: 24px;
    border: 3px solid #c4b5fd;
    border-top-color: #764ba2;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Floating Compare Button */
.hp-compare-fab {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, #E8829A 0%, #FFB5C5 100%);
    color: white;
    padding: 16px 24px;
    border-radius: 30px;
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 14px;
    border: none;
    cursor: pointer;
    box-shadow: 0 6px 24px rgba(232, 130, 154, 0.5);
    display: none;
    align-items: center;
    gap: 8px;
    z-index: 1000;
    transition: all 0.3s ease;
}

.hp-compare-fab:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(232, 130, 154, 0.6);
}

.hp-compare-fab.visible {
    display: flex;
}

/* Modal */
.hp-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 10000;
    justify-content: center;
    align-items: center;
}

.hp-modal.active {
    display: flex;
}

.hp-modal-content {
    background: white;
    border-radius: 20px;
    padding: 30px;
    max-width: 900px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.hp-modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 36px;
    height: 36px;
    background: #E8829A;
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Empty State */
.hp-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.hp-empty-state svg {
    width: 80px;
    height: 80px;
    color: #98D9C2;
    margin-bottom: 20px;
}

.hp-empty-state h3 {
    font-family: 'Quicksand', sans-serif;
    font-size: 18px;
    color: #5A4A5A;
    margin: 0 0 8px 0;
}

.hp-empty-state p {
    color: #8A7A8A;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
    .historical-photos-wrap {
        padding: 15px;
    }
    
    .hp-comparison-container {
        flex-direction: column;
    }
    
    .hp-comparison-slot {
        max-width: 100%;
    }
    
    .hp-profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .hp-compare-fab {
        bottom: 20px;
        right: 20px;
        padding: 12px 20px;
    }
}
</style>

<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="wrap">
    <div class="historical-photos-wrap">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#5FBDA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            Historical Photos
        </h1>

        <?php if (!$selected_customer_id): ?>
        <!-- Search View -->
        <div class="hp-card">
            <h2><span class="icon-dot"></span>Search Customer</h2>
            <div class="hp-search-box">
                <input type="text" id="hp-customer-search" placeholder="Search by name, email, or phone..." />
                <button type="button" class="hp-btn" id="hp-search-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Search
                </button>
            </div>
            
            <div id="hp-search-results">
                <div class="hp-empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <h3>Search for a Customer</h3>
                    <p>Enter a name, email, or phone number to find customer photos</p>
                </div>
            </div>
        </div>

        <!-- Recent Customers with Photos -->
        <div class="hp-card">
            <h2><span class="icon-dot"></span>Recent Customers with Photos</h2>
            <div id="hp-recent-customers" class="hp-customer-results">
                <?php
                global $wpdb;
                $recent_customers = $wpdb->get_results("
                    SELECT u.id, u.firstName, u.lastName, u.email, u.phone,
                           COUNT(DISTINCT p.id) as photo_count,
                           MAX(p.upload_date) as last_photo
                    FROM {$wpdb->prefix}amelia_users u
                    INNER JOIN {$wpdb->prefix}amelia_patient_photos p ON u.id = p.customer_id
                    WHERE u.type = 'customer'
                    GROUP BY u.id
                    ORDER BY last_photo DESC
                    LIMIT 12
                ");
                
                if (empty($recent_customers)):
                ?>
                <div class="hp-empty-state" style="grid-column: 1 / -1;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <h3>No Photos Yet</h3>
                    <p>Photos uploaded from body charts will appear here</p>
                </div>
                <?php else:
                    foreach ($recent_customers as $customer):
                ?>
                <a href="<?php echo admin_url('admin.php?page=amelia-historical-photos&customer_id=' . $customer->id); ?>" class="hp-customer-card">
                    <h3><?php echo esc_html($customer->firstName . ' ' . $customer->lastName); ?></h3>
                    <p><?php echo esc_html($customer->email); ?></p>
                    <?php if ($customer->phone): ?>
                    <p><?php echo esc_html($customer->phone); ?></p>
                    <?php endif; ?>
                    <span class="photo-count"><?php echo $customer->photo_count; ?> photos</span>
                </a>
                <?php 
                    endforeach;
                endif;
                ?>
            </div>
        </div>

        <?php else: ?>
        <!-- Customer Detail View -->
        <div class="hp-card">
            <div class="hp-profile-header">
                <div class="hp-profile-avatar">
                    <?php echo strtoupper(substr($customer_info->firstName, 0, 1) . substr($customer_info->lastName, 0, 1)); ?>
                </div>
                <div class="hp-profile-info">
                    <h2 style="margin:0;"><?php echo esc_html($customer_info->firstName . ' ' . $customer_info->lastName); ?></h2>
                    <p><?php echo esc_html($customer_info->email); ?></p>
                    <?php if ($customer_info->phone): ?>
                    <p><?php echo esc_html($customer_info->phone); ?></p>
                    <?php endif; ?>
                </div>
                <div style="margin-left: auto;">
                    <a href="<?php echo admin_url('admin.php?page=amelia-historical-photos'); ?>" class="hp-btn hp-btn-secondary">
                        ← Back to Search
                    </a>
                </div>
            </div>
        </div>

        <!-- Body Map Navigator -->
        <div class="hp-card">
            <h2><span class="icon-dot"></span>Body Region Overview</h2>
            <div class="hp-body-map" id="hp-body-map">
                <?php
                $regions = array(
                    'face1Canvas' => array('name' => 'Face (Front)', 'icon' => 'view-face1.png'),
                    'face2Canvas' => array('name' => 'Face (Side)', 'icon' => 'view-face2.png'),
                    'frontCanvas' => array('name' => 'Body (Front)', 'icon' => 'front-part.png'),
                    'backCanvas' => array('name' => 'Body (Back)', 'icon' => 'back-part.png')
                );
                
                foreach ($regions as $region_id => $region):
                    $count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}amelia_patient_photos 
                         WHERE customer_id = %d AND body_location = %s",
                        $selected_customer_id, $region_id
                    ));
                ?>
                <div class="hp-body-region" data-region="<?php echo $region_id; ?>">
                    <img src="<?php echo WPAMELIA_ADDON_PLUGIN_URL; ?>admin/images/<?php echo $region['icon']; ?>" alt="<?php echo $region['name']; ?>">
                    <span><?php echo $region['name']; ?></span>
                    <?php if ($count > 0): ?>
                    <span class="lesion-count"><?php echo $count; ?> photos</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tracked Lesions -->
        <div class="hp-card">
            <h2><span class="icon-dot"></span>Tracked Lesions</h2>
            <p style="color: #8A7A8A; font-size: 14px; margin-bottom: 16px;">
                Lesions are automatically grouped by their location on the body chart. Click a lesion to see its history.
            </p>
            <div class="hp-lesion-list" id="hp-lesion-list">
                <?php
                $lesions = $wpdb->get_results($wpdb->prepare("
                    SELECT lesion_id, body_location, 
                           MIN(upload_date) as first_seen,
                           MAX(upload_date) as last_seen,
                           COUNT(*) as photo_count
                    FROM {$wpdb->prefix}amelia_patient_photos
                    WHERE customer_id = %d AND lesion_id IS NOT NULL AND lesion_id != ''
                    GROUP BY lesion_id, body_location
                    ORDER BY last_seen DESC
                ", $selected_customer_id));
                
                if (empty($lesions)):
                ?>
                <div class="hp-empty-state" style="grid-column: 1 / -1;">
                    <h3>No Tracked Lesions</h3>
                    <p>Lesions will appear here when photos are linked to body chart markers</p>
                </div>
                <?php else:
                    $location_names = array(
                        'frontCanvas' => 'Body Front',
                        'backCanvas' => 'Body Back',
                        'face1Canvas' => 'Face Front',
                        'face2Canvas' => 'Face Side'
                    );
                    foreach ($lesions as $lesion):
                ?>
                <div class="hp-lesion-card" data-lesion-id="<?php echo esc_attr($lesion->lesion_id); ?>">
                    <div class="lesion-id">Lesion #<?php echo esc_html(substr($lesion->lesion_id, 0, 8)); ?></div>
                    <div class="lesion-location"><?php echo $location_names[$lesion->body_location] ?? $lesion->body_location; ?></div>
                    <div class="lesion-visits">
                        <?php echo $lesion->photo_count; ?> photos · 
                        First: <?php echo date('M j, Y', strtotime($lesion->first_seen)); ?>
                    </div>
                </div>
                <?php 
                    endforeach;
                endif;
                ?>
            </div>
        </div>

        <!-- Photo Timeline -->
        <div class="hp-card">
            <h2><span class="icon-dot"></span>Photo Timeline</h2>
            <div class="hp-timeline" id="hp-photo-timeline">
                <?php
                $appointments_with_photos = $wpdb->get_results($wpdb->prepare("
                    SELECT p.*, a.bookingStart, s.name as service_name
                    FROM {$wpdb->prefix}amelia_patient_photos p
                    LEFT JOIN {$wpdb->prefix}amelia_appointments a ON p.appointment_id = a.id
                    LEFT JOIN {$wpdb->prefix}amelia_services s ON a.serviceId = s.id
                    WHERE p.customer_id = %d
                    ORDER BY p.upload_date DESC
                ", $selected_customer_id));
                
                // Group by date
                $grouped_photos = array();
                foreach ($appointments_with_photos as $photo) {
                    $date_key = date('Y-m-d', strtotime($photo->upload_date));
                    if (!isset($grouped_photos[$date_key])) {
                        $grouped_photos[$date_key] = array(
                            'date' => $photo->upload_date,
                            'service' => $photo->service_name,
                            'photos' => array()
                        );
                    }
                    $grouped_photos[$date_key]['photos'][] = $photo;
                }
                
                if (empty($grouped_photos)):
                ?>
                <div class="hp-empty-state">
                    <h3>No Photos Found</h3>
                    <p>Photos will appear here when uploaded from body chart appointments</p>
                </div>
                <?php else:
                    $location_names = array(
                        'frontCanvas' => 'Front',
                        'backCanvas' => 'Back',
                        'face1Canvas' => 'Face',
                        'face2Canvas' => 'Face Side'
                    );
                    foreach ($grouped_photos as $date => $group):
                ?>
                <div class="hp-timeline-item">
                    <div class="hp-timeline-date">
                        <?php echo date('F j, Y', strtotime($group['date'])); ?>
                        <?php if ($group['service']): ?>
                        <span style="color: #8A7A8A; font-weight: 400;"> - <?php echo esc_html($group['service']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="hp-timeline-photos">
                        <?php foreach ($group['photos'] as $photo): ?>
                        <div class="hp-photo-thumb" 
                             data-photo-id="<?php echo $photo->id; ?>"
                             data-photo-url="<?php echo esc_url($photo->file_url); ?>"
                             data-photo-date="<?php echo date('M j, Y', strtotime($photo->upload_date)); ?>"
                             data-photo-notes="<?php echo esc_attr($photo->notes); ?>"
                             data-lesion-id="<?php echo esc_attr($photo->lesion_id); ?>">
                            <img src="<?php echo esc_url($photo->file_url); ?>" alt="Patient photo">
                            <span class="location-badge"><?php echo $location_names[$photo->body_location] ?? 'Unknown'; ?></span>
                            <div class="compare-checkbox" data-photo-id="<?php echo $photo->id; ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php 
                    endforeach;
                endif;
                ?>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<!-- Floating Compare Button -->
<button type="button" class="hp-compare-fab" id="hp-compare-fab">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="17 1 21 5 17 9"></polyline>
        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
        <polyline points="7 23 3 19 7 15"></polyline>
        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
    </svg>
    Compare Selected (<span id="hp-compare-count">0</span>)
</button>

<!-- Comparison Modal -->
<div class="hp-modal" id="hp-comparison-modal">
    <div class="hp-modal-content" style="max-width: 1000px;">
        <button type="button" class="hp-modal-close" id="hp-modal-close">&times;</button>
        <h2 style="font-family: 'Quicksand', sans-serif; color: #5A4A5A; margin-bottom: 20px;">
            <span class="icon-dot" style="display: inline-block; width: 10px; height: 10px; background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%); border-radius: 50%;"></span>
            Photo Comparison
        </h2>
        <div class="hp-comparison-container" id="hp-comparison-container">
            <!-- Photos will be inserted here via JS -->
        </div>
        
        <!-- AI Analysis Section -->
        <div class="hp-ai-section">
            <button type="button" class="hp-ai-btn" id="hp-ai-analyze-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a10 10 0 1 0 10 10H12V2z"></path>
                    <path d="M12 2a10 10 0 0 1 10 10"></path>
                    <circle cx="12" cy="12" r="6"></circle>
                    <circle cx="12" cy="12" r="2"></circle>
                </svg>
                Analyze Changes with AI
            </button>
            <p style="margin-top: 8px; font-size: 12px; color: #8A7A8A;">
                Uses GPT-4o Vision to analyze lesion changes using ABCDE criteria
            </p>
            
            <div id="hp-ai-loading" class="hp-ai-loading" style="display: none;">
                <div class="spinner"></div>
                <span>Analyzing photos with AI... This may take a moment.</span>
            </div>
            
            <div id="hp-ai-result" class="hp-ai-result" style="display: none;">
                <h4>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#764ba2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    AI Analysis Results
                    <span class="risk-badge" id="hp-risk-badge"></span>
                </h4>
                <div class="analysis-content" id="hp-analysis-content"></div>
                <div style="margin-top: 16px; text-align: right;">
                    <button type="button" class="hp-btn" id="hp-save-analysis-btn" style="padding: 8px 16px; font-size: 12px;">
                        Save to Patient Record
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Detail Modal -->
<div class="hp-modal" id="hp-photo-modal">
    <div class="hp-modal-content" style="max-width: 600px;">
        <button type="button" class="hp-modal-close hp-photo-modal-close">&times;</button>
        <div id="hp-photo-detail">
            <!-- Photo detail will be inserted here -->
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const selectedPhotos = new Set();
    
    // Customer Search
    $('#hp-search-btn').on('click', function() {
        searchCustomers();
    });
    
    $('#hp-customer-search').on('keypress', function(e) {
        if (e.which === 13) {
            searchCustomers();
        }
    });
    
    function searchCustomers() {
        const query = $('#hp-customer-search').val().trim();
        if (!query) return;
        
        $('#hp-search-results').html('<div class="hp-empty-state"><p>Searching...</p></div>');
        
        $.ajax({
            url: ajax.url,
            type: 'POST',
            data: {
                action: 'hp_search_customers',
                nonce: ajax.nonce,
                query: query
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '<div class="hp-customer-results">';
                    response.data.forEach(function(customer) {
                        html += `
                            <a href="<?php echo admin_url('admin.php?page=amelia-historical-photos&customer_id='); ?>${customer.id}" class="hp-customer-card">
                                <h3>${customer.firstName} ${customer.lastName}</h3>
                                <p>${customer.email}</p>
                                ${customer.phone ? `<p>${customer.phone}</p>` : ''}
                                <span class="photo-count">${customer.photo_count} photos</span>
                            </a>
                        `;
                    });
                    html += '</div>';
                    $('#hp-search-results').html(html);
                } else {
                    $('#hp-search-results').html('<div class="hp-empty-state"><h3>No Results</h3><p>No customers found matching your search</p></div>');
                }
            }
        });
    }
    
    // Photo Selection for Comparison
    $(document).on('click', '.compare-checkbox', function(e) {
        e.stopPropagation();
        const photoId = $(this).data('photo-id');
        const $thumb = $(this).closest('.hp-photo-thumb');
        
        if (selectedPhotos.has(photoId)) {
            selectedPhotos.delete(photoId);
            $(this).removeClass('selected');
        } else {
            if (selectedPhotos.size >= 4) {
                alert('You can compare up to 4 photos at a time');
                return;
            }
            selectedPhotos.add(photoId);
            $(this).addClass('selected');
        }
        
        updateCompareButton();
    });
    
    function updateCompareButton() {
        $('#hp-compare-count').text(selectedPhotos.size);
        if (selectedPhotos.size >= 2) {
            $('#hp-compare-fab').addClass('visible');
        } else {
            $('#hp-compare-fab').removeClass('visible');
        }
    }
    
    // Open Comparison Modal
    $('#hp-compare-fab').on('click', function() {
        let html = '';
        let count = 0;
        
        selectedPhotos.forEach(function(photoId) {
            const $thumb = $(`.hp-photo-thumb[data-photo-id="${photoId}"]`);
            const photoUrl = $thumb.data('photo-url');
            const photoDate = $thumb.data('photo-date');
            const photoNotes = $thumb.data('photo-notes');
            
            html += `
                <div class="hp-comparison-slot">
                    <h4>Photo ${++count}</h4>
                    <img src="${photoUrl}" alt="Comparison photo">
                    <div class="photo-date">${photoDate}</div>
                    ${photoNotes ? `<div class="photo-notes">${photoNotes}</div>` : ''}
                </div>
            `;
        });
        
        $('#hp-comparison-container').html(html);
        $('#hp-comparison-modal').addClass('active');
    });
    
    // Close Modals
    $('#hp-modal-close, .hp-photo-modal-close').on('click', function() {
        $('.hp-modal').removeClass('active');
    });
    
    $('.hp-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).removeClass('active');
        }
    });
    
    // View Photo Detail
    $(document).on('click', '.hp-photo-thumb', function(e) {
        if ($(e.target).hasClass('compare-checkbox') || $(e.target).closest('.compare-checkbox').length) {
            return;
        }
        
        const photoUrl = $(this).data('photo-url');
        const photoDate = $(this).data('photo-date');
        const photoNotes = $(this).data('photo-notes');
        const lesionId = $(this).data('lesion-id');
        
        let html = `
            <img src="${photoUrl}" style="width: 100%; border-radius: 12px; margin-bottom: 16px;">
            <p><strong>Date:</strong> ${photoDate}</p>
            ${lesionId ? `<p><strong>Lesion ID:</strong> ${lesionId.substring(0, 8)}</p>` : ''}
            ${photoNotes ? `<p><strong>Notes:</strong> ${photoNotes}</p>` : ''}
        `;
        
        $('#hp-photo-detail').html(html);
        $('#hp-photo-modal').addClass('active');
    });
    
    // Lesion Card Click
    $(document).on('click', '.hp-lesion-card', function() {
        const lesionId = $(this).data('lesion-id');
        // Filter timeline to show only photos for this lesion
        $('.hp-photo-thumb').each(function() {
            const $this = $(this);
            if ($this.data('lesion-id') === lesionId) {
                $this.css('opacity', '1');
            } else {
                $this.css('opacity', '0.3');
            }
        });
        
        // Add reset button
        if (!$('#hp-reset-filter').length) {
            $('.hp-card:has(.hp-timeline)').find('h2').append(
                '<button id="hp-reset-filter" class="hp-btn hp-btn-secondary" style="margin-left: 15px; padding: 6px 12px; font-size: 12px;">Clear Filter</button>'
            );
        }
    });
    
    // Reset Filter
    $(document).on('click', '#hp-reset-filter', function() {
        $('.hp-photo-thumb').css('opacity', '1');
        $(this).remove();
    });
    
    // Body Region Filter
    $(document).on('click', '.hp-body-region', function() {
        const region = $(this).data('region');
        $('.hp-body-region').removeClass('active');
        $(this).addClass('active');
        
        // Could implement region filtering here
    });
    
    // ==========================================
    // AI LESION ANALYSIS
    // ==========================================
    
    let currentComparisonPhotos = [];
    let currentLesionId = '';
    
    // Store photo data when opening comparison
    $('#hp-compare-fab').on('click', function() {
        currentComparisonPhotos = [];
        
        // Reset AI section
        $('#hp-ai-result').hide();
        $('#hp-ai-loading').hide();
        
        selectedPhotos.forEach(function(photoId) {
            const $thumb = $(`.hp-photo-thumb[data-photo-id="${photoId}"]`);
            currentComparisonPhotos.push({
                id: photoId,
                url: $thumb.data('photo-url'),
                date: $thumb.data('photo-date'),
                lesionId: $thumb.data('lesion-id')
            });
        });
        
        // Sort by date (oldest first)
        currentComparisonPhotos.sort((a, b) => new Date(a.date) - new Date(b.date));
        
        // Store lesion ID if all photos share the same one
        const lesionIds = [...new Set(currentComparisonPhotos.map(p => p.lesionId).filter(id => id))];
        currentLesionId = lesionIds.length === 1 ? lesionIds[0] : '';
    });
    
    // AI Analysis Button
    $('#hp-ai-analyze-btn').on('click', function() {
        if (currentComparisonPhotos.length < 2) {
            alert('Please select at least 2 photos to analyze');
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true);
        $('#hp-ai-loading').show();
        $('#hp-ai-result').hide();
        
        const photoUrls = currentComparisonPhotos.map(p => p.url);
        const photoDates = currentComparisonPhotos.map(p => p.date);
        
        $.ajax({
            url: ajax.url,
            type: 'POST',
            data: {
                action: 'hp_ai_analyze_lesions',
                nonce: ajax.nonce,
                photo_urls: photoUrls,
                photo_dates: photoDates
            },
            success: function(response) {
                $('#hp-ai-loading').hide();
                $btn.prop('disabled', false);
                
                if (response.success) {
                    const data = response.data;
                    
                    // Update risk badge
                    const $badge = $('#hp-risk-badge');
                    $badge.removeClass('risk-low risk-medium risk-high');
                    
                    if (data.risk_level === 'high') {
                        $badge.addClass('risk-high').text('High Concern');
                    } else if (data.risk_level === 'medium') {
                        $badge.addClass('risk-medium').text('Medium Concern');
                    } else if (data.risk_level === 'low') {
                        $badge.addClass('risk-low').text('Low Concern');
                    } else {
                        $badge.text('');
                    }
                    
                    // Format and display analysis
                    let formattedAnalysis = data.analysis
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\n/g, '<br>');
                    
                    $('#hp-analysis-content').html(formattedAnalysis);
                    $('#hp-ai-result').show();
                } else {
                    alert('AI Analysis failed: ' + response.data);
                }
            },
            error: function() {
                $('#hp-ai-loading').hide();
                $btn.prop('disabled', false);
                alert('Error connecting to AI service');
            }
        });
    });
    
    // Save AI Analysis
    $('#hp-save-analysis-btn').on('click', function() {
        if (!currentLesionId) {
            alert('Cannot save: Photos must be linked to the same lesion ID');
            return;
        }
        
        const analysis = $('#hp-analysis-content').text();
        
        $.ajax({
            url: ajax.url,
            type: 'POST',
            data: {
                action: 'hp_save_ai_analysis',
                nonce: ajax.nonce,
                lesion_id: currentLesionId,
                analysis: analysis
            },
            success: function(response) {
                if (response.success) {
                    alert('Analysis saved to patient record!');
                } else {
                    alert('Failed to save: ' + response.data);
                }
            }
        });
    });
});
</script>

<?php
