<?php
/**
 * Admin Dashboard
 * Main administration panel with statistics and overview
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminAuth();

// Get current admin
$admin = getCurrentAdmin();

// Get database connection
$pdo = getDBConnection();

// Get statistics
$stats = [];

// Total counts
try {
    $stats['majors'] = $pdo->query("SELECT COUNT(*) FROM majors WHERE is_active = 1")->fetchColumn();
    $stats['materials'] = $pdo->query("SELECT COUNT(*) FROM materials WHERE is_active = 1")->fetchColumn();
    $stats['chapters'] = $pdo->query("SELECT COUNT(*) FROM chapters WHERE is_active = 1")->fetchColumn();
    $stats['questions'] = $pdo->query("SELECT COUNT(*) FROM questions WHERE is_active = 1")->fetchColumn();
    $stats['exams'] = $pdo->query("SELECT COUNT(*) FROM exam_results")->fetchColumn();
    
    // Average score
    $avgScore = $pdo->query("SELECT AVG(percentage) FROM exam_results")->fetchColumn();
    $stats['average_score'] = $avgScore ? round($avgScore, 1) : 0;
    
    // Recent exam results (last 10)
    $recentResults = $pdo->query("
        SELECT er.*, ch.title as chapter_title, m.name as material_name, mj.name as major_name
        FROM exam_results er
        JOIN chapters ch ON er.chapter_id = ch.id
        JOIN materials m ON ch.material_id = m.id
        JOIN majors mj ON m.major_id = mj.id
        ORDER BY er.completed_at DESC
        LIMIT 10
    ");
    
    // Exams per day (last 7 days)
    $examsPerDay = $pdo->query("
        SELECT DATE(completed_at) as date, COUNT(*) as count
        FROM exam_results
        WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(completed_at)
        ORDER BY date ASC
    ");
    
    // Score distribution
    $scoreDistribution = $pdo->query("
        SELECT 
            CASE 
                WHEN percentage >= 90 THEN '90-100%'
                WHEN percentage >= 80 THEN '80-89%'
                WHEN percentage >= 70 THEN '70-79%'
                WHEN percentage >= 60 THEN '60-69%'
                ELSE '0-59%'
            END as range,
            COUNT(*) as count
        FROM exam_results
        GROUP BY range
        ORDER BY range
    ");
    
    // Most popular chapters
    $popularChapters = $pdo->query("
        SELECT ch.title, COUNT(*) as exam_count
        FROM exam_results er
        JOIN chapters ch ON er.chapter_id = ch.id
        GROUP BY ch.id, ch.title
        ORDER BY exam_count DESC
        LIMIT 5
    ");
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    flashMessage('error', 'Failed to load dashboard statistics.');
}

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
        <p>Welcome back, <strong><?php echo sanitize($admin['username']); ?></strong>!</p>
        <div class="header-stats">
            <span class="date"><?php echo date('l, F d, Y'); ?></span>
            <span class="time" id="current-time"><?php echo date('h:i A'); ?></span>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon majors">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['majors']); ?></h3>
                <p>Total Majors</p>
                <a href="majors/" class="stat-link">View All <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon materials">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['materials']); ?></h3>
                <p>Total Materials</p>
                <a href="materials/" class="stat-link">View All <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon chapters">
                <i class="fas fa-list-ol"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['chapters']); ?></h3>
                <p>Total Chapters</p>
                <a href="chapters/" class="stat-link">View All <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon questions">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['questions']); ?></h3>
                <p>Total Questions</p>
                <a href="questions/" class="stat-link">View All <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon exams">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['exams']); ?></h3>
                <p>Exams Taken</p>
                <a href="results/" class="stat-link">View Results <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon average">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['average_score']; ?>%</h3>
                <p>Average Score</p>
                <span class="stat-link">Overall Performance</span>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="majors/add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add Major
            </a>
            <a href="materials/add.php" class="btn btn-secondary">
                <i class="fas fa-plus"></i>
                Add Material
            </a>
            <a href="chapters/add.php" class="btn btn-tertiary">
                <i class="fas fa-plus"></i>
                Add Chapter
            </a>
            <a href="questions/add.php" class="btn btn-quaternary">
                <i class="fas fa-plus"></i>
                Add Question
            </a>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="charts-section">
        <div class="chart-container">
            <h3>Exams Per Day (Last 7 Days)</h3>
            <canvas id="examsChart" width="400" height="200"></canvas>
        </div>
        
        <div class="chart-container">
            <h3>Score Distribution</h3>
            <canvas id="scoreChart" width="400" height="200"></canvas>
        </div>
        
        <div class="chart-container">
            <h3>Most Popular Chapters</h3>
            <canvas id="popularChart" width="400" height="200"></canvas>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="recent-activity">
        <h2>Recent Exam Results</h2>
        <div class="activity-table">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Chapter</th>
                        <th>Material</th>
                        <th>Major</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentResults && $recentResults->rowCount() > 0): ?>
                        <?php while ($result = $recentResults->fetch()): ?>
                            <tr>
                                <td><?php echo sanitize($result['student_name']); ?></td>
                                <td><?php echo sanitize($result['chapter_title']); ?></td>
                                <td><?php echo sanitize($result['material_name']); ?></td>
                                <td><?php echo sanitize($result['major_name']); ?></td>
                                <td><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?></td>
                                <td>
                                    <span class="percentage-badge <?php echo $result['percentage'] >= PASSING_PERCENTAGE ? 'pass' : 'fail'; ?>">
                                        <?php echo $result['percentage']; ?>%
                                    </span>
                                </td>
                                <td><?php echo formatDateTime($result['completed_at']); ?></td>
                                <td>
                                    <a href="results/view.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-results">No exam results yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Update current time
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
        document.getElementById('current-time').textContent = timeString;
    }
    setInterval(updateTime, 1000);
    
    // Exams per day chart
    const examsData = <?php 
        $data = [];
        if ($examsPerDay) {
            while ($row = $examsPerDay->fetch()) {
                $data[] = ['date' => $row['date'], 'count' => (int)$row['count']];
            }
        }
        echo json_encode($data);
    ?>;
    
    if (examsData.length > 0) {
        new Chart(document.getElementById('examsChart'), {
            type: 'line',
            data: {
                labels: examsData.map(d => new Date(d.date).toLocaleDateString()),
                datasets: [{
                    label: 'Exams Taken',
                    data: examsData.map(d => d.count),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
    
    // Score distribution chart
    const scoreData = <?php 
        $data = [];
        if ($scoreDistribution) {
            while ($row = $scoreDistribution->fetch()) {
                $data[] = ['range' => $row['range'], 'count' => (int)$row['count']];
            }
        }
        echo json_encode($data);
    ?>;
    
    if (scoreData.length > 0) {
        new Chart(document.getElementById('scoreChart'), {
            type: 'bar',
            data: {
                labels: scoreData.map(d => d.range),
                datasets: [{
                    label: 'Students',
                    data: scoreData.map(d => d.count),
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
    
    // Popular chapters chart
    const popularData = <?php 
        $data = [];
        if ($popularChapters) {
            while ($row = $popularChapters->fetch()) {
                $data[] = ['chapter' => $row['title'], 'count' => (int)$row['exam_count']];
            }
        }
        echo json_encode($data);
    ?>;
    
    if (popularData.length > 0) {
        new Chart(document.getElementById('popularChart'), {
            type: 'doughnut',
            data: {
                labels: popularData.map(d => d.chapter),
                datasets: [{
                    data: popularData.map(d => d.count),
                    backgroundColor: ['#4f46e5', '#7c3aed', '#ec4899', '#f59e0b', '#10b981']
                }]
            },
            options: {
                responsive: true,
                plugins: { 
                    legend: { position: 'bottom' }
                }
            }
        });
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>