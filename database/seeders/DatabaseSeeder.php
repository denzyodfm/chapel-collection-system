<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\HugpongBanay;
use App\Models\HugpongBanayLeaderHistory;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Chapel Admin',
            'email' => 'admin@chapel.test',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Chapel Treasurer',
            'email' => 'treasurer@chapel.test',
            'role' => 'treasurer',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Chapel Viewer',
            'email' => 'viewer@chapel.test',
            'role' => 'viewer',
            'password' => Hash::make('password'),
        ]);

        $hugpongBanays = collect([
            'San Isidro' => HugpongBanay::create(['name' => 'San Isidro', 'description' => 'Families near the main chapel lane.']),
            'San Roque' => HugpongBanay::create(['name' => 'San Roque', 'description' => 'Families near the basketball court.']),
            'Our Lady of Fatima' => HugpongBanay::create(['name' => 'Our Lady of Fatima', 'description' => 'Families near the chapel entrance.']),
            'Holy Family' => HugpongBanay::create(['name' => 'Holy Family', 'description' => 'Families near the inner homes block.']),
        ]);

        $members = collect([
            ['PHFC-001', 'Maria Santos', '0917-100-0001', 'Block 1 Lot 3', 'San Isidro', 'active', '2023-01-15'],
            ['PHFC-002', 'Jose Dela Cruz', '0917-100-0002', 'Block 1 Lot 8', 'San Isidro', 'active', '2023-02-10'],
            ['PHFC-003', 'Ana Reyes', '0917-100-0003', 'Block 2 Lot 4', 'San Roque', 'active', '2023-03-05'],
            ['PHFC-004', 'Pedro Garcia', '0917-100-0004', 'Block 2 Lot 9', 'San Roque', 'active', '2023-04-20'],
            ['PHFC-005', 'Luz Fernandez', '0917-100-0005', 'Block 3 Lot 2', 'Our Lady of Fatima', 'active', '2023-05-11'],
            ['PHFC-006', 'Nena Villanueva', '0917-100-0006', 'Block 3 Lot 7', 'Our Lady of Fatima', 'inactive', '2022-09-09'],
            ['PHFC-007', 'Ramon Bautista', '0917-100-0007', 'Block 4 Lot 5', 'Holy Family', 'active', '2024-01-08'],
            ['PHFC-008', 'Elena Mendoza', '0917-100-0008', 'Block 4 Lot 11', 'Holy Family', 'active', '2024-02-14'],
        ])->map(fn ($row) => Member::create([
            'member_id' => $row[0],
            'full_name' => $row[1],
            'contact_number' => $row[2],
            'address_purok' => $row[3],
            'hugpong_banay_id' => $hugpongBanays[$row[4]]->id,
            'status' => $row[5],
            'date_joined' => $row[6],
        ]));

        foreach ([
            'San Isidro' => $members[0],
            'San Roque' => $members[2],
            'Our Lady of Fatima' => $members[4],
            'Holy Family' => $members[7],
        ] as $name => $leader) {
            $hugpongBanay = $hugpongBanays[$name];
            $hugpongBanay->update(['current_leader_id' => $leader->id]);

            HugpongBanayLeaderHistory::create([
                'hugpong_banay_id' => $hugpongBanay->id,
                'member_id' => $leader->id,
                'started_at' => '2025-01-01',
                'notes' => 'Seeded current leader.',
            ]);
        }

        HugpongBanayLeaderHistory::create([
            'hugpong_banay_id' => $hugpongBanays['San Isidro']->id,
            'member_id' => $members[1]->id,
            'started_at' => '2024-01-01',
            'ended_at' => '2024-12-31',
            'notes' => 'Previous Hugpong Banay leader.',
        ]);

        $currentMonth = now()->format('Y-m');
        $previousMonth = now()->subMonth()->format('Y-m');

        foreach ($members->where('status', 'active')->take(5) as $member) {
            Collection::create([
                'member_id' => $member->id,
                'collection_type' => Collection::BALIK_GASA,
                'amount' => 100,
                'collection_date' => now()->startOfMonth()->addDays($member->id)->toDateString(),
                'collection_month' => $currentMonth,
                'remarks' => 'Monthly Balik Gasa',
                'encoded_by' => $admin->id,
            ]);
        }

        foreach ($members->where('status', 'active') as $member) {
            Collection::create([
                'member_id' => $member->id,
                'collection_type' => Collection::BALIK_GASA,
                'amount' => 100,
                'collection_date' => now()->subMonth()->startOfMonth()->addDays($member->id)->toDateString(),
                'collection_month' => $previousMonth,
                'remarks' => 'Previous month Balik Gasa',
                'encoded_by' => $admin->id,
            ]);
        }

        Collection::create(['member_id' => $members[1]->id, 'collection_type' => Collection::DONATION, 'amount' => 500, 'collection_date' => now()->subDays(6), 'remarks' => 'Chapel flowers', 'encoded_by' => $admin->id]);
        Collection::create(['member_id' => $members[2]->id, 'collection_type' => Collection::DONATION, 'amount' => 750, 'collection_date' => now()->subDays(3), 'remarks' => 'Community outreach', 'encoded_by' => $admin->id]);
        Collection::create(['member_id' => null, 'collection_type' => Collection::HALAD, 'amount' => 1250, 'collection_date' => now()->subDays(2), 'remarks' => 'Sunday mass Halad total', 'encoded_by' => $admin->id]);
        Collection::create(['member_id' => null, 'collection_type' => Collection::HALAD, 'amount' => 980, 'collection_date' => now(), 'remarks' => 'Thanksgiving mass Halad total', 'encoded_by' => $admin->id]);
    }
}
