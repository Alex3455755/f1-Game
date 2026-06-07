<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CircuitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $circuits = [
            ['name' => 'Bahrain International Circuit', 'country' => 'Бахрейн', 'city' => 'Сахир',
             'lap_count' => 57, 'lap_length' => 5.412, 'circuit_type' => 'permanent',
             'downforce_requirement' => 65, 'tire_wear_rate' => 75, 'fuel_consumption' => 60, 'overtaking_difficulty' => 35],
            ['name' => 'Jeddah Corniche Circuit', 'country' => 'Саудовская Аравия', 'city' => 'Джидда',
             'lap_count' => 50, 'lap_length' => 6.174, 'circuit_type' => 'street',
             'downforce_requirement' => 45, 'tire_wear_rate' => 55, 'fuel_consumption' => 70, 'overtaking_difficulty' => 60],
            ['name' => 'Albert Park Circuit', 'country' => 'Австралия', 'city' => 'Мельбурн',
             'lap_count' => 58, 'lap_length' => 5.278, 'circuit_type' => 'street',
             'downforce_requirement' => 60, 'tire_wear_rate' => 60, 'fuel_consumption' => 55, 'overtaking_difficulty' => 55],
            ['name' => 'Suzuka Circuit', 'country' => 'Япония', 'city' => 'Сузука',
             'lap_count' => 53, 'lap_length' => 5.807, 'circuit_type' => 'permanent',
             'downforce_requirement' => 75, 'tire_wear_rate' => 70, 'fuel_consumption' => 65, 'overtaking_difficulty' => 65],
            ['name' => 'Miami International Autodrome', 'country' => 'США', 'city' => 'Майами',
             'lap_count' => 57, 'lap_length' => 5.412, 'circuit_type' => 'street',
             'downforce_requirement' => 55, 'tire_wear_rate' => 65, 'fuel_consumption' => 58, 'overtaking_difficulty' => 45],
            ['name' => 'Autodromo Enzo e Dino Ferrari', 'country' => 'Италия', 'city' => 'Имола',
             'lap_count' => 63, 'lap_length' => 4.909, 'circuit_type' => 'permanent',
             'downforce_requirement' => 70, 'tire_wear_rate' => 65, 'fuel_consumption' => 55, 'overtaking_difficulty' => 70],
            ['name' => 'Circuit de Monaco', 'country' => 'Монако', 'city' => 'Монте-Карло',
             'lap_count' => 78, 'lap_length' => 3.337, 'circuit_type' => 'street',
             'downforce_requirement' => 90, 'tire_wear_rate' => 40, 'fuel_consumption' => 45, 'overtaking_difficulty' => 95],
            ['name' => 'Circuit Gilles Villeneuve', 'country' => 'Канада', 'city' => 'Монреаль',
             'lap_count' => 70, 'lap_length' => 4.361, 'circuit_type' => 'street',
             'downforce_requirement' => 55, 'tire_wear_rate' => 60, 'fuel_consumption' => 60, 'overtaking_difficulty' => 40],
            ['name' => 'Circuit de Barcelona-Catalunya', 'country' => 'Испания', 'city' => 'Барселона',
             'lap_count' => 66, 'lap_length' => 4.655, 'circuit_type' => 'permanent',
             'downforce_requirement' => 65, 'tire_wear_rate' => 75, 'fuel_consumption' => 60, 'overtaking_difficulty' => 60],
            ['name' => 'Red Bull Ring', 'country' => 'Австрия', 'city' => 'Шпильберг',
             'lap_count' => 71, 'lap_length' => 4.318, 'circuit_type' => 'permanent',
             'downforce_requirement' => 45, 'tire_wear_rate' => 55, 'fuel_consumption' => 50, 'overtaking_difficulty' => 30],
            ['name' => 'Silverstone Circuit', 'country' => 'Великобритания', 'city' => 'Сильверстоун',
             'lap_count' => 52, 'lap_length' => 5.891, 'circuit_type' => 'permanent',
             'downforce_requirement' => 60, 'tire_wear_rate' => 70, 'fuel_consumption' => 65, 'overtaking_difficulty' => 40],
            ['name' => 'Hungaroring', 'country' => 'Венгрия', 'city' => 'Будапешт',
             'lap_count' => 70, 'lap_length' => 4.381, 'circuit_type' => 'permanent',
             'downforce_requirement' => 80, 'tire_wear_rate' => 65, 'fuel_consumption' => 55, 'overtaking_difficulty' => 75],
            ['name' => 'Circuit de Spa-Francorchamps', 'country' => 'Бельгия', 'city' => 'Спа',
             'lap_count' => 44, 'lap_length' => 7.004, 'circuit_type' => 'permanent',
             'downforce_requirement' => 55, 'tire_wear_rate' => 60, 'fuel_consumption' => 75, 'overtaking_difficulty' => 35],
            ['name' => 'Circuit Zandvoort', 'country' => 'Нидерланды', 'city' => 'Зандфорт',
             'lap_count' => 72, 'lap_length' => 4.259, 'circuit_type' => 'permanent',
             'downforce_requirement' => 70, 'tire_wear_rate' => 70, 'fuel_consumption' => 55, 'overtaking_difficulty' => 70],
            ['name' => 'Autodromo Nazionale Monza', 'country' => 'Италия', 'city' => 'Монца',
             'lap_count' => 51, 'lap_length' => 5.793, 'circuit_type' => 'permanent',
             'downforce_requirement' => 20, 'tire_wear_rate' => 50, 'fuel_consumption' => 65, 'overtaking_difficulty' => 25],
            ['name' => 'Baku City Circuit', 'country' => 'Азербайджан', 'city' => 'Баку',
             'lap_count' => 51, 'lap_length' => 6.003, 'circuit_type' => 'street',
             'downforce_requirement' => 45, 'tire_wear_rate' => 45, 'fuel_consumption' => 65, 'overtaking_difficulty' => 30],
            ['name' => 'Marina Bay Street Circuit', 'country' => 'Сингапур', 'city' => 'Сингапур',
             'lap_count' => 61, 'lap_length' => 5.063, 'circuit_type' => 'street',
             'downforce_requirement' => 85, 'tire_wear_rate' => 65, 'fuel_consumption' => 60, 'overtaking_difficulty' => 80],
            ['name' => 'Circuit of the Americas', 'country' => 'США', 'city' => 'Остин',
             'lap_count' => 56, 'lap_length' => 5.513, 'circuit_type' => 'permanent',
             'downforce_requirement' => 65, 'tire_wear_rate' => 70, 'fuel_consumption' => 60, 'overtaking_difficulty' => 40],
            ['name' => 'Autodromo Hermanos Rodriguez', 'country' => 'Мексика', 'city' => 'Мехико',
             'lap_count' => 71, 'lap_length' => 4.304, 'circuit_type' => 'permanent',
             'downforce_requirement' => 70, 'tire_wear_rate' => 60, 'fuel_consumption' => 55, 'overtaking_difficulty' => 50],
            ['name' => 'Autodromo Jose Carlos Pace', 'country' => 'Бразилия', 'city' => 'Сан-Паулу',
             'lap_count' => 71, 'lap_length' => 4.309, 'circuit_type' => 'permanent',
             'downforce_requirement' => 60, 'tire_wear_rate' => 65, 'fuel_consumption' => 60, 'overtaking_difficulty' => 35],
            ['name' => 'Las Vegas Strip Circuit', 'country' => 'США', 'city' => 'Лас-Вегас',
             'lap_count' => 50, 'lap_length' => 6.120, 'circuit_type' => 'street',
             'downforce_requirement' => 40, 'tire_wear_rate' => 50, 'fuel_consumption' => 68, 'overtaking_difficulty' => 35],
            ['name' => 'Losail International Circuit', 'country' => 'Катар', 'city' => 'Лусаил',
             'lap_count' => 57, 'lap_length' => 5.380, 'circuit_type' => 'permanent',
             'downforce_requirement' => 55, 'tire_wear_rate' => 80, 'fuel_consumption' => 60, 'overtaking_difficulty' => 45],
            ['name' => 'Yas Marina Circuit', 'country' => 'ОАЭ', 'city' => 'Абу-Даби',
             'lap_count' => 58, 'lap_length' => 5.281, 'circuit_type' => 'permanent',
             'downforce_requirement' => 55, 'tire_wear_rate' => 55, 'fuel_consumption' => 60, 'overtaking_difficulty' => 45],
        ];

        foreach ($circuits as $circuit) {
            \App\Models\Circuit::create($circuit);
        }
    }
}
