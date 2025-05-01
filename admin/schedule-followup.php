<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
include('../admin/includes/header.php');
include('../admin/includes/sidebar.php');
include('../admin/includes/topbar.php');

// Check if form is submitted for new follow-up plan
if (isset($_POST['create_plan'])) {
    $appointment_id = $_POST['appointment_id'];
    $plan_type = $_POST['plan_type'];
    $frequency = $_POST['frequency'];
    $contract_duration = $_POST['contract_duration'];
    $start_date = $_POST['start_date'];
    $created_by = $_SESSION['user_id'];
    
    // Insert new follow-up plan
    $stmt = $conn->prepare("INSERT INTO followup_plan (appointment_id, plan_type, frequency, contract_duration, start_date, created_by, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issiis", $appointment_id, $plan_type, $frequency, $contract_duration, $start_date, $created_by);
    
    if ($stmt->execute()) {
        $plan_id = $conn->insert_id;
        
        // Calculate and generate follow-up visits based on frequency
        $visits = generateFollowupVisits($plan_type, $frequency, $contract_duration, $start_date, $plan_id, $appointment_id);
        
        // Insert all generated visits
        foreach ($visits as $visit) {
            $stmt = $conn->prepare("INSERT INTO followup_visits 
                (plan_id, appointment_id, followup_date, followup_time, visit_number, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'Scheduled', NOW())");
            $stmt->bind_param("iissi", $plan_id, $appointment_id, $visit['date'], $visit['time'], $visit['visit_number']);
            $stmt->execute();
        }
        
        $_SESSION['success'] = "Follow-up plan created successfully with " . count($visits) . " scheduled visits.";
    } else {
        $_SESSION['error'] = "Error creating follow-up plan: " . $conn->error;
    }
    
    header("Location: schedule-followup.php");
    exit;
}

// Function to generate follow-up visits based on plan parameters
function generateFollowupVisits($plan_type, $frequency, $duration, $start_date, $plan_id, $appointment_id) {
    $visits = [];
    $start = new DateTime($start_date);
    $visit_number = 1;
    $interval = null;
    
    // Default time for visits (can be adjusted as needed)
    $default_time = '09:00:00';
    
    // Set interval based on plan type and frequency
    switch ($plan_type) {
        case 'weekly':
            $interval = new DateInterval('P' . $frequency . 'W');
            $total_visits = ceil($duration * 4.33 / $frequency); // Approx 4.33 weeks per month
            break;
            
        case 'monthly':
            $interval = new DateInterval('P' . $frequency . 'M');
            $total_visits = ceil($duration / $frequency);
            break;
            
        case 'quarterly':
            $interval = new DateInterval('P3M'); // 3 months
            $total_visits = ceil($duration / 3);
            break;
            
        case 'yearly':
            $interval = new DateInterval('P1Y');
            $total_visits = ceil($duration / 12);
            break;
            
        default:
            return $visits; // Empty array if invalid plan type
    }
    
    $current_date = clone $start;
    
    // Generate visit dates
    for ($i = 0; $i < $total_visits; $i++) {
        if ($i > 0) { // Skip first iteration to use start_date as first visit
            $current_date->add($interval);
        }
        
        $visits[] = [
            'date' => $current_date->format('Y-m-d'),
            'time' => $default_time,
            'visit_number' => $visit_number++
        ];
    }
    
    return $visits;
}

// Delete follow-up plan
if (isset($_POST['delete_plan'])) {
    $plan_id = $_POST['plan_id'];
    
    $stmt = $conn->prepare("DELETE FROM followup_plan WHERE id = ?");
    $stmt->bind_param("i", $plan_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Follow-up plan deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting follow-up plan: " . $conn->error;
    }
    
    header("Location: schedule-followup.php");
    exit;
}

// Delete specific follow-up visit
if (isset($_POST['delete_visit'])) {
    $visit_id = $_POST['visit_id'];
    
    $stmt = $conn->prepare("DELETE FROM followup_visits WHERE id = ?");
    $stmt->bind_param("i", $visit_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Follow-up visit deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting follow-up visit: " . $conn->error;
    }
    
    header("Location: schedule-followup.php");
    exit;
}

// Update follow-up visit
if (isset($_POST['update_visit'])) {
    $visit_id = $_POST['visit_id'];
    $followup_date = $_POST['followup_date'];
    $followup_time = $_POST['followup_time'];
    $status = $_POST['status'];
    $technician_id = !empty($_POST['technician_id']) ? $_POST['technician_id'] : NULL;
    $notes = $_POST['notes'];
    
    $stmt = $conn->prepare("UPDATE followup_visits SET 
                          followup_date = ?, 
                          followup_time = ?, 
                          status = ?, 
                          technician_id = ?, 
                          notes = ?, 
                          updated_at = CURRENT_TIMESTAMP 
                          WHERE id = ?");
    $stmt->bind_param("sssisi", $followup_date, $followup_time, $status, $technician_id, $notes, $visit_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Follow-up visit updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating follow-up visit: " . $conn->error;
    }
    
    header("Location: schedule-followup.php");
    exit;
}

// Get all technicians for assignment dropdowns
$technicians = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) AS name FROM users WHERE role='technician' AND status='active'");
$techOptions = [];
while ($tech = $technicians->fetch_assoc()) {
    $techOptions[$tech['id']] = $tech['name'];
}

// Get completed appointments eligible for follow-ups
$stmt = $conn->prepare("SELECT a.id, a.service_type, s.service_name, CONCAT(u.firstname, ' ', u.lastname) as client_name, 
                      a.appointment_date, a.street_address, a.city, a.barangay
                      FROM appointments a 
                      LEFT JOIN services s ON a.service_id = s.service_id
                      LEFT JOIN users u ON a.user_id = u.id
                      WHERE a.status = 'Completed' 
                      ORDER BY a.appointment_date DESC");
$stmt->execute();
$appointments = $stmt->get_result();

// Get all follow-up plans with their visits from the view
$followup_plans = $conn->query("
    SELECT 
        fp.id as plan_id,
        fp.appointment_id,
        fp.plan_type,
        fp.frequency,
        fp.contract_duration,
        fp.start_date,
        CONCAT(u.firstname, ' ', u.lastname) as client_name,
        s.service_name,
        a.service_type,
        a.street_address,
        a.city,
        a.barangay
    FROM followup_plan fp
    JOIN appointments a ON fp.appointment_id = a.id
    JOIN users u ON a.user_id = u.id
    JOIN services s ON a.service_id = s.service_id
    ORDER BY fp.created_at DESC
");

// Get all follow-up visits with details using the view
$followup_visits = $conn->query("
    SELECT 
        sf.visit_id,
        sf.plan_id,
        sf.original_appt_id as appointment_id,
        sf.visit_date,
        sf.visit_time,
        sf.visit_seq,
        sf.visit_status,
        sf.tech_id as technician_id,
        sf.visit_notes,
        CONCAT(u.firstname, ' ', u.lastname) as client_name,
        s.service_name,
        a.service_type,
        a.street_address,
        a.city,
        a.barangay,
        CONCAT(t.firstname, ' ', t.lastname) as technician_name
    FROM scheduled_followups sf
    JOIN appointments a ON sf.original_appt_id = a.id
    JOIN users u ON a.user_id = u.id
    JOIN services s ON a.service_id = s.service_id
    LEFT JOIN users t ON sf.tech_id = t.id
    WHERE sf.visit_id IS NOT NULL
    ORDER BY sf.visit_date, sf.visit_time
");

?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Flash messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Schedule Follow-up Visits</h1>
        <button class="btn btn-primary" data-toggle="modal" data-target="#newFollowupModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> Create New Follow-up Plan
        </button>
    </div>

    <!-- Follow-up Plans Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Follow-up Plans</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="followupPlansTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Plan ID</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Plan Type</th>
                            <th>Frequency</th>
                            <th>Duration (Months)</th>
                            <th>Start Date</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($plan = $followup_plans->fetch_assoc()): ?>
                        <tr>
                            <td><?= $plan['plan_id'] ?></td>
                            <td><?= htmlspecialchars($plan['client_name']) ?></td>
                            <td><?= htmlspecialchars($plan['service_name']) ?> (<?= $plan['service_type'] ?>)</td>
                            <td><?= ucfirst($plan['plan_type']) ?></td>
                            <td><?= $plan['frequency'] ?></td>
                            <td><?= $plan['contract_duration'] ?></td>
                            <td><?= date('M d, Y', strtotime($plan['start_date'])) ?></td>
                            <td><?= htmlspecialchars($plan['street_address'] . ', ' . $plan['barangay'] . ', ' . $plan['city']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-info viewVisitsBtn" data-plan-id="<?= $plan['plan_id'] ?>">
                                    <i class="fas fa-eye"></i> View Visits
                                </button>
                                <form action="" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this follow-up plan?');">
                                    <input type="hidden" name="plan_id" value="<?= $plan['plan_id'] ?>">
                                    <button type="submit" name="delete_plan" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Follow-up Visits Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Scheduled Follow-up Visits</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="followupVisitsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Visit ID</th>
                            <th>Plan ID</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Visit #</th>
                            <th>Date & Time</th>
                            <th>Technician</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($visit = $followup_visits->fetch_assoc()): ?>
                        <tr class="<?= $visit['visit_status'] == 'Completed' ? 'table-success' : ($visit['visit_status'] == 'Canceled' ? 'table-danger' : '') ?>">
                            <td><?= $visit['visit_id'] ?></td>
                            <td><?= $visit['plan_id'] ?></td>
                            <td><?= htmlspecialchars($visit['client_name']) ?></td>
                            <td><?= htmlspecialchars($visit['service_name']) ?> (<?= $visit['service_type'] ?>)</td>
                            <td><?= $visit['visit_seq'] ?></td>
                            <td><?= date('M d, Y', strtotime($visit['visit_date'])) ?> at <?= date('h:i A', strtotime($visit['visit_time'])) ?></td>
                            <td><?= $visit['technician_name'] ?: 'Unassigned' ?></td>
                            <td><span class="badge <?= $visit['visit_status'] == 'Scheduled' ? 'badge-primary' : ($visit['visit_status'] == 'Completed' ? 'badge-success' : 'badge-danger') ?>"><?= $visit['visit_status'] ?></span></td>
                            <td><?= htmlspecialchars($visit['street_address'] . ', ' . $visit['barangay'] . ', ' . $visit['city']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary editVisitBtn" 
                                    data-visit-id="<?= $visit['visit_id'] ?>"
                                    data-date="<?= $visit['visit_date'] ?>"
                                    data-time="<?= $visit['visit_time'] ?>"
                                    data-status="<?= $visit['visit_status'] ?>"
                                    data-technician="<?= $visit['technician_id'] ?>"
                                    data-notes="<?= htmlspecialchars($visit['visit_notes'] ?: '') ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form action="" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this follow-up visit?');">
                                    <input type="hidden" name="visit_id" value="<?= $visit['visit_id'] ?>">
                                    <button type="submit" name="delete_visit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- New Follow-up Plan Modal -->
<div class="modal fade" id="newFollowupModal" tabindex="-1" role="dialog" aria-labelledby="newFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newFollowupModalLabel">Create New Follow-up Plan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="post" id="newFollowupForm">
                    <div class="form-group">
                        <label for="appointment_id">Select Completed Service:</label>
                        <select class="form-control" id="appointment_id" name="appointment_id" required>
                            <option value="">Select a completed service</option>
                            <?php $appointments->data_seek(0); ?>
                            <?php while ($appt = $appointments->fetch_assoc()): ?>
                                <option value="<?= $appt['id'] ?>">
                                    <?= date('M d, Y', strtotime($appt['appointment_date'])) ?> - 
                                    <?= htmlspecialchars($appt['service_name']) ?> (<?= $appt['service_type'] ?>) - 
                                    <?= htmlspecialchars($appt['client_name']) ?> - 
                                    <?= htmlspecialchars($appt['street_address'] . ', ' . $appt['barangay'] . ', ' . $appt['city']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="plan_type">Plan Type:</label>
                            <select class="form-control" id="plan_type" name="plan_type" required>
                                <option value="">Select plan type</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="frequency">Frequency:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="frequency" name="frequency" min="1" value="1" required>
                                <div class="input-group-append">
                                    <span class="input-group-text" id="frequency-label">week(s)</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">How often visits occur (e.g., every 2 weeks)</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="contract_duration">Contract Duration (months):</label>
                            <input type="number" class="form-control" id="contract_duration" name="contract_duration" min="1" required>
                            <small class="form-text text-muted">Total contract length in months</small>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="start_date">Start Date:</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-12">
                            <div id="visit-preview" class="card d-none">
                                <div class="card-header">
                                    Preview of Follow-up Visits
                                </div>
                                <div class="card-body">
                                    <ul id="visit-list" class="list-group"></ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-info" id="previewVisitsBtn">Preview Visits</button>
                        <button type="submit" name="create_plan" class="btn btn-primary">Create Follow-up Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Visit Modal -->
<div class="modal fade" id="editVisitModal" tabindex="-1" role="dialog" aria-labelledby="editVisitModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editVisitModalLabel">Edit Follow-up Visit</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="post" id="editVisitForm">
                    <input type="hidden" name="visit_id" id="edit_visit_id">
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="followup_date">Visit Date:</label>
                            <input type="date" class="form-control" id="followup_date" name="followup_date" required>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="followup_time">Visit Time:</label>
                            <input type="time" class="form-control" id="followup_time" name="followup_time" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="status">Status:</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Completed">Completed</option>
                                <option value="Canceled">Canceled</option>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="technician_id">Assign Technician:</label>
                            <select class="form-control" id="technician_id" name="technician_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($techOptions as $id => $name): ?>
                                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_visit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Filter Visits by Plan Modal -->
<div class="modal fade" id="viewVisitsModal" tabindex="-1" role="dialog" aria-labelledby="viewVisitsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewVisitsModalLabel">Follow-up Visits for Plan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="planVisitsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Visit #</th>
                                <th>Date & Time</th>
                                <th>Technician</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="planVisitsTableBody">
                            <!-- Will be populated via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add custom JavaScript to handle modal interactions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Edit Visit button clicks
    const editButtons = document.querySelectorAll('.editVisitBtn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const visitId = this.getAttribute('data-visit-id');
            const date = this.getAttribute('data-date');
            const time = this.getAttribute('data-time');
            const status = this.getAttribute('data-status');
            const technician = this.getAttribute('data-technician');
            const notes = this.getAttribute('data-notes');
            
            document.getElementById('edit_visit_id').value = visitId;
            document.getElementById('followup_date').value = date;
            document.getElementById('followup_time').value = time;
            document.getElementById('status').value = status;
            document.getElementById('technician_id').value = technician || '';
            document.getElementById('notes').value = notes;
            
            $('#editVisitModal').modal('show');
        });
    });
    
    // Handle View Visits button clicks
    const viewButtons = document.querySelectorAll('.viewVisitsBtn');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const planId = this.getAttribute('data-plan-id');
            
            // Fetch visits for this plan
            fetch(`../api/get_plan_visits.php?plan_id=${planId}`)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('planVisitsTableBody');
                    tableBody.innerHTML = '';
                    
                    if (data.length === 0) {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td colspan="5" class="text-center">No visits scheduled for this plan</td>';
                        tableBody.appendChild(row);
                    } else {
                        data.forEach(visit => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${visit.visit_seq}</td>
                                <td>${new Date(visit.visit_date).toLocaleDateString()} at ${visit.visit_time}</td>
                                <td>${visit.technician_name || 'Unassigned'}</td>
                                <td><span class="badge ${visit.visit_status === 'Scheduled' ? 'badge-primary' : (visit.visit_status === 'Completed' ? 'badge-success' : 'badge-danger')}">${visit.visit_status}</span></td>
                                <td>${visit.visit_notes || ''}</td>
                            `;
                            tableBody.appendChild(row);
                        });
                    }
                    
                    $('#viewVisitsModal').modal('show');
                })
                .catch(error => {
                    console.error('Error fetching visits:', error);
                    alert('Failed to load visit data');
                });
        });
    });
    
    // Update frequency label based on plan type
    const planTypeSelect = document.getElementById('plan_type');
    planTypeSelect.addEventListener('change', function() {
        const frequencyLabel = document.getElementById('frequency-label');
        switch (this.value) {
            case 'weekly':
                frequencyLabel.textContent = 'week(s)';
                break;
            case 'monthly':
                frequencyLabel.textContent = 'month(s)';
                break;
            case 'quarterly':
                frequencyLabel.textContent = 'quarter(s)';
                break;
            case 'yearly':
                frequencyLabel.textContent = 'year(s)';
                break;
            default:
                frequencyLabel.textContent = '';
        }
    });

    // Preview visits functionality
    document.getElementById('previewVisitsBtn').addEventListener('click', function() {
        const planType = document.getElementById('plan_type').value;
        const frequency = document.getElementById('frequency').value;
        const duration = document.getElementById('contract_duration').value;
        const startDate = document.getElementById('start_date').value;
        
        if (!planType || !frequency || !duration || !startDate) {
            alert('Please fill out all fields to preview visits');
            return;
        }
        
        // Call API to generate preview visits
        fetch('../api/preview_visits.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                plan_type: planType,
                frequency: frequency,
                duration: duration,
                start_date: startDate
            }),
        })
        .then(response => response.json())
        .then(data => {
            const visitList = document.getElementById('visit-list');
            visitList.innerHTML = '';
            
            data.forEach((visit, index) => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.innerHTML = `<b>Visit ${visit.visit_number}:</b> ${new Date(visit.date).toLocaleDateString()} at ${visit.time}`;
                visitList.appendChild(li);
            });
            
            document.getElementById('visit-preview').classList.remove('d-none');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to generate visit preview');
        });
    });

    // Initialize DataTables
    $('#followupPlansTable').DataTable();
    $('#followupVisitsTable').DataTable({
        "order": [[5, "asc"]] // Sort by date (ascending)
    });
});
</script>

<?php
include('../admin/includes/footer.php');
?>
