<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Segment;
use App\Models\User;
use App\Models\MessageTemplate;
use App\Models\FollowUpSequence;
use App\Models\SequenceStep;
use App\Services\SegmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@astra.co.id'],
            [
                'name' => 'Admin Sales',
                'password' => Hash::make('password'),
            ]
        );

        // Create sales users
        $sales1 = User::firstOrCreate(
            ['email' => 'budi@astra.co.id'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
            ]
        );

        $sales2 = User::firstOrCreate(
            ['email' => 'dewi@astra.co.id'],
            [
                'name' => 'Dewi Kartika',
                'password' => Hash::make('password'),
            ]
        );

        // Create sample contacts
        Contact::factory()->count(50)->create([
            'assigned_to' => $sales1->id,
        ]);

        Contact::factory()->count(50)->create([
            'assigned_to' => $sales2->id,
        ]);

        // Create segments
        $segmentService = new SegmentService();

        $newLeadsSegment = $segmentService->createSegment(
            'New Leads',
            [['field' => 'lead_status', 'operator' => '=', 'value' => 'new']],
            'Semua lead baru yang belum dikontak'
        );

        $toyotaSegment = $segmentService->createSegment(
            'Toyota Enthusiasts',
            [['field' => 'vehicle_interest', 'value' => 'Toyota']],
            'Leads yang tertarik dengan produk Toyota'
        );

        $highBudgetSegment = $segmentService->createSegment(
            'High Budget',
            [['field' => 'budget_min', 'value' => 500000000]],
            'Leads dengan budget di atas 500 juta'
        );

        // Create message templates
        MessageTemplate::create([
            'name' => 'Greeting Baru',
            'category' => 'greeting',
            'content' => "Halo [Nama]! 👋\n\nTerima kasih sudah mengunjungi showroom kami.\n\nSaya [SalesName] dari Astra International, siap membantu Anda menemukan kendaraan impian.\n\nAda yang bisa saya bantu?",
            'variables' => ['Nama', 'SalesName'],
            'is_active' => true,
        ]);

        MessageTemplate::create([
            'name' => 'Promo Akhir Tahun',
            'category' => 'promo',
            'content' => "🎉 PROMO AKHIR TAHUN 🎉\n\nHalo [Nama]!\n\nKabar gembira! Untuk pembelian [Kendaraan] di bulan ini:\n✅ Diskon hingga 50 Juta\n✅ Free Asuransi 1 Tahun\n✅ Gratis Aksesoris\n\nBerminat? Balas pesan ini untuk info lebih lanjut!",
            'variables' => ['Nama', 'Kendaraan'],
            'is_active' => true,
        ]);

        MessageTemplate::create([
            'name' => 'Follow Up SPK',
            'category' => 'follow_up',
            'content' => "Halo [Nama],\n\nBagaimana kelanjutan rencana pembelian [Kendaraan] Anda?\n\nJika ada pertanyaan atau butuh bantuan, saya siap membantu.\n\nTerima kasih! 🙏",
            'variables' => ['Nama', 'Kendaraan'],
            'is_active' => true,
        ]);

        // Create follow-up sequence
        $sequence = FollowUpSequence::create([
            'name' => 'New Lead Follow-up',
            'description' => 'Sequence untuk follow-up lead baru',
            'is_active' => true,
            'trigger_event' => 'lead_created',
        ]);

        SequenceStep::create([
            'sequence_id' => $sequence->id,
            'step_order' => 1,
            'delay_hours' => 0,
            'message_template' => "Halo [Nama]! 👋\n\nTerima kasih sudah menghubungi kami.\nAda yang bisa saya bantu terkait [Kendaraan]?",
        ]);

        SequenceStep::create([
            'sequence_id' => $sequence->id,
            'step_order' => 2,
            'delay_hours' => 24,
            'message_template' => "Halo [Nama],\n\nHanya ingin follow up - apakah ada pertanyaan tentang [Kendaraan] yang bisa saya jawab?\n\nKami juga punya promo menarik bulan ini! 🎁",
        ]);

        SequenceStep::create([
            'sequence_id' => $sequence->id,
            'step_order' => 3,
            'delay_hours' => 72,
            'message_template' => "Halo [Nama],\n\nSaya mengerti Anda mungkin masih mempertimbangkan. Kapan waktu yang tepat untuk diskusi lebih lanjut?\n\nSaya bisa membantu menghitung simulasi kredit! 📊",
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin: admin@astra.co.id / password');
    }
}
