<?php
require '../includes/init.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $trip = filter_input(INPUT_GET, 'trip', FILTER_VALIDATE_INT);
    if (!$trip || !Bus::trip($trip)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid trip.', 'taken' => []]);
        exit;
    }

    Booking::releaseExpired();
    echo json_encode([
        'success' => true,
        'trip_id' => (int)$trip,
        'taken' => Seat::takenIds((int)$trip),
        'checked_at' => date(DATE_ATOM)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to refresh seats.', 'taken' => []]);
}
