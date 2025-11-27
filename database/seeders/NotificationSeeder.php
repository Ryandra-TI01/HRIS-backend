<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Membuat notifikasi untuk user (Tim FWD Batch 3):
     * - Setiap employee punya 4-5 notifikasi random
     * - Campuran status read/unread (2 pertama read, sisanya unread)
     * - Berbagai jenis notifikasi (leave approved, salary slip, review, meeting, system)
     *
     * Dependency: UserSeeder (butuh user_id dengan role employee)
     */
    public function run(): void
    {
        $employees = User::where('role', 'employee')->get();

        if ($employees->isEmpty()) {
            $this->command->error('❌ No employees found! Please run UserSeeder first.');
            return;
        }

        // Expanded notification templates with more variety
        $notificationTemplates = [
            // Leave related
            ['type' => 'leave_approved', 'message' => 'Pengajuan cuti Anda untuk tanggal %s telah disetujui oleh Manager. Selamat berlibur!'],
            ['type' => 'leave_rejected', 'message' => 'Mohon maaf, pengajuan cuti tanggal %s tidak dapat disetujui. Silakan hubungi Manager untuk diskusi.'],
            ['type' => 'leave_reminder', 'message' => 'Reminder: Anda memiliki cuti yang akan dimulai besok (%s). Pastikan handover sudah selesai.'],

            // Salary related
            ['type' => 'salary_slip', 'message' => 'Slip gaji bulan %s sudah tersedia. Silakan cek di menu Salary Slips.'],
            ['type' => 'salary_bonus', 'message' => 'Selamat! Anda mendapat bonus kinerja bulan %s. Cek slip gaji untuk detailnya.'],

            // Performance related
            ['type' => 'performance_review', 'message' => 'Penilaian kinerja bulan %s telah dipublikasikan. Rating Anda: %d bintang.'],
            ['type' => 'performance_feedback', 'message' => 'Manager telah memberikan feedback untuk kinerja Anda bulan %s. Silakan cek di Performance Review.'],

            // Meeting & events
            ['type' => 'meeting_reminder', 'message' => 'Jangan lupa meeting %s hari ini jam %s via %s. Link sudah dikirim ke email.'],
            ['type' => 'training_invitation', 'message' => 'Anda diundang mengikuti training "%s" pada tanggal %s. Silakan konfirmasi kehadiran.'],
            ['type' => 'company_event', 'message' => 'Company Event: %s akan diadakan pada %s. Save the date!'],

            // System & admin
            ['type' => 'system_maintenance', 'message' => 'Sistem HRIS akan maintenance pada %s pukul %s. Mohon maklum atas ketidaknyamanannya.'],
            ['type' => 'policy_update', 'message' => 'Kebijakan %s telah diperbarui. Silakan baca update terbaru di company handbook.'],
            ['type' => 'document_required', 'message' => 'Mohon segera upload dokumen %s yang diperlukan untuk keperluan administrasi.'],

            // Birthday & celebrations
            ['type' => 'birthday_wish', 'message' => 'Selamat ulang tahun! Semoga hari istimewa Anda menyenangkan. HRD Team 🎉'],
            ['type' => 'work_anniversary', 'message' => 'Congratulations pada work anniversary ke-%d tahun! Terima kasih atas dedikasi Anda.'],

            // General announcements
            ['type' => 'announcement', 'message' => 'Pengumuman: %s. Untuk info lebih lanjut, hubungi HRD.'],
            ['type' => 'deadline_reminder', 'message' => 'Reminder: Deadline %s adalah %s. Pastikan Anda sudah submit tepat waktu.'],
        ];

        // Sample data for template placeholders
        $sampleDates = [
            '15-17 November 2025', '20-22 December 2025', '10-12 October 2025', '5-7 September 2025'
        ];

        $sampleMonths = ['November 2025', 'Oktober 2025', 'September 2025', 'Agustus 2025'];

        $meetingTypes = ['Team Standup', 'Project Review', 'All Hands', 'Department Meeting', '1-on-1 Session'];
        $meetingTimes = ['09:00', '10:00', '13:00', '14:00', '15:30', '16:00'];
        $meetingPlatforms = ['Zoom', 'Google Meet', 'Microsoft Teams', 'Meeting Room A'];

        $trainingTopics = ['Laravel Advanced', 'Leadership Skills', 'Time Management', 'Digital Marketing', 'Data Analysis'];

        $policyTypes = ['Work From Home', 'Reimbursement', 'Annual Leave', 'Dress Code', 'Security'];

        $documents = ['KTP', 'NPWP', 'Medical Check-up', 'Kontrak Kerja', 'Emergency Contact'];

        $announcements = [
            'Kantor akan tutup tanggal 25 Desember 2025',
            'Program vaksinasi booster gratis untuk karyawan',
            'Survey kepuasan karyawan 2025 telah dibuka',
            'Recruitment program untuk posisi baru'
        ];

        $deadlines = [
            'Self Assessment Performance Review',
            'Monthly Report Submission',
            'Training Evaluation Form',
            'Expense Report Oktober'
        ];

        $totalNotifications = 0;

        foreach ($employees as $user) {
            // Buat 12-20 notifikasi untuk setiap user (3 bulan intensive period)
            $numNotifications = rand(12, 20);

            // Generate notifications across 3 months (Sep-Nov focus)
            $notificationDates = [];
            for ($i = 0; $i < $numNotifications; $i++) {
                // Weight towards Sep-Nov 2025
                $monthWeights = [9 => 35, 10 => 40, 11 => 25]; // September-November
                $targetMonth = $this->getWeightedRandomMonth($monthWeights);

                $randomDay = rand(1, 28);
                $notificationDate = Carbon::create(2025, $targetMonth, $randomDay)
                    ->addHours(rand(8, 17))
                    ->addMinutes(rand(0, 59));

                $notificationDates[] = $notificationDate;
            }

            // Sort dates (newest first)
            usort($notificationDates, function($a, $b) {
                return $b->timestamp - $a->timestamp;
            });

            for ($i = 0; $i < $numNotifications; $i++) {
                $template = $notificationTemplates[array_rand($notificationTemplates)];
                $createdAt = $notificationDates[$i];

                // Fill template placeholders based on type
                $message = $template['message'];
                switch ($template['type']) {
                    case 'leave_approved':
                    case 'leave_rejected':
                    case 'leave_reminder':
                        $message = sprintf($message, $sampleDates[array_rand($sampleDates)]);
                        break;
                    case 'salary_slip':
                    case 'salary_bonus':
                    case 'performance_feedback':
                        $message = sprintf($message, $sampleMonths[array_rand($sampleMonths)]);
                        break;
                    case 'performance_review':
                        $message = sprintf($message, $sampleMonths[array_rand($sampleMonths)], rand(7, 10));
                        break;
                    case 'meeting_reminder':
                        $message = sprintf($message,
                            $meetingTypes[array_rand($meetingTypes)],
                            $meetingTimes[array_rand($meetingTimes)],
                            $meetingPlatforms[array_rand($meetingPlatforms)]
                        );
                        break;
                    case 'training_invitation':
                        $message = sprintf($message,
                            $trainingTopics[array_rand($trainingTopics)],
                            $createdAt->addDays(rand(7, 21))->format('d F Y')
                        );
                        break;
                    case 'company_event':
                        $message = sprintf($message,
                            'Team Building 2025',
                            $createdAt->addDays(rand(14, 45))->format('d F Y')
                        );
                        break;
                    case 'system_maintenance':
                        $message = sprintf($message,
                            $createdAt->addDays(rand(1, 7))->format('d F Y'),
                            '01:00-05:00 WIB'
                        );
                        break;
                    case 'policy_update':
                        $message = sprintf($message, $policyTypes[array_rand($policyTypes)]);
                        break;
                    case 'document_required':
                        $message = sprintf($message, $documents[array_rand($documents)]);
                        break;
                    case 'work_anniversary':
                        $message = sprintf($message, rand(1, 5));
                        break;
                    case 'announcement':
                        $message = sprintf($message, $announcements[array_rand($announcements)]);
                        break;
                    case 'deadline_reminder':
                        $message = sprintf($message,
                            $deadlines[array_rand($deadlines)],
                            $createdAt->addDays(rand(2, 7))->format('d F Y')
                        );
                        break;
                }

                // Realistic read status (newer notifications more likely to be unread)
                $daysSinceCreated = $createdAt->diffInDays(Carbon::now());
                $readProbability = min(90, $daysSinceCreated * 15); // Older = more likely to be read
                $isRead = rand(1, 100) <= $readProbability;

                Notification::create([
                    'user_id' => $user->id,
                    'type' => $template['type'],
                    'message' => $message,
                    'is_read' => $isRead,
                    'created_at' => $createdAt,
                    'updated_at' => $isRead ? $createdAt->addMinutes(rand(5, 300)) : $createdAt // Read notifications have updated_at
                ]);

                $totalNotifications++;
            }
        }

        $this->command->info("✅ {$totalNotifications} Notifications created successfully!");
    }

    /**
     * Get weighted random month
     */
    private function getWeightedRandomMonth(array $weights): int
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($weights as $month => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $month;
            }
        }

        return 9; // Fallback
    }
}
