<?php
 
$pageTitle = "Select Seats - Cinema Seating System";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('system_error', 'Database offline. Please start MySQL in XAMPP.', 'danger');
    redirect('movies.php');
}

$db = getDB();

$showId = isset($_GET['show_id']) ? (int)$_GET['show_id'] : 0;
if ($showId <= 0) {
 
    setFlash('booking_error', 'Please select a movie showtime to choose your seats.', 'warning');
    redirect('movies.php');
}

$showStmt = $db->prepare("
    SELECT 
        s.*,
        m.title AS movie_title,
        m.genre,
        m.duration_minutes,
        m.rating,
        m.poster_image,
        m.language,
        c.name AS cinema_name,
        c.city AS cinema_city,
        c.location AS cinema_location,
        scr.screen_name,
        scr.screen_type,
        scr.total_seats,
        scr.rows_count,
        scr.cols_count
    FROM shows s
    JOIN movies m ON s.movie_id = m.id
    JOIN cinemas c ON s.cinema_id = c.id
    JOIN screens scr ON s.screen_id = scr.id
    WHERE s.id = ? LIMIT 1
");
$showStmt->execute([$showId]);
$show = $showStmt->fetch();

if (!$show) {
    setFlash('booking_error', 'Invalid Show: The requested movie showtime was not found or is no longer available.', 'danger');
    redirect('movies.php');
}

$showTimestamp = strtotime($show['show_date'] . ' ' . $show['start_time']);
if (time() > $showTimestamp) {
    setFlash('booking_error', 'Invalid Show: This screening has already commenced or concluded. Online bookings are closed.', 'danger');
    redirect('movies.php');
}

$screenId  = (int)$show['screen_id'];
$basePrice = (float)$show['ticket_price'];

$seatStmt = $db->prepare("
    SELECT * 
    FROM seats 
    WHERE screen_id = ? 
    ORDER BY seat_row ASC, seat_column ASC
");
$seatStmt->execute([$screenId]);
$allSeats = $seatStmt->fetchAll();

$bookedStmt = $db->prepare("
    SELECT bs.seat_id 
    FROM booking_seats bs 
    JOIN bookings b ON bs.booking_id = b.id 
    WHERE bs.show_id = ? AND b.booking_status != 'cancelled'
");
$bookedStmt->execute([$showId]);
$bookedSeatIds = $bookedStmt->fetchAll(PDO::FETCH_COLUMN);

$groupedSeats = [];
$totalCapacity = count($allSeats);
$bookedCount   = count($bookedSeatIds);
$availableCount = max(0, $totalCapacity - $bookedCount);

foreach ($allSeats as $seat) {
    $groupedSeats[$seat['seat_row']][] = $seat;
}

$flashError = getFlash('booking_error');
$flashSuccess = getFlash('booking_success');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-4 my-lg-5 flex-grow-1">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i>Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('movies.php') ?>" class="text-secondary text-decoration-none">Movies</a></li>
            <li class="breadcrumb-item"><a href="<?= url('movie-details.php?id=' . $show['movie_id']) ?>" class="text-secondary text-decoration-none"><?= htmlspecialchars($show['movie_title']) ?></a></li>
            <li class="breadcrumb-item active text-danger fw-bold" aria-current="page">Select Seats</li>
        </ol>
    </nav>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($flashError['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($flashSuccess['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="cine-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <?php if (!empty($show['poster_image'])): ?>
                    <img src="<?= url('assets/images/' . $show['poster_image']) ?>" 
                         alt="<?= htmlspecialchars($show['movie_title']) ?>" 
                         class="rounded shadow" 
                         style="width: 70px; height: 95px; object-fit: cover; border: 1px solid var(--cine-card-border);"
                         onerror="this.style.display='none'">
                <?php endif; ?>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge bg-danger-subtle text-danger border border-danger small">
                        <?= htmlspecialchars($show['screen_type']) ?> Projection
                    </span>
                    <span class="badge bg-dark border border-secondary text-light small">
                        <i class="fa-regular fa-clock me-1 text-danger"></i> <?= htmlspecialchars($show['duration_minutes']) ?> Mins
                    </span>
                    <span class="badge bg-warning-subtle text-warning border border-warning small">
                        <i class="fa-solid fa-star me-1"></i> <?= htmlspecialchars($show['rating']) ?>/10
                    </span>
                </div>
                <h3 class="text-white fw-bold mb-1"><?= htmlspecialchars($show['movie_title']) ?></h3>
                <p class="text-secondary small mb-0">
                    <i class="fa-solid fa-video text-danger me-1"></i> <strong><?= htmlspecialchars($show['cinema_name']) ?></strong> &bull; <?= htmlspecialchars($show['screen_name']) ?> (<?= htmlspecialchars($show['cinema_city']) ?>)
                </p>
            </div>
            <div class="col-md-auto text-md-end">
                <div class="p-2 rounded bg-dark border border-secondary d-inline-block text-start text-md-end">
                    <div class="text-secondary small">Showtime Schedule</div>
                    <div class="text-white fw-bold fs-6">
                        <i class="fa-solid fa-calendar-day text-danger me-1"></i> <?= date('D, d M Y', strtotime($show['show_date'])) ?>
                    </div>
                    <div class="text-danger fw-bold fs-5">
                        <i class="fa-regular fa-clock me-1"></i> <?= date('h:i A', strtotime($show['start_time'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="seat-grid-wrapper text-center shadow-lg">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 px-2">
                    <div class="text-start">
                        <h5 class="text-white fw-bold mb-0">
                            <i class="fa-solid fa-chair text-danger me-2"></i> Cinema Hall Seating Layout
                        </h5>
                        <small class="text-secondary">Click on any available seat to select or unselect (Max 8 seats per order)</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-dark border border-secondary text-light px-3 py-2">
                            <span class="text-success fw-bold"><?= $availableCount ?></span> Available / <span class="text-secondary"><?= $totalCapacity ?></span> Total
                        </span>
                    </div>
                </div>

                <div class="cinema-screen-container mx-auto" style="max-width: 580px;">
                    <div class="cinema-screen-curved">
                        <span class="cinema-screen-label">
                            <i class="fa-solid fa-film me-2 text-danger"></i> ALL EYES THIS WAY &bull; CINEMA SCREEN
                        </span>
                    </div>
                    <div class="d-flex justify-content-center mt-2">
                        <span class="small text-secondary opacity-75">
                            <i class="fa-solid fa-angles-up me-1 text-danger"></i> FRONT OF AUDITORIUM <i class="fa-solid fa-angles-up ms-1 text-danger"></i>
                        </span>
                    </div>
                </div>

                <div class="py-2" style="min-width: 320px;">
                    <?php if (!empty($groupedSeats)): ?>
                        <?php foreach ($groupedSeats as $rowName => $seatsInRow): ?>
                            <div class="seat-row-wrap" data-row="<?= htmlspecialchars($rowName) ?>">
                                <span class="seat-row-label" title="Row <?= htmlspecialchars($rowName) ?>">
                                    <?= htmlspecialchars($rowName) ?>
                                </span>

                                <div class="seat-row-seats">
                                    <?php foreach ($seatsInRow as $seat): ?>
                                        <?php 
                                        $seatId = (int)$seat['id'];
                                        $seatNum = htmlspecialchars($seat['seat_number']);
                                        $seatTier = htmlspecialchars($seat['seat_type']);
                                        $multiplier = (float)($seat['price_multiplier'] ?? 1.00);
                                        $seatCalculatedPrice = round($basePrice * $multiplier);

$isBooked = in_array($seatId, $bookedSeatIds);
                                        $isMaintenance = ($seat['status'] === 'under_maintenance');

                                        if ($isBooked) {
                                            $statusClass = 'status-booked';
                                            $statusText = 'Booked / Sold Out';
                                            $clickable = false;
                                        } elseif ($isMaintenance) {
                                            $statusClass = 'status-maintenance';
                                            $statusText = 'Under Maintenance';
                                            $clickable = false;
                                        } else {
                                            $statusClass = 'status-available';
                                            $statusText = 'Available';
                                            $clickable = true;
                                        }

                                        $tierClass = 'tier-' . strtolower($seatTier);
                                        ?>
                                        <button type="button" 
                                                class="seat-item <?= $statusClass ?> <?= $tierClass ?>"
                                                data-seat-id="<?= $seatId ?>"
                                                data-seat-number="<?= $seatNum ?>"
                                                data-seat-tier="<?= $seatTier ?>"
                                                data-seat-price="<?= $seatCalculatedPrice ?>"
                                                data-status="<?= $isBooked ? 'booked' : ($isMaintenance ? 'maintenance' : 'available') ?>"
                                                title="Seat <?= $seatNum ?> &bull; <?= $seatTier ?> Tier (Rs. <?= number_format($seatCalculatedPrice) ?>) &bull; <?= $statusText ?>"
                                                <?= !$clickable ? 'disabled' : '' ?>>
                                            <span><?= $seatNum ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <span class="seat-row-label" title="Row <?= htmlspecialchars($rowName) ?>">
                                    <?= htmlspecialchars($rowName) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-5 text-center text-secondary">
                            <i class="fa-solid fa-chair fs-2 mb-3 d-block"></i>
                            <p>No seats configured for this screen auditorium.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-center flex-wrap gap-4 mt-4 pt-4 border-top border-secondary small text-secondary">
                    <div class="d-flex align-items-center gap-2">
                        <span class="seat-legend-box" style="background: #171923; border: 1.5px solid #2d3142;"></span>
                        <span class="text-light">Available</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="seat-legend-box" style="background: #22c55e; border: 1.5px solid #16a34a; box-shadow: 0 0 8px rgba(34,197,94,0.6);"></span>
                        <span class="text-success fw-bold">Selected</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="seat-legend-box" style="background: #1e1215; border: 1.5px solid #3b1b21;"></span>
                        <span class="text-danger">Booked / Sold Out</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="seat-legend-box position-relative" style="background: #171923; border: 1.5px solid #2d3142;">
                            <span style="position: absolute; top: 1px; right: 1px; width: 5px; height: 5px; background: #f59e0b; border-radius: 50%;"></span>
                        </span>
                        <span>VIP (1.3x)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="seat-legend-box position-relative" style="background: #171923; border: 1.5px solid #2d3142;">
                            <span style="position: absolute; top: 1px; right: 1px; width: 5px; height: 5px; background: #06b6d4; border-radius: 50%;"></span>
                        </span>
                        <span>Premium (1.15x)</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="booking-summary-card p-4 shadow-lg">
                <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom border-secondary">
                    <h5 class="text-white fw-bold mb-0">
                        <i class="fa-solid fa-receipt text-danger me-2"></i> Booking Summary
                    </h5>
                    <button type="button" id="btnClearSeats" class="btn btn-sm btn-outline-secondary py-1 px-2" style="display: none;">
                        <i class="fa-solid fa-rotate-left me-1"></i> Clear
                    </button>
                </div>

                <div class="mb-3">
                    <div class="text-light fw-bold mb-1"><?= htmlspecialchars($show['movie_title']) ?></div>
                    <div class="text-secondary small mb-1">
                        <i class="fa-solid fa-location-dot text-danger me-1"></i> <?= htmlspecialchars($show['cinema_name']) ?>
                    </div>
                    <div class="text-secondary small mb-1">
                        <i class="fa-solid fa-tv text-danger me-1"></i> <?= htmlspecialchars($show['screen_name']) ?> &bull; <?= htmlspecialchars($show['screen_type']) ?>
                    </div>
                    <div class="text-secondary small">
                        <i class="fa-regular fa-clock text-danger me-1"></i> <?= date('D, d M', strtotime($show['show_date'])) ?> &bull; <?= date('h:i A', strtotime($show['start_time'])) ?>
                    </div>
                </div>

                <hr class="border-secondary my-3">

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary small fw-semibold">Selected Seats (<span id="seatCountBadge">0</span>/8):</span>
                        <small class="text-muted" id="seatLimitNotice">Max 8 tickets</small>
                    </div>

                    <div id="selectedSeatsList" class="d-flex flex-wrap gap-2 py-2 min-vh-20">
                        <span class="text-secondary small fst-italic" id="noSeatsPlaceholder">
                            <i class="fa-solid fa-arrow-pointer me-1"></i> Please tap seats on the layout to choose.
                        </span>
                    </div>
                </div>

                <hr class="border-secondary my-3">

                <div class="d-flex flex-column gap-2 mb-3 small">
                    <div class="d-flex justify-content-between text-secondary">
                        <span>Base Ticket Price:</span>
                        <span class="text-light fw-semibold">Rs. <?= number_format($basePrice, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-secondary">
                        <span>Tickets Quantity:</span>
                        <span class="text-light fw-semibold" id="ticketQuantityText">0 Tickets</span>
                    </div>
                    <div class="d-flex justify-content-between text-secondary">
                        <span>Seat Multipliers & Upgrades:</span>
                        <span class="text-light fw-semibold" id="upgradeSubtotalText">Rs. 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between text-secondary">
                        <span>Cinema Service Tax (5%):</span>
                        <span class="text-light fw-semibold" id="taxText">Rs. 0.00</span>
                    </div>
                </div>

                <hr class="border-secondary my-3">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <div class="text-secondary small text-uppercase fw-bold">Grand Total Amount</div>
                        <div class="text-success fw-bold fs-4" id="grandTotalDisplay">Rs. 0</div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1 small">
                        PKR Currency
                    </span>
                </div>

                <form id="seatBookingForm" method="POST" action="<?= url('booking-summary.php') ?>">
                    <input type="hidden" name="show_id" value="<?= $showId ?>">
                    <input type="hidden" name="selected_seat_ids" id="hiddenSeatIds" value="">
                    <input type="hidden" name="selected_seat_numbers" id="hiddenSeatNumbers" value="">
                    <input type="hidden" name="total_amount" id="hiddenTotalAmount" value="0">

                    <button type="submit" id="btnProceedBooking" class="btn btn-cine-primary w-100 py-2 fw-bold" disabled>
                        <i class="fa-solid fa-receipt me-2"></i> Proceed to Booking Summary &rarr;
                    </button>
                    <?php if (!isLoggedIn()): ?>
                        <small class="text-muted d-block text-center mt-2" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-info-circle me-1"></i> You can select seats now and review summary. You'll sign in before final confirmation.
                        </small>
                    <?php endif; ?>
                </form>

                <div class="text-center mt-3">
                    <small class="text-secondary" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> Double-booking protection active. Seats held upon confirmation.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const MAX_SEATS = 8;
    const baseTicketPrice = <?= (float)$basePrice ?>;
    
    // State of chosen seats: Map of seatId -> { id, number, tier, price }
    const selectedSeats = new Map();

    const seatButtons = document.querySelectorAll('.seat-item.status-available');
    const selectedSeatsList = document.getElementById('selectedSeatsList');
    const noSeatsPlaceholder = document.getElementById('noSeatsPlaceholder');
    const seatCountBadge = document.getElementById('seatCountBadge');
    const ticketQuantityText = document.getElementById('ticketQuantityText');
    const upgradeSubtotalText = document.getElementById('upgradeSubtotalText');
    const taxText = document.getElementById('taxText');
    const grandTotalDisplay = document.getElementById('grandTotalDisplay');
    const btnProceedBooking = document.getElementById('btnProceedBooking');
    const btnClearSeats = document.getElementById('btnClearSeats');

    // Auto-restore any pending seats preserved from login
    <?php
    $restoredIds = [];
    if (!empty($_SESSION['pending_booking']) && (int)($_SESSION['pending_booking']['show_id'] ?? 0) === $showId) {
        $restoredIds = array_filter(array_map('intval', explode(',', $_SESSION['pending_booking']['seat_ids'] ?? '')));
    }
    ?>
    const restoredSeatIds = <?= json_encode($restoredIds) ?>;

    // Hidden Form Inputs
    const hiddenSeatIds = document.getElementById('hiddenSeatIds');
    const hiddenSeatNumbers = document.getElementById('hiddenSeatNumbers');
    const hiddenTotalAmount = document.getElementById('hiddenTotalAmount');
    const seatBookingForm = document.getElementById('seatBookingForm');

    // Handle Seat Click
    seatButtons.forEach(button => {
        button.addEventListener('click', function () {
            const seatId = this.getAttribute('data-seat-id');
            const seatNumber = this.getAttribute('data-seat-number');
            const seatTier = this.getAttribute('data-seat-tier');
            const seatPrice = parseFloat(this.getAttribute('data-seat-price')) || baseTicketPrice;

            if (selectedSeats.has(seatId)) {
                // Deselect seat
                deselectSeat(seatId, this);
            } else {
                // Select seat
                if (selectedSeats.size >= MAX_SEATS) {
                    alert('Maximum ' + MAX_SEATS + ' seats can be selected per booking reservation.');
                    return;
                }
                selectSeat(seatId, seatNumber, seatTier, seatPrice, this);
            }

            renderSummary();
        });
    });

    function selectSeat(id, number, tier, price, element) {
        selectedSeats.set(id, { id, number, tier, price });
        element.classList.remove('status-available');
        element.classList.add('status-selected');
    }

    function deselectSeat(id, element) {
        selectedSeats.delete(id);
        if (element) {
            element.classList.remove('status-selected');
            element.classList.add('status-available');
        } else {
            // Find element by data-seat-id
            const btn = document.querySelector(`.seat-item[data-seat-id="${id}"]`);
            if (btn) {
                btn.classList.remove('status-selected');
                btn.classList.add('status-available');
            }
        }
    }

    function renderSummary() {
        const count = selectedSeats.size;
        seatCountBadge.textContent = count;
        ticketQuantityText.textContent = count + (count === 1 ? ' Ticket' : ' Tickets');

        if (count === 0) {
            noSeatsPlaceholder.style.display = 'inline';
            selectedSeatsList.innerHTML = '';
            selectedSeatsList.appendChild(noSeatsPlaceholder);
            btnClearSeats.style.display = 'none';

            upgradeSubtotalText.textContent = 'Rs. 0.00';
            taxText.textContent = 'Rs. 0.00';
            grandTotalDisplay.textContent = 'Rs. 0';
            btnProceedBooking.disabled = true;

            hiddenSeatIds.value = '';
            hiddenSeatNumbers.value = '';
            hiddenTotalAmount.value = '0';
            return;
        }

        noSeatsPlaceholder.style.display = 'none';
        btnClearSeats.style.display = 'inline-block';
        btnProceedBooking.disabled = false;

        // Render chips
        selectedSeatsList.innerHTML = '';
        let subtotal = 0;
        const seatNumbersArray = [];
        const seatIdsArray = [];

        selectedSeats.forEach(seat => {
            subtotal += seat.price;
            seatNumbersArray.push(seat.number);
            seatIdsArray.push(seat.id);

            const chip = document.createElement('span');
            chip.className = 'selected-seat-chip animate__animated animate__fadeIn';
            chip.innerHTML = `
                <span>${seat.number} <small class="opacity-75">(${seat.tier})</small></span>
                <i class="fa-solid fa-xmark text-danger" style="cursor: pointer;" title="Remove seat"></i>
            `;
            chip.querySelector('.fa-xmark').addEventListener('click', function (e) {
                e.stopPropagation();
                deselectSeat(seat.id);
                renderSummary();
            });
            selectedSeatsList.appendChild(chip);
        });

        // 5% cinema service tax
        const tax = Math.round(subtotal * 0.05);
        const grandTotal = subtotal + tax;

        upgradeSubtotalText.textContent = 'Rs. ' + subtotal.toLocaleString();
        taxText.textContent = 'Rs. ' + tax.toLocaleString();
        grandTotalDisplay.textContent = 'Rs. ' + grandTotal.toLocaleString();

        // Update hidden form fields
        hiddenSeatIds.value = seatIdsArray.join(',');
        hiddenSeatNumbers.value = seatNumbersArray.join(', ');
        hiddenTotalAmount.value = grandTotal;
    }

    // Clear all selected seats button
    if (btnClearSeats) {
        btnClearSeats.addEventListener('click', function () {
            selectedSeats.forEach((_, id) => {
                const btn = document.querySelector(`.seat-item[data-seat-id="${id}"]`);
                if (btn) {
                    btn.classList.remove('status-selected');
                    btn.classList.add('status-available');
                }
            });
            selectedSeats.clear();
            renderSummary();
        });
    }

    // Form submit validation
    seatBookingForm.addEventListener('submit', function (e) {
        if (selectedSeats.size === 0) {
            e.preventDefault();
            alert('Please select at least 1 seat to continue.');
            return false;
        }
    });

    // Auto-select restored seats if returning from login
    if (Array.isArray(restoredSeatIds) && restoredSeatIds.length > 0) {
        restoredSeatIds.forEach(id => {
            const btn = document.querySelector(`.seat-item.status-available[data-seat-id="${id}"]`);
            if (btn) btn.click();
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
