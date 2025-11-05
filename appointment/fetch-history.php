<?php
require_once '../db.php';

$user_id = $_GET['user_id'] ?? 0;
if (!$user_id) exit('Invalid user ID.');

$historyQuery = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.status,
        p.name AS pet_name,
        p.breed AS pet_breed,
        pk.name AS package_name,
        a.notes,
        a.rating,
        a.feedback
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN packages pk ON a.package_id = pk.package_id
    WHERE a.user_id = $1
    ORDER BY a.appointment_date DESC
";

$result = pg_query_params($conn, $historyQuery, array($user_id));

if (!$result) {
    echo '<table><tr><td style="text-align: center; color: red;">Error fetching history</td></tr></table>';
    exit;
}

if (pg_num_rows($result) === 0) {
    echo '<table><tr><td style="text-align: center; color: #666;">No appointment history found.</td></tr></table>';
    exit;
}
?>

<style>
.history-table {
    width: 100%;
    border-collapse: collapse;
}
.history-table th,
.history-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    font-size: 0.9rem;
}
.history-table th {
    background-color: #A8E6CF;
    color: #252525;
    font-weight: 600;
}
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}
.status-completed { background: #d4edda; color: #155724; }
.status-confirmed { background: #cce5ff; color: #004085; }
.status-cancelled { background: #f8d7da; color: #721c24; }
.status-no-show { background: #fff3cd; color: #856404; }
.status-pending { background: #e2e3e5; color: #383d41; }
.feedback-stars {
    color: #FFD700;
    font-size: 0.9rem;
}
</style>

<table class="history-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Pet</th>
            <th>Service</th>
            <th>Status</th>
            <th>Feedback</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = pg_fetch_assoc($result)): ?>
            <tr>
                <td><?= date('M d, Y g:i A', strtotime($row['appointment_date'])) ?></td>
                <td>
                    <strong><?= htmlspecialchars($row['pet_name']) ?></strong><br>
                    <small style="color: #666;"><?= htmlspecialchars($row['pet_breed']) ?></small>
                </td>
                <td><?= htmlspecialchars($row['package_name']) ?></td>
                <td>
                    <?php
                    $status = strtolower($row['status']);
                    $statusClass = 'status-' . str_replace('_', '-', $status);
                    $statusText = ucfirst(str_replace('_', ' ', $status));
                    ?>
                    <span class="status-badge <?= $statusClass ?>">
                        <?= $statusText ?>
                    </span>
                </td>
                <td>
                    <?php if (!empty($row['rating'])): ?>
                        <div class="feedback-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa<?= $i <= $row['rating'] ? 's' : 'r' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <?php if (!empty($row['feedback'])): ?>
                            <small style="color: #666; font-style: italic;">
                                <?= htmlspecialchars(substr($row['feedback'], 0, 50)) ?>
                                <?= strlen($row['feedback']) > 50 ? '...' : '' ?>
                            </small>
                        <?php endif; ?>
                    <?php else: ?>
                        <em style="color: #999;">No feedback</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>