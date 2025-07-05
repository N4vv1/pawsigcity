<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login/admin-login.php");
    exit;
}

$result = $mysqli->query("SELECT pay.*, u.full_name, a.appointment_date, pk.name AS package_name 
                          FROM payments pay
                          JOIN users u ON pay.user_id = u.user_id
                          JOIN appointments a ON pay.appointment_id = a.appointment_id
                          JOIN packages pk ON a.package_id = pk.id
                          WHERE pay.status = 'pending'
                          ORDER BY pay.created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending Payments</title>
    <style>
        table {
            width: 90%;
            margin: auto;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: center;
        }
        a.button {
            padding: 6px 12px;
            background-color: #A8E6CF;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            color: #252525;
        }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Pending Payments</h2>
    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Appointment</th>
                <th>Package</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Screenshot</th>
                <th>Mark as Paid</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= $row['appointment_date'] ?></td>
                    <td><?= $row['package_name'] ?></td>
                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                    <td><?= $row['method'] ?></td>
                    <td>
                        <?php if ($row['screenshot_url']): ?>
                            <a href="../uploads/<?= $row['screenshot_url'] ?>" target="_blank">View</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><a class="button" href="mark-paid.php?payment_id=<?= $row['payment_id'] ?>">Mark Paid</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
