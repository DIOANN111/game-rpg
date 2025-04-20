<?php
function getPossibleDirections($location_id, $pdo) {
    $stmt = $pdo->prepare("SELECT d.direction_name, l.name, d.target_location_id 
                           FROM directions d 
                           JOIN locations l ON d.target_location_id = l.id
                           WHERE d.location_id = ?");
    $stmt->execute([$location_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
