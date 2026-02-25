<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\Question;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TestController extends Controller
{
    public function index()
    {
        $tests = Test::where('is_active', true)->get();
        return response()->json($tests);
    }

    public function show($id)
    {
        $test = Test::with(['sections.questions' => function($query) {
            $query->select('id', 'test_section_id', 'type', 'order', 'points');
        }])->findOrFail($id);

        return response()->json($test);
    }

    public function getSpeakingTest($testId)
    {
        $test = Test::with(['sections' => function($query) {
            $query->orderBy('order');
        }])->where('id', $testId)
        ->where('type', 'speaking')
        ->firstOrFail();

        return response()->json($test);
    }

    public function getWritingTest($testId)
    {
        $test = Test::with(['sections.questions' => function($query) {
            $query->orderBy('order');
        }])->where('id', $testId)
        ->where('type', 'writing')
        ->firstOrFail();

        return response()->json($test);
    }

    public function startTest(Request $request, $testId)
    {
        $user = $request->user();
        $test = Test::findOrFail($testId);

        // Check for existing in-progress attempt
        $existingAttempt = TestAttempt::where('user_id', $user->id)
            ->where('test_id', $testId)
            ->where('status', 'in_progress')
            ->where('started_at', '>=', Carbon::now()->subHours(1))
            ->first();
            
        if ($existingAttempt) {
            return response()->json([
                'message' => 'Continuing existing test attempt',
                'attempt_id' => $existingAttempt->id,
                'user_id' => $user->id,
                'test' => $test,
                'started_at' => $existingAttempt->started_at,
            ]);
        }

        // If there's an old in-progress attempt (older than 1 hours), mark it as abandoned
        TestAttempt::where('user_id', $user->id)
            ->where('test_id', $testId)
            ->where('status', 'in_progress')
            ->where('started_at', '<', Carbon::now()->subHours(1))
            ->update(['status' => 'abandoned']);


        $attempt = TestAttempt::create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'guest_session_id' => null, // Explicitly null for logged-in users
            'status' => 'in_progress',
            'started_at' => Carbon::now(),
            'score' => 0
        ]);

        return response()->json([
            'attempt_id' => $attempt->id,
            'user_id' => $user->id,
            'test' => $test,
            'started_at' => $attempt->started_at,
        ]);
    }

    public function startTestAsGuest(Request $request, $testId)
    {
        // This route is for guest users (no auth required)
        $test = Test::findOrFail($testId);
        
        $guestSessionId = $request->input('guest_session_id', Str::uuid());
        
        // Check for existing in-progress attempt for this guest
        $existingAttempt = TestAttempt::where('guest_session_id', $guestSessionId)
            ->where('test_id', $testId)
            ->where('status', 'in_progress')
            ->where('started_at', '>=', Carbon::now()->subHours(1))
            ->first();
            
        if ($existingAttempt) {
            return response()->json([
                'message' => 'Continuing existing test attempt',
                'attempt_id' => $existingAttempt->id,
                'guest_session_id' => $existingAttempt->guest_session_id,
                'test' => $test,
                'started_at' => $existingAttempt->started_at,
            ]);
        }

        TestAttempt::where('guest_session_id', $guestSessionId)
            ->where('test_id', $testId)
            ->where('status', 'in_progress')
            ->where('started_at', '<', Carbon::now()->subHours(1))
            ->update(['status' => 'abandoned']);

        // Create new attempt for guest user
        $attempt = TestAttempt::create([
            'user_id' => null, // Explicitly null for guest users
            'test_id' => $test->id,
            'guest_session_id' => Str::uuid(),
            'status' => 'in_progress',
            'started_at' => Carbon::now(),
            'score' => 0
        ]);

        return response()->json([
            'attempt_id' => $attempt->id,
            'guest_session_id' => $attempt->guest_session_id,
            'test' => $test,
            'started_at' => $attempt->started_at,
        ]);
    }

    public function getTestQuestions($attemptId)
    {
        $attempt = TestAttempt::with(['test.sections.questions' => function($query) {
            $query->orderBy('order');
        }])->findOrFail($attemptId);

        // Don't include correct answers
        $testData = $attempt->test->toArray();
        
        foreach ($testData['sections'] as &$section) {
            foreach ($section['questions'] as &$question) {
                unset($question['correct_answers']);
            }
        }

        return response()->json($testData);
    }

    public function submitAnswer(Request $request, $attemptId)
    {

        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer' => 'required',
            'time_spent' => 'integer|min:0'
        ]);

        $attempt = TestAttempt::with('test')->findOrFail($attemptId);
        
        if ($attempt->status !== 'in_progress') {
            return response()->json(['message' => 'Test is not in progress'], 400);
        }

        $question = Question::findOrFail($request->question_id);

        // Check if answer already exists
        $existingAnswer = UserAnswer::where('test_attempt_id', $attemptId)
            ->where('question_id', $request->question_id)
            ->first();

        if ($existingAnswer) {
            $existingAnswer->update([
                'user_answer' => $request->answer,
                'time_spent_seconds' => $request->time_spent,
                'is_correct' => false, // Default for writing, will be updated in submitTest
                'points_earned' => 0,   // Default for writing, will be updated in submitTest
            ]);
            return response()->json(['message' => 'Answer updated']);
        }


        // Create new answer
        // For writing questions, set default values
        UserAnswer::create([
            'test_attempt_id' => $attemptId,
            'question_id' => $request->question_id,
            'user_answer' => $request->answer,
            'time_spent_seconds' => $request->time_spent,
            'is_correct' => false, // Default for writing
            'points_earned' => 0,   // Default for writing
        ]);

        return response()->json(['message' => 'Answer submitted']);
    }

    
    public function submitTest(Request $request, $attemptId)
    {
        $attempt = TestAttempt::with(['test', 'userAnswers.question'])->findOrFail($attemptId);

        if ($attempt->status === 'completed') {
            return response()->json(['message' => 'Test already submitted'], 400);
        }

        // Check if this is a writing test
        $isWritingTest = $attempt->test->type === 'writing';

        if ($isWritingTest) {
            // For writing tests, don't calculate scores
            // Just mark answers as submitted
        
            foreach ($attempt->userAnswers as $userAnswer) {
                // For writing, we don't check correctness automatically
                $userAnswer->is_correct = false; // Writing needs manual grading
                $userAnswer->points_earned = 0;   // No automated scoring
                $userAnswer->save();
            }

            // Set writing-specific band scores
            $bandScores = [
                'overall' => 0,
                'message' => 'Writing test submitted. No automated scoring available.',
                'writing_feedback' => 'Your writing has been saved. Writing tests require manual evaluation.',
                'submitted_at' => now()->toDateTimeString(),
            ];
            
            $score = 0;
        } else {
            // Calculate score for reading/listening tests
            $totalPointsEarned = 0;
            $totalPointsPossible = 0;

            foreach ($attempt->userAnswers as $userAnswer) {
                $question = $userAnswer->question;
                
                // Check answer and get points earned
                $pointsEarned = $this->checkAnswer($userAnswer->user_answer, $question->correct_answers, $question->type);
                
                $userAnswer->points_earned = $pointsEarned;
                $userAnswer->is_correct = ($pointsEarned > 0);
                $userAnswer->save();
                
                $totalPointsEarned += $pointsEarned;
                $totalPointsPossible += $question->points;
            }

            // Calculate IELTS band score for reading/listening
            $score = $this->calculateIELTSScore($totalPointsEarned, $attempt->test->type);

            $bandScores = [
                'overall' => $score,
                'correct_answers' => $totalPointsEarned,
                'total_questions' => 40,
                'test_type' => $attempt->test->type
            ];
        }

        // Update attempt
        $attempt->status = 'completed';
        $attempt->completed_at = Carbon::now();
        $attempt->time_spent_seconds = Carbon::parse($attempt->started_at)->diffInSeconds(Carbon::now());
        $attempt->score = $score;
        $attempt->band_scores = $bandScores;
        $attempt->save();

        return response()->json([
            'message' => 'Test submitted successfully',
            'results' => $bandScores,
            'attempt_id' => $attempt->id,
            'test_type' => $attempt->test->type
        ]);
    }
    private function checkAnswer($userAnswer, $correctAnswers, $questionType)
    {
        // Handle gap-based question types
        $gapBasedTypes = ['summary_completion', 'form_completion', 'diagram_label', 
                        'map_plan_labeling', 'matching_headings', 'matching_features'];
        
        if (in_array($questionType, $gapBasedTypes)) {
            // Decode JSON string to array
            $userAnswerArray = [];
            if (is_string($userAnswer)) {
                $userAnswerArray = json_decode($userAnswer, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $userAnswerArray = [];
                }
            } elseif (is_array($userAnswer)) {
                $userAnswerArray = $userAnswer;
            }
        
            // Ensure userAnswerArray is an array
            if (!is_array($userAnswerArray)) {
                $userAnswerArray = [];
            }
            
            // Ensure correctAnswers is an array
            $correctArray = is_array($correctAnswers) ? $correctAnswers : [];
            
            $pointsEarned = 0;
            $totalGaps = count($correctArray);
        
            for ($i = 0; $i < $totalGaps; $i++) {
                $userAns = isset($userAnswerArray[$i]) ? trim($userAnswerArray[$i]) : '';
                
                // Skip if user answer is empty
                if (empty($userAns)) {
                    continue;
                }
                
                $correctAns = isset($correctArray[$i]) ? $correctArray[$i] : '';
                
                $isCorrect = false;
                
                // Check if correct answer is an array of multiple acceptable answers
                if (is_array($correctAns)) {
                    // Multiple possible correct answers for this gap
                    foreach ($correctAns as $possibleCorrect) {
                        if (strtolower($userAns) === strtolower(trim($possibleCorrect))) {
                            $isCorrect = true;
                            break;
                        }
                    }
                } else {
                    // Single correct answer for this gap
                    $isCorrect = strtolower($userAns) === strtolower(trim($correctAns));
                }
                
                if ($isCorrect) {
                    $pointsEarned++;
                }
            }
            return $pointsEarned;
        }
        
        // Handle single answer (multiple choice, true/false, etc.)
        // Check if correct answers is an array of multiple acceptable answers
        $singleUserAnswer = is_array($userAnswer) ? ($userAnswer[0] ?? '') : $userAnswer;
        $singleUserAnswer = trim($singleUserAnswer);
        
        if (empty($singleUserAnswer)) {
            return 0;
        }
        
        // Handle case where correctAnswers might be an array of acceptable answers
        if (is_array($correctAnswers)) {
            // If it's an array of multiple acceptable answers (like ["colour", "color"])
            foreach ($correctAnswers as $possibleCorrect) {
                if (is_string($possibleCorrect) && strtolower($singleUserAnswer) === strtolower(trim($possibleCorrect))) {
                    return 1;
                }
            }
            // If it's an array with a single answer (like ["A"])
            if (isset($correctAnswers[0]) && is_string($correctAnswers[0])) {
                return (strtolower($singleUserAnswer) === strtolower(trim($correctAnswers[0]))) ? 1 : 0;
            }
            return 0;
        }
        
        // Simple string comparison
        return (strtolower($singleUserAnswer) === strtolower(trim($correctAnswers))) ? 1 : 0;
    }
    private function calculateIELTSScore($correctAnswers, $testType)
    {
        // For summary completion with multiple blanks, $correctAnswers is the total points earned
        // $totalQuestions is the total points possible
        
        if ($testType === 'listening') {
            $conversion = [
                40 => 9.0, 39 => 9.0,  // 39-40 = 9
                38 => 8.5, 37 => 8.5,  // 37-38 = 8.5
                36 => 8.0, 35 => 8.0,  // 35-36 = 8
                34 => 7.5, 33 => 7.5, 32 => 7.5, // 32-34 = 7.5
                31 => 7.0, 30 => 7.0,  // 30-31 = 7
                29 => 6.5, 28 => 6.5, 27 => 6.5, 26 => 6.5, // 26-29 = 6.5
                25 => 6.0, 24 => 6.0, 23 => 6.0, // 23-25 = 6
                22 => 5.5, 21 => 5.5, 20 => 5.5, 19 => 5.5, 18 => 5.5, // 18-22 = 5.5
                17 => 5.0, 16 => 5.0,  // 16-17 = 5
                15 => 4.5, 14 => 4.5, 13 => 4.5, // 13-15 = 4.5
                12 => 4.0, 11 => 4.0,  // 11-12 = 4
            ];
        } else { // reading
            $conversion = [
                40 => 9.0, 39 => 9.0,  // 39-40 = 9
                38 => 8.5, 37 => 8.5,  // 37-38 = 8.5
                36 => 8.0, 35 => 8.0,  // 35-36 = 8
                34 => 7.5, 33 => 7.5,  // 33-34 = 7.5
                32 => 7.0, 31 => 7.0, 30 => 7.0, // 30-32 = 7
                29 => 6.5, 28 => 6.5, 27 => 6.5, // 27-29 = 6.5
                26 => 6.0, 25 => 6.0, 24 => 6.0, 23 => 6.0, // 23-26 = 6
                22 => 5.5, 21 => 5.5, 20 => 5.5, 19 => 5.5, // 19-22 = 5.5
                18 => 5.0, 17 => 5.0, 16 => 5.0, 15 => 5.0, // 15-18 = 5
                14 => 4.5, 13 => 4.5,  // 13-14 = 4.5
                12 => 4.0, 11 => 4.0, 10 => 4.0, // 10-12 = 4
                9 => 3.5, 8 => 3.5,    // 8-9 = 3.5
                7 => 3.0, 6 => 3.0,    // 6-7 = 3
            ];
        }

        // Find the band score based on number of correct answers
        foreach ($conversion as $minCorrect => $bandScore) {
            if ($correctAnswers >= $minCorrect) {
                return $bandScore;
            }
        }

        return 0.0;
    }

    public function getResults($attemptId)
    {
        $attempt = TestAttempt::with(['test', 'userAnswers.question'])->findOrFail($attemptId);

        if ($attempt->status !== 'completed') {
            return response()->json(['message' => 'Test not completed'], 400);
        }

        return response()->json([
            'attempt' => $attempt,
            'results' => $attempt->band_scores
        ]);
    }

    public function getUserAttempts(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $attempts = TestAttempt::with('test')
            ->where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json($attempts);
    }

    public function getGuestResults($attemptId)
    {
        $attempt = TestAttempt::with(['test', 'userAnswers.question'])->findOrFail($attemptId);

        if ($attempt->status !== 'completed') {
            return response()->json(['message' => 'Test not completed'], 400);
        }

        return response()->json([
            'attempt' => $attempt,
            'results' => $attempt->band_scores
        ]);
    }

    public function getUserAnswers($attemptId)
    {
        try {
            $attempt = TestAttempt::find($attemptId);
            
            if (!$attempt) {
                return response()->json(['error' => 'Attempt not found'], 404);
            }

            // Get answers with question and test section details
            $answers = UserAnswer::where('test_attempt_id', $attemptId)
                ->with(['question' => function($query) {
                    $query->select('id', 'test_section_id', 'question_text', 'type', 'order', 'points', 'correct_answers');
                }])
                ->orderBy('id')
                ->get();

            $formattedAnswers = [];
            foreach ($answers as $answer) {
                // Get test section separately
                $testSection = null;
                if ($answer->question && $answer->question->test_section_id) {
                    $testSection = \App\Models\TestSection::select('id', 'title', 'image_url', 'order', 'content')
                        ->where('id', $answer->question->test_section_id)
                        ->first();
                }

                // Format the answer based on question type
                $userAnswer = $answer->user_answer;
                $correctAnswer = $answer->question->correct_answers ?? null;
                
                // For gap-based questions, decode the JSON
                if (in_array($answer->question->type, [
                    'summary_completion', 'form_completion', 'diagram_label', 
                    'map_plan_labeling', 'matching_headings', 'matching_features'
                ])) {
                    if (is_string($userAnswer)) {
                        $userAnswer = json_decode($userAnswer, true);
                    }
                    if (is_string($correctAnswer)) {
                        $correctAnswer = json_decode($correctAnswer, true);
                    }
                }

                $formattedAnswers[] = [
                    'id' => $answer->id,
                    'user_answer' => $userAnswer,
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned,
                    'time_spent_seconds' => $answer->time_spent_seconds,
                    'created_at' => $answer->created_at,
                    'question' => $answer->question ? [
                        'id' => $answer->question->id,
                        'question_text' => $answer->question->question_text,
                        'type' => $answer->question->type,
                        'order' => $answer->question->order,
                        'points' => $answer->question->points,
                        'correct_answers' => $correctAnswer,
                        'test_section' => $testSection ? [
                            'id' => $testSection->id,
                            'title' => $testSection->title,
                            'image_url' => $testSection->image_url,
                            'content' => $testSection->content,
                            'order' => $testSection->order
                        ] : null
                    ] : null
                ];
            }

            return response()->json($formattedAnswers);
            
        } catch (\Exception $e) {
            \Log::error('Error in getUserAnswers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch answers'], 500);
        }
    }
}