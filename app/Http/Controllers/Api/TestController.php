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

    public function startTest(Request $request, $testId)
    {
        $test = Test::findOrFail($testId);
        
        $attempt = new TestAttempt([
            'test_id' => $test->id,
            'status' => 'in_progress',
            'started_at' => Carbon::now(),
        ]);

        if ($request->user()) {
            $attempt->user_id = $request->user()->id;
        } else {
            $attempt->guest_session_id = Str::uuid();
        }

        $attempt->save();

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

        $attempt = TestAttempt::findOrFail($attemptId);
        
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
            ]);
            return response()->json(['message' => 'Answer updated']);
        }

        // Create new answer
        UserAnswer::create([
            'test_attempt_id' => $attemptId,
            'question_id' => $request->question_id,
            'user_answer' => $request->answer,
            'time_spent_seconds' => $request->time_spent,
        ]);

        return response()->json(['message' => 'Answer submitted']);
    }

    public function submitTest(Request $request, $attemptId)
    {
        $attempt = TestAttempt::with(['test', 'userAnswers.question'])->findOrFail($attemptId);

        if ($attempt->status === 'completed') {
            return response()->json(['message' => 'Test already submitted'], 400);
        }

        // Calculate score
        $totalPoints = 0;
        $earnedPoints = 0;
        $correctAnswers = 0;
        $totalQuestions = $attempt->test->total_questions;

        foreach ($attempt->userAnswers as $userAnswer) {
            $question = $userAnswer->question;
            $totalPoints += $question->points;
            
            $isCorrect = $this->checkAnswer($userAnswer->user_answer, $question->correct_answers, $question->type);
            
            if ($isCorrect) {
                $correctAnswers++;
                $earnedPoints += $question->points;
                $userAnswer->is_correct = true;
                $userAnswer->points_earned = $question->points;
            }
            
            $userAnswer->save();
        }

        // Calculate IELTS band score
        $score = $this->calculateIELTSScore($correctAnswers, $totalQuestions);
        
        // Convert to band scores
        $bandScores = [
            'overall' => $score,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'percentage' => ($correctAnswers / $totalQuestions) * 100
        ];

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
            'attempt_id' => $attempt->id
        ]);
    }

    private function checkAnswer($userAnswer, $correctAnswers, $questionType)
    {
        switch ($questionType) {
            case 'multiple_choice':
                return $userAnswer == $correctAnswers;
            
            case 'true_false_notgiven':
            case 'yes_no_notgiven':
                return strtolower($userAnswer) === strtolower($correctAnswers[0]);
            
            case 'sentence_completion':
            case 'short_answer':
                $userAnswer = strtolower(trim($userAnswer));
                $correct = array_map('strtolower', array_map('trim', $correctAnswers));
                return in_array($userAnswer, $correct);
            
            case 'matching_headings':
            case 'matching_features':
                return $userAnswer == $correctAnswers;
            
            default:
                return false;
        }
    }

    private function calculateIELTSScore($correctAnswers, $totalQuestions)
    {
        // IELTS scoring conversion
        $conversion = [
            39 => 9.0, 37 => 8.5, 35 => 8.0, 33 => 7.5, 30 => 7.0,
            27 => 6.5, 23 => 6.0, 19 => 5.5, 15 => 5.0, 13 => 4.5,
            10 => 4.0, 8 => 3.5, 6 => 3.0, 4 => 2.5
        ];

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

    public function getGuestResults($guestSessionId)
    {
        $attempts = TestAttempt::with('test')
            ->where('guest_session_id', $guestSessionId)
            ->where('status', 'completed')
            ->get();

        return response()->json($attempts);
    }
}