<?php
session_start();
require_once 'database.php';

// Authentication Check
if (!isset($_SESSION['admin'])) {
    header("Location: adminLogin.php");
    exit();
}

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header("Location: adminLogin.php");
    exit();
}

// Handle various AJAX actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');

    // Membership Status Update
    if (isset($_POST['action']) && isset($_POST['userId'])) {
        $userId = intval($_POST['userId']);
        $action = $_POST['action'];

        $newStatus = '';
        $frozenAt = null;

        switch ($action) {
            case 'activate':
                $newStatus = 'active';
                break;
            case 'deactivate':
                $newStatus = 'inactive';
                break;
            case 'freeze':
                $newStatus = 'frozen';
                $frozenAt = date("Y-m-d H:i:s");
                break;
            case 'unfreeze':
                $newStatus = 'active';
                break;
        }

        if ($newStatus) {
            $updateSql = "UPDATE users SET membership_status = ?, frozen_at = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("ssi", $newStatus, $frozenAt, $userId);

            $response = [];
            if ($stmt->execute()) {
                $response = [
                    "success" => true, 
                    "status" => $newStatus,
                    "message" => "Membership status updated successfully"
                ];
            } else {
                $response = [
                    "success" => false, 
                    "error" => "Database error: " . $stmt->error
                ];
            }

            echo json_encode($response);
            $stmt->close();
            exit();
        }
    }

    // User Edit
    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $userId = intval($_POST['userId']);
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $fullName = $_POST['fullName'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];

        $updateSql = "UPDATE users SET first_name = ?, last_name = ?, fullName = ?, emailAddress = ?, phoneNumber = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("sssssi", $firstName, $lastName, $fullName, $email, $phone, $userId);

        $response = [];
        if ($stmt->execute()) {
            $response = [
                "success" => true, 
                "message" => "User updated successfully",
                "userData" => [
                    "firstName" => $firstName,
                    "lastName" => $lastName,
                    "fullName" => $fullName,
                    "email" => $email,
                    "phone" => $phone
                ]
            ];
        } else {
            $response = [
                "success" => false, 
                "error" => "Database error: " . $stmt->error
            ];
        }

        echo json_encode($response);
        $stmt->close();
        exit();
    }

    // User Removal
    if (isset($_POST['action']) && $_POST['action'] === 'remove_user') {
        $userId = intval($_POST['userId']);

        $deleteSql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bind_param("i", $userId);

        $response = [];
        if ($stmt->execute()) {
            $response = [
                "success" => true, 
                "message" => "User deleted successfully",
                "userId" => $userId
            ];
        } else {
            $response = [
                "success" => false, 
                "error" => "Database error: " . $stmt->error
            ];
        }

        echo json_encode($response);
        $stmt->close();
        exit();
    }
}

// Fetch Users
$sql = "SELECT id, first_name, last_name, fullName, username, emailAddress, phoneNumber, birthday, membership_status, date_started, next_payment FROM users";
$result = $conn->query($sql);

