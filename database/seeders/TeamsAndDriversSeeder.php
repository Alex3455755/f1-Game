<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamsAndDriversSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            [
                'name' => 'Red Bull Racing', 'short_name' => 'RBR', 'color' => '#1E3A8A',
                'base_country' => 'Австрия', 'overall_rating' => 97, 'engine_rating' => 95,
                'aero_rating' => 98, 'reliability_rating' => 92, 'budget' => 500000000,
                'drivers' => [
                    ['name' => 'Макс Ферстаппен', 'code' => 'VER', 'number' => 1, 'nationality' => 'Нидерланды',
                     'pace' => 99, 'consistency' => 97, 'wet_skill' => 96, 'overtaking' => 98, 'defending' => 95, 'tire_management' => 94],
                    ['name' => 'Серхио Перес', 'code' => 'PER', 'number' => 11, 'nationality' => 'Мексика',
                     'pace' => 88, 'consistency' => 85, 'wet_skill' => 82, 'overtaking' => 86, 'defending' => 84, 'tire_management' => 90],
                ],
            ],
            [
                'name' => 'Ferrari', 'short_name' => 'FER', 'color' => '#DC143C',
                'base_country' => 'Италия', 'overall_rating' => 93, 'engine_rating' => 92,
                'aero_rating' => 93, 'reliability_rating' => 85, 'budget' => 450000000,
                'drivers' => [
                    ['name' => 'Шарль Леклер', 'code' => 'LEC', 'number' => 16, 'nationality' => 'Монако',
                     'pace' => 95, 'consistency' => 88, 'wet_skill' => 90, 'overtaking' => 92, 'defending' => 86, 'tire_management' => 85],
                    ['name' => 'Карлос Сайнс', 'code' => 'SAI', 'number' => 55, 'nationality' => 'Испания',
                     'pace' => 91, 'consistency' => 92, 'wet_skill' => 88, 'overtaking' => 88, 'defending' => 90, 'tire_management' => 91],
                ],
            ],
            [
                'name' => 'Mercedes', 'short_name' => 'MER', 'color' => '#00D2BE',
                'base_country' => 'Великобритания', 'overall_rating' => 91, 'engine_rating' => 96,
                'aero_rating' => 90, 'reliability_rating' => 93, 'budget' => 480000000,
                'drivers' => [
                    ['name' => 'Льюис Хэмилтон', 'code' => 'HAM', 'number' => 44, 'nationality' => 'Великобритания',
                     'pace' => 96, 'consistency' => 95, 'wet_skill' => 97, 'overtaking' => 95, 'defending' => 93, 'tire_management' => 96],
                    ['name' => 'Джордж Расселл', 'code' => 'RUS', 'number' => 63, 'nationality' => 'Великобритания',
                     'pace' => 90, 'consistency' => 91, 'wet_skill' => 88, 'overtaking' => 89, 'defending' => 88, 'tire_management' => 89],
                ],
            ],
            [
                'name' => 'McLaren', 'short_name' => 'MCL', 'color' => '#FF8700',
                'base_country' => 'Великобритания', 'overall_rating' => 89, 'engine_rating' => 93,
                'aero_rating' => 88, 'reliability_rating' => 88, 'budget' => 380000000,
                'drivers' => [
                    ['name' => 'Ландо Норрис', 'code' => 'NOR', 'number' => 4, 'nationality' => 'Великобритания',
                     'pace' => 93, 'consistency' => 90, 'wet_skill' => 91, 'overtaking' => 90, 'defending' => 87, 'tire_management' => 88],
                    ['name' => 'Оскар Пиастри', 'code' => 'PIA', 'number' => 81, 'nationality' => 'Австралия',
                     'pace' => 87, 'consistency' => 88, 'wet_skill' => 84, 'overtaking' => 85, 'defending' => 84, 'tire_management' => 87],
                ],
            ],
            [
                'name' => 'Aston Martin', 'short_name' => 'AST', 'color' => '#006F62',
                'base_country' => 'Великобритания', 'overall_rating' => 85, 'engine_rating' => 90,
                'aero_rating' => 84, 'reliability_rating' => 87, 'budget' => 330000000,
                'drivers' => [
                    ['name' => 'Фернандо Алонсо', 'code' => 'ALO', 'number' => 14, 'nationality' => 'Испания',
                     'pace' => 93, 'consistency' => 94, 'wet_skill' => 95, 'overtaking' => 94, 'defending' => 96, 'tire_management' => 95],
                    ['name' => 'Лэнс Стролл', 'code' => 'STR', 'number' => 18, 'nationality' => 'Канада',
                     'pace' => 78, 'consistency' => 74, 'wet_skill' => 72, 'overtaking' => 76, 'defending' => 75, 'tire_management' => 77],
                ],
            ],
            [
                'name' => 'Alpine', 'short_name' => 'ALP', 'color' => '#0090FF',
                'base_country' => 'Франция', 'overall_rating' => 80, 'engine_rating' => 85,
                'aero_rating' => 79, 'reliability_rating' => 82, 'budget' => 280000000,
                'drivers' => [
                    ['name' => 'Эстебан Окон', 'code' => 'OCO', 'number' => 31, 'nationality' => 'Франция',
                     'pace' => 82, 'consistency' => 80, 'wet_skill' => 81, 'overtaking' => 80, 'defending' => 82, 'tire_management' => 83],
                    ['name' => 'Пьер Гасли', 'code' => 'GAS', 'number' => 10, 'nationality' => 'Франция',
                     'pace' => 84, 'consistency' => 82, 'wet_skill' => 83, 'overtaking' => 83, 'defending' => 81, 'tire_management' => 82],
                ],
            ],
            [
                'name' => 'Williams', 'short_name' => 'WIL', 'color' => '#005AFF',
                'base_country' => 'Великобритания', 'overall_rating' => 74, 'engine_rating' => 90,
                'aero_rating' => 72, 'reliability_rating' => 80, 'budget' => 200000000,
                'drivers' => [
                    ['name' => 'Алекс Албон', 'code' => 'ALB', 'number' => 23, 'nationality' => 'Таиланд',
                     'pace' => 83, 'consistency' => 84, 'wet_skill' => 82, 'overtaking' => 82, 'defending' => 83, 'tire_management' => 85],
                    ['name' => 'Логан Сарджент', 'code' => 'SAR', 'number' => 2, 'nationality' => 'США',
                     'pace' => 72, 'consistency' => 70, 'wet_skill' => 68, 'overtaking' => 70, 'defending' => 71, 'tire_management' => 72],
                ],
            ],
            [
                'name' => 'AlphaTauri', 'short_name' => 'AT', 'color' => '#2B4562',
                'base_country' => 'Италия', 'overall_rating' => 76, 'engine_rating' => 93,
                'aero_rating' => 74, 'reliability_rating' => 83, 'budget' => 230000000,
                'drivers' => [
                    ['name' => 'Юки Цунода', 'code' => 'TSU', 'number' => 22, 'nationality' => 'Япония',
                     'pace' => 82, 'consistency' => 75, 'wet_skill' => 80, 'overtaking' => 83, 'defending' => 76, 'tire_management' => 78],
                    ['name' => 'Даниэль Риккардо', 'code' => 'RIC', 'number' => 3, 'nationality' => 'Австралия',
                     'pace' => 85, 'consistency' => 82, 'wet_skill' => 83, 'overtaking' => 89, 'defending' => 80, 'tire_management' => 84],
                ],
            ],
            [
                'name' => 'Alfa Romeo', 'short_name' => 'ALF', 'color' => '#900000',
                'base_country' => 'Швейцария', 'overall_rating' => 77, 'engine_rating' => 90,
                'aero_rating' => 76, 'reliability_rating' => 84, 'budget' => 240000000,
                'drivers' => [
                    ['name' => 'Валттери Боттас', 'code' => 'BOT', 'number' => 77, 'nationality' => 'Финляндия',
                     'pace' => 85, 'consistency' => 86, 'wet_skill' => 84, 'overtaking' => 83, 'defending' => 82, 'tire_management' => 88],
                    ['name' => 'Чжоу Гуаньюй', 'code' => 'ZHO', 'number' => 24, 'nationality' => 'Китай',
                     'pace' => 77, 'consistency' => 76, 'wet_skill' => 74, 'overtaking' => 75, 'defending' => 75, 'tire_management' => 78],
                ],
            ],
            [
                'name' => 'Haas', 'short_name' => 'HAA', 'color' => '#B6BABD',
                'base_country' => 'США', 'overall_rating' => 72, 'engine_rating' => 90,
                'aero_rating' => 71, 'reliability_rating' => 78, 'budget' => 180000000,
                'drivers' => [
                    ['name' => 'Кевин Магнуссен', 'code' => 'MAG', 'number' => 20, 'nationality' => 'Дания',
                     'pace' => 80, 'consistency' => 78, 'wet_skill' => 79, 'overtaking' => 80, 'defending' => 84, 'tire_management' => 79],
                    ['name' => 'Нико Хюлькенберг', 'code' => 'HUL', 'number' => 27, 'nationality' => 'Германия',
                     'pace' => 83, 'consistency' => 83, 'wet_skill' => 82, 'overtaking' => 82, 'defending' => 82, 'tire_management' => 84],
                ],
            ],
        ];

        foreach ($teams as $teamData) {
            $drivers = $teamData['drivers'];
            unset($teamData['drivers']);

            $team = \App\Models\Team::create($teamData);

            foreach ($drivers as $driverData) {
                $team->drivers()->create($driverData);
            }
        }
    }
}
