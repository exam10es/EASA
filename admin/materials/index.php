<?php
/**
 * Materials Management - List View
 * Displays all materials with filtering, search, and pagination
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
        // Check if material has chapters
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chapters WHERE material_id = ?");
        $stmt->execute([$id]);
        $chapterCount = $stmt->fetchColumn();
        
        if ($chapterCount > 0) {
            flashMessage('error', "This material has {$chapterCount} chapter(s). Delete them first or reassign.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
            $stmt->execute([$id]);
            logAdminActivity('delete', 'materials', $id);
            flashMessage('success', 'Material deleted successfully.');
        }
    } catch (Exception $e) {
        flashMessage('error', 'Failed to delete material.');
        error_log("Delete material error: " . $e->getMessage());
    }
    
    redirect('index.php');
}

// Get filters
$search = trim($_GET['search'] ?? '');
$majorId = isset($_GET['major_id']) ? (int)$_GET['major_id'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));

// Get all majors for filter
$majors = $pdo->query("SELECT id, name FROM majors WHERE is_active = 1 ORDER BY name ASC")
               ->fetchAll(PDO::FETCH_KEY_PAIR);

// Build query
$where = '';
$params = [];

if ($search) {
    $where .= ' WHERE (ma.name LIKE ? OR ma.description LIKE ?)';
    $params = ["%$search%", "%$search%"];
}

if ($majorId > 0) {
    if ($where) {
        $where .= ' AND ma.major_id = ?';
    } else {
        $where = ' WHERE ma.major_id = ?';
    }
    $params[] = $majorId;
}

// Get total count
$countQuery = "
    SELECT COUNT(*) 
    FROM materials ma
    JOIN majors mj ON ma.major_id = mj.id
    " . $where;
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

// Get pagination params
$pagination = getPaginationParams($page, $totalItems);

// Get materials
$query = "
    SELECT ma.*, mj.name as major_name,
           COUNT(ch.id) as chapter_count
    FROM materials ma
    JOIN majors mj ON ma.major_id = mj.id
    LEFT JOIN chapters ch ON ma.id = ch.material_id
    " . $where . "
    GROUP BY ma.id
    ORDER BY mj.name, ma.name ASC
    LIMIT ? OFFSET ?
";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materials = $stmt->fetchAll();

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set page title
$pageTitle = 'Materials Management';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="header-actions">
        <h1>Materials Management</h1>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Add New Material
        </a>
    </div>
    
    <div class="filters-bar">
        <form method="GET" action="">
            <div class="filter-group">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search materials..." 
                           value="<?php echo sanitize($search); ?>">
                    <?php if ($search): ?>
                        <a href="index.php" class="clear-search" title="Clear search">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <select name="major_id" onchange="this.form.submit()">
                    <option value="">All Majors</option>
                    <?php foreach ($majors as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo $majorId == $id ? 'selected' : ''; ?>>
                            <?php echo sanitize($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if ($search || $majorId): ?>
                    <a href="index.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="data-table-container">
    <?php if (empty($materials)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-layer-group"></i>
            </div>
            <h3>No materials found</h3>
            <p><?php echo $search || $majorId ? 'Try adjusting your filters.' : 'Get started by creating your first material.'; ?></p>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add Material
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th>Major</th>
                        <th>Material Name</th>
                        <th>Description</th>
                        <th>Chapters</th>
                        <th>Created</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materials as $material): ?>
                        <tr>
                            <td><?php echo $material['id']; ?></td>
                            <td>
                                <span class="badge badge-primary"><?php echo sanitize($material['major_name']); ?></span>
                            </td>
                            <td>
                                <strong><?php echo sanitize($material['name']); ?></strong>
                            </td>
                            <td>
                                <div class="description-cell">
                                    <?php echo truncateText(sanitize($material['description']), 80); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?php echo $material['chapter_count']; ?></span>
                            </td>
                            <td>
                                <?php echo formatDate($material['created_at']); ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit.php?id=<?php echo $material['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="" class="delete-form" 
                                          onsubmit="return confirmDelete('<?php echo addslashes($material['name']); ?>', <?php echo $material['chapter_count']; ?>)">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="delete_id" value="<?php echo $material['id']; ?>">
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
                <?php 
                $baseUrl = 'index.php';
                $params = [];
                if ($search) $params['search'] = $search;
                if ($majorId) $params['major_id'] = $majorId;
                $queryString = $params ? '?' . http_build_query($params) : '';
                echo displayPagination($pagination, $baseUrl . $queryString);
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function confirmDelete(name, chapterCount) {
    let message = 'Are you sure you want to delete "' + name + '"?';
    if (chapterCount > 0) {
        message += '\\n\\nThis material has ' + chapterCount + ' chapter(s). You must delete or reassign them first.';
        alert(message);
        return false;
    }
    return confirm(message);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>