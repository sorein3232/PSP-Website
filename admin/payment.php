<?php
ob_start(); // Start output buffering
session_start();
include 'database.php';
include('includes/header.php');

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header("Location: adminLogin.php");
    exit();
}

// Handle payment deletion via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_payment'])) {
    ob_clean(); // Clear any previous output
    header('Content-Type: application/json');
    
    $payment_id = $_POST['payment_id'];
    
    try {
        // Start a transaction
        $conn->begin_transaction();

        // First, get the user_id and money_paid for the payment
        $stmt_get_payment = $conn->prepare("SELECT user_id, money_paid FROM payments WHERE payment_id = ?");
        $stmt_get_payment->bind_param("i", $payment_id);
        $stmt_get_payment->execute();
        $result = $stmt_get_payment->get_result();
        
        if ($result->num_rows == 0) {
            throw new Exception("Payment not found");
        }
        
        $payment = $result->fetch_assoc();
        $user_id = $payment['user_id'];
        $money_paid = $payment['money_paid'];

        // Restore the user's account balance
        $stmt_restore_balance = $conn->prepare("UPDATE users SET account_balance = account_balance + ? WHERE id = ?");
        $stmt_restore_balance->bind_param("di", $money_paid, $user_id);
        $stmt_restore_balance->execute();

        // Delete the payment
        $stmt_delete = $conn->prepare("DELETE FROM payments WHERE payment_id = ?");
        $stmt_delete->bind_param("i", $payment_id);
        $stmt_delete->execute();

        // Commit the transaction
        $conn->commit();

        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        // Rollback the transaction
        $conn->rollback();
        
        echo json_encode([
            "success" => false, 
            "error" => $e->getMessage()
        ]);
    }
    exit();
}

// Handle balance addition deletion via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_balance_addition'])) {
    ob_clean(); // Clear any previous output
    header('Content-Type: application/json');
    
    $balance_addition_id = $_POST['balance_addition_id'];
    
    try {
        // Start a transaction
        $conn->begin_transaction();

        // First, get the user_id and balance_amount for the balance addition
        $stmt_get_balance = $conn->prepare("SELECT user_id, balance_amount FROM balance_additions WHERE balance_addition_id = ?");
        $stmt_get_balance->bind_param("i", $balance_addition_id);
        $stmt_get_balance->execute();
        $result = $stmt_get_balance->get_result();
        
        if ($result->num_rows == 0) {
            throw new Exception("Balance addition not found");
        }
        
        $balance = $result->fetch_assoc();
        $user_id = $balance['user_id'];
        $balance_amount = $balance['balance_amount'];

        // Restore the user's account balance
        $stmt_restore_balance = $conn->prepare("UPDATE users SET account_balance = account_balance - ? WHERE id = ?");
        $stmt_restore_balance->bind_param("di", $balance_amount, $user_id);
        $stmt_restore_balance->execute();

        // Delete the balance addition
        $stmt_delete = $conn->prepare("DELETE FROM balance_additions WHERE balance_addition_id = ?");
        $stmt_delete->bind_param("i", $balance_addition_id);
        $stmt_delete->execute();

        // Commit the transaction
        $conn->commit();

        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        // Rollback the transaction
        $conn->rollback();
        
        echo json_encode([
            "success" => false, 
            "error" => $e->getMessage()
        ]);
    }
    exit();
}

