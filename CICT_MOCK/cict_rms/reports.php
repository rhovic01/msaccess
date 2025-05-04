<?php
require 'db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$isAdmin = ($_SESSION['role'] === 'admin');

// Initialize filter variables
$reportType = $_POST['report_type'] ?? 'both'; // 'inventory', 'transactions', or 'both'
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$inventoryStatus = $_POST['inventory_status'] ?? 'all';
$transactionStatus = $_POST['transaction_status'] ?? 'all';

// Build inventory query
$inventoryWhere = [];
if ($inventoryStatus !== 'all') {
    $inventoryWhere[] = "item_availability = '" . $conn->real_escape_string($inventoryStatus) . "'";
}
$inventorySql = "SELECT * FROM inventory";
if (!empty($inventoryWhere)) {
    $inventorySql .= " WHERE " . implode(" AND ", $inventoryWhere);
}

// Build transactions query
$transactionWhere = [];
if ($transactionStatus !== 'all') {
    $transactionWhere[] = "status = '" . $conn->real_escape_string($transactionStatus) . "'";
}
if (!empty($startDate)) {
    $transactionWhere[] = "transaction_date >= '" . $conn->real_escape_string($startDate) . "'";
}
if (!empty($endDate)) {
    $transactionWhere[] = "transaction_date <= '" . $conn->real_escape_string($endDate) . " 23:59:59'";
}

$transactionSql = "SELECT * FROM transactions";
if (!empty($transactionWhere)) {
    $transactionSql .= " WHERE " . implode(" AND ", $transactionWhere);
}

// Execute queries based on selected report type
$inventoryData = [];
$transactionData = [];

if ($reportType === 'inventory' || $reportType === 'both') {
    $inventoryResult = $conn->query($inventorySql);
    $inventoryData = $inventoryResult->fetch_all(MYSQLI_ASSOC);
}

