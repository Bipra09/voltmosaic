<?php
declare(strict_types=1);
header('Content-Type: application/json');
header('Cache-Control: no-store');

function send(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_THROW_ON_ERROR);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    send(405, ['error' => 'Method not allowed.']);
}

try {
    $db = new PDO(
        getenv('VOLT_DSN') ?: 'mysql:host=127.0.0.1;dbname=voltmosaic;charset=utf8mb4',
        getenv('VOLT_USER') ?: 'voltmosaic',
        getenv('VOLT_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
    if ($method === 'GET') {
        $rows = $db->query('SELECT id, service, amount, created_at FROM payments ORDER BY created_at DESC, id DESC LIMIT 30');
        send(200, ['payments' => $rows->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
        send(415, ['error' => 'Send application/json.']);
    }
    $raw = file_get_contents('php://input', false, null, 0, 4097);
    if ($raw === false || strlen($raw) > 4096) {
        send(413, ['error' => 'Request too large.']);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        send(400, ['error' => 'Expected a JSON object.']);
    }
    $service = $data['service'] ?? null;
    $reference = $data['reference'] ?? null;
    $amount = $data['amount'] ?? null;
    $requestId = $data['request_id'] ?? null;
    if (!in_array($service, ['mobile', 'electricity', 'internet'], true)
        || !is_string($reference) || !preg_match('/^[a-zA-Z0-9-]{4,32}$/D', $reference)
        || !is_int($amount) || $amount < 100 || $amount > 50000
        || !is_string($requestId) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D', $requestId)) {
        send(422, ['error' => 'Invalid service, account reference, amount, or request ID.']);
    }
    $id = bin2hex(random_bytes(16));
    $insert = $db->prepare('INSERT INTO payments (id, request_id, service, reference, amount) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = id');
    $insert->execute([$id, $requestId, $service, $reference, $amount]);
    $lookup = $db->prepare('SELECT id, service, reference, amount FROM payments WHERE request_id = ?');
    $lookup->execute([$requestId]);
    $row = $lookup->fetch(PDO::FETCH_ASSOC);
    if (!$row || $row['service'] !== $service || $row['reference'] !== $reference || (int)$row['amount'] !== $amount) {
        send(409, ['error' => 'This request ID is already in use.']);
    }
    send($row['id'] === $id ? 201 : 200, ['id' => $row['id'], 'status' => 'simulated']);
} catch (Throwable $error) {
    error_log($error->getMessage());
    send(500, ['error' => 'Could not process the transaction. Check the demo database configuration.']);
}
