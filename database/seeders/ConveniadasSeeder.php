<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Conveniada;

class ConveniadasSeeder extends Seeder
{
    public function run(): void
    {
        $conveniadas = [
            'Prefeitura de Leopoldina',
            'Prefeitura de Cataguases',
            'Prefeitura de Ponte Nova',
            'Prefeitura de Uba',
            'Prefeitura de Muriae',
            'Exercito de Leopoldina',
            'Exercito de Cataguases',
            'Governo de SP',
            'Prefeitura de Goiania',
            'Prefeitura de Sao Paulo',
        ];

        foreach ($conveniadas as $nome) {
            Conveniada::firstOrCreate(['nome' => $nome]);
        }
    }
}