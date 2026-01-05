<?php
/**
 * Chapters Management - List View
 * Displays all chapters with filtering and pagination
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
        // Check if chapter has questions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE chapter_id = ?");
        $stmt->execute([$id]);
        $questionCount = $stmt->fetchColumn();
        
        if ($questionCount > 0) {
            flashMessage('error', "This chapter has {$questionCount} question(s). Delete them first or reassign.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM chapters WHERE id = ?");
            $stmt->execute([$id]);
            logAdminActivity('delete', 'chapters', $id);
            flashMessage('success', 'Chapter deleted successfully.');
        }
    } catch (Exception $e) {
        flashMessage('error', 'Failed to delete chapter.');
        error_log("Delete chapter error: " . $e->getMessage());
    }
    
    redirect('index.php');
}

// Get filters
$search = trim($_GET['search'] ?? '');
$majorId = isset($_GET['major_id']) ? (int)$_GET['major_id'] : 0;
$materialId = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));

// Get all majors for filter
$majors = $pdo->query("SELECT id, name FROM majors WHERE is_active = 1 ORDER BY name ASC")
               ->fetchAll(PDO::FETCH_KEY_PAIR);

// Get materials based on selected major
$materials = [];
if ($majorId > 0) {
    $stmt = $pdo->prepare("SELECT id, name FROM materials WHERE major_id = ? AND is_active = 1 ORDER BY name ASC");
    $stmt->execute([$majorId]);
    $materials = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Build query
$where = '';
$params = [];

if ($search) {
    $where .= ' WHERE (ch.title LIKE ? OR ch.description LIKE ?)';
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

if ($materialId > 0) {
    if ($where) {
        $where .= ' AND ch.material_id = ?';
    } else {
        $where = ' WHERE ch.material_id = ?';
    }
    $params[] = $materialId;
}

// Get total count
$countQuery = "
    SELECT COUNT(*) 
    FROM chapters ch
    JOIN materials ma ON ch.material_id = ma.id
    JOIN majors mj ON ma.major_id = mj.id
    " . $where;
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

// Get pagination params
$pagination = getPaginationParams($page, $totalItems);

// Get chapters
$query = "
    SELECT ch.*, ma.name as material_name, mj.name as major_name,
           COUNT(q.id) as question_count
    FROM chapters ch
    JOIN materials ma ON ch.material_id = ma.id
    JOIN majors mj ON ma.major_id = mj.id
    LEFT JOIN questions q ON ch.id = q.chapter_id
    " . $where . "
    GROUP BY ch.id
    ORDER BY mj.name, ma.name, ch.chapter_number ASC
    LIMIT ? OFFSET ?
";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$chapters = $stmt->fetchAll();

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set page title
$pageTitle = 'Chapters Management';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="header-actions">
        <h1>Chapters Management</h1>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Add New Chapter
        </a>
    </div>
    
    <div class="filters-bar">
        <form method="GET" action="">
            <div class="filter-group">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search chapters..." 
                           value="<?php echo sanitize($search); ?>">
                    <?php if ($search): ?>
                        <a href="index.php" class="clear-search" title="Clear search">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <select name="major_id" id="major-filter" onchange="this.form.submit()">
                    <option value="">All Majors</option>
                    <?php foreach ($majors as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo $majorId == $id ? 'selected' : ''; ?>>
                            <?php echo sanitize($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="material_id" id="material-filter" onchange="this.form.submit()">
                    <option value="">All Materials</option>
                    <?php foreach ($materials as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo $materialId == $id ? 'selected' : ''; ?>>
                            <?php echo sanitize($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if ($search || $majorId || $materialId): ?>
                    <a href="index.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="data-table-container">
    <?php if (empty($chapters)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-list-ol"></i>
            </div>
            <h3>No chapters found</h3>
            <p><?php echo $search || $majorId || $materialId ? 'Try adjusting your filters.' : 'Get started by creating your first chapter.'; ?></p>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add Chapter
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th>Major</th>
                        <th>Material</th>
                        <th>Chapter #</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Questions</th>
                        <th>Created</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chapters as $chapter): ?>
                        <tr>
                            <td><?php echo $chapter['id']; ?></td>
                            <td>
                                <span class="badge badge-primary"><?php echo sanitize($chapter['major_name']); ?></span>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?php echo sanitize($chapter['material_name']); ?></span>
                            </td>
                            <td>
                                <span class="badge badge-info">Chapter <?php echo $chapter['chapter_number']; ?></span>
                            </td>
                            <td>
                                <strong><?php echo sanitize($chapter['title']); ?></strong>
                            </td>
                            <td>
                                <div class="description-cell">
                                    <?php echo truncateText(sanitize($chapter['description']), 60); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-warning"><?php echo $chapter['question_count']; ?></span>
                            </td>
                            <td>
                                <?php echo formatDate($chapter['created_at']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit.php?id=<?php echo $chapter['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="" class="delete-form" 
                                          onsubmit="return confirmDelete('<?php echo addslashes($chapter['title']); ?>', <?php echo $chapter['question_count']; ?>)">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="delete_id" value="<?php echo $chapter['id']; ?>">
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
                if ($materialId) $params['material_id'] = $materialId;
                $queryString = $params ? '?' . http_build_query($params) : '';
                echo displayPagination($pagination, $baseUrl . $queryString);
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function confirmDelete(name, questionCount) {
    let message = 'Are you sure you want to delete "' + name + '"?';
    if (questionCount > 0) {
        message += '\\n\\nThis chapter has ' + questionCount + ' question(s). You must delete or reassign them first.';
        alert(message);
        return false;
    }
    return confirm(message);
}

// Load materials when major is selected
function loadMaterials(majorId, selectedMaterialId = 0) {
    const materialFilter = document.getElementById('material-filter');
    materialFilter.innerHTML = '<option value="">All Materials</option>';
    
    if (majorId) {
        fetch('get_materials.php?major_id=' + majorId)
            .then(response => response.json())
            .then(materials => {
                materials.forEach(material => {
                    const option = document.createElement('option');
                    option.value = material.id;
                    option.textContent = material.name;
                    if (material.id == selectedMaterialId) {
                        option.selected = true;
                    }
                    materialFilter.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading materials:', error));
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const majorFilter = document.getElementById('major-filter');
    const materialFilter = document.getElementById('material-filter');
    
    if (majorFilter.value) {
        loadMaterials(majorFilter.value, materialFilter.value);
    }
    
    majorFilter.addEventListener('change', function() {
        loadMaterials(this.value);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>