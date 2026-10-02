<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action !== 'process_payment') {
    redirect('movies.php');
}

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('booking_error', 'Database offline. Please try again.', 'danger');
    redirect('movies.php');
}

$db = getDB();
$userId = (int)$_SESSION['user_id'];

$bookingId         = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$showId            = isset($_POST['show_id']) ? (int)$_POST['show_id'] : 0;
$seatIdsRaw        = trim($_POST['selected_seat_ids'] ?? '');
$totalAmount       = (float)($_POST['total_amount'] ?? 0);
$rawPaymentMethod  = trim($_POST['payment_method'] ?? 'Debit/Credit Card');

$allowedMethods = ['Cash', 'Debit/Credit Card', 'EasyPaisa', 'JazzCash'];
$paymentMethod = in_array($rawPaymentMethod, $allowedMethods) ? $rawPaymentMethod : 'Debit/Credit Card';

$prefix = 'TXN-';
switch ($paymentMethod) {
    case 'EasyPaisa': $prefix .= 'EP-'; break;
    case 'JazzCash':  $prefix .= 'JC-'; break;
    case 'Cash':      $prefix .= 'CASH-'; break;
    default:          $prefix .= 'CARD-'; break;
}
$transactionId = $prefix . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)) . rand(10, 99);

try {
    if ($bookingId > 0) {
 
        $bStmt = $db->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
        $bStmt->execute([$bookingId, $userId]);
        $existingBooking = $bStmt->fetch();

        if (!$existingBooking) {
            setFlash('payment_error', 'Invalid booking record.', 'danger');
            redirect('user/bookings.php');
        }

        $db->beginTransaction();

$updBk = $db->prepare("UPDATE bookings SET booking_status = 'confirmed' WHERE id = ?");
        $updBk->execute([$bookingId]);

$insPay = $db->prepare("
            INSERT INTO payments (booking_id, user_id, payment_method, transaction_id, amount, payment_status, payment_date)
            VALUES (?, ?, ?, ?, ?, 'completed', NOW())
            ON DUPLICATE KEY UPDATE 
                payment_method = VALUES(payment_method),
                transaction_id = VALUES(transaction_id),
                payment_status = 'completed',
                payment_date = NOW()
        ");
        $insPay->execute([$bookingId, $userId, $paymentMethod, $transactionId, $existingBooking['total_amount']]);

        $db->commit();
        unset($_SESSION['pending_booking']);

        setFlash('booking_success', "Payment successful! Admission ticket generated with Reference: {$existingBooking['booking_code']}", 'success');
        redirect('booking-success.php?booking_id=' . $bookingId);

    } else {
 
        if ($showId <= 0 || empty($seatIdsRaw)) {
            setFlash('payment_error', 'Invalid booking submission.', 'danger');
            redirect('movies.php');
        }

        $seatIds = array_filter(array_map('intval', explode(',', $seatIdsRaw)));
        if (empty($seatIds) || count($seatIds) > 8) {
            setFlash('payment_error', 'Invalid seat count selected.', 'danger');
            redirect('user/select-seats.php?show_id=' . $showId);
        }

        $db->beginTransaction();

$showStmt = $db->prepare("SELECT * FROM shows WHERE id = ? AND status != 'cancelled' LIMIT 1 FOR UPDATE");
        $showStmt->execute([$showId]);
        $show = $showStmt->fetch();

        if (!$show) {
            $db->rollBack();
            setFlash('payment_error', 'The showtime is no longer active.', 'danger');
            redirect('movies.php');
        }

$inPlaceholders = implode(',', array_fill(0, count($seatIds), '?'));
        $conflictStmt = $db->prepare("
            SELECT bs.seat_id, s.seat_number 
            FROM booking_seats bs 
            JOIN seats s ON bs.seat_id = s.id 
            JOIN bookings b ON bs.booking_id = b.id 
            WHERE bs.show_id = ? AND bs.seat_id IN ($inPlaceholders) AND b.booking_status != 'cancelled'
        ");
        $conflictStmt->execute(array_merge([$showId], $seatIds));
        $conflicts = $conflictStmt->fetchAll();

        if (!empty($conflicts)) {
            $db->rollBack();
            $taken = array_column($conflicts, 'seat_number');
            setFlash('booking_error', 'Seat(s) ' . implode(', ', $taken) . ' were just booked by someone else. Please pick alternative seats.', 'danger');
            redirect('user/select-seats.php?show_id=' . $showId);
        }

$seatsStmt = $db->prepare("SELECT id, price_multiplier FROM seats WHERE id IN ($inPlaceholders) AND screen_id = ?");
        $seatsStmt->execute(array_merge($seatIds, [$show['screen_id']]));
        $validSeats = $seatsStmt->fetchAll();

        $basePrice = (float)$show['ticket_price'];
        $subtotal = 0;
        $seatPriceMap = [];
        foreach ($validSeats as $vs) {
            $p = round($basePrice * (float)($vs['price_multiplier'] ?? 1.0));
            $subtotal += $p;
            $seatPriceMap[$vs['id']] = $p;
        }
        $tax = round($subtotal * 0.05);
        $grandTotal = $subtotal + $tax;

$bookingCode = 'CP-BK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

$insBk = $db->prepare("
            INSERT INTO bookings (booking_code, user_id, show_id, total_seats, total_amount, booking_status, booking_date)
            VALUES (?, ?, ?, ?, ?, 'confirmed', NOW())
        ");
        $insBk->execute([$bookingCode, $userId, $showId, count($seatIds), $grandTotal]);
        $newBookingId = (int)$db->lastInsertId();

$insBs = $db->prepare("INSERT INTO booking_seats (booking_id, show_id, seat_id, seat_price) VALUES (?, ?, ?, ?)");
        foreach ($seatIds as $sid) {
            $insBs->execute([$newBookingId, $showId, $sid, $seatPriceMap[$sid] ?? $basePrice]);
        }

$insPay = $db->prepare("
            INSERT INTO payments (booking_id, user_id, payment_method, transaction_id, amount, payment_status, payment_date)
            VALUES (?, ?, ?, ?, ?, 'completed', NOW())
        ");
        $insPay->execute([$newBookingId, $userId, $paymentMethod, $transactionId, $grandTotal]);

        $db->commit();
        unset($_SESSION['pending_booking']);

        setFlash('booking_success', "Payment successful! Your tickets for booking {$bookingCode} have been generated.", 'success');
        redirect('booking-success.php?booking_id=' . $newBookingId);
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('payment_error', 'Payment transaction failed: ' . $e->getMessage(), 'danger');
    redirect('user/payment.php?' . (!empty($bookingId) ? 'booking_id=' . $bookingId : 'show_id=' . $showId . '&seat_ids=' . urlencode($seatIdsRaw)));
}
