<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

if (empty($query)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Thiếu tham số 'q' (Ví dụ: suggest.php?q=hel&limit=5)"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

$dbPath = __DIR__ . '/dictionary_en_vi.db';

try {
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tìm các từ bắt đầu bằng chuỗi $query
    $sql = "SELECT id, word FROM words WHERE word LIKE :prefix ORDER BY LENGTH(word) ASC LIMIT :limit";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':prefix', $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "query" => $query,
        "count" => count($results),
        "data" => $results
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Lỗi truy vấn: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}