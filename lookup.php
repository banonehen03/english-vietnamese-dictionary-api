<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

// Kiểm tra tham số từ khóa
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

if (empty($keyword)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Thiếu tham số 'keyword' (Ví dụ: lookup.php?keyword=hello)"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Đường dẫn file SQLite nằm cùng thư mục
$dbPath = __DIR__ . '/dictionary_en_vi.db';

if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Không tìm thấy file cơ sở dữ liệu dictionary_en_vi.db"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

try {
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Truy vấn kết hợp 4 bảng chuẩn hóa
    $sql = "SELECT 
                w.word,
                p.ipa,
                p.region,
                d.pos,
                d.definition
            FROM words w
            LEFT JOIN pronunciations p ON w.id = p.word_id
            LEFT JOIN word_definitions wd ON w.id = wd.word_id
            LEFT JOIN definitions d ON wd.definition_id = d.id
            WHERE LOWER(w.word) = LOWER(:word)";

    $stmt = $db->prepare($sql);
    $stmt->execute([':word' => $keyword]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Không tìm thấy từ '{$keyword}' trong từ điển"
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    // Gom nhóm dữ liệu trả về theo cấu trúc gọn gàng
    $result = [
        "word" => $rows[0]['word'],
        "pronunciations" => [],
        "definitions" => []
    ];

    $seenIpa = [];
    $seenDef = [];

    foreach ($rows as $row) {
        if (!empty($row['ipa']) && !in_array($row['ipa'], $seenIpa)) {
            $result['pronunciations'][] = [
                "ipa" => $row['ipa'],
                "region" => $row['region']
            ];
            $seenIpa[] = $row['ipa'];
        }

        if (!empty($row['definition']) && !in_array($row['definition'], $seenDef)) {
            $result['definitions'][] = [
                "pos" => $row['pos'],
                "meaning" => $row['definition']
            ];
            $seenDef[] = $row['definition'];
        }
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => $result
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Lỗi truy vấn cơ sở dữ liệu: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}