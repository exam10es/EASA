<?php
/**
 * Exam Results Page
 * Displays detailed exam results with question breakdown
 */

require_once __DIR__ . '/../includes/functions.php';

// Get result ID
$resultId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($resultId <= 0) {
    flashMessage('error', 'Invalid result ID.');
    redirect('index.php');
}

// Get result data
try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT er.*, ch.title as chapter_title, ch.chapter_number,
               ma.name as material_name, mj.name as major_name
        FROM exam_results er
        JOIN chapters ch ON er.chapter_id = ch.id
        JOIN materials ma ON ch.material_id = ma.id
        JOIN majors mj ON ma.major_id = mj.id
        WHERE er.id = ?
    ");
    $stmt->execute([$resultId]);
    $result = $stmt->fetch();
    
    if (!$result) {
        flashMessage('error', 'Result not found.');
        redirect('index.php');
    }
    
    // Parse answers data
    $answersData = json_decode($result['answers_data'], true) ?? [];
    
    // Get detailed question info
    $questionDetails = [];
    foreach ($answersData as $answerData) {
        $stmt = $pdo->prepare("
            SELECT id, question_text, choice_a, choice_b, choice_c, correct_answer, explanation
            FROM questions
            WHERE id = ?
        ");
        $stmt->execute([$answerData['question_id']]);
        $question = $stmt->fetch();
        
        if ($question) {
            $questionDetails[] = array_merge($question, $answerData);
        }
    }
    
} catch (Exception $e) {
    flashMessage('error', 'Failed to load results.');
    error_log("Results error: " . $e->getMessage());
    redirect('index.php');
}

// Determine pass/fail
$passed = $result['percentage'] >= PASSING_PERCENTAGE;

// Set page title
$pageTitle = 'Exam Results - ' . $result['chapter_title'];
include __DIR__ . '/../includes/header.php';
?>

<div class="results-container">
    <!-- Results Header -->
    <div class="results-header">
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="major.php?id=<?php echo $result['chapter_id']; ?>"><?php echo sanitize($result['major_name']); ?></a>
            <i class="fas fa-chevron-right"></i>
            <span>Results</span>
        </div>
        
        <h1>Exam Results</h1>
        <p><?php echo sanitize($result['chapter_title']); ?> - <?php echo sanitize($result['material_name']); ?></p>
    </div>
    
    <!-- Score Summary -->
    <div class="score-summary <?php echo $passed ? 'passed' : 'failed'; ?>">
        <div class="score-card">
            <div class="score-circle">
                <div class="score-percentage">
                    <span class="percentage"><?php echo $result['percentage']; ?>%</span>
                    <span class="label">Score</span>
                </div>
            </div>
            
            <div class="score-details">
                <h2><?php echo $passed ? 'Congratulations!' : 'Keep Practicing!'; ?></h2>
                <p><?php echo $passed ? 'You passed the exam!' : 'You need ' . PASSING_PERCENTAGE . '% to pass.'; ?></p>
                
                <div class="score-breakdown">
                    <div class="score-item">
                        <span class="score-value"><?php echo $result['score']; ?></span>
                        <span class="score-label">Correct</span>
                    </div>
                    <div class="score-item">
                        <span class="score-value"><?php echo $result['wrong_answers']; ?></span>
                        <span class="score-label">Wrong</span>
                    </div>
                    <div class="score-item">
                        <span class="score-value"><?php echo $result['total_questions']; ?></span>
                        <span class="score-label">Total</span>
                    </div>
                    <?php if ($result['time_taken_seconds']): ?>
                        <div class="score-item">
                            <span class="score-value"><?php echo gmdate('i:s', $result['time_taken_seconds']); ?></span>
                            <span class="score-label">Time</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="results-actions">
        <a href="material.php?id=<?php echo $result['chapter_id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back to Chapters
        </a>
        
        <?php if (ALLOW_RETAKES): ?>
            <a href="exam.php?chapter_id=<?php echo $result['chapter_id']; ?>" class="btn btn-primary">
                <i class="fas fa-redo"></i>
                Retake Exam
            </a>
        <?php endif; ?>
        
        <button onclick="window.print()" class="btn btn-info">
            <i class="fas fa-print"></i>
            Print Results
        </button>
    </div>
    
    <!-- Detailed Review -->
    <div class="detailed-review">
        <h2>Question Review</h2>
        <p>Review your answers and see detailed explanations</p>
        
        <div class="review-questions">
            <?php foreach ($questionDetails as $index => $question): ?>
                <div class="review-question <?php echo $question['is_correct'] ? 'correct' : 'incorrect'; ?>">
                    <div class="question-header">
                        <span class="question-number">Question <?php echo $index + 1; ?></span>
                        <span class="question-status">
                            <?php if ($question['is_correct']): ?>
                                <i class="fas fa-check-circle"></i> Correct
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i> Incorrect
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="question-content">
                        <p class="question-text"><?php echo nl2br(sanitize($question['question_text'])); ?></p>
                        
                        <div class="answer-review">
                            <?php foreach (['A' => $question['choice_a'], 'B' => $question['choice_b'], 'C' => $question['choice_c']] as $letter => $choice): ?>
                                <div class="answer-option <?php 
                                    echo $letter === $question['correct_answer'] ? 'correct-answer' : '';
                                    echo $letter === $question['user_answer'] && !$question['is_correct'] ? 'incorrect-answer' : '';
                                    echo $letter === $question['user_answer'] ? 'selected' : '';
                                ?>">
                                    <span class="option-letter"><?php echo $letter; ?></span>
                                    <span class="option-text"><?php echo nl2br(sanitize($choice)); ?></span>
                                    
                                    <?php if ($letter === $question['correct_answer']): ?>
                                        <i class="fas fa-check correct-icon"></i>
                                    <?php elseif ($letter === $question['user_answer'] && !$question['is_correct']): ?>
                                        <i class="fas fa-times incorrect-icon"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (SHOW_EXPLANATIONS && $question['explanation']): ?>
                            <div class="explanation">
                                <h4><i class="fas fa-lightbulb"></i> Explanation</h4>
                                <p><?php echo nl2br(sanitize($question['explanation'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
/* Print Styles */
@media print {
    .main-header,
    .results-actions,
    .footer-section {
        display: none !important;
    }
    
    .results-container {
        max-width: none;
        margin: 0;
        padding: 0;
    }
    
    body {
        background: white !important;
        color: black !important;
    }
    
    .review-question {
        page-break-inside: avoid;
        margin-bottom: 20px;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>