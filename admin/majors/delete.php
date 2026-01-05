<?php
/**
 * Delete Major
 * Handles major deletion with cascade checks
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

// Check if major has materials
$stmt = $pdo->prepare("SELECT COUNT(*) FROM materials WHERE major_id = ?");
$stmt->execute([$id]);
$materialCount = $stmt->fetchColumn();

// If has materials, show warning and redirect
if ($materialCount > 0) {
    flashMessage('error', "Cannot delete major '{$major['name']}'. It has {$materialCount} material(s) assigned. Delete materials first or reassign them.");
    redirect('index.php');
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        flashMessage('error', 'Invalid request. Please try again.');
        redirect('index.php');
    }
    
    try {
        // Delete the major
        $stmt = $pdo->prepare("DELETE FROM majors WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete associated image if exists
        if ($major['image_url'] && file_exists(__DIR__ . '/../../' . $major['image_url'])) {
            deleteFile($major['image_url']);
        }
        
        logAdminActivity('delete', 'majors', $id);
        flashMessage('success', 'Major deleted successfully.');
        redirect('index.php');
    } catch (Exception $e) {
        flashMessage('error', 'Failed to delete major. Please try again.');
        error_log("Delete major error: " . $e->getMessage());
        redirect('index.php');
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set page title
$pageTitle = 'Delete Major - ' . $major['name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="delete-confirmation">
    <div class="delete-card">
        <div class="delete-header">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>Confirm Deletion</h1>
            <p>You are about to delete the following major:</p>
        </div>
        
        <div class="delete-details">
            <div class="detail-item">
                <strong>Name:</strong>
                <span><?php echo sanitize($major['name']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Description:</strong>
                <span><?php echo truncateText(sanitize($major['description']), 100); ?></span>
            </div>
            <div class="detail-item">
                <strong>Created:</strong>
                <span><?php echo formatDateTime($major['created_at']); ?></span>
            </div>
        </div>
        
        <?php if ($materialCount > 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Cannot Delete:</strong> This major has <?php echo $materialCount; ?> material(s) assigned. 
                    You must delete or reassign these materials before deleting this major.
                </div>
            </div>
            
            <div class="delete-actions">
                <a href="index.php" class="btn btn-secondary">Back to Majors</a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Warning:</strong> This action cannot be undone. The major will be permanently deleted.
                </div>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="confirm_delete" value="1">
                
                <div class="delete-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Delete Major
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
.delete-confirmation {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 20px;
}

.delete-card {
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
    max-width: 500px;
    width: 100%;
    overflow: hidden;
}

.delete-header {
    background: #fef3c7;
    padding: 30px;
    text-align: center;
    border-bottom: 1px solid #f59e0b;
}

.delete-icon {
    font-size: 3rem;
    color: #f59e0b;
    margin-bottom: 15px;
}

.delete-header h1 {
    color: #92400e;
    font-size: 1.5rem;
    margin-bottom: 8px;
}

.delete-header p {
    color: #a16207;
}

.delete-details {
    padding: 30px;
    background: #f9fafb;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e5e7eb;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item strong {
    color: var(--text-primary);
}

.detail-item span {
    color: var(--text-secondary);
    text-align: right;
    flex: 1;
    margin-left: 20px;
}

.delete-actions {
    padding: 30px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .delete-actions {
        flex-direction: column;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>