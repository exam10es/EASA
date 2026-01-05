<?php
/**
 * Questions Management - List View
 * Displays all questions with advanced filtering and bulk actions
 */

require_once __DIR__ . '/../../includes/functions.php';
requireAdminAuth();

// Get database connection
$pdo = getDBConnection();

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected']) && isset($_POST['csrf_token'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        flashMessage('error', 'Invalid request. Please try again.');
        redirect('index.php');
    }
    
    $selected = array_map('intval', $_POST['selected']);
    $action = $_POST['bulk_action'];
    
    if (!empty($selected)) {
        try {
            $ids = implode(',', $selected);
            
            switch ($action) {
                case 'delete':
                    $pdo->exec("DELETE FROM questions WHERE id IN ($ids)");
                    logAdminActivity('bulk_delete', 'questions', 0);
                    flashMessage('success', count($selected) . ' questions deleted successfully.');
                    break;
                    
                case 'export':
                    // Export to CSV
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="questions_' . date('Y-m-d') . '.csv"');
                    
                    $output = fopen('php://output', 'w');
                    fputcsv($output, ['ID', 'Major', 'Material', 'Chapter', 'Question', 'Choice A', 'Choice B', 'Choice C', 'Correct Answer', 'Explanation']);
                    
                    $stmt = $pdo->query("
                        SELECT q.id, mj.name as major, ma.name as material, ch.title as chapter,
                               q.question_text, q.choice_a, q.choice_b, q.choice_c, 
                               q.correct_answer, q.explanation
                        FROM questions q
                        JOIN chapters ch ON q.chapter_id = ch.id
                        JOIN materials ma ON ch.material_id = ma.id
                        JOIN majors mj ON ma.major_id = mj.id
                        WHERE q.id IN ($ids)
                        ORDER BY mj.name, ma.name, ch.chapter_number
                    ");
                    
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, $row);
                    }
                    fclose($output);
                    exit;
                    break;
            }
        } catch (Exception $e) {
            flashMessage('error', 'Failed to perform bulk action.');
            error_log("Bulk action error: " . $e->getMessage());
        }
    }
    
    redirect('index.php');
}

// Get filters
$search = trim($_GET['search'] ?? '');
$majorId = isset($_GET['major_id']) ? (int)$_GET['major_id'] : 0;
$materialId = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
$chapterId = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;
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

