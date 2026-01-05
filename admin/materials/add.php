<?php
/**
 * Add New Material
 * Handles the creation of new materials under majors
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
    
    $majorId = (int)($_POST['major_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate input
    $errors = [];
    
    if ($majorId <= 0) {
        $errors[] = 'Please select a major.';
    }
    
    if (empty($name)) {
        $errors[] = 'Material name is required.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Material name must be 100 characters or less.';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required.';
    }
    
    // Check if material name already exists under this major
    if (empty($errors) && $majorId > 0) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM materials WHERE name = ? AND major_id = ?");
            $stmt->execute([$name, $majorId]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'A material with this name already exists under the selected major.';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to check for existing material.';
        }
    }
    
    // Create material if no errors
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO materials (major_id, name, description, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$majorId, $name, $description]);
            
            $materialId = $pdo->lastInsertId();
            logAdminActivity('create', 'materials', $materialId);
            
            flashMessage('success', 'Material created successfully!');
            redirect('edit.php?id=' . $materialId);
        } catch (Exception $e) {
            $errors[] = 'Failed to create material. Please try again.';
            error_log("Create material error: " . $e->getMessage());
        }
    }
}

// Get all majors for dropdown
$majors = [];
try {
    $pdo = getDBConnection();
    $majors = $pdo->query("SELECT id, name FROM majors WHERE is_active = 1 ORDER BY name ASC")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    flashMessage('error', 'Failed to load majors.');
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set page title
$pageTitle = 'Add New Material';
include __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h1>Add New Material</h1>
        <p>Create a new course or module under an existing major.</p>
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
    
    <form method="POST" action="" class="material-form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        
        <div class="form-section">
            <h3>Material Information</h3>
            
            <div class="form-group">
                <label for="major_id" class="form-label">Major <span class="required">*</span></label>
                <select id="major_id" name="major_id" class="form-select" required>
                    <option value="">Select a major...</option>
                    <?php foreach ($majors as $id => $name): ?>
                        <option value="<?php echo $id; ?>" 
                                <?php echo (isset($_POST['major_id']) && $_POST['major_id'] == $id) ? 'selected' : ''; ?>>
                            <?php echo sanitize($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-help">Choose the major this material belongs to.</small>
            </div>
            
            <div class="form-group">
                <label for="name" class="form-label">Material Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-input" 
                       placeholder="e.g., Introduction to Programming" required
                       value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
                <small class="form-help">Maximum 100 characters. This will be displayed to students.</small>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description <span class="required">*</span></label>
                <textarea id="description" name="description" class="form-textarea" 
                          placeholder="Brief description of what this material covers..." required
                          rows="4"><?php echo isset($_POST['description']) ? sanitize($_POST['description']) : ''; ?></textarea>
                <small class="form-help">Describe the topics and concepts covered in this material.</small>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Create Material
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>