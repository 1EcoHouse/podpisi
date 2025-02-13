<?php
header('Content-Type: application/json');

$errors = [];
$data = json_decode(file_get_contents('php://input'), true);

try {
    $db = new SQLite3('petitions.db');
    
    // Проверка данных
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    }

    if (empty($data['nickname'])) {
        $errors['nickname'] = 'Никнейм обязателен';
    }

    if (!isset($data['agree'])) {
        $errors['agree'] = 'Необходимо согласие';
    }

    // Проверка уникальности никнейма
    if (empty($errors)) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM signatures WHERE nickname = :nickname');
        $stmt->bindValue(':nickname', $data['nickname'], SQLITE3_TEXT);
        $result = $stmt->execute();
        $count = $result->fetchArray()[0];
        
        if ($count > 0) {
            $errors['nickname'] = 'Этот никнейм уже используется';
        }
    }

    // Если ошибок нет - сохраняем
    if (empty($errors)) {
        $stmt = $db->prepare('
            INSERT INTO signatures (email, nickname, created_at) 
            VALUES (:email, :nickname, datetime("now"))
        ');
        $stmt->bindValue(':email', $data['email'], SQLITE3_TEXT);
        $stmt->bindValue(':nickname', $data['nickname'], SQLITE3_TEXT);
        $stmt->execute();
    }

    // Получаем список подписавших
    $signers = [];
    $result = $db->query('SELECT nickname FROM signatures ORDER BY created_at DESC');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $signers[] = $row;
    }

    echo json_encode([
        'success' => empty($errors),
        'errors' => $errors,
        'signers' => $signers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}