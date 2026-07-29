<?php
/**
 * Photo Migration Tool
 * Migrates existing body chart photos from Media Library to protected storage
 * 
 * @since 9.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get migration status
$migration_status = get_option('amelia_photo_migration_status', array(
    'completed' => false,
    'total_found' => 0,
    'migrated' => 0,
    'errors' => array(),
    'last_run' => null
));

$protected_dir = WP_CONTENT_DIR . '/patient-photos/';
$protected_url = content_url('/patient-photos/');
?>

<style>
.migration-wrap {
    background: #D4F1E8;
    padding: 30px;
    border-radius: 16px;
    font-family: 'Quicksand', sans-serif;
    max-width: 900px;
    margin-top: 20px;
}

.migration-wrap h1 {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 28px;
    color: #5A4A5A;
    margin-bottom: 24px;
}

.migration-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(255, 181, 197, 0.12);
    margin-bottom: 20px;
}

.migration-card h2 {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 18px;
    color: #5A4A5A;
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.migration-card h2 .icon-dot {
    width: 10px;
    height: 10px;
    background: linear-gradient(135deg, #FFB5C5 0%, #E8829A 100%);
    border-radius: 50%;
}

.migration-info {
    background: #FFF9F5;
    border: 2px solid #FFE4EC;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}

.migration-info p {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #5A4A5A;
}

.migration-info p:last-child {
    margin-bottom: 0;
}

.migration-info strong {
    color: #E8829A;
}

.migration-warning {
    background: #FFF3CD;
    border: 2px solid #FFCA28;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}

.migration-warning h4 {
    color: #856404;
    margin: 0 0 8px 0;
    font-size: 14px;
}

.migration-warning ul {
    margin: 0;
    padding-left: 20px;
    color: #856404;
    font-size: 13px;
}

.migration-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
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

.migration-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(152, 217, 194, 0.5);
}

.migration-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.migration-btn-danger {
    background: linear-gradient(135deg, #E8829A 0%, #D63384 100%);
    box-shadow: 0 4px 16px rgba(232, 130, 154, 0.4);
}

.migration-btn-secondary {
    background: linear-gradient(135deg, #98D9C2 0%, #5FBDA0 100%);
}

.migration-progress {
    display: none;
    margin-top: 20px;
}

.migration-progress.active {
    display: block;
}

.progress-bar-container {
    background: #E9ECEF;
    border-radius: 10px;
    height: 24px;
    overflow: hidden;
    margin-bottom: 12px;
}

.progress-bar {
    background: linear-gradient(135deg, #98D9C2 0%, #5FBDA0 100%);
    height: 100%;
    width: 0%;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 12px;
}

.progress-status {
    font-size: 14px;
    color: #5A4A5A;
}

.migration-log {
    background: #1E1E1E;
    color: #98D9C2;
    font-family: 'Consolas', monospace;
    font-size: 12px;
    padding: 16px;
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
    margin-top: 16px;
}

.migration-log .log-entry {
    margin-bottom: 4px;
}

.migration-log .log-success {
    color: #98D9C2;
}

.migration-log .log-error {
    color: #FF6B6B;
}

.migration-log .log-info {
    color: #74B9FF;
}

.migration-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.stat-box {
    background: #FFF9F5;
    border: 2px solid #FFE4EC;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
}

.stat-box .stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #5FBDA0;
}

.stat-box .stat-label {
    font-size: 12px;
    color: #8A7A8A;
    margin-top: 4px;
}

.stat-box.errors .stat-number {
    color: #E8829A;
}

.migration-complete {
    background: #D4EDDA;
    border: 2px solid #28A745;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    color: #155724;
}

.migration-complete h3 {
    margin: 0 0 8px 0;
}
</style>

<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="wrap">
    <div class="migration-wrap">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#5FBDA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            Photo Migration Tool
        </h1>

        <?php if ($migration_status['completed']): ?>
        <div class="migration-card">
            <div class="migration-complete">
                <h3>Migration Completed!</h3>
                <p>All photos have been migrated to protected storage.</p>
                <p>Last run: <?php echo date('F j, Y g:i A', strtotime($migration_status['last_run'])); ?></p>
            </div>
            <div class="migration-stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $migration_status['total_found']; ?></div>
                    <div class="stat-label">Photos Found</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $migration_status['migrated']; ?></div>
                    <div class="stat-label">Successfully Migrated</div>
                </div>
                <div class="stat-box errors">
                    <div class="stat-number"><?php echo count($migration_status['errors']); ?></div>
                    <div class="stat-label">Errors</div>
                </div>
            </div>
            
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;">
                <p style="color: #5A4A5A; margin-bottom: 12px;">Need to run the migration again? (e.g. to pick up photos added since last run)</p>
                <button type="button" class="migration-btn migration-btn-secondary" id="reset-migration-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                    Reset & Re-scan
                </button>
            </div>
        </div>
        <?php else: ?>
        
        <div class="migration-card">
            <h2><span class="icon-dot"></span>About This Tool</h2>
            <div class="migration-info">
                <p>This tool will migrate all existing patient photos from the WordPress Media Library to a <strong>protected storage location</strong>.</p>
                <p><strong>Protected folder:</strong> <?php echo esc_html($protected_dir); ?></p>
            </div>
            
            <div class="migration-warning">
                <h4>⚠️ Important - Please Read Before Proceeding:</h4>
                <ul>
                    <li>Photos will be <strong>moved</strong> from Media Library to protected storage</li>
                    <li>Original files will be <strong>deleted</strong> from Media Library</li>
                    <li>Body chart data will be updated with new URLs</li>
                    <li>This process <strong>cannot be undone</strong> - please backup your database first</li>
                    <li>Photos will no longer appear in Media Library searches</li>
                </ul>
            </div>
        </div>

        <div class="migration-card">
            <h2><span class="icon-dot"></span>Step 1: Scan for Photos</h2>
            <p style="color: #5A4A5A; margin-bottom: 16px;">First, let's scan your database to find all patient photos that need to be migrated.</p>
            <button type="button" class="migration-btn migration-btn-secondary" id="scan-photos-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Scan for Photos
            </button>
            
            <div id="scan-results" style="display: none; margin-top: 20px;">
                <div class="migration-stats">
                    <div class="stat-box">
                        <div class="stat-number" id="total-photos">0</div>
                        <div class="stat-label">Total Photos Found</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" id="total-customers">0</div>
                        <div class="stat-label">Customers</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" id="total-appointments">0</div>
                        <div class="stat-label">Appointments</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="migration-card" id="migration-section" style="display: none;">
            <h2><span class="icon-dot"></span>Step 2: Run Migration</h2>
            <p style="color: #5A4A5A; margin-bottom: 16px;">Click below to start the migration process. This may take several minutes depending on the number of photos.</p>
            
            <button type="button" class="migration-btn migration-btn-danger" id="start-migration-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Start Migration
            </button>
            
            <div class="migration-progress" id="migration-progress">
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progress-bar">0%</div>
                </div>
                <div class="progress-status" id="progress-status">Initializing...</div>
                <div class="migration-log" id="migration-log"></div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let scannedPhotos = [];
    
    // Reset migration status (allows re-running after a prior completion)
    $('#reset-migration-btn').on('click', function() {
        if (!confirm('Reset the migration status? The scan/migrate buttons will become available again. No photos will be moved until you explicitly run the migration.')) {
            return;
        }
        const $btn = $(this);
        $btn.prop('disabled', true).text('Resetting…');
        $.ajax({
            url: ajax.url,
            type: 'POST',
            data: {
                action: 'pm_reset_migration',
                nonce: ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Reset failed: ' + (response.data || 'Unknown error'));
                    $btn.prop('disabled', false).text('Reset & Re-scan');
                }
            },
            error: function() {
                alert('Reset failed due to a network error');
                $btn.prop('disabled', false).text('Reset & Re-scan');
            }
        });
    });
    
    // Scan for photos
    $('#scan-photos-btn').on('click', function() {
        const $btn = $(this);
        $btn.text('Scanning...').prop('disabled', true);
        
        $.ajax({
            url: ajax.url,
            type: 'POST',
            data: {
                action: 'pm_scan_photos',
                nonce: ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    scannedPhotos = response.data.photos;
                    $('#total-photos').text(response.data.total_photos);
                    $('#total-customers').text(response.data.total_customers);
                    $('#total-appointments').text(response.data.total_appointments);
                    $('#scan-results').show();
                    
                    if (response.data.total_photos > 0) {
                        $('#migration-section').show();
                    }
                } else {
                    alert('Scan failed: ' + response.data);
                }
                $btn.html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> Scan Again').prop('disabled', false);
            },
            error: function() {
                alert('Error during scan');
                $btn.text('Scan for Photos').prop('disabled', false);
            }
        });
    });
    
    // Start migration
    $('#start-migration-btn').on('click', function() {
        if (!confirm('Are you sure you want to start the migration? This cannot be undone. Make sure you have a database backup!')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true);
        $('#migration-progress').addClass('active');
        
        let processed = 0;
        const total = scannedPhotos.length;
        
        function processNext() {
            if (processed >= total) {
                // Mark migration complete
                $.ajax({
                    url: ajax.url,
                    type: 'POST',
                    data: {
                        action: 'pm_complete_migration',
                        nonce: ajax.nonce,
                        total: total,
                        migrated: processed
                    },
                    success: function() {
                        addLog('Migration completed!', 'success');
                        $('#progress-status').text('Migration completed! Refreshing page...');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                });
                return;
            }
            
            const photo = scannedPhotos[processed];
            const percent = Math.round((processed / total) * 100);
            
            $('#progress-bar').css('width', percent + '%').text(percent + '%');
            $('#progress-status').text('Processing ' + (processed + 1) + ' of ' + total + '...');
            
            $.ajax({
                url: ajax.url,
                type: 'POST',
                data: {
                    action: 'pm_migrate_single_photo',
                    nonce: ajax.nonce,
                    photo: JSON.stringify(photo)
                },
                success: function(response) {
                    if (response.success) {
                        addLog('Migrated: ' + photo.url.split('/').pop(), 'success');
                    } else {
                        addLog('Error: ' + response.data, 'error');
                    }
                    processed++;
                    processNext();
                },
                error: function() {
                    addLog('Failed to migrate: ' + photo.url, 'error');
                    processed++;
                    processNext();
                }
            });
        }
        
        addLog('Starting migration of ' + total + ' photos...', 'info');
        processNext();
    });
    
    function addLog(message, type) {
        const $log = $('#migration-log');
        $log.append('<div class="log-entry log-' + type + '">[' + new Date().toLocaleTimeString() + '] ' + message + '</div>');
        $log.scrollTop($log[0].scrollHeight);
    }
});
</script>
<?php