// Handle balance addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_balance'])) {
    $username = $_POST['username'];
    $balance_amount = $_POST['balance_amount'];
    $balance_date = $_POST['balance_date'];
    $balance_note = $_POST['balance_note'];

    // Get user ID from username
    $stmt = $conn->prepare("SELECT id, account_balance FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $current_balance = $user['account_balance'];

        // Calculate new balance (add balance amount)
        $new_balance = $current_balance + $balance_amount;

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Update user's account balance
            $update_balance_stmt = $conn->prepare("UPDATE users SET account_balance = ? WHERE id = ?");
            $update_balance_stmt->bind_param("di", $new_balance, $user_id);
            $update_balance_stmt->execute();

            // Insert balance addition record
            $insert_balance_stmt = $conn->prepare("INSERT INTO balance_additions (user_id, balance_amount, balance_date, balance_note) VALUES (?, ?, ?, ?)");
            $insert_balance_stmt->bind_param("idss", $user_id, $balance_amount, $balance_date, $balance_note);
            $insert_balance_stmt->execute();

            // Commit transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            // Log error or handle as needed
            error_log("Balance addition failed: " . $e->getMessage());
        }
    }
    
    header("Location: payment.php");
    exit();
}

// Handle payment addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_payment'])) {
    $username = $_POST['username'];
    $payment_date = $_POST['payment_date'];
    $payment_due = $_POST['payment_due'];
    $money_paid = $_POST['money_paid'];
    $promo_applied = $_POST['promo_applied'];

    // Get user ID and current balance from username
    $stmt = $conn->prepare("SELECT id, account_balance FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $current_balance = $user['account_balance'];

        // Calculate new balance (deduct money paid)
        $new_balance = $current_balance - $money_paid;

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Update user's account balance
            $update_balance_stmt = $conn->prepare("UPDATE users SET account_balance = ? WHERE id = ?");
            $update_balance_stmt->bind_param("di", $new_balance, $user_id);
            $update_balance_stmt->execute();

            // Insert payment record
            $insert_payment_stmt = $conn->prepare("INSERT INTO payments (user_id, payment_date, payment_due, money_paid, promo_applied) VALUES (?, ?, ?, ?, ?)");
            $insert_payment_stmt->bind_param("issss", $user_id, $payment_date, $payment_due, $money_paid, $promo_applied);
            $insert_payment_stmt->execute();

            // Commit transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            // Log error or handle as needed
            error_log("Payment insertion failed: " . $e->getMessage());
        }
    }
    
    header("Location: payment.php");
    exit();
}

// Fetch payments with user details and account balance
$payments_query = "SELECT p.payment_id, u.username, 
                 DATE_FORMAT(p.payment_date, '%m/%d/%Y') AS payment_date, 
                 DATE_FORMAT(p.payment_due, '%m/%d/%Y') AS payment_due, 
                 p.money_paid, p.promo_applied,
                 u.account_balance
          FROM payments p
          JOIN users u ON p.user_id = u.id
          ORDER BY p.payment_date DESC";
$payments_result = $conn->query($payments_query);

// Fetch balance additions with user details
$balance_additions_query = "SELECT ba.balance_addition_id, u.username, 
                                   DATE_FORMAT(ba.balance_date, '%m/%d/%Y') AS balance_date, 
                                   ba.balance_amount, 
                                   ba.balance_note,
                                   DATE_FORMAT(ba.created_at, '%m/%d/%Y %H:%i:%s') AS created_at
                            FROM balance_additions ba
                            JOIN users u ON ba.user_id = u.id
                            ORDER BY ba.created_at DESC";
$balance_additions_result = $conn->query($balance_additions_query);

