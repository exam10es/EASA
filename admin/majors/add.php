<?php
/**
 * Add New Major
 * Handles the creation of new majors with image upload
 */

require_once __DIR__ . '/../../includes/functions.php';
requireAdminAuth();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        flashMessage('error', 'Invalid request. Please try again.');
        redirect('add.php');
    }
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Major name is required.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Major name must be 100 characters or less.';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required.';
    }
    
    // Check if major name already exists
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM majors WHERE name = ?");
            $stmt->execute([$name]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'A major with this name already exists.';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to check for existing major.';
        }
    }
    
    // Handle image upload
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = uploadFile($_FILES['image'], 'uploads/majors/');
        if ($uploadedFile) {
            $imageUrl = $uploadedFile;
        } else {
            $errors[] = 'Failed to upload image. Please use JPG, PNG, or GIF format (max 2MB).';
        }
    }
    
    // Create major if no errors
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO majors (name, description, image_url, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $description, $imageUrl]);
            
            $majorId = $pdo->lastInsertId();
            logAdminActivity('create', 'majors', $majorId);
            
            flashMessage('success', 'Major created successfully!');
            redirect('edit.php?id=' . $majorId);
        } catch (Exception $e) {
            // Delete uploaded image if database failed
            if ($imageUrl) {
                deleteFile($imageUrl);
            }
            $errors[] = 'Failed to create major. Please try again.';
            error_log("Create major error: " . $e->getMessage());
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set page title
$pageTitle = 'Add New Major';
include __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h1>Add New Major</h1>
        <p>Create a new subject or category for your examination system.</p>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo sanitize($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data" class="major-form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        
        <div class="form-section">
            <h3>Basic Information</h3>
            
            <div class="form-group">
                <label for="name" class="form-label">Major Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-input" 
                       placeholder="e.g., Computer Science" required
                       value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
                <small class="form-help">Maximum 100 characters. This will be displayed to students.</small>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description <span class="required">*</span></label>
                <textarea id="description" name="description" class="form-textarea" 
                          placeholder="Brief description of what this major covers..." required
                          rows="4"><?php echo isset($_POST['description']) ? sanitize($_POST['description']) : ''; ?></textarea>
                <small class="form-help">Describe the subjects and topics covered in this major.</small>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Major Image</h3>
            
            <div class="form-group">
                <label for="image" class="form-label">Upload Image</label>
                <div class="file-upload">
                    <input type="file" id="image" name="image" accept="image/*" 
                           onchange="previewImage(this)">
                    <div class="file-upload-display">
                        <div class="upload-placeholder" id="upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to select an image</p>
                            <small>JPG, PNG, or GIF (max 2MB)</small>
                        </div>
                        <div class="image-preview" id="image-preview" style="display: none;">
                            <img id="preview-img" alt="Preview">
                            <button type="button" class="remove-image" onclick="removeImage()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <small class="form-help">Optional. Recommended size: 400x300px for best display.</small>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Create Major
            </button>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const placeholder = document.getElementById('upload-placeholder');
    const img = document.getElementById('preview-img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            placeholder.style.display = 'none';
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage() {
    const input = document.getElementById('image');
    const preview = document.getElementById('image-preview');
    const placeholder = document.getElementById('upload-placeholder');
    
    input.value = '';
    preview.style.display = 'none';
    placeholder.style.display = 'block';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>