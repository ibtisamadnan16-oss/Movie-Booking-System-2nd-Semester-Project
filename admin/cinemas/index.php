<?php
$adminTitle = "Manage Cinemas - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$search = trim($_GET['search'] ?? '');
$cinemas = [];
$dbStatus = checkDBStatus();
$flashSuccess = getFlash('cinema_success');
$flashError = getFlash('cinema_error');

if ($dbStatus['status']) {
    try {
        $db = getDB();
        $sql = "SELECT c.*, COUNT(s.id) as screen_count 
                FROM cinemas c 
                LEFT JOIN screens s ON c.id = s.cinema_id 
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (c.name LIKE ? OR c.city LIKE ? OR c.location LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " GROUP BY c.id ORDER BY c.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $cinemas = $stmt->fetchAll();
    } catch (Exception $e) {
        $flashError = ['message' => 'Error querying cinemas: ' . $e->getMessage(), 'type' => 'danger'];
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
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-video text-danger me-2"></i> Cinema Theatres Management</h3>
                    <p class="text-secondary small mb-0">Manage partner cinemas, locations, branches, and contact information</p>
                </div>
                <div>
                    <a href="<?= url('admin/cinemas/add.php') ?>" class="btn btn-cine-primary">
                        <i class="fa-solid fa-plus me-1"></i> Add New Cinema
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
                <form action="<?= url('admin/cinemas/index.php') ?>" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-9 col-lg-8">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="search" class="form-control bg-dark border-secondary text-light" placeholder="Search by cinema name, city, location..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3 col-lg-4 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-outline-danger px-3">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= url('admin/cinemas/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="cine-card p-4">
                <?php if (!empty($cinemas)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-secondary small border-secondary">
                                    <th>#</th>
                                    <th>Cinema Name</th>
                                    <th>City / Location</th>
                                    <th>Full Address</th>
                                    <th>Contact Phone</th>
                                    <th>Email</th>
                                    <th>Active Screens</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cinemas as $c): ?>
                                    <tr>
                                        <td class="text-secondary small"><?= htmlspecialchars($c['id']) ?></td>
                                        <td>
                                            <strong class="text-white d-block"><?= htmlspecialchars($c['name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger border border-danger">
                                                <i class="fa-solid fa-city me-1"></i> <?= htmlspecialchars($c['city']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-secondary" style="max-width: 250px;">
                                            <?= htmlspecialchars($c['location']) ?>
                                        </td>
                                        <td>
                                            <span class="small text-light">
                                                <i class="fa-solid fa-phone me-1 text-danger"></i> <?= htmlspecialchars($c['contact_number']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-secondary">
                                            <?= htmlspecialchars($c['email'] ?: 'N/A') ?>
                                        </td>
                                        <td>
                                            <a href="<?= url('admin/screens/index.php?cinema_id=' . $c['id']) ?>" class="badge bg-dark border border-secondary text-info text-decoration-none py-2 px-3" title="Manage Screens in this Cinema">
                                                <i class="fa-solid fa-tv me-1"></i> <?= htmlspecialchars($c['screen_count']) ?> Screen(s)
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('admin/screens/add.php?cinema_id=' . $c['id']) ?>" class="btn btn-outline-success" title="Add Screen to Cinema">
                                                    <i class="fa-solid fa-plus"></i>
                                                </a>
                                                <a href="<?= url('admin/cinemas/edit.php?id=' . $c['id']) ?>" class="btn btn-outline-warning" title="Edit Cinema Details">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="<?= url('admin/cinemas/delete.php?id=' . $c['id']) ?>" class="btn btn-outline-danger" title="Delete Cinema" onclick="return confirm('Deleting \'<?= htmlspecialchars(addslashes($c['name'])) ?>\' will also remove all its screens, seats, and shows. Continue?');">
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
                        <i class="fa-solid fa-video text-secondary fs-1 mb-3"></i>
                        <h5 class="text-white">No Cinemas Registered Yet</h5>
                        <p class="text-secondary small mb-3">Add your first cinema branch to configure screens and show schedules.</p>
                        <a href="<?= url('admin/cinemas/add.php') ?>" class="btn btn-cine-primary btn-sm">
                            <i class="fa-solid fa-plus me-1"></i> Register New Cinema
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
