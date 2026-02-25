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
        $listeningQuestions = [
            // [
            //     'type' => 'diagram_label',
            //     'question_text' => 'Complete the flow chart below. Write ONE WORD ONLY for each answer.',
            //     'metadata' => [
            //         'imageUrl' => ['/storage/image/listening/ielts16_L2_P3.png'],
                    
            //         'diagramGaps' => [
            //             ['text' => '25 ','isGap' => false],
            //             ['text'=> '','isGap'=> true],
            //             ['text' => '\n26','isGap'=> false],
            //             ['text'=> '','isGap'=> true],
            //             ['text' => '\n27','isGap' => false],
            //             ['text'=> '','isGap'=> true],
            //             ['text' => '\n28','isGap'=> false],
            //             ['text'=> '','isGap'=> true],
            //             ['text' => '\n29','isGap'=> false],
            //             ['text'=> '','isGap'=> true],
            //             ['text' => '\n30','isGap'=> false],
            //             ['text'=> '','isGap'=> true],
            //         ]
            //     ],
            //     'correct_answers' => ['history', 'paper', ['humans', 'people'], 'stress', 'graph', 'evaluate'],
            //     'points' => 6,
            //     'order' => 5,
            // ],
            
            [
                'type' => 'form_completion',
                'question_text' => 'Complete the notes below. Write ONE WORD ONLY for each answer.',
                'correct_answers' => ['creativity','therapy','fitness','balance','brain','motivation','isolation','calories','obesity','habit'],
                'points' => 10,
                'order' => 1,
                'metadata' => [
                    'summaryGaps' => [
                        ['text' => '\t \t Health benefits of dance','isGap' => false],
                        ['text' => 'Recent findings: ','isGap' => false],
                        ['text' => '\n • All forms of dance produce various hormones associated with feelings of happiness.
                        \n • Dancing with others has a more positive impact than dancing alone.
                        \n • An experiment on university students suggested that dance increases ','isGap' => false],
                        ['text'=> '','isGap'=> true],
                        ['text' => '\n • For those with mental illness, dance could be used as a form of ','isGap'=> false],
                        ['text'=> '','isGap'=> true],
                        ['text' => '\n Benefits of dance for older people:
                        \n • accessible for people with low levels of','isGap' => false],
                        ['text'=> '','isGap'=> true],
                        ['text' => '\n • reduces the risk of heart disease \n • better','isGap'=> false],
                        ['text'=> '','isGap'=> true],
                        ['text' => 'reduces the risk of accidents \n • improves','isGap'=> false],
                        ['text'=> '','isGap'=> true],
                        ['text' => ' function by making it work faster \n • improves participant\'s general well-being
                        \n • gives people more ','isGap'=> false],
                        ['text'=> '','isGap'=> true],
                        ['text' => ' to take exercise
                        \n • can lessen the feeling of ','isGap'=> false],
                        ['text'=> '','isGap'=> true],
                        ['text' => ' , very common in older people \n Benefits of Zumba:
                        \n • A study at The University of Wisconsin showed that doing Zumba for
                        40 minutes uses up as many ','isGap'=> false],
                        ['text'=> '','isGap'=> true],
                        ['text' => ' as other quite intense forms of exercise. \n
                        • The American Journal of Health Behavior study showed that:\n
                        - women suffering from ','isGap'=> false],
                        ['text'=> '','isGap'=> true],
                        ['text' => ' benefited from doing Zumba.

Ma M M Z, [2/25/2026 10:25 AM]
\n- Zumba became a ','isGap'=> false],
                        ['text'=> '','isGap'=> true],
                        ['text' => ' for the participants.','isGap'=> false],
                    ]
                ],
            ],
         ];

        foreach ($listeningQuestions as $q) {
            Question::create(array_merge($q, ['test_section_id' => 10]));
        }
    }
}
