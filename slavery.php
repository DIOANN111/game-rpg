<?php
function isSlave($user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT is_slave FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return (bool)$stmt->fetchColumn();
}
?>
