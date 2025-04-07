<?php
session_start();
require_once 'database.php';

// Handle logout before anything else
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header("Location: adminLogin.php"); // Redirect to the login page
    exit();
}

if (!isset($_SESSION['admin'])) {
    header("Location: adminLogin.php");
    exit();
}

// Handle membership status updates
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && isset($_POST['userId'])) {
    $userId = intval($_POST['userId']);
    $action = $_POST['action'];

    $newStatus = '';
    $frozenAt = null;

    if ($action === 'activate') {
        $newStatus = 'active';
    } elseif ($action === 'deactivate') {
        $newStatus = 'inactive';
    } elseif ($action === 'freeze') {
        $newStatus = 'frozen';
        $frozenAt = date("Y-m-d H:i:s");
    } elseif ($action === 'unfreeze') {
        $newStatus = 'active';
    }

    if ($newStatus) {
        $updateSql = "UPDATE users SET membership_status = ?, frozen_at = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ssi", $newStatus, $frozenAt, $userId);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "status" => $newStatus]);
        } else {
            echo json_encode(["success" => false, "error" => "Database error"]);
        }

        $stmt->close();
    }

    $conn->close();
    exit();
}

// Fetch gym members
$sql = "SELECT id, fullName, username, emailAddress, phoneNumber, birthday, membership_status, date_started, next_payment FROM users";
$result = $conn->query($sql);
?>

<?php include('includes/header.php') ?>

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
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Gym Members List</h3>
            </div>

            <div class="card-body">
                <table id="member-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>USERNAME</th>
                            <th>USERID</th>
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

                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['fullName']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['emailAddress']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['phoneNumber']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['birthday']) . "</td>";
                                echo "<td id='status-" . $row['id'] . "'>" . $status . "</td>";
                                echo "<td><button class='btn btn-primary btn-sm' onclick='editUser(" . $row['id'] . ")'>Edit</button></td>";

                                echo "<td id='freeze-unfreeze-" . $row['id'] . "'>";
                                if ($status === 'active') {
                                    echo "<button class='btn btn-warning btn-sm' onclick='updateMembership(" . $row['id'] . ", \"freeze\")'>Freeze</button>";
                                } elseif ($status === 'frozen') {
                                    echo "<button class='btn btn-info btn-sm' onclick='updateMembership(" . $row['id'] . ", \"unfreeze\")'>Unfreeze</button>";
                                }
                                echo "</td>";

                                echo "<td><button class='btn $buttonClass btn-sm' id='toggle-btn-" . $row['id'] . "' onclick='updateMembership(" . $row['id'] . ", \"" . ($status === 'active' || $status === 'frozen' ? 'deactivate' : 'activate') . "\")'>$buttonText</button></td>";

                                echo "<td><button class='btn btn-danger btn-sm' onclick='removeUser(" . $row['id'] . ")'>Remove</button></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='11' class='text-center'>No gym members found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>


