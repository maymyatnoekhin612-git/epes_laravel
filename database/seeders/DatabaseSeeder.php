<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Test;
use App\Models\TestSection;
use App\Models\Question;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        /*
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
        */
        
        $readingTest = Test::create([
            'title' => 'IELTS Academic Reading Practice Test',
            'type' => 'reading',
            'description' => 'Full IELTS Academic Reading test with 3 sections and 40 questions',
            'duration_minutes' => 60,
            'total_questions' => 40,
            'passing_score' => 6.0,
            'is_active' => true,
        ]);

        $section1 = TestSection::create([
            'test_id' => $readingTest->id,
            'title' => 'Reading Passage 1',
            'content' => 'The giant panda (Ailuropoda melanoleuca) is a bear native to south central China. It is easily recognized by the large, distinctive black patches around its eyes, over the ears, and across its round body. Though it belongs to the order Carnivora, the giant panda\'s diet is over 99% bamboo. Giant pandas in the wild will occasionally eat other grasses, wild tubers, or even meat in the form of birds, rodents, or carrion. In captivity, they may receive honey, eggs, fish, yams, shrub leaves, oranges, or bananas along with specially prepared food.

The giant panda lives in a few mountain ranges in central China, mainly in Sichuan, but also in neighbouring Shaanxi and Gansu. As a result of farming, deforestation, and other development, the giant panda has been driven out of the lowland areas where it once lived.

The giant panda is a conservation reliant vulnerable species. A 2007 report showed 239 pandas living in captivity inside China and another 27 outside the country. Wild population estimates vary; one estimate shows that there are about 1,590 individuals living in the wild, while a 2006 study via DNA analysis estimated that this figure could be as high as 2,000 to 3,000. Some reports also show that the number of giant pandas in the wild is on the rise.

The giant panda has been a symbol of the World Wide Fund for Nature (WWF) since its formation in 1961.',
            'order' => 1,
            'question_count' => 13,
        ]);

        $questions1 = [
            [
                'type' => 'multiple_choice',
                'question_text' => 'What is the main diet of the giant panda?',
                'options' => ['Meat', 'Bamboo', 'Fish', 'Fruits'],
                'correct_answers' => ['Bamboo'],
                'points' => 1,
                'order' => 1,
            ],
            [
                'type' => 'true_false_notgiven',
                'question_text' => 'Giant pandas are native to Japan.',
                'correct_answers' => ['false'],
                'points' => 1,
                'order' => 2,
            ],
            [
                'type' => 'sentence_completion',
                'question_text' => 'The giant panda has been the symbol of ______ since 1961.',
                'correct_answers' => ['WWF', 'World Wide Fund for Nature'],
                'points' => 1,
                'order' => 3,
            ],
        ];

        foreach ($questions1 as $q) {
            Question::create(array_merge($q, ['test_section_id' => $section1->id]));
        }

        $listeningTest = Test::create([
            'title' => 'IELTS Academic Listening Practice Test',
            'type' => 'listening',
            'description' => 'Full IELTS Academic Listening test with 4 sections and 40 questions',
            'duration_minutes' => 30,
            'total_questions' => 40,
            'passing_score' => 6.0,
            'is_active' => true,
        ]);

        $listeningSection1 = TestSection::create([
            'test_id' => $listeningTest->id,
            'title' => 'Section 1: Conversation about accommodation',
            'audio_url' => '/audio/listening-section1.mp3',
            'audio_duration' => 180,
            'order' => 1,
            'question_count' => 10,
        ]);

        $listeningQuestions = [
            [
                'type' => 'form_completion',
                'question_text' => 'Complete the form below. Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.',
                'metadata' => [
                    'form_fields' => [
                        ['label' => 'Type of accommodation:', 'answer_key' => 'apartment'],
                        ['label' => 'Monthly rent:', 'answer_key' => '£650'],
                        ['label' => 'Address:', 'answer_key' => '24 Park Road'],
                    ]
                ],
                'correct_answers' => ['apartment', '£650', '24 Park Road'],
                'points' => 1,
                'order' => 1,
            ],
            [
                'type' => 'multiple_choice',
                'question_text' => 'What does the man want to know about the accommodation?',
                'options' => ['The parking facilities', 'The nearest bus stop', 'The garden size', 'The electricity bills'],
                'correct_answers' => ['The nearest bus stop'],
                'points' => 1,
                'order' => 2,
            ],
        ];

        foreach ($listeningQuestions as $q) {
            Question::create(array_merge($q, ['test_section_id' => $listeningSection1->id]));
        }
    }
}
