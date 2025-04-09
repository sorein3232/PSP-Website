<?php
session_start();
include 'database.php'; // Database connection
include '../session_handler/session_timeout.php';

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header("Location: adminLogin.php");
    exit();
}

// Handle form submission (Add or Edit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $imagePath = '';

    if (isset($_FILES['publication']) && $_FILES['publication']['error'] == 0) {
        $targetDir = "uploads/";
        $fileName = basename($_FILES["publication"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["publication"]["tmp_name"], $targetFilePath)) {
                $imagePath = $targetFilePath;
            } else {
                echo "<script>alert('Error uploading file.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file type. Allowed types: JPG, PNG, GIF.');</script>";
        }
    }

    // Insert or update announcement
    if (!empty($title)) {
        // Get current timestamp
        $current_timestamp = date('Y-m-d H:i:s');

        if (isset($_POST['announcement_id']) && $_POST['announcement_id'] != '') {
            // Update existing announcement
            $id = $_POST['announcement_id'];
            
            // If no new image is uploaded, keep the existing image
            if (empty($imagePath)) {
                $stmt = $conn->prepare("UPDATE announcements SET title = ?, created_at = ? WHERE id = ?");
                $stmt->bind_param("ssi", $title, $current_timestamp, $id);
            } else {
                $stmt = $conn->prepare("UPDATE announcements SET title = ?, image_path = ?, created_at = ? WHERE id = ?");
                $stmt->bind_param("sssi", $title, $imagePath, $current_timestamp, $id);
            }
        } else {
            // Insert new announcement
            $stmt = $conn->prepare("INSERT INTO announcements (title, image_path, created_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $imagePath, $current_timestamp);
        }

        if ($stmt->execute()) {
            header("Location: announcement.php"); // Refresh to show updates
            exit();
        } else {
            echo "<script>alert('Database error: " . $stmt->error . "');</script>";
        }
    } else {
        echo "<script>alert('Please enter a title.');</script>";
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: announcement.php");
    exit();
}

// Fetch announcements
$result = $conn->query("SELECT id, title, image_path FROM announcements ORDER BY created_at DESC");

include('includes/header.php');
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Announcements</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Announcements</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h2>Announcement Management</h2>

                <!-- Add Announcement Button -->
                <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#announcementModal">
                    Add Announcement
                </button>

                <!-- Announcements Table -->
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Title</th>
                            <th>Publication Material</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title']); ?></td>
                                <td><img src="<?= htmlspecialchars($row['image_path']); ?>" alt="Publication" class="img-thumbnail" width="100"></td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm"
                                        data-toggle="modal" data-target="#announcementModal"
                                        data-id="<?= $row['id'] ?>"
                                        data-title="<?= htmlspecialchars($row['title']) ?>"
                                        data-image="<?= $row['image_path'] ?>">
                                        Edit
                                    </button>
                                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Bootstrap 4.5 Modal for Add/Edit Announcement -->
<div class="modal fade" id="announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="announcementModalLabel">Manage Announcement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="announcement_id" id="announcement_id">

                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="Enter content title" required>
                    </div>

                    <div class="form-group">
                        <label>Current Image:</label>
                        <img id="currentImage" src="" class="img-fluid w-100 border rounded">
                    </div>

                    <div class="form-group">
                        <label for="publication">Insert Publication Material:</label>
                        <input type="file" name="publication" id="publication" class="form-control-file">
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $('#announcementModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var title = button.data('title');
        var image = button.data('image');

        $('#announcement_id').val(id);
        $('#title').val(title);
        $('#currentImage').attr('src', image ? image : '');
    });
</script>

<?php include('includes/footer.php'); ?>