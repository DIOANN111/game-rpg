<?php
// update_players_list.php
require 'db.php';

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE location_id = ? AND last_activity > NOW() - INTERVAL 5 MINUTE");
$stmt->execute([$_GET['location_id']]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($players as $player) {
    echo "<h4><a href='user.php?id={$player['id']}'>" . htmlspecialchars($player['username']) . "</a></h4>";
}
?>
