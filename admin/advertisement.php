<?php
session_start();
include 'database.php'; // Ensure this file contains your database connection
include '../session_handler/session_timeout.php';
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header("Location: adminLogin.php");
    exit();
}

// Function to validate image file with improved error handling
function isValidImageFile($file) {
    // Allowed image file extensions
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    
    // Allowed MIME types
    $allowedMimeTypes = [
        'image/jpeg',
        'image/png', 
        'image/gif', 
        'image/webp', 
        'image/bmp'
    ];

    // Maximum file size (5MB)
    $maxFileSize = 5 * 1024 * 1024; // 5 MB in bytes

    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_error'] = "No file uploaded or file upload failed.";
        return false;
    }

    // Check file size
    if ($file['size'] > $maxFileSize) {
        $_SESSION['upload_error'] = "File is too large. Maximum file size is 5MB.";
        return false;
    }

    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Get file MIME type
    $fileMimeType = mime_content_type($file['tmp_name']);

    // Check file extension
    if (!in_array($fileExtension, $allowedExtensions)) {
        $_SESSION['upload_error'] = "Invalid file type. Allowed extensions are: " . implode(', ', $allowedExtensions);
        return false;
    }

    // Check MIME type
    if (!in_array($fileMimeType, $allowedMimeTypes)) {
        $_SESSION['upload_error'] = "Detected file type is not allowed. Only image files are permitted.";
        return false;
    }

    // Additional check for image validity
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $_SESSION['upload_error'] = "Invalid image file. The file could not be processed as an image.";
        return false;
    }

    return true;
}