<script>
    function updateMembership(userId, action) {
        console.log(`Action: ${action} for userId: ${userId}`);

        $.ajax({
            url: "gymMembers.php",
            type: "POST",
            data: {
                userId: userId,
                action: action
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    let newStatus = response.status;
                    console.log(`New status for userId ${userId}: ${newStatus}`);

                    $("#status-" + userId).text(newStatus);

                    let toggleButton = $("#toggle-btn-" + userId);
                    let freezeUnfreezeCell = $("#freeze-unfreeze-" + userId);

                    if (newStatus === "active") {
                        console.log("User activated");
                        toggleButton.text("Deactivate")
                            .removeClass("activate")
                            .addClass("deactivate")
                            .attr("onclick", `updateMembership(${userId}, 'deactivate')`);

                        freezeUnfreezeCell.html("<button class='freeze' onclick='updateMembership(" + userId + ", \"freeze\")'>Freeze</button>");
                    } else if (newStatus === "inactive") {
                        console.log("User deactivated");
                        toggleButton.text("Activate")
                            .removeClass("deactivate")
                            .addClass("activate")
                            .attr("onclick", `updateMembership(${userId}, 'activate')`);

                        freezeUnfreezeCell.html("");
                    } else if (newStatus === "frozen") {
                        console.log("User frozen");
                        toggleButton.text("Deactivate")
                            .removeClass("activate")
                            .addClass("deactivate")
                            .attr("onclick", `updateMembership(${userId}, 'deactivate')`);

                        freezeUnfreezeCell.html("<button class='unfreeze' onclick='updateMembership(" + userId + ", \"unfreeze\")'>Unfreeze</button>");
                    }
                } else {
                    console.error("Error:", response.error);
                    alert("Failed to update membership status: " + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert("An error occurred while updating the membership status. Please try again.");
            }
        });
    }



    function editUser(userId) {
        let row = document.querySelector(`tr td button[onclick='editUser(${userId})']`).parentNode.parentNode;

        let columns = row.getElementsByTagName("td");

        let fullName = columns[2].innerText.trim();
        let email = columns[3].innerText.trim();
        let phone = columns[4].innerText.trim();
        let birthday = columns[5].innerText.trim();

        // Make fields editable
        columns[2].innerHTML = `<input type="text" id="edit-fullName-${userId}" value="${fullName}">`;
        columns[3].innerHTML = `<input type="email" id="edit-email-${userId}" value="${email}">`;
        columns[4].innerHTML = `<input type="tel" id="edit-phone-${userId}" value="${phone}">`;
        columns[5].innerHTML = `<input type="date" id="edit-birthday-${userId}" value="${birthday}">`;

        columns[9].innerHTML = `<button class='save' onclick='saveUser(${userId})'>Save</button>`;
    }

    function saveUser(userId) {
        let fullName = document.getElementById(`edit-fullName-${userId}`).value;
        let email = document.getElementById(`edit-email-${userId}`).value;
        let phone = document.getElementById(`edit-phone-${userId}`).value;

        let formData = new FormData();
        formData.append("userId", userId);
        formData.append("fullName", fullName);
        formData.append("email", email);
        formData.append("phone", phone);

        fetch("updateMember.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                if (data.success) {
                    let row = document.querySelector(`tr td button[onclick='saveUser(${userId})']`).parentNode.parentNode;
                    let columns = row.getElementsByTagName("td");

                    columns[2].innerText = fullName;
                    columns[3].innerText = email;
                    columns[4].innerText = phone;

                    columns[9].innerHTML = `<button class='edit' onclick='editUser(${userId})'>Edit</button>`;
                    alert("User updated successfully!");
                } else {
                    alert("Failed to update user.");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while updating the user.");
            });
    }

    function editUser(userId) {
        let row = document.querySelector(`tr td button[onclick='editUser(${userId})']`).parentNode.parentNode;

        let columns = row.getElementsByTagName("td");

        let fullName = columns[2].innerText.trim();
        let email = columns[3].innerText.trim();
        let phone = columns[4].innerText.trim();
        let birthday = columns[5].innerText.trim();

        // Make fields editable
        columns[2].innerHTML = `<input type="text" id="edit-fullName-${userId}" value="${fullName}">`;
        columns[3].innerHTML = `<input type="email" id="edit-email-${userId}" value="${email}">`;
        columns[4].innerHTML = `<input type="tel" id="edit-phone-${userId}" value="${phone}">`;
        columns[5].innerHTML = birthday; // Keep birthday as plain text


        // Change Edit button to Save button
        columns[9].innerHTML = `<button class='save' onclick='saveUser(${userId})'>Save</button>`;
    }

    document.getElementById("search-member").addEventListener("input", function() {
        let searchValue = this.value.trim().toLowerCase();
        let rows = document.querySelectorAll(".member-table tbody tr");

        rows.forEach(row => {
            let userId = row.cells[1].innerText.trim().toLowerCase(); // User ID column
            if (userId.includes(searchValue) || searchValue === "") {
                row.style.display = ""; // Show row if it matches
            } else {
                row.style.display = "none"; // Hide row if it doesn't match
            }
        });
    });

    function toggleNotifications() {
        var dropdown = document.getElementById("notificationDropdown");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    window.onclick = function(event) {
        if (!event.target.matches('.notification-btn')) {
            var dropdown = document.getElementById("notificationDropdown");
            if (dropdown.style.display === "block") {
                dropdown.style.display = "none";
            }
        }
    }

    function removeUser(userId) {
        if (!confirm("Are you sure you want to delete this user?")) {
            return;
        }

        $.post("delete_user.php", {
            user_id: userId
        }, function(data) {
            if (data.success) {
                alert("User deleted successfully!");
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                alert("Error: " + data.error);
            }
        }, "json").fail(function() {
            alert("An error occurred. Please try again.");
        });
    }
</script>

<script>
    $(document).ready(function() {
        setInterval(checkMembershipStatus, 1000);
    });

    function checkMembershipStatus() {
        $.ajax({
            url: "check_membership.php",
            type: "POST",
            dataType: "json",
            success: function(data) {
                if (data.success && data.message.includes("unfrozen")) {
                    alert(data.message);
                    setTimeout(() => {
                        location.reload()
                    }, 1500);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error checking membership:", error);
            }
        });
    }
</script>

<?php include('includes/footer.php') ?>