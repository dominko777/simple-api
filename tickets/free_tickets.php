<?php
require_once '../config/database.php';
require_once '../objects/ticket.php';
require_once '../components/parser.php';

$data = json_decode(file_get_contents("php://input"));

$url = $_POST['url'];
if (!$url) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missed data.']);
}
else {
    $parser = new Parser($url);
    $parsedData = $parser->parse($url);
    $database = new Database();
    $db = $database->getConnection();
    $ticket = new Ticket($db);
    $count = $ticket->insertFree($parsedData);
    if ($count > 0) {
        $eventId = array_values(array_slice($parsedData[0], -1))[0];
        http_response_code(200);
        echo json_encode(['status' => 'success', 'count' => $count, 'event_id' => $eventId]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'No tickets were inserted.']);
    }
}
