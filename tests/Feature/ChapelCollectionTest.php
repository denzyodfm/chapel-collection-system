<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\HugpongBanay;
use App\Models\HugpongBanayLeaderHistory;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapelCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Unpaid Balik Gasa');
    }

    public function test_valid_login_redirects_to_dashboard(): void
    {
        User::factory()->create([
            'email' => 'admin@chapel.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'admin@chapel.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_invalid_login_shows_error_on_login_page(): void
    {
        $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login.attempt'), [
                'email' => 'missing@chapel.test',
                'password' => 'wrong-password',
            ])
            ->assertSee('The provided credentials do not match our records.');
    }

    public function test_balik_gasa_duplicate_for_same_member_and_month_is_rejected(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = Member::create([
            'member_id' => 'PHFC-T01',
            'full_name' => 'Test Member',
            'status' => 'active',
            'date_joined' => '2026-01-01',
        ]);

        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 100,
            'collection_date' => '2026-06-01',
            'collection_month' => '2026-06',
            'encoded_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->post(route('collections.store'), [
                'member_id' => $member->id,
                'collection_type' => Collection::BALIK_GASA,
                'amount' => 100,
                'collection_date' => '2026-06-10',
                'collection_month' => '2026-06',
            ])
            ->assertSessionHasErrors('collection_month');
    }

    public function test_member_id_is_auto_generated_when_member_is_created(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hugpongBanay = HugpongBanay::create(['name' => 'Auto ID Hugpong']);

        $this->actingAs($admin)
            ->post(route('members.store'), [
                'full_name' => 'Auto Generated Member',
                'contact_number' => '0917-555-0101',
                'address_purok' => 'Block 9 Lot 1',
                'hugpong_banay_id' => $hugpongBanay->id,
                'status' => 'active',
                'date_joined' => '2026-06-01',
            ])
            ->assertRedirect(route('members.index'));

        $this->assertDatabaseHas('members', [
            'full_name' => 'Auto Generated Member',
            'member_id' => 'PHFC-0001',
        ]);
    }

    public function test_halad_can_be_recorded_without_a_member(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $this->actingAs($treasurer)
            ->post(route('collections.store'), [
                'collection_type' => Collection::HALAD,
                'amount' => 1500,
                'collection_date' => '2026-06-14',
                'remarks' => 'Sunday mass total',
            ])
            ->assertRedirect(route('collections.index'));

        $this->assertDatabaseHas('collections', [
            'member_id' => null,
            'collection_type' => Collection::HALAD,
            'amount' => 1500,
        ]);
    }

    public function test_donation_still_requires_a_member(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $this->actingAs($treasurer)
            ->post(route('collections.store'), [
                'collection_type' => Collection::DONATION,
                'amount' => 500,
                'collection_date' => '2026-06-14',
            ])
            ->assertSessionHasErrors('member_id');
    }

    public function test_balik_gasa_monitoring_can_filter_by_hugpong_banay(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $selectedHugpongBanay = HugpongBanay::create(['name' => 'Selected Hugpong']);
        $otherHugpongBanay = HugpongBanay::create(['name' => 'Other Hugpong']);
        $selectedMember = Member::create([
            'full_name' => 'Selected Member',
            'hugpong_banay_id' => $selectedHugpongBanay->id,
            'status' => 'active',
        ]);
        Member::create([
            'full_name' => 'Other Member',
            'hugpong_banay_id' => $otherHugpongBanay->id,
            'status' => 'active',
        ]);

        Collection::create([
            'member_id' => $selectedMember->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 100,
            'collection_date' => '2026-06-01',
            'collection_month' => '2026-06',
        ]);

        $this->actingAs($viewer)
            ->get(route('balik-gasa.index', [
                'month' => '2026-06',
                'hugpong_banay_id' => $selectedHugpongBanay->id,
            ]))
            ->assertOk()
            ->assertSee('Selected Hugpong')
            ->assertSee('Selected Member')
            ->assertSee('Already paid')
            ->assertDontSee('Other Member');
    }

    public function test_donation_and_offering_have_separate_monitoring_pages(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $this->actingAs($treasurer)
            ->get(route('balik-gasa.index', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Balik Gasa Monthly Monitoring')
            ->assertDontSee('Donation Payment')
            ->assertDontSee('Post Offering');

        $this->actingAs($treasurer)
            ->get(route('donations.index', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Donation Monthly Monitoring')
            ->assertSee('Quick Donation');

        $this->actingAs($treasurer)
            ->get(route('offerings.index', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Offering Monthly Monitoring')
            ->assertSee('Post Offering After Mass');
    }

    public function test_member_balik_gasa_year_endpoint_returns_month_plot(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $member = Member::create([
            'full_name' => 'Plot Member',
            'status' => 'active',
        ]);

        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 100,
            'collection_date' => '2026-02-10',
            'collection_month' => '2026-02',
            'reference_no' => 'BG-2026-02',
        ]);

        $this->actingAs($viewer)
            ->getJson(route('members.balik-gasa-year', ['member' => $member, 'year' => 2026]))
            ->assertOk()
            ->assertJsonPath('member.name', 'Plot Member')
            ->assertJsonPath('months.1.paid', true)
            ->assertJsonPath('months.1.reference_no', 'BG-2026-02');
    }

    public function test_quick_donation_posts_member_donation_with_reference(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = Member::create([
            'full_name' => 'Donation Member',
            'status' => 'active',
        ]);

        $this->actingAs($treasurer)
            ->post(route('donations.quick-pay', $member), [
                'collection_month' => '2026-06',
                'amount' => 250,
                'reference_no' => 'DON-001',
                'remarks' => 'Monthly donation',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collections', [
            'member_id' => $member->id,
            'collection_type' => Collection::DONATION,
            'amount' => 250,
            'reference_no' => 'DON-001',
        ]);
    }

    public function test_quick_offering_posts_mass_collection_without_member(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $this->actingAs($treasurer)
            ->post(route('offerings.quick-post'), [
                'collection_date' => '2026-06-21',
                'amount' => 1200,
                'reference_no' => 'OFF-001',
                'remarks' => 'After mass',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collections', [
            'member_id' => null,
            'collection_type' => Collection::HALAD,
            'amount' => 1200,
            'reference_no' => 'OFF-001',
        ]);
    }

    public function test_collection_index_search_filters_by_member_name(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $ana = Member::create([
            'full_name' => 'Ana Reyes',
            'status' => 'active',
        ]);
        $jose = Member::create([
            'full_name' => 'Jose Dela Cruz',
            'status' => 'active',
        ]);

        Collection::create([
            'member_id' => $ana->id,
            'collection_type' => Collection::DONATION,
            'amount' => 750,
            'collection_date' => '2026-06-25',
        ]);
        Collection::create([
            'member_id' => $jose->id,
            'collection_type' => Collection::DONATION,
            'amount' => 500,
            'collection_date' => '2026-06-22',
        ]);

        $this->actingAs($treasurer)
            ->get(route('collections.index', ['search' => 'Ana']))
            ->assertOk()
            ->assertSee('Ana Reyes')
            ->assertDontSee('<td class="px-4 py-3 font-medium">Jose Dela Cruz</td>', false);
    }

    public function test_ledger_can_record_manual_entry_and_expense(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $this->actingAs($treasurer)
            ->post(route('ledger.entries.store'), [
                'fund_type' => Collection::DONATION,
                'entry_type' => 'credit',
                'amount' => 1000,
                'entry_date' => '2026-06-01',
                'reference_no' => 'BEG-001',
                'remarks' => 'Beginning balance',
            ])
            ->assertRedirect(route('ledger.index'));

        $this->actingAs($treasurer)
            ->post(route('ledger.expenses.store'), [
                'fund_type' => Collection::DONATION,
                'category' => 'Chapel supplies',
                'amount' => 300,
                'expense_date' => '2026-06-02',
                'reference_no' => 'EXP-001',
            ])
            ->assertRedirect(route('ledger.index'));

        $this->assertDatabaseHas('ledger_entries', [
            'fund_type' => Collection::DONATION,
            'amount' => 1000,
            'reference_no' => 'BEG-001',
        ]);
        $this->assertDatabaseHas('expenses', [
            'fund_type' => Collection::DONATION,
            'amount' => 300,
            'reference_no' => 'EXP-001',
        ]);
    }

    public function test_total_chapel_fund_summarizes_all_collection_funds(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = Member::create([
            'full_name' => 'Fund Summary Member',
            'status' => 'active',
        ]);

        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 100,
            'collection_date' => '2026-06-01',
            'collection_month' => '2026-06',
            'encoded_by' => $treasurer->id,
        ]);
        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::DONATION,
            'amount' => 250,
            'collection_date' => '2026-06-02',
            'encoded_by' => $treasurer->id,
        ]);
        Collection::create([
            'member_id' => null,
            'collection_type' => Collection::HALAD,
            'amount' => 500,
            'collection_date' => '2026-06-03',
            'encoded_by' => $treasurer->id,
        ]);
        Expense::create([
            'fund_type' => Collection::DONATION,
            'category' => 'Supplies',
            'amount' => 150,
            'expense_date' => '2026-06-04',
            'encoded_by' => $treasurer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('ledger.index'))
            ->assertOk()
            ->assertSee('Total Chapel Fund')
            ->assertDontSee('General Chapel Fund')
            ->assertSee('PHP 700.00');
    }

    public function test_database_prevents_duplicate_balik_gasa_for_same_member_and_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = Member::create([
            'member_id' => 'PHFC-T02',
            'full_name' => 'Database Guard Member',
            'status' => 'active',
            'date_joined' => '2026-01-01',
        ]);

        $payload = [
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 100,
            'collection_date' => '2026-06-01',
            'collection_month' => '2026-06',
            'encoded_by' => $admin->id,
        ];

        Collection::create($payload);

        $this->expectException(QueryException::class);
        Collection::create($payload);
    }

    public function test_printable_monthly_report_renders(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer)
            ->get(route('reports.print', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Monthly Collection Report')
            ->assertSee('Princess Homes Fatima Chapel');
    }

    public function test_hugpong_banay_leader_change_keeps_tenure_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hugpongBanay = HugpongBanay::create(['name' => 'Test Hugpong Banay']);
        $oldLeader = Member::create([
            'member_id' => 'PHFC-H01',
            'full_name' => 'Old Leader',
            'hugpong_banay_id' => $hugpongBanay->id,
            'status' => 'active',
        ]);
        $newLeader = Member::create([
            'member_id' => 'PHFC-H02',
            'full_name' => 'New Leader',
            'hugpong_banay_id' => $hugpongBanay->id,
            'status' => 'active',
        ]);

        $hugpongBanay->update(['current_leader_id' => $oldLeader->id]);
        HugpongBanayLeaderHistory::create([
            'hugpong_banay_id' => $hugpongBanay->id,
            'member_id' => $oldLeader->id,
            'started_at' => '2026-01-01',
        ]);

        $this->actingAs($admin)
            ->put(route('hugpong-banays.update', $hugpongBanay), [
                'name' => 'Test Hugpong Banay',
                'status' => 'active',
                'description' => 'Updated',
                'current_leader_id' => $newLeader->id,
                'leader_started_at' => '2026-06-01',
            ])
            ->assertRedirect(route('hugpong-banays.show', $hugpongBanay));

        $this->assertDatabaseHas('hugpong_banays', [
            'id' => $hugpongBanay->id,
            'current_leader_id' => $newLeader->id,
        ]);
        $this->assertDatabaseHas('hugpong_banay_leader_histories', [
            'hugpong_banay_id' => $hugpongBanay->id,
            'member_id' => $oldLeader->id,
            'ended_at' => '2026-05-31 00:00:00',
        ]);
        $this->assertDatabaseHas('hugpong_banay_leader_histories', [
            'hugpong_banay_id' => $hugpongBanay->id,
            'member_id' => $newLeader->id,
            'started_at' => '2026-06-01 00:00:00',
            'ended_at' => null,
        ]);
    }
}
