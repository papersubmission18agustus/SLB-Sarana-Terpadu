<?php

namespace Database\Seeders;

use App\Models\AccessToken;
use App\Models\Badge;
use App\Models\Level;
use App\Models\MaterialCategory;
use App\Models\Material;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrator', 'email' => 'admin@smartlearning.test', 'role' => 'admin', 'password' => 'Admin12345!']
        );

        User::updateOrCreate(
            ['username' => 'guru.maya'],
            ['name' => 'Maya Sari', 'email' => 'maya@smartlearning.test', 'role' => 'guru', 'password' => 'Guru12345!']
        );

        User::updateOrCreate(
            ['username' => 'guru.budi'],
            ['name' => 'Budi Santoso', 'email' => 'budi@smartlearning.test', 'role' => 'guru', 'password' => 'Guru12345!']
        );

        $levels = collect([
            ['code' => 'L1', 'name' => 'Pemula', 'minimum_points' => 0, 'description' => 'Materi dasar dengan instruksi sederhana.'],
            ['code' => 'L2', 'name' => 'Berkembang', 'minimum_points' => 40, 'description' => 'Materi menengah dengan contoh tambahan.'],
            ['code' => 'L3', 'name' => 'Mahir', 'minimum_points' => 75, 'description' => 'Materi lanjutan dengan tantangan ringan.'],
        ])->mapWithKeys(fn (array $level) => [$level['code'] => Level::updateOrCreate(['code' => $level['code']], $level)]);

        foreach ([
            ['name' => 'Master Warna', 'slug' => 'master-warna', 'description' => 'Menyelesaikan pembelajaran warna dengan baik.', 'required_points' => 40],
            ['name' => 'Penjelajah Bentuk', 'slug' => 'penjelajah-bentuk', 'description' => 'Mengenali bentuk geometri dasar.', 'required_points' => 75],
            ['name' => 'Bintang Berhitung', 'slug' => 'bintang-berhitung', 'description' => 'Berlatih berhitung dasar 1 sampai 10.', 'required_points' => 100],
        ] as $badge) {
            Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
        }

        $categories = [
            ['name' => 'Matematika', 'slug' => 'matematika'],
            ['name' => 'Bahasa', 'slug' => 'bahasa'],
            ['name' => 'Pengenalan Warna', 'slug' => 'warna'],
            ['name' => 'Bentuk dan Geometri Dasar', 'slug' => 'bentuk'],
            ['name' => 'Berhitung Dasar 1-10', 'slug' => 'berhitung'],
            ['name' => 'Objek Umum: Hewan dan Buah', 'slug' => 'objek'],
        ];

        foreach ($categories as $category) {
            MaterialCategory::updateOrCreate(['slug' => $category['slug']], $category);
        }

        $categoryIds = MaterialCategory::whereIn('slug', collect($categories)->pluck('slug'))->pluck('id', 'slug');
        foreach ([
            ['category' => 'matematika', 'title' => 'Mengenal Angka 1 sampai 10', 'description' => 'Belajar mengenal dan menghitung angka dengan cara menyenangkan.', 'content' => 'Kenali angka 1 sampai 10. Hitung benda di sekitarmu, lalu sebutkan jumlahnya dengan lantang.', 'pdf_url' => '/materials/matematika.html', 'ppt_url' => '/materials/matematika.html?mode=ppt', 'video_url' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4'],
            ['category' => 'bahasa', 'title' => 'Mengenal Huruf dan Kata', 'description' => 'Belajar membaca, menulis, dan memahami kata sederhana.', 'content' => 'Ayo kenali huruf vokal A, I, U, E, O. Gabungkan huruf menjadi kata sederhana seperti BOLA dan BUKU.', 'pdf_url' => '/materials/bahasa.html', 'ppt_url' => '/materials/bahasa.html?mode=ppt', 'video_url' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4'],
            ['category' => 'berhitung', 'title' => 'Latihan Berhitung Dasar', 'description' => 'Belajar mengenal dan menghitung angka dengan cara menyenangkan.'],
            ['category' => 'berhitung', 'title' => 'Menjumlahkan Benda', 'description' => 'Latihan penjumlahan sederhana menggunakan gambar.'],
            ['category' => 'bentuk', 'title' => 'Mengenal Bentuk Dasar', 'description' => 'Kenali lingkaran, persegi, segitiga, dan bentuk lainnya.'],
            ['category' => 'warna', 'title' => 'Mengenal Warna', 'description' => 'Belajar membedakan warna melalui benda sehari-hari.'],
            ['category' => 'objek', 'title' => 'Hewan di Sekitar Kita', 'description' => 'Kenali nama dan gambar hewan yang sering ditemui.'],
        ] as $material) {
            Material::updateOrCreate(['title' => $material['title']], [
                'category_id' => $categoryIds[$material['category']],
                'created_by' => $admin->id,
                'description' => $material['description'],
                'content' => $material['content'] ?? null,
                'pdf_url' => $material['pdf_url'] ?? null,
                'ppt_url' => $material['ppt_url'] ?? null,
                'video_url' => $material['video_url'] ?? null,
                'is_published' => true,
            ]);
        }

        $students = [
            ['nama' => 'Alya Putri', 'tanggal_lahir' => '2017-04-12', 'tempat_lahir' => 'Bandung', 'nama_orang_tua_wali' => 'Rina Putri', 'pendamping_email' => 'rina@example.test', 'pendamping_phone' => '081234567890'],
            ['nama' => 'Raka Pratama', 'tanggal_lahir' => '2016-09-20', 'tempat_lahir' => 'Jakarta', 'nama_orang_tua_wali' => 'Dedi Pratama', 'pendamping_email' => 'dedi@example.test', 'pendamping_phone' => '081298765432'],
        ];

        foreach ($students as $studentData) {
            $student = Student::updateOrCreate(['nama' => $studentData['nama']], [
                ...$studentData,
                'current_level_id' => $levels['L1']->id,
            ]);

            $plainToken = 'DEMO-' . Str::upper(Str::random(16));
            if (! $student->accessTokens()->where('is_active', true)->exists()) {
                AccessToken::create([
                    'student_id' => $student->id,
                    'token' => Hash::make($plainToken),
                    'expires_at' => now()->addYear(),
                    'is_active' => true,
                    'created_by' => $admin->id,
                ]);
                $this->command?->info("Token {$student->nama}: {$plainToken}");
            }
        }
    }
}
