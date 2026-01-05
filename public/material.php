<?php
/**
 * Material Detail Page
 * Displays chapters for a specific material
 */

require_once __DIR__ . '/../includes/functions.php';

// Get material ID
$materialId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($materialId <= 0) {
    flashMessage('error', 'Invalid material selected.');
    redirect('index.php');
}

// Get database connection
try {
    $pdo = getDBConnection();
    
    // Get material info with major
    $stmt = $pdo->prepare("
        SELECT ma.*, mj.id as major_id, mj.name as major_name
        FROM materials ma
        JOIN majors mj ON ma.major_id = mj.id
        WHERE ma.id = ? AND ma.is_active = 1
    ");
    $stmt->execute([$materialId]);
    $material = $stmt->fetch();
    
    if (!$material) {
        flashMessage('error', 'Material not found.');
        redirect('index.php');
    }
    
    // Get chapters for this material
    $chapters = $pdo->prepare("
        SELECT ch.*, COUNT(q.id) as question_count
        FROM chapters ch
        LEFT JOIN questions q ON ch.id = q.chapter_id AND q.is_active = 1
        WHERE ch.material_id = ? AND ch.is_active = 1
        GROUP BY ch.id
        ORDER BY ch.chapter_number ASC
    ");
    $chapters->execute([$materialId]);
    $chapters = $chapters->fetchAll();
    
} catch (Exception $e) {
    flashMessage('error', 'Failed to load material.');
    redirect('index.php');
}

// Set page title
$pageTitle = sanitize($material['name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <i class="fas fa-chevron-right"></i>
        <a href="major.php?id=<?php echo $material['major_id']; ?>"><?php echo sanitize($material['major_name']); ?></a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo sanitize($material['name']); ?></span>
    </div>
    
    <div class="material-info">
        <h1><?php echo sanitize($material['name']); ?></h1>
        <p><?php echo nl2br(sanitize($material['description'])); ?></p>
        
        <div class="material-stats">
            <span class="stat">
                <i class="fas fa-list-ol"></i>
                <?php echo count($chapters); ?> Chapters
            </span>
        </div>
    </div>
</div>

<div class="chapters-section">
    <?php if (empty($chapters)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-list-ol"></i>
            </div>
            <h3>No chapters available</h3>
            <p>There are no chapters available for this material yet. Please check back later.</p>
            <a href="major.php?id=<?php echo $material['major_id']; ?>" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                Back to Subject
            </a>
        </div>
    <?php else: ?>
        <div class="chapters-list">
            <?php foreach ($chapters as $chapter): ?>
                <div class="chapter-card" data-aos="fade-up">
                    <div class="chapter-number">
                        <span><?php echo $chapter['chapter_number']; ?></span>
                    </div>
                    
                    <div class="chapter-content">
                        <h3><?php echo sanitize($chapter['title']); ?></h3>
                        <p><?php echo truncateText(sanitize($chapter['description']), 150); ?></p>
                        
                        <div class="chapter-stats">
                            <span class="stat">
                                <i class="fas fa-question-circle"></i>
                                <?php echo $chapter['question_count']; ?> Questions
                            </span>
                        </div>
                    </div>
                    
                    <div class="chapter-actions">
                        <?php if ($chapter['question_count'] > 0): ?>
                            <a href="exam.php?chapter_id=<?php echo $chapter['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-play"></i>
                                Start Exam
                            </a>
                        <?php else: ?>
                            <span class="no-exam">No questions available</span>
                        <?php endif; ?>
                    </div>
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