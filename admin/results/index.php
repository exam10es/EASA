<?php
/**
 * Exam Results Management - List View
 * Displays all exam results with filtering and export
 */

require_once __DIR__ . '/../../includes/functions.php';
requireAdminAuth();

// Get database connection
$pdo = getDBConnection();

// Get filters
$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
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
    $where .= ' WHERE (er.student_name LIKE ?)';
    $params = ["%$search%"];
}

if ($dateFrom) {
    if ($where) {
        $where .= ' AND DATE(er.completed_at) >= ?';
    } else {
        $where = ' WHERE DATE(er.completed_at) >= ?';
    }
    $params[] = $dateFrom;
}

if ($dateTo) {
    if ($where) {
        $where .= ' AND DATE(er.completed_at) <= ?';
    } else {
        $where = ' WHERE DATE(er.completed_at) <= ?';
    }
    $params[] = $dateTo;
}

if ($majorId > 0) {
    if ($where) {
        $where .= ' AND mj.id = ?';
    } else {
        $where = ' WHERE mj.id = ?';
    }
    $params[] = $majorId;
}

if ($materialId > 0) {
    if ($where) {
        $where .= ' AND ma.id = ?';
    } else {
        $where = ' WHERE ma.id = ?';
    }
    $params[] = $materialId;
}

if ($chapterId > 0) {
    if ($where) {
        $where .= ' AND ch.id = ?';
    } else {
        $where = ' WHERE ch.id = ?';
    }
    $params[] = $chapterId;
}

// Get total count
$countQuery = "
    SELECT COUNT(*) 
    FROM exam_results er
    JOIN chapters ch ON er.chapter_id = ch.id
    JOIN materials ma ON ch.material_id = ma.id
    JOIN majors mj ON ma.major_id = mj.id
    " . $where;
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

// Get pagination params
$pagination = getPaginationParams($page, $totalItems);

// Get results
$query = "
    SELECT er.*, ch.title as chapter_title, ch.chapter_number,
           ma.name as material_name, mj.name as major_name
    FROM exam_results er
    JOIN chapters ch ON er.chapter_id = ch.id
    JOIN materials ma ON ch.material_id = ma.id
    JOIN majors mj ON ma.major_id = mj.id
    " . $where . "
    ORDER BY er.completed_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set page title
$pageTitle = 'Exam Results';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="header-actions">
        <h1>Exam Results</h1>
        <div class="export-actions">
            <a href="export.php<?php echo http_build_query($_GET); ?>" class="btn btn-secondary">
                <i class="fas fa-download"></i>
                Export CSV
            </a>
        </div>
    </div>
    
    <div class="filters-bar">
        <form method="GET" action="">
            <div class="filter-group">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search student name..." 
                           value="<?php echo sanitize($search); ?>">
                </div>
                
                <input type="date" name="date_from" value="<?php echo sanitize($dateFrom); ?>" 
                       placeholder="From Date">
                <input type="date" name="date_to" value="<?php echo sanitize($dateTo); ?>" 
                       placeholder="To Date">
                
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
                
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-filter"></i>
                    Filter
                </button>
                
                <a href="index.php" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="data-table-container">
    <?php if (empty($results)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <h3>No results found</h3>
            <p>No exam results match your current filters.</p>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-times"></i>
                Clear Filters
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Major</th>
                        <th>Material</th>
                        <th>Chapter</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Time</th>
                        <th>Date</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?php echo $result['id']; ?></td>
                            <td><?php echo sanitize($result['student_name']); ?></td>
                            <td><span class="badge badge-primary"><?php echo sanitize($result['major_name']); ?></span></td>
                            <td><span class="badge badge-secondary"><?php echo sanitize($result['material_name']); ?></span></td>
                            <td>
                                <span class="badge badge-info">
                                    Ch <?php echo $result['chapter_number']; ?>: <?php echo truncateText(sanitize($result['chapter_title']), 20); ?>
                                </span>
                            </td>
                            <td><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?></td>
                            <td>
                                <span class="percentage-badge <?php echo $result['percentage'] >= PASSING_PERCENTAGE ? 'pass' : 'fail'; ?>">
                                    <?php echo $result['percentage']; ?>%
                                </span>
                            </td>
                            <td><?php echo $result['time_taken_seconds'] ? gmdate('i:s', $result['time_taken_seconds']) : '--:--'; ?></td>
                            <td><?php echo formatDateTime($result['completed_at']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-info" title="View Details">
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
                $queryParams = [];
                if ($search) $queryParams['search'] = $search;
                if ($dateFrom) $queryParams['date_from'] = $dateFrom;
                if ($dateTo) $queryParams['date_to'] = $dateTo;
                if ($majorId) $queryParams['major_id'] = $majorId;
                if ($materialId) $queryParams['material_id'] = $materialId;
                if ($chapterId) $queryParams['chapter_id'] = $chapterId;
                $queryString = $queryParams ? '?' . http_build_query($queryParams) : '';
                echo displayPagination($pagination, $baseUrl . $queryString);
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
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
        fetch('../chapters/get_chapters.php?material_id=' + materialId)
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