if ($reportType === 'transactions' || $reportType === 'both') {
    $transactionResult = $conn->query($transactionSql);
    $transactionData = $transactionResult->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .badge-available {
            background-color: #28a745;
        }
        .badge-unavailable {
            background-color: #dc3545;
        }
        .badge-borrowed {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-returned {
            background-color: #17a2b8;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                font-size: 12px;
            }
            .table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Reports</h1>

        <!-- Filter Section -->
        <div class="filter-section no-print">
            <h4><i class="fas fa-filter"></i> Filter Options</h4>
            <form method="POST" id="reportForm">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type">
                            <option value="both" <?= $reportType === 'both' ? 'selected' : '' ?>>Both Reports</option>
                            <option value="inventory" <?= $reportType === 'inventory' ? 'selected' : '' ?>>Inventory Only</option>
                            <option value="transactions" <?= $reportType === 'transactions' ? 'selected' : '' ?>>Transactions Only</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3" id="inventoryStatusField" style="<?= $reportType === 'transactions' ? 'display:none' : '' ?>">
                        <label for="inventory_status" class="form-label">Inventory Status</label>
                        <select class="form-select" id="inventory_status" name="inventory_status">
                            <option value="all" <?= $inventoryStatus === 'all' ? 'selected' : '' ?>>All Items</option>
                            <option value="available" <?= $inventoryStatus === 'available' ? 'selected' : '' ?>>Available Only</option>
                            <option value="unavailable" <?= $inventoryStatus === 'unavailable' ? 'selected' : '' ?>>Unavailable Only</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3" id="transactionStatusField" style="<?= $reportType === 'inventory' ? 'display:none' : '' ?>">
                        <label for="transaction_status" class="form-label">Transaction Status</label>
                        <select class="form-select" id="transaction_status" name="transaction_status">
                            <option value="all" <?= $transactionStatus === 'all' ? 'selected' : '' ?>>All Transactions</option>
                            <option value="borrowed" <?= $transactionStatus === 'borrowed' ? 'selected' : '' ?>>Borrowed Only</option>
                            <option value="returned" <?= $transactionStatus === 'returned' ? 'selected' : '' ?>>Returned Only</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3" id="dateRangeFields" style="<?= $reportType === 'inventory' ? 'display:none' : '' ?>">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control datepicker" id="start_date" name="start_date" value="<?= $startDate ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control datepicker" id="end_date" name="end_date" value="<?= $endDate ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                <button type="button" class="btn btn-secondary" onclick="resetFilters()"><i class="fas fa-undo"></i> Reset</button>
            </form>
        </div>

        <!-- Action Buttons -->
        <div class="mb-3 no-print">
            <button class="btn btn-primary" onclick="printReport()"><i class="fas fa-print"></i> Print Report</button>
            <button class="btn btn-success" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Export to Excel</button>
            <button class="btn btn-danger" onclick="exportToPDF()"><i class="fas fa-file-pdf"></i> Export to PDF</button>
        </div>

        <!-- Report Summary -->
        <div class="card mb-4 no-print">
            <div class="card-header">
                <h5 class="mb-0">Report Summary</h5>
            </div>
            <div class="card-body">
                <p><strong>Generated on:</strong> <?= date('Y-m-d H:i:s') ?></p>
                <p><strong>Generated by:</strong> <?= $_SESSION['username'] ?></p>
                <p><strong>Report Type:</strong> <?= ucfirst($reportType) ?> report</p>
                <?php if ($reportType !== 'inventory'): ?>
                <p><strong>Date Range:</strong> <?= !empty($startDate) ? $startDate : 'Start' ?> to <?= !empty($endDate) ? $endDate : 'End' ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($inventoryData) && empty($transactionData)): ?>
            <div class="alert alert-warning" role="alert">
                No data available for the selected filters.
            </div>
        <?php else: ?>
            <!-- Inventory Report -->
            <?php if (($reportType === 'inventory' || $reportType === 'both') && !empty($inventoryData)): ?>
                <div class="report-section mb-5">
                    <h3>Inventory Report</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Availability</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventoryData as $item): ?>
                                    <tr>
                                        <td><?= $item['id'] ?></td>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= $item['item_quantity'] ?></td>
                                        <td>
                                            <span class="badge <?= $item['item_availability'] === 'available' ? 'badge-available' : 'badge-unavailable' ?>">
                                                <?= ucfirst($item['item_availability']) ?>
                                            </span>
                                        </td>
                                        <td><?= !empty($item['last_updated']) ? date('Y-m-d H:i', strtotime($item['last_updated'])) : 'N/A' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end">
                                        <strong>Total Items:</strong> <?= count($inventoryData) ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Transactions Report -->
            <?php if (($reportType === 'transactions' || $reportType === 'both') && !empty($transactionData)): ?>
                <div class="report-section">
                    <h3>Transactions Report</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Item ID</th>
                                    <th>Item Name</th>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Verified By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactionData as $transaction): ?>
                                    <tr>
                                        <td><?= $transaction['id'] ?></td>
                                        <td><?= $transaction['item_id'] ?></td>
                                        <td><?= htmlspecialchars($transaction['item_name'] ?? 'N/A') ?></td>
                                        <td><?= $transaction['student_id'] ?></td>
                                        <td><?= htmlspecialchars($transaction['student_name']) ?></td>
                                        <td>
                                            <span class="badge <?= $transaction['transaction_type'] === 'borrowed' ? 'bg-primary' : 'bg-success' ?>">
                                                <?= ucfirst($transaction['transaction_type']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('Y-m-d H:i', strtotime($transaction['transaction_date'])) ?></td>
                                        <td>
                                            <span class="badge <?= $transaction['status'] === 'borrowed' ? 'badge-borrowed' : 'badge-returned' ?>">
                                                <?= ucfirst($transaction['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($transaction['verified_by']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="9" class="text-end">
                                        <strong>Total Transactions:</strong> <?= count($transactionData) ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script>
        // Initialize date pickers
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            allowInput: true
        });

        // Show/hide fields based on report type
        document.getElementById('report_type').addEventListener('change', function() {
            const reportType = this.value;
            const inventoryField = document.getElementById('inventoryStatusField');
            const transactionField = document.getElementById('transactionStatusField');
            const dateRangeField = document.getElementById('dateRangeFields');

            if (reportType === 'both') {
                inventoryField.style.display = '';
                transactionField.style.display = '';
                dateRangeField.style.display = '';
            } else if (reportType === 'inventory') {
                inventoryField.style.display = '';
                transactionField.style.display = 'none';
                dateRangeField.style.display = 'none';
            } else if (reportType === 'transactions') {
                inventoryField.style.display = 'none';
                transactionField.style.display = '';
                dateRangeField.style.display = '';
            }
        });

        function resetFilters() {
            document.getElementById('reportForm').reset();
            document.getElementById('reportForm').submit();
        }

        function printReport() {
            window.print();
        }

        function exportToExcel() {
            // Add hidden inputs with current filter values
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_to_excel.php';
            
            const reportTypeInput = document.createElement('input');
            reportTypeInput.type = 'hidden';
            reportTypeInput.name = 'report_type';
            reportTypeInput.value = document.getElementById('report_type').value;
            form.appendChild(reportTypeInput);
            
            const inventoryStatusInput = document.createElement('input');
            inventoryStatusInput.type = 'hidden';
            inventoryStatusInput.name = 'inventory_status';
            inventoryStatusInput.value = document.getElementById('inventory_status').value;
            form.appendChild(inventoryStatusInput);
            
            const transactionStatusInput = document.createElement('input');
            transactionStatusInput.type = 'hidden';
            transactionStatusInput.name = 'transaction_status';
            transactionStatusInput.value = document.getElementById('transaction_status').value;
            form.appendChild(transactionStatusInput);
            
            const startDateInput = document.createElement('input');
            startDateInput.type = 'hidden';
            startDateInput.name = 'start_date';
            startDateInput.value = document.getElementById('start_date').value;
            form.appendChild(startDateInput);
            
            const endDateInput = document.createElement('input');
            endDateInput.type = 'hidden';
            endDateInput.name = 'end_date';
            endDateInput.value = document.getElementById('end_date').value;
            form.appendChild(endDateInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(18);
            doc.text('Inventory Management System Report', 105, 15, { align: 'center' });
            
            // Add report details
            doc.setFontSize(12);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 25);
            doc.text(`Generated by: <?= $_SESSION['username'] ?>`, 14, 30);
            doc.text(`Report Type: ${document.getElementById('report_type').options[document.getElementById('report_type').selectedIndex].text}`, 14, 35);
            
            <?php if ($reportType !== 'inventory'): ?>
            doc.text(`Date Range: ${document.getElementById('start_date').value || 'Start'} to ${document.getElementById('end_date').value || 'End'}`, 14, 40);
            <?php endif; ?>
            
            let yPosition = 50;
            
            <?php if (($reportType === 'inventory' || $reportType === 'both') && !empty($inventoryData)): ?>
            // Inventory table
            doc.setFontSize(14);
            doc.text('Inventory Report', 14, yPosition);
            yPosition += 10;
            
            const inventoryHeaders = [['ID', 'Item Name', 'Quantity', 'Availability']];
            const inventoryData = [
                <?php foreach ($inventoryData as $item): ?>
                [
                    '<?= $item['id'] ?>',
                    '<?= addslashes($item['item_name']) ?>',
                    '<?= $item['item_quantity'] ?>',
                    '<?= ucfirst($item['item_availability']) ?>'
                ],
                <?php endforeach; ?>
            ];
            
            doc.autoTable({
                startY: yPosition,
                head: inventoryHeaders,
                body: inventoryData,
                margin: { left: 14 },
                styles: { fontSize: 10 }
            });
            
            yPosition = doc.lastAutoTable.finalY + 10;
            doc.text(`Total Items: <?= count($inventoryData) ?>`, 14, yPosition);
            yPosition += 15;
            <?php endif; ?>
            
            <?php if (($reportType === 'transactions' || $reportType === 'both') && !empty($transactionData)): ?>
            // Transactions table
            doc.setFontSize(14);
            doc.text('Transactions Report', 14, yPosition);
            yPosition += 10;
            
            const transactionHeaders = [['ID', 'Item ID', 'Item Name', 'Student ID', 'Student Name', 'Type', 'Date', 'Status']];
            const transactionData = [
                <?php foreach ($transactionData as $transaction): ?>
                [
                    '<?= $transaction['id'] ?>',
                    '<?= $transaction['item_id'] ?>',
                    '<?= addslashes($transaction['item_name'] ?? 'N/A') ?>',
                    '<?= $transaction['student_id'] ?>',
                    '<?= addslashes($transaction['student_name']) ?>',
                    '<?= ucfirst($transaction['transaction_type']) ?>',
                    '<?= date('Y-m-d H:i', strtotime($transaction['transaction_date'])) ?>',
                    '<?= ucfirst($transaction['status']) ?>'
                ],
                <?php endforeach; ?>
            ];
            
            doc.autoTable({
                startY: yPosition,
                head: transactionHeaders,
                body: transactionData,
                margin: { left: 14 },
                styles: { fontSize: 8 }
            });
            
            yPosition = doc.lastAutoTable.finalY + 10;
            doc.text(`Total Transactions: <?= count($transactionData) ?>`, 14, yPosition);
            <?php endif; ?>
            
            // Save the PDF
            doc.save('IMS_Report_<?= date('Ymd_His') ?>.pdf');
        }
    </script>
</body>
</html>