// Handle form submission for creating an advertisement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];

    // Get current timestamp from user's device
    $created_at = date('Y-m-d H:i:s');

    if (!empty($_FILES['file-upload']['name'])) {
        // Validate image file
        if (isValidImageFile($_FILES['file-upload'])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Generate a unique filename to prevent overwriting
            $filename = uniqid() . '_' . basename($_FILES["file-upload"]["name"]);
            $image = $target_dir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES["file-upload"]["tmp_name"], $image)) {
                // Handle upload error
                $_SESSION['error'] = "File upload failed.";
                header("Location: advertisement.php");
                exit();
            }
        } else {
            // Upload error is already set in the isValidImageFile function
            header("Location: advertisement.php");
            exit();
        }
    } else {
        $image = "";
    }

    // Default is_active value is 1 (active)
    $is_active = 1;
    
    $stmt = $conn->prepare("INSERT INTO advertisements (title, image, description, created_at, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $title, $image, $description, $created_at, $is_active);
    $stmt->execute();
    $stmt->close();
    header("Location: advertisement.php");
    exit();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // First, find the image path to delete the file
    $stmt = $conn->prepare("SELECT image FROM advertisements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM advertisements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Delete the image file if it exists
    if (!empty($row['image']) && file_exists($row['image'])) {
        unlink($row['image']);
    }

    header("Location: advertisement.php");
    exit();
}

// Handle activation/deactivation
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    
    // Get current status
    $stmt = $conn->prepare("SELECT is_active FROM advertisements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    // Toggle status (1 -> 0, 0 -> 1)
    $new_status = ($row['is_active'] == 1) ? 0 : 1;
    
    // Update status
    $stmt = $conn->prepare("UPDATE advertisements SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $stmt->execute();
    $stmt->close();
    
    // Set success message
    $status_text = ($new_status == 1) ? "activated" : "deactivated";
    $_SESSION['success'] = "Advertisement successfully " . $status_text . ".";
    
    header("Location: advertisement.php");
    exit();
}

// Fetch advertisements
$result = $conn->query("SELECT * FROM advertisements ORDER BY created_at DESC");
include('includes/header.php');
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Advertisements</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Advertisements</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php 
        // Display upload error if exists
        if(isset($_SESSION['upload_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['upload_error']) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php 
            unset($_SESSION['upload_error']); 
        endif; 

        // Display other session messages
        if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php 
            unset($_SESSION['error']); 
        endif; 

        if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php 
            unset($_SESSION['success']); 
        endif; 
        ?>

        <div class="card">
            <div class="card-body">
                <h3>Create Advertisement</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="Enter Content Title" required>
                    </div>
                    <div class="form-group">
                        <label for="file-upload">Insert Publication Material:</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="file-upload" name="file-upload" 
                                   accept="image/jpeg,image/png,image/gif,image/webp,image/bmp" required>
                            <label class="custom-file-label" for="file-upload">Choose file</label>
                        </div>
                        <small class="form-text text-muted">
                            Allowed file types: .jpg, .jpeg, .png, .gif, .webp, .bmp (Max 5MB)
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="description">Content Description:</label>
                        <textarea id="description" name="description" class="form-control" placeholder="Enter Advertisement Description" required></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit" name="submit">Submit</button>
                </form>

                <h3 class="mt-4">Advertisement Listings</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Title</th>
                                <th>Material</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th>Status</th>
                                <th>Edit</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()) : 
                                $inactive_class = $row['is_active'] == 0 ? 'table-secondary text-muted' : '';
                            ?>
                                <tr class="<?= $inactive_class ?>">
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td>
                                        <?php if (!empty($row['image'])): ?>
                                            <img src="<?= htmlspecialchars($row['image']) ?>" alt="Advertisement" class="img-thumbnail" width="100">
                                        <?php else: ?>
                                            No Image
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                    <td>
                                        <?php if($row['is_active'] == 1): ?>
                                            <span class="badge badge-success">Active</span>
                                            <a href="?toggle=<?= $row['id'] ?>" class="btn btn-outline-secondary btn-sm ml-2">
                                                Deactivate
                                            </a>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactive</span>
                                            <a href="?toggle=<?= $row['id'] ?>" class="btn btn-outline-success btn-sm ml-2">
                                                Activate
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm"
                                            data-toggle="modal" data-target="#editModal"
                                            data-id="<?= htmlspecialchars($row["id"]) ?>"
                                            data-title="<?= htmlspecialchars($row["title"], ENT_QUOTES) ?>"
                                            data-image="<?= htmlspecialchars($row["image"]) ?>"
                                            data-description="<?= htmlspecialchars($row["description"], ENT_QUOTES) ?>"
                                            data-is-active="<?= htmlspecialchars($row["is_active"]) ?>">
                                            Edit
                                        </button>
                                    </td>
                                    <td>
                                        <a class="btn btn-danger btn-sm" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Remove</a>
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

<!-- Bootstrap 4.5 Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Advertisement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm" action="updateAdvertisement.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="editId" name="id">
                    <div class="form-group">
                        <label for="editTitle">Title:</label>
                        <input type="text" id="editTitle" name="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Current Image:</label>
                        <img id="editImage" src="" class="img-fluid w-100 rounded border" style="max-height: 400px; object-fit: contain;">
                    </div>

                    <div class="form-group">
                        <label for="editNewImage">New Image:</label>
                        <input type="file" id="editNewImage" name="file_upload" 
                               class="form-control-file"
                               accept="image/jpeg,image/png,image/gif,image/webp,image/bmp">
                        <input type="hidden" id="editOldImage" name="old_image" class="form-control-file">
                        <small class="form-text text-muted">
                            Allowed file types: .jpg, .jpeg, .png, .gif, .webp, .bmp (Max 5MB)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="editDescription">Description:</label>
                        <textarea id="editDescription" name="description" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="editIsActive">Status:</label>
                        <select id="editIsActive" name="is_active" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Display file name in label when selected
    document.getElementById("file-upload").addEventListener("change", function() {
        let fileName = this.files[0] ? this.files[0].name : "Choose file";
        this.nextElementSibling.innerText = fileName;
    });
</script>

<script>
    $('#editModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var title = button.data('title');
        var image = button.data('image');
        var description = button.data('description');
        var isActive = button.data('is-active');

        var modal = $(this);
        modal.find('#editId').val(id);
        modal.find('#editTitle').val(title);
        modal.find('#editDescription').val(description);
        modal.find('#editOldImage').val(image);
        modal.find('#editIsActive').val(isActive);

        if (image) {
            modal.find('#editImage').attr('src', image).show();
        } else {
            modal.find('#editImage').hide();
        }
    });
</script>

<?php include('includes/footer.php'); ?>