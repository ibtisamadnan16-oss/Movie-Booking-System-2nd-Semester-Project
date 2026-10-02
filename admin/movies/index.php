<?php
$adminTitle = "Manage Movies - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$movies = [];
$dbStatus = checkDBStatus();
$flashSuccess = getFlash('movie_success');
$flashError = getFlash('movie_error');

if ($dbStatus['status']) {
    try {
        $db = getDB();
        $sql = "SELECT * FROM movies WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR genre LIKE ? OR language LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($statusFilter)) {
            $sql .= " AND status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $movies = $stmt->fetchAll();
    } catch (Exception $e) {
        $flashError = ['message' => 'Error querying movies: ' . $e->getMessage(), 'type' => 'danger'];
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
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-film text-danger me-2"></i> Movies Catalog</h3>
                    <p class="text-secondary small mb-0">Add, update, view, and manage movie listings & show schedules</p>
                </div>
                <div>
                    <a href="<?= url('admin/movies/add.php') ?>" class="btn btn-cine-primary">
                        <i class="fa-solid fa-plus me-1"></i> Add New Movie
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
                <form action="<?= url('admin/movies/index.php') ?>" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="search" class="form-control bg-dark border-secondary text-light" placeholder="Search by title, genre, language..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <select name="status" class="form-select form-select-sm bg-dark border-secondary text-light">
                            <option value="">All Statuses</option>
                            <option value="now_showing" <?= $statusFilter === 'now_showing' ? 'selected' : '' ?>>Now Showing</option>
                            <option value="upcoming" <?= $statusFilter === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="archived" <?= $statusFilter === 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Filter</button>
                        <?php if (!empty($search) || !empty($statusFilter)): ?>
                            <a href="<?= url('admin/movies/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="cine-card p-4">
                <?php if (!empty($movies)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-secondary small border-secondary">
                                    <th>#</th>
                                    <th>Poster</th>
                                    <th>Movie Title</th>
                                    <th>Genre</th>
                                    <th>Duration</th>
                                    <th>Language</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Release Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movies as $m): ?>
                                    <tr>
                                        <td class="text-secondary small"><?= htmlspecialchars($m['id']) ?></td>
                                        <td style="width: 55px;">
                                            <img src="<?= getMoviePoster($m['poster_image'] ?? '', $m['title']) ?>" alt="<?= htmlspecialchars($m['title']) ?>" class="rounded" style="width: 45px; height: 60px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <strong class="text-white d-block"><?= htmlspecialchars($m['title']) ?></strong>
                                            <?php if (!empty($m['trailer_url'])): ?>
                                                <a href="<?= htmlspecialchars($m['trailer_url']) ?>" target="_blank" class="text-danger small text-decoration-none">
                                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Trailer
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($m['genre']) ?></span></td>
                                        <td class="small text-secondary"><?= htmlspecialchars($m['duration_minutes']) ?> mins</td>
                                        <td class="small text-secondary"><?= htmlspecialchars($m['language'] ?? 'English') ?></td>
                                        <td>
                                            <span class="text-warning small fw-bold">
                                                <i class="fa-solid fa-star me-1"></i><?= htmlspecialchars($m['rating']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($m['status'] === 'now_showing'): ?>
                                                <span class="badge bg-success">NOW SHOWING</span>
                                            <?php elseif ($m['status'] === 'upcoming'): ?>
                                                <span class="badge bg-warning text-dark">UPCOMING</span>
                                            <?php else: ?>
                                                <span class="badge bg-dark border border-secondary text-secondary">ARCHIVED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-secondary"><?= date('d M Y', strtotime($m['release_date'])) ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('movie-details.php?id=' . $m['id']) ?>" target="_blank" class="btn btn-outline-info" title="View in User Portal">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="<?= url('admin/movies/edit.php?id=' . $m['id']) ?>" class="btn btn-outline-warning" title="Edit Movie">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="<?= url('admin/movies/delete.php?id=' . $m['id']) ?>" class="btn btn-outline-danger" title="Delete Movie" onclick="return confirm('Are you sure you want to delete \'<?= htmlspecialchars(addslashes($m['title'])) ?>\'? This action cannot be undone.');">
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
                        <i class="fa-solid fa-film text-secondary fs-1 mb-3"></i>
                        <h5 class="text-white">No Movies Found</h5>
                        <p class="text-secondary small mb-3">There are no movie records matching your search criteria.</p>
                        <a href="<?= url('admin/movies/add.php') ?>" class="btn btn-cine-primary btn-sm">
                            <i class="fa-solid fa-plus me-1"></i> Add First Movie
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
