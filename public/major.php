<?php
/**
 * Major Detail Page
 * Displays materials for a specific major
 */

require_once __DIR__ . '/../includes/functions.php';

// Get major ID
$majorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($majorId <= 0) {
    flashMessage('error', 'Invalid major selected.');
    redirect('index.php');
}

// Get database connection
try {
    $pdo = getDBConnection();
    
    // Get major info
    $stmt = $pdo->prepare("SELECT * FROM majors WHERE id = ? AND is_active = 1");
    $stmt->execute([$majorId]);
    $major = $stmt->fetch();
    
    if (!$major) {
        flashMessage('error', 'Major not found.');
        redirect('index.php');
    }
    
    // Get materials for this major
    $materials = $pdo->prepare("
        SELECT ma.*, COUNT(DISTINCT ch.id) as chapter_count,
               COUNT(DISTINCT q.id) as question_count
        FROM materials ma
        LEFT JOIN chapters ch ON ma.id = ch.material_id AND ch.is_active = 1
        LEFT JOIN questions q ON ch.id = q.chapter_id AND q.is_active = 1
        WHERE ma.major_id = ? AND ma.is_active = 1
        GROUP BY ma.id
        ORDER BY ma.name ASC
    ");
    $materials->execute([$majorId]);
    $materials = $materials->fetchAll();
    
} catch (Exception $e) {
    flashMessage('error', 'Failed to load major.');
    redirect('index.php');
}

// Set page title
$pageTitle = sanitize($major['name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo sanitize($major['name']); ?></span>
    </div>
    
    <div class="major-info">
        <h1><?php echo sanitize($major['name']); ?></h1>
        <p><?php echo nl2br(sanitize($major['description'])); ?></p>
        
        <div class="major-stats">
            <span class="stat">
                <i class="fas fa-layer-group"></i>
                <?php echo count($materials); ?> Materials
            </span>
        </div>
    </div>
</div>

<div class="materials-section">
    <?php if (empty($materials)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-layer-group"></i>
            </div>
            <h3>No materials available</h3>
            <p>There are no materials available for this major yet. Please check back later.</p>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                Back to Subjects
            </a>
        </div>
    <?php else: ?>
        <div class="materials-grid">
            <?php foreach ($materials as $material): ?>
                <div class="material-card" data-aos="fade-up">
                    <div class="material-header">
                        <h3><?php echo sanitize($material['name']); ?></h3>
                        <p><?php echo truncateText(sanitize($material['description']), 120); ?></p>
                    </div>
                    
                    <div class="material-stats">
                        <span class="stat">
                            <i class="fas fa-list-ol"></i>
                            <?php echo $material['chapter_count']; ?> Chapters
                        </span>
                        <span class="stat">
                            <i class="fas fa-question-circle"></i>
                            <?php echo $material['question_count']; ?> Questions
                        </span>
                    </div>
                    
                    <a href="material.php?id=<?php echo $material['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-book-open"></i>
                        View Material
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
AOS.init({
    duration: 1000,
    once: true
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>