// Fetch users for dropdown
$users = $conn->query("SELECT username FROM users ORDER BY username");
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Payment Management</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Payments</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Payments Card -->
        <div class="card">
            <div class="card-body">
                <h2>Payment Records</h2>

                <!-- Add Payment and Add Balance Buttons -->
                <div class="mb-3">
                    <button type="button" class="btn btn-primary mr-2" data-toggle="modal" data-target="#paymentModal">
                        Add Payment
                    </button>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#balanceModal">
                        Add Balance
                    </button>
                </div>

                <!-- Payments Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Username</th>
                                <th>Payment Date</th>
                                <th>Payment Due</th>
                                <th>Money Paid</th>
                                <th>Promo Applied</th>
                                <th>Account Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $payments_result->fetch_assoc()) { ?>
                                <tr id="row-<?php echo $row['payment_id']; ?>">
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['payment_due']); ?></td>
                                    <td><?php echo number_format($row['money_paid'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['promo_applied']); ?></td>
                                    <td><?php echo number_format($row['account_balance'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="removePayment(<?php echo $row['payment_id']; ?>)">Remove</button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Balance Additions Card -->
        <div class="card mt-4">
            <div class="card-body">
                <h2>Balance Additions</h2>
                
                <!-- Balance Additions Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Username</th>
                                <th>Balance Date</th>
                                <th>Balance Amount</th>
                                <th>Balance Note</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $balance_additions_result->fetch_assoc()) { ?>
                                <tr id="balance-row-<?php echo $row['balance_addition_id']; ?>">
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['balance_date']); ?></td>
                                    <td><?php echo number_format($row['balance_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['balance_note'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="removeBalanceAddition(<?php echo $row['balance_addition_id']; ?>)">Remove</button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Add Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Username:</label>
                        <select name="username" class="form-control" required>
                            <?php 
                            // Reset the users result pointer
                            $users->data_seek(0);
                            while ($user = $users->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($user['username']); ?>"><?= htmlspecialchars($user['username']); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Payment Date:</label>
                        <input type="date" name="payment_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Payment Due:</label>
                        <input type="date" name="payment_due" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Money Paid:</label>
                        <input type="number" step="0.01" name="money_paid" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Promo Applied (Optional):</label>
                        <input type="text" name="promo_applied" class="form-control">
                    </div>

                    <div class="modal-footer">
                        <button type="submit" name="add_payment" class="btn btn-success">Save Payment</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Balance Modal -->
<div class="modal fade" id="balanceModal" tabindex="-1" role="dialog" aria-labelledby="balanceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="balanceModalLabel">Add Balance</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Username:</label>
                        <select name="username" class="form-control" required>
                            <?php 
                            // Reset the users result pointer
                            $users->data_seek(0);
                            while ($user = $users->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($user['username']); ?>"><?= htmlspecialchars($user['username']); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Balance Amount:</label>
                        <input type="number" step="0.01" name="balance_amount" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Balance Date:</label>
                        <input type="date" name="balance_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Balance Note (Optional):</label>
                        <input type="text" name="balance_note" class="form-control">
                    </div>

                    <div class="modal-footer">
                        <button type="submit" name="add_balance" class="btn btn-success">Add Balance</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function removePayment(paymentId) {
        if (confirm("Are you sure you want to remove this payment?")) {
            $.ajax({
                url: window.location.href,
                type: "POST",
                dataType: "json",
                data: {
                    remove_payment: true,
                    payment_id: paymentId
                },
                success: function(response) {
                    if (response.success) {
                        $("#row-" + paymentId).fadeOut(500, function() {
                            $(this).remove();
                        });
                    } else {
                        alert("Failed to remove payment: " + (response.error || "Unknown error"));
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("An error occurred while removing the payment: " + error);
                }
            });
        }
    }

    function removeBalanceAddition(balanceAdditionId) {
        if (confirm("Are you sure you want to remove this balance addition?")) {
            $.ajax({
                url: window.location.href,
                type: "POST",
                dataType: "json",
                data: {
                    remove_balance_addition: true,
                    balance_addition_id: balanceAdditionId
                },
                success: function(response) {
                    if (response.success) {
                        $("#balance-row-" + balanceAdditionId).fadeOut(500, function() {
                            $(this).remove();
                        });
                    } else {
                        alert("Failed to remove balance addition: " + (response.error || "Unknown error"));
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("An error occurred while removing the balance addition: " + error);
                }
            });
        }
    }

    // Ensure jQuery and Bootstrap are loaded
    $(document).ready(function() {
        // Optional: Add any page load initialization here
    });
</script>

<?php 
include('includes/footer.php');
ob_end_flush(); // Send the buffered output
?>