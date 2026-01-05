<?php
/**
 * Exam Interface
 * Interactive examination page with timer and question navigation
 */

require_once __DIR__ . '/../includes/functions.php';

// Get chapter ID
$chapterId = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;

if ($chapterId <= 0) {
    flashMessage('error', 'Invalid chapter selected.');
    redirect('index.php');
}

// Get database connection
try {
    $pdo = getDBConnection();
    
    // Get chapter and related info
    $stmt = $pdo->prepare("
        SELECT ch.*, ma.name as material_name, mj.name as major_name
        FROM chapters ch
        JOIN materials ma ON ch.material_id = ma.id
        JOIN majors mj ON ma.major_id = mj.id
        WHERE ch.id = ? AND ch.is_active = 1
    ");
    $stmt->execute([$chapterId]);
    $chapter = $stmt->fetch();
    
    if (!$chapter) {
        flashMessage('error', 'Chapter not found.');
        redirect('index.php');
    }
    
    // Get questions for this chapter
    $questions = $pdo->prepare("
        SELECT id, question_text, choice_a, choice_b, choice_c, correct_answer, explanation
        FROM questions
        WHERE chapter_id = ? AND is_active = 1
        ORDER BY RAND()
    ");
    $questions->execute([$chapterId]);
    $questions = $questions->fetchAll();
    
    if (empty($questions)) {
        flashMessage('error', 'No questions available for this chapter.');
        redirect('material.php?id=' . $chapter['material_id']);
    }
    
    // Initialize exam session
    if (!isset($_SESSION['exam'])) {
        $_SESSION['exam'] = [
            'chapter_id' => $chapterId,
            'start_time' => time(),
            'questions' => $questions,
            'answers' => [],
            'current_question' => 0
        ];
    }
    
    $exam = $_SESSION['exam'];
    $totalQuestions = count($exam['questions']);
    
} catch (Exception $e) {
    flashMessage('error', 'Failed to load exam.');
    redirect('index.php');
}

// Handle answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $currentIndex = (int)($_POST['current_question'] ?? 0);
    
    // Save current answer
    if (isset($_POST['answer'])) {
        $exam['answers'][$currentIndex] = $_POST['answer'];
        $_SESSION['exam']['answers'] = $exam['answers'];
    }
    
    switch ($action) {
        case 'next':
            if ($currentIndex < $totalQuestions - 1) {
                $exam['current_question'] = $currentIndex + 1;
                $_SESSION['exam']['current_question'] = $exam['current_question'];
            }
            break;
            
        case 'prev':
            if ($currentIndex > 0) {
                $exam['current_question'] = $currentIndex - 1;
                $_SESSION['exam']['current_question'] = $exam['current_question'];
            }
            break;
            
        case 'jump':
            $jumpIndex = (int)($_POST['jump_to'] ?? 0);
            if ($jumpIndex >= 0 && $jumpIndex < $totalQuestions) {
                $exam['current_question'] = $jumpIndex;
                $_SESSION['exam']['current_question'] = $exam['current_question'];
            }
            break;
            
        case 'submit':
            // Redirect to submit page
            redirect('submit_exam.php');
            break;
    }
}

$currentQuestionIndex = $exam['current_question'];
$currentQuestion = $exam['questions'][$currentQuestionIndex];
$timerSettings = getExamTimerSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam - <?php echo sanitize($chapter['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo assetsUrl(); ?>css/exam.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="exam-page">
    <div class="exam-container">
        <!-- Header -->
        <header class="exam-header">
            <div class="exam-info">
                <div class="breadcrumb">
                    <a href="index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="major.php?id=<?php echo $chapter['major_id']; ?>"><?php echo sanitize($chapter['major_name']); ?></a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="material.php?id=<?php echo $chapter['material_id']; ?>"><?php echo sanitize($chapter['material_name']); ?></a>
                </div>
                <h1><?php echo sanitize($chapter['title']); ?></h1>
            </div>
            
            <?php if ($timerSettings['enabled']): ?>
                <div class="timer" id="timer">
                    <i class="fas fa-clock"></i>
                    <span id="time-display"><?php echo floor($timerSettings['duration'] / 60); ?>:<?php echo sprintf('%02d', $timerSettings['duration'] % 60); ?></span>
                </div>
            <?php endif; ?>
        </header>
        
        <!-- Main Content -->
        <div class="exam-main">
            <!-- Question Palette -->
            <aside class="question-palette">
                <h3>Question Palette</h3>
                <div class="palette-grid">
                    <?php for ($i = 0; $i < $totalQuestions; $i++): ?>
                        <?php 
                        $status = '';
                        if ($i == $currentQuestionIndex) {
                            $status = 'current';
                        } elseif (isset($exam['answers'][$i])) {
                            $status = 'answered';
                        } else {
                            $status = 'unanswered';
                        }
                        ?>
                        <button type="button" class="palette-btn <?php echo $status; ?>" 
                                onclick="jumpToQuestion(<?php echo $i; ?>)">
                            <?php echo $i + 1; ?>
                        </button>
                    <?php endfor; ?>
                </div>
                
                <div class="palette-legend">
                    <div class="legend-item">
                        <span class="legend-color current"></span>
                        <span>Current</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color answered"></span>
                        <span>Answered</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color unanswered"></span>
                        <span>Unanswered</span>
                    </div>
                </div>
            </aside>
            
            <!-- Question Area -->
            <main class="question-area">
                <form method="POST" action="" id="exam-form">
                    <input type="hidden" name="current_question" value="<?php echo $currentQuestionIndex; ?>">
                    
                    <!-- Question Header -->
                    <div class="question-header">
                        <div class="question-progress">
                            <span>Question <?php echo $currentQuestionIndex + 1; ?> of <?php echo $totalQuestions; ?></span>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo (($currentQuestionIndex + 1) / $totalQuestions) * 100; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Question Content -->
                    <div class="question-content">
                        <div class="question-text">
                            <h2><?php echo nl2br(sanitize($currentQuestion['question_text'])); ?></h2>
                        </div>
                        
                        <div class="answer-options">
                            <?php foreach (['A' => $currentQuestion['choice_a'], 'B' => $currentQuestion['choice_b'], 'C' => $currentQuestion['choice_c']] as $letter => $choice): ?>
                                <label class="answer-option">
                                    <input type="radio" name="answer" value="<?php echo $letter; ?>" 
                                           <?php echo (isset($exam['answers'][$currentQuestionIndex]) && $exam['answers'][$currentQuestionIndex] === $letter) ? 'checked' : ''; ?>>
                                    <div class="option-content">
                                        <span class="option-letter"><?php echo $letter; ?></span>
                                        <span class="option-text"><?php echo nl2br(sanitize($choice)); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="question-navigation">
                        <button type="submit" name="action" value="prev" class="btn btn-secondary" 
                                <?php echo $currentQuestionIndex === 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-chevron-left"></i>
                            Previous
                        </button>
                        
                        <div class="nav-info">
                            <?php if ($currentQuestionIndex === $totalQuestions - 1): ?>
                                <span class="last-question">Last Question</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($currentQuestionIndex < $totalQuestions - 1): ?>
                            <button type="submit" name="action" value="next" class="btn btn-primary">
                                Next
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="submit" class="btn btn-success" 
                                    onclick="return confirmSubmit()">
                                <i class="fas fa-check"></i>
                                Submit Exam
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </main>
        </div>
    </div>
    
    <script>
        // Timer functionality
        <?php if ($timerSettings['enabled']): ?>
        let timeLeft = <?php echo $timerSettings['duration']; ?>; // seconds
        let timerInterval;
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('time-display').textContent = 
                minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                alert('Time is up! Your exam will be submitted automatically.');
                document.getElementById('exam-form').submit();
            }
            
            timeLeft--;
        }
        
        timerInterval = setInterval(updateTimer, 1000);
        <?php endif; ?>
        
        // Jump to question
        function jumpToQuestion(index) {
            const form = document.getElementById('exam-form');
            const jumpInput = document.createElement('input');
            jumpInput.type = 'hidden';
            jumpInput.name = 'action';
            jumpInput.value = 'jump';
            form.appendChild(jumpInput);
            
            const jumpToInput = document.createElement('input');
            jumpToInput.type = 'hidden';
            jumpToInput.name = 'jump_to';
            jumpToInput.value = index;
            form.appendChild(jumpToInput);
            
            form.submit();
        }
        
        // Confirm submit
        function confirmSubmit() {
            const unanswered = <?php echo $totalQuestions - count($exam['answers']); ?>;
            let message = 'Are you sure you want to submit the exam?';
            
            if (unanswered > 0) {
                message += ' You have ' + unanswered + ' unanswered question(s).';
            }
            
            return confirm(message);
        }
        
        // Auto-save answer when changed
        document.querySelectorAll('input[name="answer"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Update palette button style
                const currentBtn = document.querySelector('.palette-btn.current');
                if (currentBtn) {
                    currentBtn.classList.remove('unanswered');
                    currentBtn.classList.add('answered');
                }
            });
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' && <?php echo $currentQuestionIndex; ?> > 0) {
                document.querySelector('button[name="action"][value="prev"]').click();
            } else if (e.key === 'ArrowRight' && <?php echo $currentQuestionIndex; ?> < <?php echo $totalQuestions - 1; ?>) {
                document.querySelector('button[name="action"][value="next"]').click();
            } else if (e.key >= '1' && e.key <= '9') {
                const num = parseInt(e.key);
                if (num <= <?php echo $totalQuestions; ?>) {
                    jumpToQuestion(num - 1);
                }
            }
        });
        
        // Prevent form submission on Enter key
        document.getElementById('exam-form').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>