<?php
// Security check (remains unchanged)
if (!defined('INCLUDED_FROM_DASHBOARD') && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Search functionality (remains unchanged)
$search_query = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = "%" . $conn->real_escape_string($_GET['search']) . "%";
}

// Pagination (remains unchanged)
$items_per_page = 10; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Pagination range calculation (remains unchanged)
$max_pages = 5; 
$start_page = max(1, $current_page - 2);
$end_page = min($max_pages, $current_page + 2);

if ($end_page - $start_page < $max_pages - 1) {
    $start_page = max(1, $end_page - ($max_pages - 1));
}

// Get the tab (remains unchanged)
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'history';

// COUNT(*) query:  Corrected bind_param
$sql = "SELECT COUNT(*) FROM transactions t
        JOIN inventory i ON t.item_id = i.id
        JOIN transactions s ON t.student_id = s.student_id
        WHERE 1=1 " . (!empty($search_query) ? "AND s.student_name LIKE ?" : "");

$stmt = $conn->prepare($sql);
if (!empty($search_query)) {
    $stmt->bind_param("s", $search_query);
}
$stmt->execute();
$stmt->bind_result($total_items);
$stmt->fetch();
$stmt->close();

$total_pages = ceil($total_items / $items_per_page);
$current_page = max(1, min($current_page, $total_pages));

// Main SELECT query: Corrected bind_param
$sql = "SELECT t.*, i.item_name, s.student_name, s.student_id, s.section, 
       DATE_FORMAT(CONCAT(DATE(t.due_date), ' 17:00:00'), '%M %d, %Y %H:%i') AS formatted_due_date
        FROM transactions t
        JOIN inventory i ON t.item_id = i.id
        JOIN transactions s ON t.student_id = s.student_id
        WHERE 1=1 " . (!empty($search_query) ? "AND s.student_name LIKE ?" : "") . "
        ORDER BY t.transaction_date DESC
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
if (!empty($search_query)) {
    $stmt->bind_param("sii", $search_query, $offset, $items_per_page); // Corrected to "sii"
} else {
    $stmt->bind_param("ii", $offset, $items_per_page);
}
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">‎ ‎ ‎ </h5>
        <form method="GET" action="officer_dashboard.php?tab=<?php echo htmlspecialchars($active_tab); ?>">  <!-- Preserve the tab -->
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">  <!-- Redundant but ensures preservation -->
            <div class="input-group search-bar ms-auto">
                <input type="text" class="form-control" name="search" placeholder="Search by student name" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Section</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Transaction Type</th>
                        <th>Borrowed Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Verified By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo $transaction['id']; ?></td>
                                <td><?php echo htmlspecialchars($transaction['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['section']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['item_name']); ?></td>
                                <td><?php echo $transaction['quantity']; ?></td>
                                <td><?php echo ucfirst($transaction['transaction_type']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($transaction['transaction_date'])); ?></td>
                                <td><?php echo $transaction['formatted_due_date']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo ($transaction['status'] === 'borrowed' ? 'status-borrowed' : ($transaction['status'] === 'returned' ? 'status-returned' : 'status-unavailable')); ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $transaction['verified_by']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">No transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="officer_dashboard.php?tab=history&page=<?php echo $current_page - 1; ?>&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                            <a class="page-link" href="officer_dashboard.php?tab=history&page=<?php echo $i; ?>&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="officer_dashboard.php?tab=history&page=<?php echo $current_page + 1; ?>&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>