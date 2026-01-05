<?php
/**
 * Public Landing Page
 * Displays majors and general website information
 */

require_once __DIR__ . '/../includes/functions.php';

// Get database connection
try {
    $pdo = getDBConnection();
    $majors = $pdo->query("
        SELECT m.*, 
               COUNT(DISTINCT ma.id) as material_count,
               COUNT(DISTINCT ch.id) as chapter_count,
               COUNT(DISTINCT q.id) as question_count
        FROM majors m
        LEFT JOIN materials ma ON m.id = ma.major_id AND ma.is_active = 1
        LEFT JOIN chapters ch ON ma.id = ch.material_id AND ch.is_active = 1
        LEFT JOIN questions q ON ch.id = q.chapter_id AND q.is_active = 1
        WHERE m.is_active = 1
        GROUP BY m.id
        ORDER BY m.name ASC
    ")->fetchAll();
    
    // Get statistics
    $totalExams = $pdo->query("SELECT COUNT(*) FROM exam_results")->fetchColumn();
    
} catch (Exception $e) {
    $majors = [];
    $totalExams = 0;
    error_log("Home page error: " . $e->getMessage());
}

// Set page title
$pageTitle = 'Home';
include __DIR__ . '/../includes/header.php';
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Welcome to <span class="highlight"><?php echo SITE_NAME; ?></span></h1>
        <p>Test your knowledge across multiple subjects with our comprehensive examination system. Take quizzes, track your progress, and achieve your learning goals.</p>
        <div class="hero-buttons">
            <a href="#majors" class="btn btn-primary">
                <i class="fas fa-book-open"></i>
                Browse Subjects
            </a>
            <a href="#features" class="btn btn-secondary">
                <i class="fas fa-star"></i>
                Learn More
            </a>
        </div>
    </div>
    <div class="hero-image">
        <img src="<?php echo assetsUrl(); ?>images/hero-student.svg" alt="Student studying">
    </div>
</div>

<div class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-number" data-count="<?php echo count($majors); ?>">0</div>
                <div class="stat-label">Subjects Available</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-number" data-count="<?php echo $totalExams; ?>">0</div>
                <div class="stat-label">Exams Completed</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-number">95%</div>
                <div class="stat-label">Success Rate</div>
            </div>
        </div>
    </div>
</div>

<div class="majors-section" id="majors">
    <div class="container">
        <div class="section-header">
            <h2>Choose Your Subject</h2>
            <p>Explore our comprehensive collection of subjects and start your learning journey</p>
        </div>
        
        <div class="majors-grid">
            <?php foreach ($majors as $major): ?>
                <div class="major-card" data-aos="fade-up">
                    <div class="major-image">
                        <?php if ($major['image_url'] && file_exists(__DIR__ . '/../' . $major['image_url'])): ?>
                            <img src="<?php echo fileUrl($major['image_url']); ?>" 
                                 alt="<?php echo sanitize($major['name']); ?>">
                        <?php else: ?>
                            <img src="<?php echo assetsUrl(); ?>images/default-major.jpg" 
                                 alt="<?php echo sanitize($major['name']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="major-content">
                        <h3><?php echo sanitize($major['name']); ?></h3>
                        <p><?php echo truncateText(sanitize($major['description']), 120); ?></p>
                        <div class="major-stats">
                            <span class="stat">
                                <i class="fas fa-layer-group"></i>
                                <?php echo $major['material_count']; ?> Materials
                            </span>
                            <span class="stat">
                                <i class="fas fa-list-ol"></i>
                                <?php echo $major['chapter_count']; ?> Chapters
                            </span>
                        </div>
                        <a href="major.php?id=<?php echo $major['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i>
                            Explore Subject
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="features-section" id="features">
    <div class="container">
        <div class="section-header">
            <h2>Why Choose Our Platform?</h2>
            <p>Experience a modern, user-friendly examination system designed for effective learning</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Timed Examinations</h3>
                <p>Practice with timed quizzes to improve your speed and accuracy under pressure.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Instant Results</h3>
                <p>Get immediate feedback with detailed explanations for every question you answer.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Mobile Friendly</h3>
                <p>Take exams on any device - desktop, tablet, or mobile with responsive design.</p>
            </div>
            
            <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <h3>Retake Exams</h3>
                <p>Practice multiple times to improve your understanding and achieve better scores.</p>
            </div>
        </div>
    </div>
</div>

<div class="cta-section">
    <div class="container">
        <h2>Ready to Start Learning?</h2>
        <p>Choose a subject and begin your examination journey today</p>
        <a href="#majors" class="btn btn-primary btn-large">
            <i class="fas fa-play"></i>
            Start Now
        </a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
// Initialize AOS (Animate On Scroll)
AOS.init({
    duration: 1000,
    once: true
});

// Animated counters
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-count'));
        const increment = target / 100;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target.toLocaleString();
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current).toLocaleString();
            }
        }, 20);
    });
}

// Trigger counter animation when stats section is visible
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            observer.unobserve(entry.target);
        }
    });
});

const statsSection = document.querySelector('.stats-section');
if (statsSection) {
    observer.observe(statsSection);
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>