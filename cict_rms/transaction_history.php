<?php
require 'db_connect.php';


// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of transactions
$sql = "SELECT COUNT(*) AS total FROM transactions";
$result = $conn->query($sql);
$totalItems = $result->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $limit);

// Fetch transactions for the current page
$sql = "SELECT * FROM transactions ORDER BY transaction_date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Transaction History</h5>
    </div>
    <div class="card-body">
        <!-- Search Form -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search transactions..." onkeyup="searchTable()">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="fas fa-filter"></i> Filters
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="transactionTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)">Item ID <i class="fas fa-sort"></i></th>
                        <th onclick="sortTable(1)">Student ID <i class="fas fa-sort"></i></th>
                        <th onclick="sortTable(2)">Student Name <i class="fas fa-sort"></i></th>
                        <th onclick="sortTable(3)">Type <i class="fas fa-sort"></i></th>
                        <th onclick="sortTable(4)">Status <i class="fas fa-sort"></i></th>
                        <th onclick="sortTable(5)">Verified By <i class="fas fa-sort"></i></th>
                        <th onclick="sortTable(6)">Date <i class="fas fa-sort"></i></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo $transaction['item_id']; ?></td>
                            <td><?php echo $transaction['student_id']; ?></td>
                            <td><?php echo $transaction['student_name']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $transaction['transaction_type'] === 'checkout' ? 'primary' : 'success'; ?>">
                                    <?php echo ucfirst($transaction['transaction_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $transaction['verified_by']; ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($transaction['transaction_date'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewTransactionModal"
                                    data-id="<?php echo $transaction['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filter Transactions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="mb-3">
                        <label class="form-label">Transaction Type</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="type[]" value="checkout" id="checkoutCheck" checked>
                            <label class="form-check-label" for="checkoutCheck">Checkout</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="type[]" value="return" id="returnCheck" checked>
                            <label class="form-check-label" for="returnCheck">Return</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="status[]" value="pending" id="pendingCheck" checked>
                            <label class="form-check-label" for="pendingCheck">Pending</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="status[]" value="completed" id="completedCheck" checked>
                            <label class="form-check-label" for="completedCheck">Completed</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="status[]" value="cancelled" id="cancelledCheck" checked>
                            <label class="form-check-label" for="cancelledCheck">Cancelled</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="dateRange" class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="start_date">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" name="end_date">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
            </div>
        </div>
    </div>
</div>

<!-- View Transaction Modal -->
<div class="modal fade" id="viewTransactionModal" tabindex="-1" aria-labelledby="viewTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTransactionModalLabel">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="transactionDetails">
                <!-- Details will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function sortTable(columnIndex) {
        const table = document.querySelector("#transactionTable");
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));

        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();

            // Numeric sorting for Item ID and Date columns
            if (columnIndex === 0 || columnIndex === 6) {
                return aValue - bValue;
            }
            // String sorting for other columns
            return aValue.localeCompare(bValue);
        });

        // Clear the table body and append sorted rows
        tbody.innerHTML = "";
        rows.forEach(row => tbody.appendChild(row));
    }

    function searchTable() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toUpperCase();
        const table = document.getElementById("transactionTable");
        const rows = table.getElementsByTagName("tr");

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            let match = false;
            for (let j = 0; j < row.cells.length - 1; j++) { // Skip actions column
                const cell = row.cells[j];
                if (cell.textContent.toUpperCase().indexOf(filter) > -1) {
                    match = true;
                    break;
                }
            }
            row.style.display = match ? "" : "none";
        }
    }

    function applyFilters() {
        // Implement filter logic here
        console.log("Filters applied");
        $('#filterModal').modal('hide');
    }

    // View Transaction Modal Handler
    document.getElementById('viewTransactionModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const transactionId = button.getAttribute('data-id');
        
        // Load transaction details via AJAX
        fetch(`get_transaction_details.php?id=${transactionId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('transactionDetails').innerHTML = data;
            });
    });
</script>