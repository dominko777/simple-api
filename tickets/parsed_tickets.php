<?php
require_once '../config/database.php';
require_once '../objects/ticket.php';

$data = json_decode(file_get_contents("php://input"));
$eventId = $_GET['event_id'];
$eventDate = $_GET['date'];
if (!$eventId || !$eventDate) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing params.']);
}
else {
    $database = new Database();
    $db = $database->getConnection();
    $ticket = new Ticket($db);
    $tickets = $ticket->getTickets($eventId, $eventDate);
    http_response_code(200);
    echo json_encode(['status' => 'success', 'tickets' => $tickets]);
}
