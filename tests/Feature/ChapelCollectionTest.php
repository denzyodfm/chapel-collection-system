<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\HugpongBanay;
use App\Models\HugpongBanayLeaderHistory;
use App\Models\LedgerEntry;
use App\Models\Member;
use App\Models\MonthLock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapelCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Carbon::setTestNow('2026-07-09');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('June 2026')
            ->assertSee('Dashboard Month')
            ->assertSee('Recent Disbursements');

        Carbon::setTestNow();
    }

    public function test_dashboard_month_can_be_switched(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = Member::create([
            'full_name' => 'Dashboard Contributor Member',
            'status' => 'active',
        ]);

        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 150,
            'collection_date' => '2026-05-28',
            'collection_month' => '2026-05',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard', ['month' => '2026-05']))
            ->assertOk()
            ->assertSee('May 2026')
            ->assertSee('Balik Gasa Total')
            ->assertSee('PHP 150.00')
            ->assertSee('1 of 1 contributed')
            ->assertSee('2026-05', false)
            ->assertSee(route('balik-gasa.index', ['month' => '2026-05']), false)
            ->assertDontSee('All-time monthly pledges')
            ->assertDontSee('Optional gifts recorded')
            ->assertDontSee('Mass offerings recorded');
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

    public function test_member_can_be_added_directly_from_hugpong_banay_page(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $hugpongBanay = HugpongBanay::create(['name' => 'Popup Member Hugpong']);

        $this->actingAs($treasurer)
            ->post(route('hugpong-banays.members.store', $hugpongBanay), [
                'full_name' => 'Popup Added Member',
                'contact_number' => '0917-555-0202',
                'address_purok' => 'Block 10 Lot 2',
                'status' => 'active',
                'date_joined' => '2026-06-30',
            ])
            ->assertRedirect(route('hugpong-banays.show', $hugpongBanay));

        $this->assertDatabaseHas('members', [
            'member_id' => 'PHFC-0001',
            'full_name' => 'Popup Added Member',
            'hugpong_banay_id' => $hugpongBanay->id,
            'status' => 'active',
        ]);

        $this->actingAs($treasurer)
            ->get(route('hugpong-banays.show', $hugpongBanay))
            ->assertOk()
            ->assertSee('Popup Added Member')
            ->assertSee('Add Member to Hugpong Banay');
    }

    public function test_hugpong_banay_index_includes_details_modal_with_members(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $hugpongBanay = HugpongBanay::create([
            'name' => 'Modal Hugpong',
            'description' => 'Families near the chapel gate.',
        ]);
        $leader = Member::create([
            'full_name' => 'Modal Leader',
            'status' => 'active',
            'hugpong_banay_id' => $hugpongBanay->id,
        ]);
        $member = Member::create([
            'full_name' => 'Modal Member',
            'contact_number' => '0917-555-0303',
            'status' => 'active',
            'hugpong_banay_id' => $hugpongBanay->id,
        ]);
        $hugpongBanay->update(['current_leader_id' => $leader->id]);

        $this->actingAs($treasurer)
            ->get(route('hugpong-banays.index'))
            ->assertOk()
            ->assertSee('data-modal-open="hugpong-banay-'.$hugpongBanay->id.'-modal"', false)
            ->assertSee('Hugpong Banay Details')
            ->assertSee('Families near the chapel gate.')
            ->assertSee('Members Portal')
            ->assertSee($leader->member_id)
            ->assertSee('Modal Leader')
            ->assertSee($member->member_id)
            ->assertSee('Modal Member')
            ->assertSee('0917-555-0303');
    }

    public function test_member_can_be_marked_inactive(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = Member::create([
            'full_name' => 'Inactive Candidate',
            'status' => 'active',
        ]);

        $this->actingAs($treasurer)
            ->patch(route('members.deactivate', $member))
            ->assertRedirect(route('members.show', $member));

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'status' => 'inactive',
        ]);
    }

    public function test_only_admin_can_delete_member_with_typed_confirmation(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $member = Member::create([
            'full_name' => 'Delete Candidate',
            'status' => 'active',
        ]);

        $this->actingAs($treasurer)
            ->delete(route('members.destroy', $member), [
                'delete_confirmation' => 'delete',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('members.destroy', $member), [
                'delete_confirmation' => 'remove',
            ])
            ->assertSessionHasErrors('delete_confirmation');

        $this->actingAs($admin)
            ->delete(route('members.destroy', $member), [
                'delete_confirmation' => 'delete',
            ])
            ->assertRedirect(route('members.index'));

        $this->assertDatabaseMissing('members', [
            'id' => $member->id,
        ]);
    }

    public function test_member_who_is_or_was_hugpong_banay_leader_can_be_deleted_during_testing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hugpongBanay = HugpongBanay::create(['name' => 'Leader Guard Hugpong']);
        $member = Member::create([
            'full_name' => 'Protected Leader',
            'hugpong_banay_id' => $hugpongBanay->id,
            'status' => 'active',
        ]);

        $hugpongBanay->update(['current_leader_id' => $member->id]);
        HugpongBanayLeaderHistory::create([
            'hugpong_banay_id' => $hugpongBanay->id,
            'member_id' => $member->id,
            'started_at' => '2026-01-01',
        ]);

        $this->actingAs($admin)
            ->delete(route('members.destroy', $member), [
                'delete_confirmation' => 'delete',
            ])
            ->assertRedirect(route('members.index'));

        $this->assertDatabaseMissing('members', [
            'id' => $member->id,
        ]);
    }

    public function test_member_with_balik_gasa_or_donation_payments_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = Member::create([
            'full_name' => 'Protected Payer',
            'status' => 'active',
        ]);

        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 100,
            'collection_date' => '2026-06-01',
            'collection_month' => '2026-06',
            'encoded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('members.show', $member))
            ->delete(route('members.destroy', $member), [
                'delete_confirmation' => 'delete',
            ])
            ->assertRedirect(route('members.show', $member))
            ->assertSessionHas('error', 'This member cannot be deleted because they have Balik Gasa or Donation payment history.');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
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
            ->assertSee('Already contributed')
            ->assertDontSee('Other Member');
    }

    public function test_locked_balik_gasa_month_shows_only_contributors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contributorMember = Member::create([
            'full_name' => 'Contributor Locked Member',
            'status' => 'active',
        ]);
        Member::create([
            'full_name' => 'Pending Locked Member',
            'status' => 'active',
        ]);

        Collection::create([
            'member_id' => $contributorMember->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 100,
            'collection_date' => '2026-06-01',
            'collection_month' => '2026-06',
        ]);
        MonthLock::create([
            'lockable_type' => Collection::BALIK_GASA,
            'month' => '2026-06',
            'locked_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('balik-gasa.index', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Contributor Locked Member')
            ->assertSee('1 contributed / 2 active members')
            ->assertDontSee('Pending Locked Member')
            ->assertDontSee('Month locked</span>', false)
            ->assertDontSee('>Locked</span>', false);
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

    public function test_member_profile_does_not_show_offering_as_member_collection(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = Member::create([
            'full_name' => 'Member Without Offering Summary',
            'status' => 'active',
        ]);

        $balikGasa = Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 100,
            'collection_date' => '2026-06-01',
            'collection_month' => '2026-06',
            'encoded_by' => $treasurer->id,
        ]);
        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::HALAD,
            'amount' => 500,
            'collection_date' => '2026-06-02',
            'encoded_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->get(route('members.show', $member))
            ->assertOk()
            ->assertSee('Balik Gasa')
            ->assertSee('Donation')
            ->assertSee(route('collections.edit', $balikGasa), false)
            ->assertDontSee('<p class="text-sm text-slate-500">Offering</p>', false)
            ->assertDontSee('PHP 500.00');
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
            ->assertJsonPath('months.1.reference_no', 'BG-2026-02')
            ->assertJsonPath('months.2.paid', false)
            ->assertJsonPath('months.2.can_record_historical', true)
            ->assertJsonPath('months.5.can_record_historical', false);
    }

    public function test_historical_balik_gasa_plot_payment_is_excluded_from_totals(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = Member::create([
            'full_name' => 'Historical Plot Member',
            'status' => 'active',
        ]);

        $this->actingAs($treasurer)
            ->post(route('members.balik-gasa-historical.store', $member), [
                'collection_month' => '2026-05',
                'amount' => 450,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collections', [
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'collection_month' => '2026-05',
            'amount' => 450,
            'excluded_from_totals' => true,
        ]);

        $historicalPayment = Collection::where('member_id', $member->id)->firstOrFail();

        $this->actingAs($treasurer)
            ->getJson(route('members.balik-gasa-year', ['member' => $member, 'year' => 2026]))
            ->assertOk()
            ->assertJsonPath('months.4.paid', true)
            ->assertJsonPath('months.4.excluded_from_totals', true)
            ->assertJsonPath('months.4.amount', 450)
            ->assertJsonPath('months.4.historical_update_url', route('members.balik-gasa-historical.update', [$member, $historicalPayment]));

        $this->actingAs($treasurer)
            ->patch(route('members.balik-gasa-historical.update', [$member, $historicalPayment]), [
                'amount' => 525,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collections', [
            'id' => $historicalPayment->id,
            'amount' => 525,
            'excluded_from_totals' => true,
        ]);

        $this->actingAs($treasurer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('PHP 525.00');
    }

    public function test_historical_balik_gasa_plot_payment_is_only_allowed_before_june_2026(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = Member::create([
            'full_name' => 'Rejected Historical Plot Member',
            'status' => 'active',
        ]);

        $this->actingAs($treasurer)
            ->post(route('members.balik-gasa-historical.store', $member), [
                'collection_month' => '2026-06',
                'amount' => 450,
            ])
            ->assertSessionHasErrors('collection_month');
    }

    public function test_quick_balik_gasa_uses_selected_payment_date(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = Member::create([
            'full_name' => 'Quick Balik Member',
            'status' => 'active',
        ]);

        $this->actingAs($treasurer)
            ->post(route('balik-gasa.quick-pay', $member), [
                'collection_month' => '2026-06',
                'amount' => 100,
                'collection_date' => '2026-06-15',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collections', [
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 100,
            'collection_date' => '2026-06-15 00:00:00',
            'collection_month' => '2026-06',
        ]);
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

    public function test_month_lock_blocks_balik_gasa_encoding_until_admin_unlocks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = Member::create([
            'full_name' => 'Locked Month Member',
            'status' => 'active',
        ]);

        $this->actingAs($treasurer)
            ->post(route('month-locks.store'), [
                'lockable_type' => Collection::BALIK_GASA,
                'month' => '2026-06',
            ])
            ->assertRedirect();

        $this->actingAs($treasurer)
            ->post(route('balik-gasa.quick-pay', $member), [
                'collection_month' => '2026-06',
                'amount' => 100,
                'collection_date' => '2026-06-20',
            ])
            ->assertSessionHas('error', MonthLock::lockedMessage(Collection::BALIK_GASA, '2026-06'));

        $this->assertDatabaseMissing('collections', [
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'collection_month' => '2026-06',
        ]);

        $lock = MonthLock::where('lockable_type', Collection::BALIK_GASA)->where('month', '2026-06')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('month-locks.destroy', $lock))
            ->assertRedirect();

        $this->actingAs($treasurer)
            ->post(route('balik-gasa.quick-pay', $member), [
                'collection_month' => '2026-06',
                'amount' => 100,
                'collection_date' => '2026-06-20',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collections', [
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'collection_month' => '2026-06',
        ]);
    }

    public function test_disbursement_lock_blocks_create_edit_and_delete(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        MonthLock::create([
            'lockable_type' => MonthLock::DISBURSEMENT,
            'month' => '2026-06',
            'locked_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->post(route('ledger.expenses.store'), [
                'fund_type' => Collection::BALIK_GASA,
                'category' => 'Maintenance',
                'amount' => 100,
                'expense_date' => '2026-06-15',
            ])
            ->assertSessionHasErrors('expense_date');

        $expense = Expense::create([
            'fund_type' => Collection::BALIK_GASA,
            'category' => 'Maintenance',
            'amount' => 100,
            'expense_date' => '2026-06-15',
            'encoded_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->put(route('ledger.expenses.update', $expense), [
                'fund_type' => Collection::BALIK_GASA,
                'category' => 'Maintenance',
                'amount' => 150,
                'expense_date' => '2026-06-16',
            ])
            ->assertSessionHasErrors('expense_date');

        $this->actingAs($treasurer)
            ->delete(route('ledger.expenses.destroy', $expense))
            ->assertSessionHas('error', MonthLock::lockedMessage(MonthLock::DISBURSEMENT, '2026-06'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'deleted_at' => null,
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

    public function test_ledger_can_record_manual_entry_and_disbursement(): void
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
                'pay_to' => 'Local hardware',
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
            'pay_to' => 'Local hardware',
        ]);
    }

    public function test_disbursement_can_be_edited_and_deleted(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $expense = Expense::create([
            'fund_type' => Collection::BALIK_GASA,
            'category' => 'Repairs',
            'pay_to' => 'Old supplier',
            'amount' => 500,
            'expense_date' => '2026-06-10',
            'encoded_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->get(route('ledger.expenses.edit', $expense))
            ->assertOk()
            ->assertSee('Edit Disbursement');

        $this->actingAs($treasurer)
            ->put(route('ledger.expenses.update', $expense), [
                'fund_type' => Collection::DONATION,
                'category' => 'Maintenance',
                'pay_to' => 'New supplier',
                'amount' => 650,
                'expense_date' => '2026-06-11',
                'reference_no' => 'EXP-EDIT',
                'remarks' => 'Updated expense',
            ])
            ->assertRedirect(route('ledger.index'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'fund_type' => Collection::DONATION,
            'category' => 'Maintenance',
            'pay_to' => 'New supplier',
            'amount' => 650,
            'reference_no' => 'EXP-EDIT',
        ]);

        $this->actingAs($treasurer)
            ->delete(route('ledger.expenses.destroy', $expense))
            ->assertRedirect(route('ledger.index'));

        $this->assertSoftDeleted('expenses', [
            'id' => $expense->id,
        ]);
    }

    public function test_manual_ledger_entry_requires_delete_confirmation(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $entry = LedgerEntry::create([
            'fund_type' => Collection::BALIK_GASA,
            'entry_type' => LedgerEntry::CREDIT,
            'amount' => 11000,
            'entry_date' => '2026-06-28',
            'remarks' => 'from ate Cora',
            'encoded_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->delete(route('ledger.entries.destroy', $entry), [
                'delete_confirmation' => 'delete',
            ])
            ->assertSessionHasErrors('delete_confirmation');

        $this->assertDatabaseHas('ledger_entries', [
            'id' => $entry->id,
            'deleted_at' => null,
        ]);

        $this->actingAs($treasurer)
            ->delete(route('ledger.entries.destroy', $entry), [
                'delete_confirmation' => 'DELETE',
            ])
            ->assertRedirect(route('ledger.index'));

        $this->assertSoftDeleted('ledger_entries', [
            'id' => $entry->id,
        ]);
    }

    public function test_dashboard_shows_recent_disbursements(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        Expense::create([
            'fund_type' => Collection::BALIK_GASA,
            'category' => 'Electric Bill',
            'pay_to' => 'Power Company',
            'amount' => 1200,
            'expense_date' => '2026-06-29',
            'encoded_by' => $treasurer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recent Disbursements')
            ->assertSee('Electric Bill')
            ->assertSee('Power Company')
            ->assertSee('PHP 1,200.00')
            ->assertDontSee('Pending Balik Gasa');
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

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Fund Balances')
            ->assertSee('Total Chapel Fund')
            ->assertSee('Credits PHP 850.00 / Debits PHP 150.00')
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

    public function test_reports_show_trial_balance_and_balance_sheet(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $member = Member::create([
            'full_name' => 'Financial Report Member',
            'status' => 'active',
        ]);

        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 500,
            'collection_date' => '2026-06-28',
            'collection_month' => '2026-06',
        ]);
        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::DONATION,
            'amount' => 300,
            'collection_date' => '2026-06-10',
        ]);
        LedgerEntry::create([
            'fund_type' => 'general',
            'entry_type' => LedgerEntry::CREDIT,
            'amount' => 200,
            'entry_date' => '2026-06-01',
        ]);
        Expense::create([
            'fund_type' => Collection::BALIK_GASA,
            'category' => 'Maintenance',
            'amount' => 250,
            'expense_date' => '2026-06-20',
        ]);

        $this->actingAs($viewer)
            ->get(route('reports.index', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Trial Balance')
            ->assertSee('Balance Sheet / Fund Position')
            ->assertSee('Statements: As of month end')
            ->assertSee('Show Statement Details')
            ->assertSee('Cash / Chapel Funds')
            ->assertSee('Collections and Other Sources')
            ->assertSee('Disbursements and Fund Deductions')
            ->assertSee('Financial Report Member')
            ->assertSee('Maintenance')
            ->assertSee('PHP 750.00')
            ->assertSee('PHP 1,000.00')
            ->assertSee('General Chapel Fund Adjustments')
            ->assertSee('Total Chapel Fund');
    }

    public function test_reports_financial_statements_can_use_selected_month_only(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $member = Member::create([
            'full_name' => 'Monthly Basis Member',
            'status' => 'active',
        ]);

        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 777,
            'collection_date' => '2026-05-25',
            'collection_month' => '2026-05',
        ]);
        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 123,
            'collection_date' => '2026-06-28',
            'collection_month' => '2026-06',
        ]);

        LedgerEntry::create([
            'fund_type' => Collection::DONATION,
            'entry_type' => LedgerEntry::CREDIT,
            'amount' => 444,
            'entry_date' => '2026-05-01',
        ]);
        Expense::create([
            'fund_type' => Collection::BALIK_GASA,
            'category' => 'Maintenance',
            'amount' => 23,
            'expense_date' => '2026-06-20',
        ]);

        $this->actingAs($viewer)
            ->get(route('reports.index', ['month' => '2026-06', 'statement_basis' => 'monthly']))
            ->assertOk()
            ->assertSee('Statements: Selected month only')
            ->assertSee('For June 2026')
            ->assertSee('Selected month view')
            ->assertSee('PHP 123.00')
            ->assertSee('PHP 23.00')
            ->assertDontSee('PHP 777.00')
            ->assertDontSee('PHP 444.00');
    }

    public function test_monthly_report_shows_balik_gasa_share_by_hugpong_banay(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $sanIsidro = HugpongBanay::create(['name' => 'San Isidro']);
        $fatima = HugpongBanay::create(['name' => 'Our Lady of Fatima']);
        $memberOne = Member::create([
            'full_name' => 'Report Member One',
            'status' => 'active',
            'hugpong_banay_id' => $sanIsidro->id,
        ]);
        $memberTwo = Member::create([
            'full_name' => 'Report Member Two',
            'status' => 'active',
            'hugpong_banay_id' => $fatima->id,
        ]);

        Collection::create([
            'member_id' => $memberOne->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 100,
            'collection_date' => '2026-06-05',
            'collection_month' => '2026-06',
            'encoded_by' => $treasurer->id,
        ]);
        Collection::create([
            'member_id' => $memberTwo->id,
            'collection_type' => Collection::BALIK_GASA,
            'amount' => 200,
            'collection_date' => '2026-06-06',
            'collection_month' => '2026-06',
            'encoded_by' => $treasurer->id,
        ]);
        Collection::create([
            'member_id' => $memberOne->id,
            'collection_type' => Collection::DONATION,
            'amount' => 999,
            'collection_date' => '2026-06-07',
            'encoded_by' => $treasurer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('reports.index', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Balik Gasa ICP / Chapel Share by Hugpong Banay')
            ->assertSee('Balik Gasa Subsummary by Hugpong Banay')
            ->assertSee('San Isidro')
            ->assertSee('Our Lady of Fatima')
            ->assertSee('PHP 300.00')
            ->assertSee('PHP 180.00')
            ->assertSee('PHP 120.00');

        $this->actingAs($viewer)
            ->get(route('reports.print', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Balik Gasa ICP / Chapel Share by Hugpong Banay')
            ->assertSee('PHP 180.00')
            ->assertSee('PHP 120.00');

        $this->actingAs($viewer)
            ->get(route('reports.print', ['month' => '2026-06', 'collection_type' => Collection::BALIK_GASA]))
            ->assertOk()
            ->assertSee('BALIK GASA - June 2026')
            ->assertSee('San Isidro')
            ->assertSee('Report Member One')
            ->assertSee('Grand Total')
            ->assertSee('PHP 300.00')
            ->assertDontSee('PHP 999.00');

        $this->actingAs($viewer)
            ->get(route('reports.balik-gasa-subsummary.print', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Balik Gasa Subsummary by Hugpong Banay')
            ->assertSee('Report Member One')
            ->assertSee('Report Member Two')
            ->assertSee('PHP 300.00')
            ->assertDontSee('PHP 999.00');
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

    public function test_hugpong_banay_leader_must_belong_to_that_hugpong_banay(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hugpongBanay = HugpongBanay::create(['name' => 'Leader Scope Hugpong']);
        $otherHugpongBanay = HugpongBanay::create(['name' => 'Other Leader Scope Hugpong']);
        $member = Member::create([
            'member_id' => 'PHFC-H03',
            'full_name' => 'Scoped Leader',
            'hugpong_banay_id' => $hugpongBanay->id,
            'status' => 'active',
        ]);
        $outsider = Member::create([
            'member_id' => 'PHFC-H04',
            'full_name' => 'Outsider Leader',
            'hugpong_banay_id' => $otherHugpongBanay->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('hugpong-banays.edit', $hugpongBanay))
            ->assertOk()
            ->assertSee('Scoped Leader')
            ->assertDontSee('Outsider Leader');

        $this->actingAs($admin)
            ->put(route('hugpong-banays.update', $hugpongBanay), [
                'name' => 'Leader Scope Hugpong',
                'status' => 'active',
                'description' => null,
                'current_leader_id' => $outsider->id,
                'leader_started_at' => '2026-06-01',
            ])
            ->assertSessionHasErrors('current_leader_id');

        $this->actingAs($admin)
            ->put(route('hugpong-banays.update', $hugpongBanay), [
                'name' => 'Leader Scope Hugpong',
                'status' => 'active',
                'description' => null,
                'current_leader_id' => $member->id,
                'leader_started_at' => '2026-06-01',
            ])
            ->assertRedirect(route('hugpong-banays.show', $hugpongBanay));

        $this->assertDatabaseHas('hugpong_banays', [
            'id' => $hugpongBanay->id,
            'current_leader_id' => $member->id,
        ]);
    }
}
