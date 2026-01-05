<?php
/**
 * Submit Exam
 * Processes exam answers and calculates results
 */

require_once __DIR__ . '/../includes/functions.php';

// Check if exam session exists
if (!isset($_SESSION['exam'])) {
    flashMessage('error', 'No active exam found.');
    redirect('index.php');
}

$exam = $_SESSION['exam'];
$chapterId = $exam['chapter_id'];
$questions = $exam['questions'];
$userAnswers = $exam['answers'];
$startTime = $exam['start_time'];

// Calculate results
$totalQuestions = count($questions);
$correctAnswers = 0;
$wrongAnswers = 0;
$answersData = [];

foreach ($questions as $index => $question) {
    $userAnswer = $userAnswers[$index] ?? null;
    $isCorrect = $userAnswer === $question['correct_answer'];
    
    if ($isCorrect) {
        $correctAnswers++;
    } else {
        $wrongAnswers++;
    }
    
    $answersData[] = [
        'question_id' => $question['id'],
        'user_answer' => $userAnswer,
        'correct_answer' => $question['correct_answer'],
        'is_correct' => $isCorrect
    ];
}

$percentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;
$timeTaken = time() - $startTime;

// Get student name from session or use anonymous
try {
    $pdo = getDBConnection();
    
    // Get chapter info
    $stmt = $pdo->prepare("
        SELECT ch.title, ma.name as material_name, mj.name as major_name
        FROM chapters ch
        JOIN materials ma ON ch.material_id = ma.id
        JOIN majors mj ON ma.major_id = mj.id
        WHERE ch.id = ?
    ");
    $stmt->execute([$chapterId]);
    $chapterInfo = $stmt->fetch();
    
    // Save result to database
    $stmt = $pdo->prepare("
        INSERT INTO exam_results 
        (student_name, chapter_id, score, total_questions, percentage, correct_answers, wrong_answers, completed_at, time_taken_seconds, answers_data)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
    ");
    
    $studentName = 'Anonymous Student'; // In a full implementation, this would come from user session
    $answersJson = json_encode($answersData);
    
    $stmt->execute([
        $studentName,
        $chapterId,
        $correctAnswers,
        $totalQuestions,
        $percentage,
        $correctAnswers,
        $wrongAnswers,
        $timeTaken,
        $answersJson
    ]);
    
    $resultId = $pdo->lastInsertId();
    
} catch (Exception $e) {
    flashMessage('error', 'Failed to save exam results.');
    error_log("Submit exam error: " . $e->getMessage());
    redirect('index.php');
}

// Clear exam session
unset($_SESSION['exam']);

// Redirect to results page
redirect('results.php?id=' . $resultId);