include('includes/header.php');
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gym Members</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Gym Members</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div id="alert-container" class="mb-3"></div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Gym Members List</h3>
                <div class="card-tools">
                    <input type="text" id="search-member" class="form-control form-control-sm" placeholder="Search by User ID">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="member-table" class="table table-bordered table-striped">
                        <thead class="bg-black">
                            <tr>
                                <th>USERNAME</th>
                                <th>USERID</th>
                                <th>LAST NAME</th>
                                <th>FIRST NAME</th>
                                <th>FULL NAME</th>
                                <th>EMAIL</th>
                                <th>PHONE NUMBER</th>
                                <th>BIRTHDAY</th>
                                <th>MEMBERSHIP STATUS</th>
                                <th>EDIT</th>
                                <th>FREEZE / UNFREEZE</th>
                                <th>STATUS</th>
                                <th>REMOVE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $status = htmlspecialchars($row['membership_status']);
                                    $buttonText = ($status === 'active' || $status === 'frozen') ? 'Deactivate' : 'Activate';
                                    $buttonClass = ($status === 'active' || $status === 'frozen') ? 'btn-danger' : 'btn-success';

                                    echo "<tr data-user-id='{$row['id']}'>";
                                    echo "<td class='username'>" . htmlspecialchars($row['username']) . "</td>";
                                    echo "<td class='user-id'>" . htmlspecialchars($row['id']) . "</td>";
                                    echo "<td class='last-name'>" . htmlspecialchars($row['last_name']) . "</td>";
                                    echo "<td class='first-name'>" . htmlspecialchars($row['first_name']) . "</td>";
                                    echo "<td class='full-name'>" . htmlspecialchars($row['fullName']) . "</td>";
                                    echo "<td class='email'>" . htmlspecialchars($row['emailAddress']) . "</td>";
                                    echo "<td class='phone'>" . htmlspecialchars($row['phoneNumber']) . "</td>";
                                    echo "<td class='birthday'>" . htmlspecialchars($row['birthday']) . "</td>";
                                    echo "<td class='status'>" . $status . "</td>";
                                    echo "<td><button class='btn btn-primary btn-sm edit-btn'>Edit</button></td>";

                                    echo "<td class='freeze-unfreeze'>";
                                    if ($status === 'active') {
                                        echo "<button class='btn btn-warning btn-sm freeze-btn'>Freeze</button>";
                                    } elseif ($status === 'frozen') {
                                        echo "<button class='btn btn-info btn-sm unfreeze-btn'>Unfreeze</button>";
                                    }
                                    echo "</td>";

                                    echo "<td><button class='btn $buttonClass btn-sm toggle-status-btn'>$buttonText</button></td>";
                                    echo "<td><button class='btn btn-danger btn-sm remove-btn'>Remove</button></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='13' class='text-center'>No gym members found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    // Utility function to show alerts
    function showAlert(message, type = 'success') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        $('#alert-container').html(alertHtml);
    }

    // Edit User
    $(document).on('click', '.edit-btn', function() {
        const row = $(this).closest('tr');
        const userId = row.data('user-id');

        // Backup original values
        const originalFirstName = row.find('.first-name').text();
        const originalLastName = row.find('.last-name').text();
        const originalFullName = row.find('.full-name').text();
        const originalEmail = row.find('.email').text();
        const originalPhone = row.find('.phone').text();

        // Make fields editable
        row.find('.first-name').html(`<input type="text" class="form-control edit-first-name" value="${originalFirstName}">`);
        row.find('.last-name').html(`<input type="text" class="form-control edit-last-name" value="${originalLastName}">`);
        row.find('.full-name').html(`<input type="text" class="form-control edit-full-name" value="${originalFullName}">`);
        row.find('.email').html(`<input type="email" class="form-control edit-email" value="${originalEmail}">`);
        row.find('.phone').html(`<input type="tel" class="form-control edit-phone" value="${originalPhone}">`);

        $(this).replaceWith('<button class="btn btn-success btn-sm save-btn">Save</button>');
    });

    // Save User
    $(document).on('click', '.save-btn', function() {
        const row = $(this).closest('tr');
        const userId = row.data('user-id');

        const firstName = row.find('.edit-first-name').val();
        const lastName = row.find('.edit-last-name').val();
        const fullName = row.find('.edit-full-name').val();
        const email = row.find('.edit-email').val();
        const phone = row.find('.edit-phone').val();

        $.ajax({
            url: '',
            method: 'POST',
            data: {
                action: 'edit_user',
                userId: userId,
                firstName: firstName,
                lastName: lastName,
                fullName: fullName,
                email: email,
                phone: phone
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.find('.first-name').text(response.userData.firstName);
                    row.find('.last-name').text(response.userData.lastName);
                    row.find('.full-name').text(response.userData.fullName);
                    row.find('.email').text(response.userData.email);
                    row.find('.phone').text(response.userData.phone);
                    row.find('.save-btn').replaceWith('<button class="btn btn-primary btn-sm edit-btn">Edit</button>');
                    showAlert(response.message);
                } else {
                    showAlert(response.error, 'danger');
                }
            },
            error: function() {
                showAlert('An error occurred while updating user', 'danger');
            }
        });
    });

    // Toggle Membership Status
    $(document).on('click', '.toggle-status-btn', function() {
        const row = $(this).closest('tr');
        const userId = row.data('user-id');
        const currentStatus = row.find('.status').text();
        let action;

        if (currentStatus === 'active' || currentStatus === 'frozen') {
            action = 'deactivate';
        } else {
            action = 'activate';
        }

        $.ajax({
            url: '',
            method: 'POST',
            data: { userId: userId, action: action },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.find('.status').text(response.status);
                    row.find('.toggle-status-btn')
                        .text(response.status === 'active' ? 'Deactivate' : 'Activate')
                        .toggleClass('btn-success btn-danger');

                    // Update freeze/unfreeze button
                    const freezeCell = row.find('.freeze-unfreeze');
                    freezeCell.empty();
                    if (response.status === 'active') {
                        freezeCell.html('<button class="btn btn-warning btn-sm freeze-btn">Freeze</button>');
                    } else if (response.status === 'frozen') {
                        freezeCell.html('<button class="btn btn-info btn-sm unfreeze-btn">Unfreeze</button>');
                    }

                    showAlert(response.message);
                } else {
                    showAlert(response.error, 'danger');
                }
            }
        });
    });

    // Freeze/Unfreeze
    $(document).on('click', '.freeze-btn, .unfreeze-btn', function() {
        const row = $(this).closest('tr');
        const userId = row.data('user-id');
        const action = $(this).hasClass('freeze-btn') ? 'freeze' : 'unfreeze';

        $.ajax({
            url: '',
            method: 'POST',
            data: { userId: userId, action: action },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.find('.status').text(response.status);
                    const freezeCell = row.find('.freeze-unfreeze');
                    freezeCell.empty();
                    if (response.status === 'active') {
                        freezeCell.html('<button class="btn btn-warning btn-sm freeze-btn">Freeze</button>');
                    } else if (response.status === 'frozen') {
                        freezeCell.html('<button class="btn btn-info btn-sm unfreeze-btn">Unfreeze</button>');
                    }
                    showAlert(response.message);
                } else {
                    showAlert(response.error, 'danger');
                }
            }
        });
    });

    // Remove User
    $(document).on('click', '.remove-btn', function() {
        const row = $(this).closest('tr');
        const userId = row.data('user-id');

        if (!confirm('Are you sure you want to delete this user?')) return;

        $.ajax({
            url: '',
            method: 'POST',
            data: { 
                action: 'remove_user', 
                userId: userId 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.remove();
                    showAlert(response.message);
                } else {
                    showAlert(response.error, 'danger');
                }
            }
        });
    });

    // Search functionality
    $('#search-member').on('input', function() {
        const searchValue = $(this).val().toLowerCase();
        
        $('#member-table tbody tr').each(function() {
            const userId = $(this).find('.user-id').text().toLowerCase();
            $(this).toggle(userId.includes(searchValue) || searchValue === '');
        });
    });
});
</script>

<?php include('includes/footer.php') ?>