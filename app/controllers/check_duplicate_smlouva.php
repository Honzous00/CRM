<?php
include_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cislo_smlouvy'])) {
    $cislo_smlouvy = trim($conn->real_escape_string($_POST['cislo_smlouvy']));
    $current_id = isset($_POST['current_id']) ? intval($_POST['current_id']) : null;

    if (empty($cislo_smlouvy)) {
        echo json_encode([
            'duplicate' => false,
            'message' => 'Číslo smlouvy je prázdné'
        ]);
        exit;
    }

    $sql = "SELECT id, cislo_smlouvy FROM smlouvy WHERE cislo_smlouvy = ?";
    $params = [$cislo_smlouvy];
    $types = "s";

    if ($current_id) {
        $sql .= " AND id != ?";
        $params[] = $current_id;
        $types .= "i";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'duplicate' => false,
            'error' => 'Chyba při přípravě dotazu: ' . $conn->error
        ]);
        exit;
    }

    if ($types === "s") {
        $stmt->bind_param("s", ...$params);
    } else {
        $stmt->bind_param("si", ...$params);
    }

    if (!$stmt->execute()) {
        echo json_encode([
            'duplicate' => false,
            'error' => 'Chyba při vykonávání dotazu: ' . $stmt->error
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }

    $result = $stmt->get_result();
    $is_duplicate = $result->num_rows > 0;

    $response = [
        'duplicate' => $is_duplicate,
        'count' => $result->num_rows
    ];

    if ($is_duplicate) {
        $existing = $result->fetch_assoc();
        $response['existing_id'] = $existing['id'];
        $response['message'] = 'Smlouva s tímto číslem již existuje (ID: ' . $existing['id'] . ')';
    }

    echo json_encode($response);

    $stmt->close();
} else {
    echo json_encode([
        'duplicate' => false,
        'error' => 'Neplatný požadavek'
    ]);
}

$conn->close();
