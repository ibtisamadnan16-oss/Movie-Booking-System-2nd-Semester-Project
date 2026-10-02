<?php
$adminTitle = "Manage Screens - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$selectedCinemaId = isset($_GET['cinema_id']) ? (int)$_GET['cinema_id'] : 0;
$search = trim($_GET['search'] ?? '');

$screens = [];
$cinemas = [];
$dbStatus = checkDBStatus();
$flashSuccess = getFlash('screen_success');
$flashError = getFlash('screen_error');

if ($dbStatus['status']) {
    try {
        $db = getDB();

$cinemaStmt = $db->query("SELECT id, name, city FROM cinemas ORDER BY name ASC");
        $cinemas = $cinemaStmt->fetchAll();

$sql = "SELECT s.*, c.name as cinema_name, c.city,
                (SELECT COUNT(*) FROM seats WHERE screen_id = s.id) as generated_seats_count
                FROM screens s
                JOIN cinemas c ON s.cinema_id = c.id
                WHERE 1=1";
        $params = [];

        if ($selectedCinemaId > 0) {
            $sql .= " AND s.cinema_id = ?";
            $params[] = $selectedCinemaId;
        }

        if (!empty($search)) {
            $sql .= " AND (s.screen_name LIKE ? OR c.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY c.name ASC, s.screen_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $screens = $stmt->fetchAll();
    } catch (Exception $e) {
        $flashError = ['message' => 'Error querying screens: ' . $e->getMessage(), 'type' => 'danger'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-lg-5 flex-grow-1">
    <div class="row g-4">
        <div class="col-lg-3 col-xl-2">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <div class="col-lg-9 col-xl-10">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-tv text-danger me-2"></i> Cinema Screens & Halls</h3>
                    <p class="text-secondary small mb-0">Manage auditoriums, 2D/3D/IMAX screen types, seating capacity, and seat layout maps</p>
                </div>
                <div>
                    <a href="<?= url('admin/screens/add.php' . ($selectedCinemaId > 0 ? '?cinema_id=' . $selectedCinemaId : '')) ?>" class="btn btn-cine-primary">
                        <i class="fa-solid fa-plus me-1"></i> Add New Screen
                    </a>
                </div>
            </div>

            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show small shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($flashSuccess['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="alert alert-danger alert-dismissible fade show small shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i> <?= htmlspecialchars($flashError['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="cine-card p-3 mb-4">
                <form action="<?= url('admin/screens/index.php') ?>" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="search" class="form-control bg-dark border-secondary text-light" placeholder="Search screen name..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <select name="cinema_id" class="form-select form-select-sm bg-dark border-secondary text-light" onchange="this.form.submit()">
                            <option value="">All Partner Cinemas</option>
                            <?php foreach ($cinemas as $cin): ?>
                                <option value="<?= $cin['id'] ?>" <?= $selectedCinemaId === (int)$cin['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cin['name']) ?> (<?= htmlspecialchars($cin['city']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Filter</button>
                        <?php if ($selectedCinemaId > 0 || !empty($search)): ?>
                            <a href="<?= url('admin/screens/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="cine-card p-4">
                <?php if (!empty($screens)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-secondary small border-secondary">
                                    <th>#</th>
                                    <th>Screen Name</th>
                                    <th>Cinema Branch</th>
                                    <th>Screen Type</th>
                                    <th>Seating Grid</th>
                                    <th>Capacity</th>
                                    <th>Configured Seats</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($screens as $s): ?>
                                    <tr>
                                        <td class="text-secondary small"><?= htmlspecialchars($s['id']) ?></td>
                                        <td>
                                            <strong class="text-white d-block"><?= htmlspecialchars($s['screen_name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="text-light small">
                                                <i class="fa-solid fa-video text-danger me-1"></i> <?= htmlspecialchars($s['cinema_name']) ?>
                                            </span>
                                            <span class="text-secondary small d-block"><?= htmlspecialchars($s['city']) ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $typeBadge = 'bg-secondary';
                                            if ($s['screen_type'] === 'IMAX') $typeBadge = 'bg-warning text-dark fw-bold';
                                            if ($s['screen_type'] === '3D') $typeBadge = 'bg-info text-dark fw-bold';
                                            if ($s['screen_type'] === '4DX') $typeBadge = 'bg-danger text-white fw-bold';
                                            ?>
                                            <span class="badge <?= $typeBadge ?>"><?= htmlspecialchars($s['screen_type']) ?></span>
                                        </td>
                                        <td class="small text-secondary">
                                            <?= htmlspecialchars($s['rows_count']) ?> Rows &times; <?= htmlspecialchars($s['cols_count']) ?> Cols
                                        </td>
                                        <td>
                                            <span class="badge bg-dark border border-secondary text-light px-2 py-1">
                                                <i class="fa-solid fa-chair text-danger me-1"></i> <?= htmlspecialchars($s['total_seats']) ?> Total Seats
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ((int)$s['generated_seats_count'] > 0): ?>
                                                <span class="badge bg-success-subtle text-success border border-success">
                                                    <i class="fa-solid fa-circle-check me-1"></i> <?= $s['generated_seats_count'] ?> Active Seats
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning">
                                                    Pending Generation
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('admin/screens/configure.php?id=' . $s['id']) ?>" class="btn btn-outline-info" title="Configure Seat Map">
                                                    <i class="fa-solid fa-chair"></i>
                                                </a>
                                                <a href="<?= url('admin/screens/edit.php?id=' . $s['id']) ?>" class="btn btn-outline-warning" title="Edit Screen Details">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="<?= url('admin/screens/delete.php?id=' . $s['id']) ?>" class="btn btn-outline-danger" title="Delete Screen" onclick="return confirm('Deleting \'<?= htmlspecialchars(addslashes($s['screen_name'])) ?>\' will delete all associated seats and show schedules. Continue?');">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-tv text-secondary fs-1 mb-3"></i>
                        <h5 class="text-white">No Screens Found</h5>
                        <p class="text-secondary small mb-3">Create cinema screens to configure auditoriums and seat layouts.</p>
                        <a href="<?= url('admin/screens/add.php') ?>" class="btn btn-cine-primary btn-sm">
                            <i class="fa-solid fa-plus me-1"></i> Add First Screen
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
