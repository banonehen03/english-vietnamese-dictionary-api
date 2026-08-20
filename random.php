<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

$dbPath = __DIR__ . '/dictionary_en_vi.db';

try {
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lấy ngẫu nhiên 1 từ
    $randomWordSql = "SELECT id, word FROM words ORDER BY RANDOM() LIMIT 1";
    $wordStmt = $db->query($randomWordSql);
    $wordRow = $wordStmt->fetch(PDO::FETCH_ASSOC);

    if (!$wordRow) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Database trống"], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $wordId = $wordRow['id'];

    // Lấy phiên âm
    $pronStmt = $db->prepare("SELECT ipa, region FROM pronunciations WHERE word_id = :id");
    $pronStmt->execute([':id' => $wordId]);
    $pronunciations = $pronStmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh sách nghĩa
    $defSql = "SELECT d.pos, d.definition 
               FROM definitions d
               JOIN word_definitions wd ON d.id = wd.definition_id
               WHERE wd.word_id = :id";
    $defStmt = $db->prepare($defSql);
    $defStmt->execute([':id' => $wordId]);
    $definitions = $defStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "id" => $wordId,
            "word" => $wordRow['word'],
            "pronunciations" => $pronunciations,
            "definitions" => $definitions
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}