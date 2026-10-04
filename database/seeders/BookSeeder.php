<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hosanna International Church & Christian literature
        $books = [
            [
                'title' => 'Kanuni na Mwongozo wa Hosanna International Church',
                'author' => 'Hosanna International Church',
                'description' => 'Mwongozo mkuu wa utawala, taratibu, na kanuni za kiutendaji za Hosanna International Church.',
                'file_path' => 'documents/sample.pdf',
                'cover_image_path' => null,
            ],
            [
                'title' => 'Ubatizo wa Roho Mtakatifu na Karama zake',
                'author' => 'Idara ya Elimu ya Kikristo - Hosanna',
                'description' => 'Mafundisho ya msingi kuhusu nguvu ya Roho Mtakatifu, ubatizo wa Roho, na utendaji wa karama ndani ya kanisa.',
                'file_path' => 'documents/sample.pdf',
                'cover_image_path' => null,
            ],
            [
                'title' => 'Mwongozo wa Shule ya Jumapili (Sunday School Manual)',
                'author' => 'Hosanna Sunday School & Watoto',
                'description' => 'Mwongozo wa walimu na wanafunzi wa masomo ya Sunday School na misingi ya neno la Mungu kwa watoto.',
                'file_path' => 'documents/sample.pdf',
                'cover_image_path' => null,
            ],
            [
                'title' => 'Mwongozo wa Vijana (Youth Ministry Manual)',
                'author' => 'Hosanna Youth Fellowship',
                'description' => 'Miongozo, mafunzo na shughuli za kiroho na uongozi kwa vijana.',
                'file_path' => 'documents/sample.pdf',
                'cover_image_path' => null,
            ],
            [
                'title' => 'Uongozi Bora wa Kanisa na Mashemasi',
                'author' => 'Baraza la Uongozi - Hosanna',
                'description' => 'Mwongozo wa kiutendaji na kiroho kwa wazee wa kanisa, wachungaji na mashemasi.',
                'file_path' => 'documents/sample.pdf',
                'cover_image_path' => null,
            ],
            [
                'title' => 'Nguvu ya Maombi na Kufunga',
                'author' => 'Idara ya Maombi na Maombezi',
                'description' => 'Mbinu na mafundisho ya jinsi ya kuishi maisha ya ushindi kupitia maombi binafsi na maombi ya pamoja ya kanisa.',
                'file_path' => 'documents/sample.pdf',
                'cover_image_path' => null,
            ],
            [
                'title' => 'Mwongozo wa Uinjilisti na Upandaji Makanisa',
                'author' => 'Idara ya Misheni na Uinjilisti',
                'description' => 'Mkakati wa kufikia watu wasiomjua Kristo, kufanya mikutano ya injili na kuanzisha makanisa mapya.',
                'file_path' => 'documents/sample.pdf',
                'cover_image_path' => null,
            ],
        ];

        foreach ($books as $bookData) {
            Book::create([
                'title' => $bookData['title'],
                'author' => $bookData['author'],
                'language' => 'sw',
                'description' => $bookData['description'],
                'file_path' => $bookData['file_path'],
                'cover_image_path' => $bookData['cover_image_path'],
            ]);
        }
    }
}