// Get chapters based on selected material
$chapters = [];
if ($materialId > 0) {
    $stmt = $pdo->prepare("SELECT id, title FROM chapters WHERE material_id = ? AND is_active = 1 ORDER BY chapter_number ASC");
    $stmt->execute([$materialId]);
    $chapters = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Build query
$where = '';
$params = [];

if ($search) {
    $where .= ' WHERE (q.question_text LIKE ?)';
    $params = ["%$search%"];
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

if ($chapterId > 0) {
    if ($where) {
        $where .= ' AND q.chapter_id = ?';
    } else {
        $where = ' WHERE q.chapter_id = ?';
    }
    $params[] = $chapterId;
}

// Get total count
$countQuery = "
    SELECT COUNT(*) 
    FROM questions q
    JOIN chapters ch ON q.chapter_id = ch.id
    JOIN materials ma ON ch.material_id = ma.id
    JOIN majors mj ON ma.major_id = mj.id
    " . $where;
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

// Get pagination params
$pagination = getPaginationParams($page, $totalItems, QUESTIONS_PER_PAGE);

// Get questions
$query = "
    SELECT q.*, ch.title as chapter_title, ch.chapter_number,
           ma.name as material_name, mj.name as major_name
    FROM questions q
    JOIN chapters ch ON q.chapter_id = ch.id
    JOIN materials ma ON ch.material_id = ma.id
    JOIN majors mj ON ma.major_id = mj.id
    " . $where . "
    ORDER BY mj.name, ma.name, ch.chapter_number, q.id
    LIMIT ? OFFSET ?
";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$questions = $stmt->fetchAll();

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set page title
$pageTitle = 'Questions Management';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="header-actions">
        <h1>Questions Management</h1>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Add New Question
        </a>
    </div>
    
    <div class="filters-bar">
        <form method="GET" action="" id="filters-form">
            <div class="filter-group">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search questions..." 
                           value="<?php echo sanitize($search); ?>">
                    <?php if ($search): ?>
                        <a href="index.php" class="clear-search" title="Clear search">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <select name="major_id" id="major-filter">
                    <option value="">All Majors</option>
                    <?php foreach ($majors as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo $majorId == $id ? 'selected' : ''; ?>>
                            <?php echo sanitize($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="material_id" id="material-filter">
                    <option value="">All Materials</option>
                    <?php foreach ($materials as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo $materialId == $id ? 'selected' : ''; ?>>
                            <?php echo sanitize($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="chapter_id" id="chapter-filter">
                    <option value="">All Chapters</option>
                    <?php foreach ($chapters as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo $chapterId == $id ? 'selected' : ''; ?>>
                            <?php echo sanitize($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if ($search || $majorId || $materialId || $chapterId): ?>
                    <a href="index.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="data-table-container">
    <form method="POST" action="" id="bulk-form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        
        <div class="table-toolbar">
            <div class="bulk-actions">
                <select name="bulk_action" id="bulk-action">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete Selected</option>
                    <option value="export">Export Selected</option>
                </select>
                <button type="submit" class="btn btn-secondary" onclick="return confirmBulkAction()">Apply</button>
            </div>
            
            <div class="table-info">
                <span><?php echo number_format($totalItems); ?> questions</span>
            </div>
        </div>
        
        <?php if (empty($questions)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3>No questions found</h3>
                <p><?php echo $search || $majorId || $materialId || $chapterId ? 'Try adjusting your filters.' : 'Get started by creating your first question.'; ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add Question
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="select-all" onclick="toggleSelectAll()">
                            </th>
                            <th width="60">ID</th>
                            <th>Major</th>
                            <th>Material</th>
                            <th>Chapter</th>
                            <th>Question</th>
                            <th>Correct</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $question): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected[]" value="<?php echo $question['id']; ?>" class="row-checkbox">
                                </td>
                                <td><?php echo $question['id']; ?></td>
                                <td>
                                    <span class="badge badge-primary"><?php echo sanitize($question['major_name']); ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo sanitize($question['material_name']); ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        Ch <?php echo $question['chapter_number']; ?>: <?php echo truncateText(sanitize($question['chapter_title']), 20); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="question-cell">
                                        <?php echo truncateText(sanitize($question['question_text']), 80); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="correct-answer"><?php echo $question['correct_answer']; ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit.php?id=<?php echo $question['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view.php?id=<?php echo $question['id']; ?>" class="btn btn-sm btn-secondary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
                    if ($chapterId) $params['chapter_id'] = $chapterId;
                    $queryString = $params ? '?' . http_build_query($params) : '';
                    echo displayPagination($pagination, $baseUrl . $queryString);
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </form>
</div>

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function confirmBulkAction() {
    const action = document.getElementById('bulk-action').value;
    const selected = document.querySelectorAll('.row-checkbox:checked');
    
    if (!action) {
        alert('Please select an action.');
        return false;
    }
    
    if (selected.length === 0) {
        alert('Please select at least one question.');
        return false;
    }
    
    if (action === 'delete') {
        return confirm('Are you sure you want to delete ' + selected.length + ' selected question(s)? This action cannot be undone.');
    }
    
    return true;
}

// Load materials when major is selected
function loadMaterials(majorId, selectedMaterialId = 0) {
    const materialFilter = document.getElementById('material-filter');
    materialFilter.innerHTML = '<option value="">All Materials</option>';
    
    if (majorId) {
        fetch('../materials/get_materials.php?major_id=' + majorId)
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

// Load chapters when material is selected
function loadChapters(materialId, selectedChapterId = 0) {
    const chapterFilter = document.getElementById('chapter-filter');
    chapterFilter.innerHTML = '<option value="">All Chapters</option>';
    
    if (materialId) {
        fetch('get_chapters.php?material_id=' + materialId)
            .then(response => response.json())
            .then(chapters => {
                chapters.forEach(chapter => {
                    const option = document.createElement('option');
                    option.value = chapter.id;
                    option.textContent = 'Ch ' + chapter.chapter_number + ': ' + chapter.title;
                    if (chapter.id == selectedChapterId) {
                        option.selected = true;
                    }
                    chapterFilter.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading chapters:', error));
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const majorFilter = document.getElementById('major-filter');
    const materialFilter = document.getElementById('material-filter');
    const chapterFilter = document.getElementById('chapter-filter');
    
    // Load existing filters
    if (majorFilter.value) {
        loadMaterials(majorFilter.value, materialFilter.value);
    }
    if (materialFilter.value) {
        loadChapters(materialFilter.value, chapterFilter.value);
    }
    
    // Add event listeners
    majorFilter.addEventListener('change', function() {
        loadMaterials(this.value);
        chapterFilter.innerHTML = '<option value="">All Chapters</option>';
    });
    
    materialFilter.addEventListener('change', function() {
        loadChapters(this.value);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>