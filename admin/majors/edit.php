<?php
/**
 * Edit Major
 * Handles updating existing majors with image management
 */

require_once __DIR__ . '/../../includes/functions.php';
requireAdminAuth();

// Get major ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flashMessage('error', 'Invalid major ID.');
    redirect('index.php');
}

// Get database connection
$pdo = getDBConnection();

// Get major data
$stmt = $pdo->prepare("SELECT * FROM majors WHERE id = ?");
$stmt->execute([$id]);
$major = $stmt->fetch();

if (!$major) {
    flashMessage('error', 'Major not found.');
    redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        flashMessage('error', 'Invalid request. Please try again.');
        redirect('edit.php?id=' . $id);
    }
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $removeImage = isset($_POST['remove_image']);
    
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
    
    // Check if major name already exists (excluding current major)
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM majors WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'A major with this name already exists.';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to check for existing major.';
        }
    }
    
    // Handle image upload or removal
    $imageUrl = $major['image_url'];
    
    if ($removeImage && $imageUrl) {
        deleteFile($imageUrl);
        $imageUrl = null;
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Delete old image if exists
        if ($major['image_url']) {
            deleteFile($major['image_url']);
        }
        
        $uploadedFile = uploadFile($_FILES['image'], 'uploads/majors/');
        if ($uploadedFile) {
            $imageUrl = $uploadedFile;
        } else {
            $errors[] = 'Failed to upload image. Please use JPG, PNG, or GIF format (max 2MB).';
        }
    }
    
    // Update major if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE majors 
                SET name = ?, description = ?, image_url = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $imageUrl, $id]);
            
            logAdminActivity('update', 'majors', $id);
            flashMessage('success', 'Major updated successfully!');
            
            // Refresh major data
            $stmt = $pdo->prepare("SELECT * FROM majors WHERE id = ?");
            $stmt->execute([$id]);
            $major = $stmt->fetch();
        } catch (Exception $e) {
            $errors[] = 'Failed to update major. Please try again.';
            error_log("Update major error: " . $e->getMessage());
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set page title
$pageTitle = 'Edit Major - ' . $major['name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h1>Edit Major</h1>
        <p>Update the details for "<?php echo sanitize($major['name']); ?>"</p>
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
                       value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : sanitize($major['name']); ?>">
                <small class="form-help">Maximum 100 characters. This will be displayed to students.</small>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description <span class="required">*</span></label>
                <textarea id="description" name="description" class="form-textarea" 
                          placeholder="Brief description of what this major covers..." required
                          rows="4"><?php echo isset($_POST['description']) ? sanitize($_POST['description']) : sanitize($major['description']); ?></textarea>
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
                        <?php if ($major['image_url'] && file_exists(__DIR__ . '/../../' . $major['image_url'])): ?>
                            <div class="image-preview" id="image-preview" style="display: block;">
                                <img id="preview-img" src="<?php echo fileUrl($major['image_url']); ?>" alt="Current image">
                                <button type="button" class="remove-image" onclick="removeImage()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="current-image-actions">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="remove_image" value="1">
                                    <span class="checkmark"></span>
                                    Remove current image
                                </label>
                            </div>
                        <?php else: ?>
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
                        <?php endif; ?>
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
                Update Major
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
            if (placeholder) placeholder.style.display = 'none';
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
    if (placeholder) placeholder.style.display = 'block';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>