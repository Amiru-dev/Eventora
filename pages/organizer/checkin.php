<?php
/**
 * Eventora - Organizer Venue Check-in Portal
 * FR-TKT-003, FR-TKT-004, FR-TKT-005, FR-TKT-006, FR-TKT-007, BR-16, UC-10
 * Fast attendee validation via camera QR scanner & manual token lookup with live feedback
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/events.php';
require_once __DIR__ . '/../../includes/tickets.php';

require_role(['organizer', 'admin']);

$currentUser = current_user();
$isOrganizer = ($currentUser['role'] === 'organizer');
$isAdmin     = ($currentUser['role'] === 'admin');

// Fetch events accessible to this user
$pdo = get_db_connection();
$events = [];

if ($pdo) {
    if ($isAdmin) {
        $stmt = $pdo->query("SELECT event_id, title, venue, start_date FROM events WHERE status IN ('published', 'draft', 'pending_approval') ORDER BY start_date ASC");
        $events = $stmt->fetchAll();
    } else {
        $orgProfile = get_organizer_profile((int)$currentUser['user_id']);
        $orgId = (int)($orgProfile['profile_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT event_id, title, venue, start_date FROM events WHERE organizer_id = :orgId ORDER BY start_date ASC");
        $stmt->execute([':orgId' => $orgId]);
        $events = $stmt->fetchAll();
    }
}

// Fallback sample events if DB is empty/offline
if (empty($events)) {
    $events = [
        ['event_id' => 1, 'title' => 'Introduction to Web Development', 'venue' => 'Lab 201, IT Building', 'start_date' => '2026-11-27 10:00:00'],
        ['event_id' => 5, 'title' => 'Hackathon: Code for Change', 'venue' => 'Innovation Hub', 'start_date' => '2026-12-10 09:00:00']
    ];
}

// Determine active selected event
$selectedEventId = (int)($_GET['event_id'] ?? 0);
$validIds = array_column($events, 'event_id');
if (!in_array($selectedEventId, $validIds, true)) {
    $selectedEventId = (int)$events[0]['event_id'];
}

// Find current event details
$selectedEvent = null;
foreach ($events as $ev) {
    if ((int)$ev['event_id'] === $selectedEventId) {
        $selectedEvent = $ev;
        break;
    }
}

// Load stats and attendees for selected event
$stats = get_event_checkin_stats($selectedEventId);
$attendees = get_event_attendees($selectedEventId);

$pageTitle = 'Venue Check-in Console · ' . APP_NAME;
$activePage = 'org_checkin';
$orgActive = 'checkin';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_organizer.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <!-- Header & Event Selector -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h1 class="h3 fw-bold mb-1">Venue Ticket Check-in</h1>
                    <p class="text-muted small mb-0">Scan attendee QR codes or enter opaque ticket tokens to authenticate entry.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label for="eventSelector" class="small fw-bold text-muted mb-0">Active Event:</label>
                    <select id="eventSelector" class="form-select form-select-sm" style="min-width: 280px;" onchange="window.location.href='checkin.php?event_id=' + this.value">
                        <?php foreach ($events as $ev): ?>
                            <option value="<?php echo (int)$ev['event_id']; ?>" <?php echo (int)$ev['event_id'] === $selectedEventId ? 'selected' : ''; ?>>
                                <?php echo e($ev['title']); ?> (<?php echo e($ev['venue']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Real-time Stats Cards (FR-TKT-007) -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-card-title">Total Tickets</div>
                        <div class="stat-card-value" id="statTotalTickets"><?php echo (int)$stats['total_tickets']; ?></div>
                        <div class="small text-muted mt-1">Issued for event</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-card-title">Checked In</div>
                        <div class="stat-card-value text-success" id="statCheckedIn"><?php echo (int)$stats['checked_in']; ?></div>
                        <div class="small text-muted mt-1">Admitted to venue</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-card-title">Remaining</div>
                        <div class="stat-card-value text-warning" id="statRemaining"><?php echo (int)$stats['remaining']; ?></div>
                        <div class="small text-muted mt-1">Awaiting arrival</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-card-title">Check-in Rate</div>
                        <div class="stat-card-value text-primary" id="statCheckinRate"><?php echo number_format((float)$stats['checkin_rate'], 1); ?>%</div>
                        <div class="small text-muted mt-1">Attendance progress</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Scanner & Validation Column -->
                <div class="col-lg-6">
                    <!-- Scanner Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                            <h2 class="h5 fw-bold mb-0"><i class="bi bi-camera me-1"></i> QR Scanner & Token Lookup</h2>
                            <button id="toggleCameraBtn" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-camera-video me-1"></i> Start Camera
                            </button>
                        </div>
                        <div class="card-body p-4 text-center">
                            <!-- Camera Video Preview Box (hidden by default until started) -->
                            <div id="cameraContainer" class="p-3 bg-dark text-white rounded-3 mb-3 d-none position-relative">
                                <video id="cameraVideo" style="width: 100%; max-height: 240px; object-fit: cover; border-radius: 6px;" autoplay playsinline muted></video>
                                <div class="scanner-laser" style="position: absolute; top: 50%; left: 10%; right: 10%; height: 2px; background: red; box-shadow: 0 0 8px red;"></div>
                                <div class="mt-2 small text-light">Scanning via camera... Hold QR code in front of lens</div>
                            </div>

                            <!-- Camera Fallback Visual Box -->
                            <div id="scannerPlaceholder" class="p-4 bg-dark text-white rounded-3 mb-3 d-flex flex-column align-items-center justify-content-center" style="min-height: 180px;">
                                <i class="bi bi-qr-code-scan display-4 text-warning mb-2" aria-hidden="true"></i>
                                <span class="small text-light">Point camera at attendee's ticket or enter token below</span>
                            </div>

                            <!-- Manual Token Input Form (FR-TKT-004) -->
                            <form id="tokenCheckinForm" class="text-start">
                                <label for="ticket_token_input" class="form-label fw-semibold">Enter Ticket Token or Ref:</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-upc-scan"></i></span>
                                    <input type="text" id="ticket_token_input" class="form-control" placeholder="e.g. TKT-8f2a9c1e4b7d603a" autocomplete="off" required>
                                    <button class="btn btn-primary fw-semibold" type="submit" id="submitCheckinBtn">
                                        <i class="bi bi-shield-check me-1"></i> Verify Entry
                                    </button>
                                </div>
                                <div class="form-text small">Enter full opaque token or booking reference (case-insensitive).</div>
                            </form>
                        </div>
                    </div>

                    <!-- Dynamic Live Feedback Banner (UC-10: VALID, ALREADY_USED, INVALID) -->
                    <div id="checkinResultContainer" class="card border-0 shadow-sm border-start border-4 border-info">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div id="resultIconBox" class="stat-card-icon stat-icon-info flex-shrink-0" style="width: 46px; height: 46px;">
                                    <i id="resultIcon" class="bi bi-info-circle fs-4"></i>
                                </div>
                                <div class="flex-grow-1" id="resultDetails">
                                    <strong class="d-block fw-bold text-dark" id="resultHeading">AWAITING SCAN</strong>
                                    <div class="text-muted small" id="resultSub">Ready to scan attendees for <?php echo e($selectedEvent['title']); ?>.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendee Roster Column (FR-TKT-007) -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h2 class="h5 fw-bold mb-0">Event Attendee Roster</h2>
                                <span class="badge bg-light text-dark border" id="rosterCountBadge">
                                    <?php echo (int)$stats['checked_in']; ?> / <?php echo (int)$stats['total_tickets']; ?> Checked in
                                </span>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                <input type="text" id="attendeeSearchInput" class="form-control" placeholder="Filter by name, email, ref, or token...">
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                            <table class="table table-custom align-middle mb-0" id="attendeeRosterTable" aria-label="Attendee Roster">
                                <thead class="sticky-top bg-light">
                                    <tr>
                                        <th scope="col">Student</th>
                                        <th scope="col">Token</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="attendeeRosterBody">
                                    <?php if (empty($attendees)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="bi bi-people fs-3 d-block mb-1"></i> No tickets booked for this event yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($attendees as $att): 
                                            $isAttUsed = ($att['ticket_status'] === 'used');
                                        ?>
                                            <tr id="attendee-row-<?php echo e($att['token']); ?>" data-token="<?php echo e($att['token']); ?>">
                                                <td>
                                                    <strong class="text-dark d-block"><?php echo e($att['student_name']); ?></strong>
                                                    <small class="text-muted"><?php echo e($att['student_email']); ?></small>
                                                    <span class="badge bg-light text-muted border" style="font-size: 0.65rem;"><?php echo (int)$att['seats']; ?> <?php echo (int)$att['seats'] === 1 ? 'Seat' : 'Seats'; ?></span>
                                                </td>
                                                <td>
                                                    <code class="small"><?php echo substr(e($att['token']), 0, 12); ?>...</code>
                                                </td>
                                                <td class="status-cell">
                                                    <?php if ($isAttUsed): ?>
                                                        <span class="badge bg-success-subtle text-success border">
                                                            <i class="bi bi-check-circle me-1"></i> Checked In
                                                        </span>
                                                        <div class="small text-muted" style="font-size: 0.7rem;">
                                                            <?php echo $att['checked_in_at'] ? format_date($att['checked_in_at'], 'h:i A') : ''; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning border">
                                                            <i class="bi bi-clock me-1"></i> Pending
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end action-cell">
                                                    <?php if ($isAttUsed): ?>
                                                        <button class="btn btn-sm btn-outline-secondary" disabled>Admitted</button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-primary checkin-btn" 
                                                                data-token="<?php echo e($att['token']); ?>"
                                                                title="Verify and check in this student">
                                                            Check In
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentEventId = <?php echo (int)$selectedEventId; ?>;
    const form = document.getElementById('tokenCheckinForm');
    const tokenInput = document.getElementById('ticket_token_input');
    const resultContainer = document.getElementById('checkinResultContainer');
    const resultIconBox = document.getElementById('resultIconBox');
    const resultIcon = document.getElementById('resultIcon');
    const resultHeading = document.getElementById('resultHeading');
    const resultSub = document.getElementById('resultSub');
    const searchInput = document.getElementById('attendeeSearchInput');
    const rosterBody = document.getElementById('attendeeRosterBody');

    // Stats elements
    const statCheckedIn = document.getElementById('statCheckedIn');
    const statRemaining = document.getElementById('statRemaining');
    const statCheckinRate = document.getElementById('statCheckinRate');
    const rosterCountBadge = document.getElementById('rosterCountBadge');

    // Handle Form Submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const token = tokenInput.value.trim();
        if (!token) return;
        processCheckin(token);
    });

    // Handle one-click "Check In" button in table
    rosterBody.addEventListener('click', function(e) {
        const btn = e.target.closest('.checkin-btn');
        if (btn) {
            const token = btn.getAttribute('data-token');
            if (token) {
                tokenInput.value = token;
                processCheckin(token);
            }
        }
    });

    // Send check-in POST request to API
    function processCheckin(token) {
        resultHeading.textContent = 'VERIFYING...';
        resultSub.textContent = 'Checking ticket in database...';
        resultContainer.className = 'card border-0 shadow-sm border-start border-4 border-info';
        resultIconBox.className = 'stat-card-icon stat-icon-info flex-shrink-0';
        resultIcon.className = 'bi bi-arrow-repeat fs-4 spin';

        fetch('<?php echo APP_URL; ?>/api/checkin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                event_id: currentEventId
            })
        })
        .then(res => res.json())
        .then(data => {
            handleCheckinResponse(data);
        })
        .catch(err => {
            console.error('Checkin error:', err);
            showResult('INVALID', 'Network error or connection failed. Please try again.');
        });
    }

    function handleCheckinResponse(data) {
        if (!data) return;

        const result = data.result || 'INVALID';
        const msg = data.message || '';
        const ticket = data.ticket || {};
        const attendee = data.attendee || {};

        if (result === 'VALID') {
            resultContainer.className = 'card border-0 shadow-sm border-start border-4 border-success';
            resultIconBox.className = 'stat-card-icon stat-icon-success flex-shrink-0';
            resultIcon.className = 'bi bi-check-lg fs-4';
            resultHeading.className = 'd-block fw-bold text-success';
            resultHeading.textContent = 'ENTRY APPROVED';
            
            const attendeeName = attendee.name || ticket.student_name || 'Attendee';
            const seats = attendee.seats || ticket.seats || 1;
            const time = data.checkin_time || 'Just now';
            resultSub.innerHTML = `<strong>${escapeHtml(attendeeName)}</strong> · ${seats} Seat(s) Confirmed<br><small class="text-muted">Admitted at ${escapeHtml(time)} · Token: ${escapeHtml(ticket.token || '')}</small>`;

            // Update row in table if present
            if (ticket.token) {
                updateTableRow(ticket.token, 'used', time);
            }

            // Update stats
            if (data.stats) {
                updateStats(data.stats);
            }

            // Clear input and focus
            tokenInput.value = '';
            tokenInput.focus();

        } else if (result === 'ALREADY_USED') {
            resultContainer.className = 'card border-0 shadow-sm border-start border-4 border-warning';
            resultIconBox.className = 'stat-card-icon stat-icon-warning flex-shrink-0';
            resultIcon.className = 'bi bi-exclamation-triangle fs-4';
            resultHeading.className = 'd-block fw-bold text-warning';
            resultHeading.textContent = 'ALREADY CHECKED IN';
            resultSub.innerHTML = `<strong>Entry Denied</strong><br><span class="text-dark">${escapeHtml(msg)}</span>`;
            tokenInput.select();

        } else if (result === 'WRONG_EVENT') {
            resultContainer.className = 'card border-0 shadow-sm border-start border-4 border-danger';
            resultIconBox.className = 'stat-card-icon stat-icon-danger flex-shrink-0';
            resultIcon.className = 'bi bi-x-octagon fs-4';
            resultHeading.className = 'd-block fw-bold text-danger';
            resultHeading.textContent = 'WRONG EVENT';
            resultSub.innerHTML = `<span class="text-dark">${escapeHtml(msg)}</span>`;
            tokenInput.select();

        } else {
            resultContainer.className = 'card border-0 shadow-sm border-start border-4 border-danger';
            resultIconBox.className = 'stat-card-icon stat-icon-danger flex-shrink-0';
            resultIcon.className = 'bi bi-x-circle fs-4';
            resultHeading.className = 'd-block fw-bold text-danger';
            resultHeading.textContent = 'INVALID TICKET';
            resultSub.innerHTML = `<span class="text-dark">${escapeHtml(msg || 'Token not found or cancelled.')}</span>`;
            tokenInput.select();
        }
    }

    function showResult(type, message) {
        resultContainer.className = 'card border-0 shadow-sm border-start border-4 border-danger';
        resultIconBox.className = 'stat-card-icon stat-icon-danger flex-shrink-0';
        resultIcon.className = 'bi bi-x-circle fs-4';
        resultHeading.className = 'd-block fw-bold text-danger';
        resultHeading.textContent = type;
        resultSub.textContent = message;
    }

    function updateTableRow(token, status, time) {
        const rows = rosterBody.querySelectorAll('tr');
        rows.forEach(row => {
            const rowToken = row.getAttribute('data-token');
            if (rowToken && (rowToken.toLowerCase() === token.toLowerCase())) {
                const statusCell = row.querySelector('.status-cell');
                const actionCell = row.querySelector('.action-cell');
                if (statusCell) {
                    statusCell.innerHTML = `<span class="badge bg-success-subtle text-success border"><i class="bi bi-check-circle me-1"></i> Checked In</span><div class="small text-muted" style="font-size: 0.7rem;">${escapeHtml(time)}</div>`;
                }
                if (actionCell) {
                    actionCell.innerHTML = `<button class="btn btn-sm btn-outline-secondary" disabled>Admitted</button>`;
                }
            }
        });
    }

    function updateStats(stats) {
        if (statCheckedIn) statCheckedIn.textContent = stats.checked_in;
        if (statRemaining) statRemaining.textContent = stats.remaining;
        if (statCheckinRate) statCheckinRate.textContent = stats.checkin_rate.toFixed(1) + '%';
        if (rosterCountBadge) rosterCountBadge.textContent = `${stats.checked_in} / ${stats.total_tickets} Checked in`;
    }

    // Client-side instant filter of attendee table
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = rosterBody.querySelectorAll('tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });

    // Camera Scanner Handling
    const toggleCamBtn = document.getElementById('toggleCameraBtn');
    const cameraContainer = document.getElementById('cameraContainer');
    const cameraVideo = document.getElementById('cameraVideo');
    const scannerPlaceholder = document.getElementById('scannerPlaceholder');
    let videoStream = null;
    let scanInterval = null;

    toggleCamBtn.addEventListener('click', function() {
        if (videoStream) {
            stopCamera();
        } else {
            startCamera();
        }
    });

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Camera access is not supported by your browser.');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
            videoStream = stream;
            cameraVideo.srcObject = stream;
            cameraContainer.classList.remove('d-none');
            scannerPlaceholder.classList.add('d-none');
            toggleCamBtn.innerHTML = '<i class="bi bi-camera-video-off me-1"></i> Stop Camera';
            toggleCamBtn.classList.replace('btn-outline-primary', 'btn-outline-danger');

            // If BarcodeDetector is available in Chromium, use it
            if ('BarcodeDetector' in window) {
                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                scanInterval = setInterval(() => {
                    detector.detect(cameraVideo)
                    .then(barcodes => {
                        if (barcodes.length > 0) {
                            const rawValue = barcodes[0].rawValue;
                            if (rawValue && rawValue !== tokenInput.value) {
                                tokenInput.value = rawValue;
                                processCheckin(rawValue);
                            }
                        }
                    })
                    .catch(() => {});
                }, 500);
            }
        })
        .catch(err => {
            console.error('Camera access error:', err);
            alert('Could not access camera: ' + err.message);
        });
    }

    function stopCamera() {
        if (scanInterval) {
            clearInterval(scanInterval);
            scanInterval = null;
        }
        if (videoStream) {
            videoStream.getTracks().forEach(t => t.stop());
            videoStream = null;
        }
        cameraContainer.classList.add('d-none');
        scannerPlaceholder.classList.remove('d-none');
        toggleCamBtn.innerHTML = '<i class="bi bi-camera-video me-1"></i> Start Camera';
        toggleCamBtn.classList.replace('btn-outline-danger', 'btn-outline-primary');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
