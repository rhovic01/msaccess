<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_item'])) {
        $item_name = trim($_POST['item_name']);
        $item_quantity = (int)$_POST['item_quantity'];
        $item_availability = ($item_quantity > 0) ? 'available' : 'unavailable';

        $sql = "INSERT INTO inventory (item_name, item_quantity, item_availability) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sis", $item_name, $item_quantity, $item_availability);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Item added successfully!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding item: " . $stmt->error;
            $_SESSION['alert_type'] = "danger";
        }
        $stmt->close();
    } elseif (isset($_POST['edit_item'])) {
        $id = (int)$_POST['id'];
        $item_name = trim($_POST['item_name']);
        $item_quantity = (int)$_POST['item_quantity'];
        $item_availability = ($item_quantity > 0) ? 'available' : 'unavailable';

        $sql = "UPDATE inventory SET item_name = ?, item_quantity = ?, item_availability = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $item_name, $item_quantity, $item_availability, $id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Item updated successfully!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating item: " . $stmt->error;
            $_SESSION['alert_type'] = "danger";
        }
        $stmt->close();
    } elseif (isset($_POST['delete_item'])) {
        $id = (int)$_POST['id'];

        $sql = "DELETE FROM inventory WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Item deleted successfully!";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting item: " . $stmt->error;
            $_SESSION['alert_type'] = "danger";
        }
        $stmt->close();
    }
}

$conn->close();

// Redirect back to admin dashboard
header("Location: admin_dashboard.php");
exit();
