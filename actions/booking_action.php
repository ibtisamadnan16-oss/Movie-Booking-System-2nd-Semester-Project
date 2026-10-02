<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create_booking':
        $showId = isset($_POST['show_id']) ? (int)$_POST['show_id'] : 0;
        $seatIdsRaw = trim($_POST['selected_seat_ids'] ?? '');
        $seatNumbersRaw = trim($_POST['selected_seat_numbers'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'Debit/Credit Card');

        if ($showId <= 0 || empty($seatIdsRaw)) {
            setFlash('booking_error', 'Invalid booking request. Please select your seats.', 'danger');
            redirect('movies.php');
        }

if (!isLoggedIn()) {
            $_SESSION['pending_booking'] = [
                'show_id' => $showId,
                'seat_ids' => $seatIdsRaw,
                'seat_numbers' => $seatNumbersRaw
            ];
            $_SESSION['redirect_url'] = 'user/select-seats.php?show_id=' . $showId;
            setFlash('auth_error', 'Please log in to confirm your seat reservation.', 'warning');
            redirect('login.php');
        }

        $userId = (int)$_SESSION['user_id'];
        $seatIds = array_filter(array_map('intval', explode(',', $seatIdsRaw)));

        if (empty($seatIds)) {
            setFlash('booking_error', 'Please select at least 1 seat to book.', 'warning');
            redirect('user/select-seats.php?show_id=' . $showId);
        }

        if (count($seatIds) > 8) {
            setFlash('booking_error', 'You cannot book more than 8 seats in a single reservation.', 'danger');
            redirect('user/select-seats.php?show_id=' . $showId);
        }

        $dbStatus = checkDBStatus();
        if (!$dbStatus['status']) {
            setFlash('booking_error', 'Database offline. Please try again in a few moments.', 'danger');
            redirect('user/select-seats.php?show_id=' . $showId);
        }

        $db = getDB();

        try {
 
            $db->beginTransaction();

$showStmt = $db->prepare("
                SELECT s.*, scr.id as scr_id, scr.total_seats 
                FROM shows s 
                JOIN screens scr ON s.screen_id = scr.id 
                WHERE s.id = ? AND s.status != 'cancelled' 
                LIMIT 1 FOR UPDATE
            ");
            $showStmt->execute([$showId]);
            $show = $showStmt->fetch();

            if (!$show) {
                $db->rollBack();
                setFlash('booking_error', 'Invalid Show: The selected showtime is no longer active or does not exist.', 'danger');
                redirect('movies.php');
            }

            $showTime = strtotime($show['show_date'] . ' ' . $show['start_time']);
            if (time() > $showTime) {
                $db->rollBack();
                setFlash('booking_error', 'Invalid Show: This movie screening has already commenced or ended.', 'danger');
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
                $takenSeats = array_column($conflicts, 'seat_number');
                setFlash('booking_error', 'Seat(s) ' . implode(', ', $takenSeats) . ' were just booked by another customer. Please choose different seats.', 'danger');
                redirect('user/select-seats.php?show_id=' . $showId);
            }

$seatsStmt = $db->prepare("
                SELECT id, seat_number, price_multiplier, status 
                FROM seats 
                WHERE id IN ($inPlaceholders) AND screen_id = ?
            ");
            $seatsStmt->execute(array_merge($seatIds, [$show['screen_id']]));
            $validSeats = $seatsStmt->fetchAll();

            if (count($validSeats) !== count($seatIds)) {
                $db->rollBack();
                setFlash('booking_error', 'One or more selected seats are invalid for this auditorium.', 'danger');
                redirect('user/select-seats.php?show_id=' . $showId);
            }

            $basePrice = (float)$show['ticket_price'];
            $subtotal = 0;
            $seatPriceMap = [];

            foreach ($validSeats as $vSeat) {
                if ($vSeat['status'] === 'under_maintenance') {
                    $db->rollBack();
                    setFlash('booking_error', "Seat {$vSeat['seat_number']} is currently under maintenance.", 'warning');
                    redirect('user/select-seats.php?show_id=' . $showId);
                }
                $multiplier = (float)($vSeat['price_multiplier'] ?? 1.0);
                $calculatedPrice = round($basePrice * $multiplier);
                $subtotal += $calculatedPrice;
                $seatPriceMap[$vSeat['id']] = $calculatedPrice;
            }

            $tax = round($subtotal * 0.05); 
            $grandTotal = $subtotal + $tax;

$bookingCode = 'CP-BK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

$insBooking = $db->prepare("
                INSERT INTO bookings (booking_code, user_id, show_id, total_seats, total_amount, booking_status, booking_date)
                VALUES (?, ?, ?, ?, ?, 'confirmed', NOW())
            ");
            $insBooking->execute([$bookingCode, $userId, $showId, count($seatIds), $grandTotal]);
            $bookingId = (int)$db->lastInsertId();

$insBSeat = $db->prepare("
                INSERT INTO booking_seats (booking_id, show_id, seat_id, seat_price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($seatIds as $sid) {
                $seatPrice = $seatPriceMap[$sid] ?? $basePrice;
                $insBSeat->execute([$bookingId, $showId, $sid, $seatPrice]);
            }

$txId = 'TXN-' . strtoupper(uniqid()) . rand(100, 999);
            $insPayment = $db->prepare("
                INSERT INTO payments (booking_id, user_id, payment_method, transaction_id, amount, payment_status, payment_date)
                VALUES (?, ?, ?, ?, ?, 'completed', NOW())
            ");
            $insPayment->execute([$bookingId, $userId, $paymentMethod, $txId, $grandTotal]);

$db->commit();

unset($_SESSION['pending_booking']);

            setFlash('booking_success', "Booking confirmed successfully! Your tickets for reference {$bookingCode} are ready.", 'success');
            redirect('booking-success.php?booking_id=' . $bookingId);

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlash('booking_error', 'Booking transaction failed: ' . $e->getMessage(), 'danger');
            redirect('user/select-seats.php?show_id=' . $showId);
        }
        break;

    case 'cancel_booking':
        if (!isLoggedIn()) {
            setFlash('auth_error', 'Please log in to manage your bookings.', 'warning');
            redirect('login.php');
        }

        $userId = (int)$_SESSION['user_id'];
        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : (isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0);
        $reason = trim($_POST['cancellation_reason'] ?? 'User requested cancellation');

        if ($bookingId <= 0) {
            setFlash('booking_error', 'Invalid booking identifier.', 'danger');
            redirect('my-bookings.php');
        }

        $dbStatus = checkDBStatus();
        if (!$dbStatus['status']) {
            setFlash('booking_error', 'Database offline. Please try again later.', 'danger');
            redirect('my-bookings.php');
        }

        $db = getDB();

        try {
 
            $stmt = $db->prepare("
                SELECT b.*, s.show_date, s.start_time, m.title as movie_title 
                FROM bookings b 
                JOIN shows s ON b.show_id = s.id 
                JOIN movies m ON s.movie_id = m.id 
                WHERE b.id = ? AND b.user_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$bookingId, $userId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                setFlash('booking_error', 'Booking record not found or access denied.', 'danger');
                redirect('my-bookings.php');
            }

            if ($booking['booking_status'] === 'cancelled') {
                setFlash('booking_error', 'This booking has already been cancelled.', 'info');
                redirect('my-bookings.php');
            }

$showDateTime = strtotime($booking['show_date'] . ' ' . $booking['start_time']);
            if (time() > $showDateTime) {
                setFlash('booking_error', 'Cannot cancel past or completed movie shows.', 'danger');
                redirect('my-bookings.php');
            }

$db->beginTransaction();

            $updBk = $db->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
            $updBk->execute([$bookingId]);

            $updPay = $db->prepare("UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ?");
            $updPay->execute([$bookingId]);

            $db->commit();

            setFlash('booking_success', "Booking #{$booking['booking_code']} for '{$booking['movie_title']}' has been cancelled successfully. Your seats have been released and refund initiated.", 'info');
            redirect('my-bookings.php');

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlash('booking_error', 'Failed to cancel booking: ' . $e->getMessage(), 'danger');
            redirect('my-bookings.php');
        }
        break;

    case 'admin_confirm_booking':
        requireAdmin();
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        if ($bookingId <= 0) {
            setFlash('admin_error', 'Invalid booking ID.', 'danger');
            redirect('admin/bookings.php');
        }
        $dbStatus = checkDBStatus();
        if (!$dbStatus['status']) {
            setFlash('admin_error', 'Database offline. Please try again.', 'danger');
            redirect('admin/bookings.php');
        }
        try {
            $db = getDB();
            $db->beginTransaction();
            $updBk = $db->prepare("UPDATE bookings SET booking_status = 'confirmed' WHERE id = ?");
            $updBk->execute([$bookingId]);

            $updPay = $db->prepare("UPDATE payments SET payment_status = 'completed' WHERE booking_id = ?");
            $updPay->execute([$bookingId]);
            $db->commit();

            setFlash('admin_success', "Booking #{$bookingId} has been confirmed successfully.", 'success');
            redirect('admin/bookings.php');
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            setFlash('admin_error', 'Failed to confirm booking: ' . $e->getMessage(), 'danger');
            redirect('admin/bookings.php');
        }
        break;

    case 'admin_cancel_booking':
        requireAdmin();
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        if ($bookingId <= 0) {
            setFlash('admin_error', 'Invalid booking ID.', 'danger');
            redirect('admin/bookings.php');
        }
        $dbStatus = checkDBStatus();
        if (!$dbStatus['status']) {
            setFlash('admin_error', 'Database offline. Please try again.', 'danger');
            redirect('admin/bookings.php');
        }
        try {
            $db = getDB();
            $db->beginTransaction();
            $updBk = $db->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
            $updBk->execute([$bookingId]);

            $updPay = $db->prepare("UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ?");
            $updPay->execute([$bookingId]);
            $db->commit();

            setFlash('admin_success', "Booking #{$bookingId} has been cancelled and seats released back to auditorium inventory.", 'info');
            redirect('admin/bookings.php');
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            setFlash('admin_error', 'Failed to cancel booking: ' . $e->getMessage(), 'danger');
            redirect('admin/bookings.php');
        }
        break;

    default:
        redirect('index.php');
        break;
}
