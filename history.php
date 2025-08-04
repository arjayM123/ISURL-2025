<?php

session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Fetch activity logs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$stmt = $conn->query("SELECT COUNT(*) FROM activity_log");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

$stmt = $conn->prepare("
    SELECT 
        activity_log.*,
        DATE_FORMAT(created_at, '%M %d, %Y %h:%i %p') as formatted_date 
    FROM activity_log 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$per_page, $offset]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity History - ISU Roxas Library</title>
    <style>
        .history-container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .history-header {
            margin-bottom: 2rem;
        }

        .history-header h2 {
            color: #216c2a;
            margin-bottom: 0.5rem;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        .history-table th,
        .history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .history-table th {
            background: #f8f9fa;
            color: #216c2a;
            font-weight: bold;
        }

        .history-table tr:hover {
            background: #f8f9fa;
        }

        .action-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: bold;
        }

        .action-create {
            background: #d4edda;
            color: #155724;
        }

        .action-update {
            background: #fff3cd;
            color: #856404;
        }

        .action-delete {
            background: #f8d7da;
            color: #721c24;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #216c2a;
            text-decoration: none;
        }

        .pagination a:hover {
            background: #e9ecef;
        }

        .pagination .active {
            background: #216c2a;
            color: white;
            border-color: #216c2a;
        }
    </style>
</head>
<body>
    <?php include 'admin_layout.php'; ?>

    <div class="history-container">
        <div class="history-header">
            <h2>Activity History</h2>
            <p>Track all changes made to the library system</p>
        </div>

        <table class="history-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Action</th>
                    <th>Section</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log['formatted_date']; ?></td>
                        <td>
                            <span class="action-badge action-<?php echo strtolower($log['action']); ?>">
                                <?php echo $log['action']; ?>
                            </span>
                        </td>
                        <td><?php echo ucfirst($log['table_name']); ?></td>
                        <td>
                            <?php
                            if ($log['new_data']) {
                                $data = json_decode($log['new_data'], true);
                                echo isset($data['title']) ? $data['title'] : 'ID: ' . $log['record_id'];
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo ($page - 1); ?>">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo ($page + 1); ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>