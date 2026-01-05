<?php
/**
 * Majors Management - List View
 * Displays all majors with search, pagination, and actions
 */

require_once __DIR__ . '/../../includes/functions.php';
requireAdminAuth();

// Get database connection
$pdo = getDBConnection();

// Handle delete action
if (isset($_POST['delete_id']) && isset($_POST['csrf_token'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        flashMessage('error', 'Invalid request. Please try again.');
        redirect('index.php');
    }
    
    $id = (int)$_POST['delete_id'];
    
    try {
        // Check if major has materials
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM materials WHERE major_id = ?");
        $stmt->execute([$id]);
        $materialCount = $stmt->fetchColumn();
        
        if ($materialCount > 0) {
            flashMessage('error', "Cannot delete major. It has {$materialCount} material(s) assigned. Delete materials first or reassign them.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM majors WHERE id = ?");
            $stmt->execute([$id]);
            logAdminActivity('delete', 'majors', $id);
            flashMessage('success', 'Major deleted successfully.');
        }
    } catch (Exception $e) {
        flashMessage('error', 'Failed to delete major.');
        error_log("Delete major error: " . $e->getMessage());
    }
    
    redirect('index.php');
}

// Get search query
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

// Build query
$where = '';
$params = [];

if ($search) {
    $where = ' WHERE (m.name LIKE ? OR m.description LIKE ?)';
    $params = ["%$search%", "%$search%"];
}

// Get total count
$countQuery = "SELECT COUNT(*) FROM majors m" . $where;
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

// Get pagination params
$pagination = getPaginationParams($page, $totalItems);

// Get majors
$query = "
    SELECT m.*, 
           COUNT(mt.id) as material_count,
           (SELECT COUNT(*) FROM chapters ch 
            JOIN materials mt2 ON ch.material_id = mt2.id 
            WHERE mt2.major_id = m.id) as chapter_count
    FROM majors m
    LEFT JOIN materials mt ON m.id = mt.major_id
    " . $where . "
    GROUP BY m.id
    ORDER BY m.name ASC
    LIMIT ? OFFSET ?
";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$majors = $stmt->fetchAll();

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set page title
$pageTitle = 'Majors Management';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="header-actions">
        <h1>Majors Management</h1>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Add New Major
        </a>
    </div>
    
    <div class="search-bar">
        <form method="GET" action="">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search majors..." 
                       value="<?php echo sanitize($search); ?>">
                <?php if ($search): ?>
                    <a href="index.php" class="clear-search" title="Clear search">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="data-table-container">
    <?php if (empty($majors)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <h3>No majors found</h3>
            <p><?php echo $search ? 'Try adjusting your search terms.' : 'Get started by creating your first major.'; ?></p>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add Major
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Materials</th>
                        <th>Chapters</th>
                        <th>Created</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($majors as $major): ?>
                        <tr>
                            <td><?php echo $major['id']; ?></td>
                            <td>
                                <?php if ($major['image_url'] && file_exists(__DIR__ . '/../../' . $major['image_url'])): ?>
                                    <img src="<?php echo fileUrl($major['image_url']); ?>" 
                                         alt="<?php echo sanitize($major['name']); ?>" 
                                         class="table-image">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo sanitize($major['name']); ?></strong>
                            </td>
                            <td>
                                <div class="description-cell">
                                    <?php echo truncateText(sanitize($major['description']), 80); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $major['material_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?php echo $major['chapter_count']; ?></span>
                            </td>
                            <td>
                                <?php echo formatDate($major['created_at']); ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit.php?id=<?php echo $major['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="" class="delete-form" 
                                          onsubmit="return confirmDelete('<?php echo addslashes($major['name']); ?>', <?php echo $major['material_count']; ?>)">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="delete_id" value="<?php echo $major['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="table-footer">
                <div class="pagination-info">
                    Showing <?php echo $pagination['offset'] + 1; ?> to 
                    <?php echo min($pagination['offset'] + $pagination['limit'], $totalItems); ?> of 
                    <?php echo number_format($totalItems); ?> entries
                </div>
                <?php echo displayPagination($pagination, 'index.php' . ($search ? '?search=' . urlencode($search) : '')); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function confirmDelete(name, materialCount) {
    let message = 'Are you sure you want to delete "' + name + '"?';
    if (materialCount > 0) {
        message += '\\n\\nThis major has ' + materialCount + ' material(s). You must delete or reassign them first.';
        alert(message);
        return false;
    }
    return confirm(message);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>