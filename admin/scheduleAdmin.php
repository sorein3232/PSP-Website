<?php
session_start();
include("../dbs_connection/database.php");

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header("Location: adminLogin.php");
    exit();
}

// Handle schedule update form submission
if (isset($_POST['submit'])) {
    // Set timezone to Manila, Philippines
    date_default_timezone_set('Asia/Manila');

    $id = mysqli_real_escape_string($conn, $_POST['edit_id']);
    $day = mysqli_real_escape_string($conn, $_POST['day']);
    $personnel = mysqli_real_escape_string($conn, $_POST['personnel']);
    $activity = mysqli_real_escape_string($conn, $_POST['activity']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $current_timestamp = date('Y-m-d H:i:s'); // Uses Manila timezone

    // Handle file upload
    if (!empty($_FILES['schedule_picture']['name'])) {
        $targetDir = "uploads/";
        $fileName = basename($_FILES['schedule_picture']['name']);
        $targetFilePath = $targetDir . $fileName;

        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array(strtolower($fileType), $allowedTypes)) {
            move_uploaded_file($_FILES['schedule_picture']['tmp_name'], $targetFilePath);
            $picture = $fileName;
            $_SESSION['toast_message'] = 'Schedule updated successfully!';
            $_SESSION['toast_type'] = 'success';
        } else {
            $_SESSION['toast_message'] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
            $_SESSION['toast_type'] = 'error';
            header("Location: scheduleAdmin.php");
            exit();
        }
    } else {
        $picture = $_POST['old_picture'];
        $_SESSION['toast_message'] = 'Schedule updated successfully!';
        $_SESSION['toast_type'] = 'success';
    }

    $sql = "UPDATE schedule SET schedule_picture='$picture', day='$day', personnel_name='$personnel', activity_description='$activity', time='$time', created_at='$current_timestamp' WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        header("Location: scheduleAdmin.php");
        exit();
    } else {
        error_log("Error updating record: " . $conn->error, 3, "error_log.txt");
        $_SESSION['toast_message'] = 'Error updating record.';
        $_SESSION['toast_type'] = 'error';
        header("Location: scheduleAdmin.php");
        exit();
    }
}

// Fetch schedule data
$sql = "SELECT * FROM schedule ORDER BY created_at ASC ";
$result = $conn->query($sql);

include('includes/header.php');
?>

<style>
    /* Custom toast styles for AdminLTE */
    #toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1060;
    }
    .toast {
        opacity: 1 !important;
        min-width: 250px;
    }
    .toast-success {
        background-color: #28a745;
    }
    .toast-error {
        background-color: #dc3545;
    }
</style>

<?php 
// Toast Notification
if (isset($_SESSION['toast_message'])) {
    $type = $_SESSION['toast_type'] ?? 'info';
    $toastClass = $type == 'success' ? 'toast-success' : 'toast-error';
?>
<div id="toast-container">
    <div class="toast <?php echo $toastClass; ?>" role="alert">
        <div class="toast-header">
            <strong class="mr-auto"><?php echo ucfirst($type); ?></strong>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">&times;</button>
        </div>
        <div class="toast-body text-white">
            <?php echo $_SESSION['toast_message']; ?>
        </div>
    </div>
</div>
<?php 
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_type']);
} 
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Schedule Management</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Schedule</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h2>Schedule Listings</h2>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Schedule Picture</th>
                                <th>Day</th>
                                <th>Personnel Name</th>
                                <th>Activity</th>
                                <th>Time</th>
                                <th>Edit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><img src="uploads/<?= $row['schedule_picture'] ?>" alt="Schedule Picture" class="img-thumbnail" width="80"></td>
                                    <td><?= $row['day'] ?></td>
                                    <td><?= $row['personnel_name'] ?></td>
                                    <td><?= $row['activity_description'] ?></td>
                                    <td><?= date('h:i A', strtotime($row['time'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm"
                                            data-toggle="modal" data-target="#editModal"
                                            data-id="<?= $row['id'] ?>"
                                            data-day="<?= $row['day'] ?>"
                                            data-personnel="<?= $row['personnel_name'] ?>"
                                            data-activity="<?= $row['activity_description'] ?>"
                                            data-time="<?= $row['time'] ?>"
                                            data-image="<?= $row['schedule_picture'] ?>">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Bootstrap 4.5 Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Schedule</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_id" id="edit_id">

                    <div class="form-group">
                        <label>Day:</label>
                        <input type="text" name="day" id="edit_day" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label>Personnel Name:</label>
                        <input type="text" name="personnel" id="edit_personnel" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Activity:</label>
                        <input type="text" name="activity" id="edit_activity" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Time:</label>
                        <input type="time" name="time" id="edit_time" class="form-control" required step="60">
                    </div>

                    <div class="form-group">
                        <label>Current Picture:</label>
                        <img id="current_picture" src="" class="img-fluid w-100 border rounded">
                        <input type="hidden" name="old_picture" id="old_picture">
                    </div>

                    <div class="form-group">
                        <label>Change Picture:</label>
                        <input type="file" name="schedule_picture" class="form-control-file">
                    </div>

                    <div class="modal-footer">
                        <button type="submit" name="submit" class="btn btn-success">Update</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Toast auto-dismiss
    $(document).ready(function() {
        $('.toast').toast({
            delay: 5000
        });
        $('.toast').toast('show');

        // Existing modal population script
        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            $('#edit_id').val(button.data('id'));
            $('#edit_day').val(button.data('day'));
            $('#edit_personnel').val(button.data('personnel'));
            $('#edit_activity').val(button.data('activity'));
            $('#edit_time').val(button.data('time'));
            $('#current_picture').attr('src', 'uploads/' + button.data('image'));
            $('#old_picture').val(button.data('image'));
        });
    });
</script>

<?php $conn->close(); ?>
<?php include('includes/footer.php'); ?>