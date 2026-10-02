<?php
 
$adminTitle = "User Management - Admin Console";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$search       = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$users         = [];
$totalUsers    = 0;
$activeUsers   = 0;
$inactiveUsers = 0;

$dbStatus = checkDBStatus();
if ($dbStatus['status']) {
    try {
        $db = getDB();

        $query = "
            SELECT 
                u.*,
                COUNT(b.id) AS total_bookings,
                COALESCE(SUM(CASE WHEN b.booking_status = 'confirmed' THEN b.total_amount ELSE 0 END), 0) AS total_spent
            FROM users u
            LEFT JOIN bookings b ON u.id = b.user_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($statusFilter)) {
            $query .= " AND u.status = ?";
            $params[] = $statusFilter;
        }

        $query .= " GROUP BY u.id ORDER BY u.id ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

$totalUsers    = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $activeUsers   = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
        $inactiveUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE status != 'active'")->fetchColumn();

    } catch (Exception $e) {
        $users = [];
    }
}

$flashAdminSuccess = getFlash('admin_success');
$flashAdminError   = getFlash('admin_error');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-lg-5 flex-grow-1">
    <div class="row g-4">
        <div class="col-lg-3 col-xl-2">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <div class="col-lg-9 col-xl-10">
            <?php if ($flashAdminSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($flashAdminSuccess['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($flashAdminError): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($flashAdminError['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="text-white fw-bold mb-1">
                        <i class="fa-solid fa-users text-danger me-2"></i> Registered Customers
                    </h3>
                    <p class="text-secondary small mb-0">Phase 16: Manage registered user accounts, contact details, account status, and booking history</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('admin/bookings.php') ?>" class="btn btn-outline-light btn-sm">
                        <i class="fa-solid fa-ticket me-1"></i> Customer Bookings
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="cine-card p-3 h-100">
                        <span class="text-secondary small fw-semibold">Total Registered Customers</span>
                        <h4 class="text-white fw-bold mb-0 mt-1"><?= $totalUsers ?></h4>
                        <small class="text-muted">Database customer records</small>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="cine-card p-3 h-100 border border-success-subtle">
                        <span class="text-success small fw-semibold">Active Accounts</span>
                        <h4 class="text-success fw-bold mb-0 mt-1"><?= $activeUsers ?></h4>
                        <small class="text-muted">Permitted to log in and reserve seats</small>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="cine-card p-3 h-100">
                        <span class="text-danger small fw-semibold">Inactive / Suspended Accounts</span>
                        <h4 class="text-danger fw-bold mb-0 mt-1"><?= $inactiveUsers ?></h4>
                        <small class="text-muted">Account access restricted</small>
                    </div>
                </div>
            </div>

            <div class="cine-card p-3 mb-4">
                <form action="<?= url('admin/users/index.php') ?>" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-dark border-secondary text-secondary">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" 
                                   name="search" 
                                   class="form-control bg-dark border-secondary text-light ps-2" 
                                   placeholder="Search customer name, email, or phone..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm bg-dark border-secondary text-light">
                            <option value="">All Account Statuses</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                        </select>
                    </div>

                    <div class="col-auto">
                        <button type="submit" class="btn btn-danger btn-sm">Filter</button>
                        <a href="<?= url('admin/users/index.php') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>
            </div>

            <div class="cine-card p-4">
                <?php if (!empty($users)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0 small">
                            <thead>
                                <tr class="text-secondary border-secondary">
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registration Date</th>
                                    <th>Status</th>
                                    <th>Activity</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <?php 
                                    $isActive = ($u['status'] === 'active');
                                    ?>
                                    <tr class="border-secondary">
                                        <td>
                                            <span class="badge bg-dark border border-secondary text-light font-monospace">
                                                #USER-<?= str_pad($u['id'], 3, '0', STR_PAD_LEFT) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-danger bg-opacity-25 text-danger fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="text-white fw-bold"><?= htmlspecialchars($u['full_name']) ?></div>
                                                    <small class="text-secondary text-uppercase"><?= htmlspecialchars($u['role'] ?? 'user') ?></small>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="text-light text-decoration-none">
                                                <i class="fa-regular fa-envelope text-secondary me-1"></i><?= htmlspecialchars($u['email']) ?>
                                            </a>
                                        </td>

                                        <td>
                                            <span class="text-light"><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></span>
                                        </td>

                                        <td>
                                            <div class="text-light">
                                                <i class="fa-solid fa-calendar-day text-secondary me-1"></i>
                                                <?= !empty($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : 'N/A' ?>
                                            </div>
                                            <small class="text-secondary">
                                                <?= !empty($u['created_at']) ? date('h:i A', strtotime($u['created_at'])) : '' ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-success-subtle text-success border border-success">
                                                    <i class="fa-solid fa-circle-check me-1"></i> ACTIVE
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i> INACTIVE
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="badge bg-dark border border-secondary text-light">
                                                <?= (int)$u['total_bookings'] ?> show<?= (int)$u['total_bookings'] != 1 ? 's' : '' ?>
                                            </span>
                                            <small class="text-success fw-bold d-block mt-1">
                                                <?= formatPrice($u['total_spent']) ?>
                                            </small>
                                        </td>

                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="<?= url('admin/bookings.php?search=' . urlencode($u['email'])) ?>" 
                                                   class="btn btn-outline-light btn-sm" 
                                                   title="View Customer's Bookings">
                                                    <i class="fa-solid fa-ticket"></i>
                                                </a>

                                                <form action="<?= url('actions/auth_action.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Change status for customer \'<?= htmlspecialchars(addslashes($u['full_name'])) ?>\' to <?= $isActive ? 'INACTIVE' : 'ACTIVE' ?>?');">
                                                    <input type="hidden" name="action" value="toggle_user_status">
                                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                    <?php if ($isActive): ?>
                                                        <button type="submit" class="btn btn-outline-warning btn-sm" title="Deactivate Account">
                                                            <i class="fa-solid fa-user-slash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-outline-success btn-sm" title="Activate Account">
                                                            <i class="fa-solid fa-user-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="fa-solid fa-users-slash fs-1 mb-3 text-secondary"></i>
                        <h6 class="text-white">No Customer Accounts Found</h6>
                        <p class="small mb-0">No registered customers match your current search or status filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
