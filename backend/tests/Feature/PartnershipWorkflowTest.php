<?php

namespace Tests\Feature;

use App\Models\PartnershipRequest;
use App\Models\User;
use App\Models\AdminPermission;
use App\Mail\PartnershipApprovedMail;
use App\Mail\PartnershipRejectedMail;
use App\Notifications\NewPartnershipRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartnershipWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $normalAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Super Admin
        $this->superAdmin = User::create([
            'name' => 'OM Super Admin',
            'email' => 'super@omgems.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin'
        ]);

        // Seed normal admin
        $this->normalAdmin = User::create([
            'name' => 'OM Normal Admin',
            'email' => 'admin@omgems.com',
            'password' => bcrypt('password'),
            'role' => 'normal_admin'
        ]);
    }

    /**
     * 1. Test partnership request submission.
     */
    public function test_partnership_request_submission()
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/partnership-requests', [
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '1234567890',
            'business_name' => 'ABC Gems',
            'business_type' => 'Retailer',
            'purpose' => 'Interested in becoming a diamond supplier'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Partnership request submitted successfully.'
        ]);

        $this->assertDatabaseHas('partnership_requests', [
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'business_name' => 'ABC Gems',
            'status' => 'Pending'
        ]);

        // Verify Super Admin is notified
        Notification::assertSentTo(
            $this->superAdmin,
            NewPartnershipRequestNotification::class
        );
    }

    /**
     * 2. Test duplicate request prevention.
     */
    public function test_duplicate_request_prevention()
    {
        // Create an existing pending request
        PartnershipRequest::create([
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '1234567890',
            'business_name' => 'ABC Gems',
            'business_type' => 'Retailer',
            'purpose' => 'Supplier',
            'status' => 'Pending'
        ]);

        // Try submitting again
        $response = $this->postJson('/api/v1/partnership-requests', [
            'full_name' => 'John Doe 2',
            'email' => 'john@example.com',
            'phone_number' => '9876543210',
            'business_name' => 'XYZ Gems',
            'business_type' => 'Manufacturer',
            'purpose' => 'Retailer'
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'A pending partnership request already exists for this email address.'
        ]);

        // Count of requests for email should still be 1
        $this->assertEquals(1, PartnershipRequest::where('email', 'john@example.com')->count());
    }

    /**
     * 3. Test approval creates Normal Admin account.
     */
    public function test_approval_creates_normal_admin_account()
    {
        Mail::fake();

        $request = PartnershipRequest::create([
            'full_name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone_number' => '1234567890',
            'business_name' => 'Jane Retail',
            'business_type' => 'Retailer',
            'purpose' => 'Supplier',
            'status' => 'Pending'
        ]);

        // Authenticate Super Admin via Session
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post(route('partnership-requests.approve', $request->id), [
                'notes' => 'Approved partner application'
            ]);

        $response->assertRedirect(route('partnership-requests.show', $request->id));
        $response->assertSessionHas('success', 'Partner account created successfully. Password setup email sent.');
        $response->assertSessionMissing('generated_password');

        $request->refresh();
        $this->assertEquals('Approved', $request->status);
        $this->assertNotNull($request->converted_to_user_id);
        $this->assertEquals('Approved partner application', $request->notes);

        // Verify User Account was created
        $user = User::find($request->converted_to_user_id);
        $this->assertNotNull($user);
        $this->assertEquals('Jane Smith', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
        $this->assertEquals('normal_admin', $user->role);

        // Verify permissions were seeded
        $this->assertDatabaseHas('admin_permissions', [
            'user_id' => $user->id,
            'permission' => 'view_inventory'
        ]);
        $this->assertDatabaseHas('admin_permissions', [
            'user_id' => $user->id,
            'permission' => 'view_shopify_orders'
        ]);

        // Verify email delivery with setup link
        Mail::assertSent(PartnershipApprovedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) &&
                   $mail->name === $user->name &&
                   $mail->email === $user->email &&
                   !empty($mail->setupPasswordUrl) &&
                   str_contains($mail->setupPasswordUrl, '/set-password?token=');
        });
    }

    /**
     * 4. Test rejection workflow.
     */
    public function test_rejection_workflow()
    {
        Mail::fake();

        $request = PartnershipRequest::create([
            'full_name' => 'Bob Miller',
            'email' => 'bob@example.com',
            'phone_number' => '1234567890',
            'business_name' => 'Bob Gems',
            'business_type' => 'Retailer',
            'purpose' => 'Supplier',
            'status' => 'Pending'
        ]);

        // Authenticate Super Admin via Session
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post(route('partnership-requests.reject', $request->id), [
                'notes' => 'Inquiry rejected'
            ]);

        $response->assertRedirect(route('partnership-requests.show', $request->id));

        $request->refresh();
        $this->assertEquals('Rejected', $request->status);
        $this->assertEquals('Inquiry rejected', $request->notes);
        $this->assertNull($request->converted_to_user_id);

        // Verify no user was created
        $this->assertDatabaseMissing('users', [
            'email' => 'bob@example.com'
        ]);

        // Verify rejection email was sent
        Mail::assertSent(PartnershipRejectedMail::class, function ($mail) use ($request) {
            return $mail->hasTo($request->email) &&
                   $mail->notes === 'Inquiry rejected';
        });
    }

    /**
     * 5. Test duplicate approval prevention.
     */
    public function test_duplicate_approval_prevention()
    {
        Mail::fake();

        $request = PartnershipRequest::create([
            'full_name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone_number' => '1234567890',
            'business_name' => 'Jane Retail',
            'business_type' => 'Retailer',
            'purpose' => 'Supplier',
            'status' => 'Pending'
        ]);

        // Direct API Approve first time
        Sanctum::actingAs($this->superAdmin);
        $response1 = $this->postJson(route('api.admin.partnership-requests.approve', $request->id), [
            'notes' => 'First approval'
        ]);
        $response1->assertStatus(200);

        // Direct API Approve second time
        $response2 = $this->postJson(route('api.admin.partnership-requests.approve', $request->id), [
            'notes' => 'Second approval'
        ]);
        $response2->assertStatus(422);
        $response2->assertJson([
            'success' => false,
            'message' => 'Approval failed: This request is not pending approval.'
        ]);
    }

    /**
     * 6. Test existing user email conflict.
     */
    public function test_existing_user_email_conflict()
    {
        Mail::fake();

        // Create user with email admin@omgems.com (already seeded in setUp as $this->normalAdmin)
        $request = PartnershipRequest::create([
            'full_name' => 'Conflict User',
            'email' => 'admin@omgems.com', // Conflict email
            'phone_number' => '1234567890',
            'business_name' => 'Conflict Business',
            'business_type' => 'Retailer',
            'purpose' => 'Supplier',
            'status' => 'Pending'
        ]);

        // Attempt approval
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post(route('partnership-requests.approve', $request->id), [
                'notes' => 'Approve conflict'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Approval failed: A user with this email address already exists.');

        $request->refresh();
        $this->assertEquals('Pending', $request->status);
        $this->assertNull($request->converted_to_user_id);
    }

    /**
     * 7. Test password setup and authentication of newly approved partner.
     */
    public function test_authentication_of_newly_approved_partner()
    {
        Mail::fake();

        $request = PartnershipRequest::create([
            'full_name' => 'Partner Account',
            'email' => 'partner@test.com',
            'phone_number' => '1234567890',
            'business_name' => 'Partner Gems',
            'business_type' => 'Retailer',
            'purpose' => 'Supplier',
            'status' => 'Pending'
        ]);

        // Approve
        $this->actingAs($this->superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post(route('partnership-requests.approve', $request->id));

        $request->refresh();

        // Extract setup URL from the mail payload
        $setupUrl = '';
        Mail::assertSent(PartnershipApprovedMail::class, function ($mail) use (&$setupUrl) {
            $setupUrl = $mail->setupPasswordUrl;
            return true;
        });

        $this->assertNotEmpty($setupUrl);

        // Parse token and email from URL query string
        $parsedUrl = parse_url($setupUrl);
        parse_str($parsedUrl['query'], $query);
        $token = $query['token'];
        $email = $query['email'];

        $this->assertNotEmpty($token);
        $this->assertEquals('partner@test.com', $email);

        // Submit to set-password route
        $passwordSetupResponse = $this->post('/set-password', [
            'token' => $token,
            'email' => $email,
            'password' => 'new_secure_password',
            'password_confirmation' => 'new_secure_password'
        ]);

        $passwordSetupResponse->assertRedirect(route('login'));
        $passwordSetupResponse->assertSessionHas('success', 'Password set successfully. You can now log in.');

        // Test login via web login route with the new password
        $loginResponse = $this->post('/login', [
            'email' => 'partner@test.com',
            'password' => 'new_secure_password'
        ]);

        $loginResponse->assertRedirect('home');
        $this->assertAuthenticated();

        // Logged-in user should be the new partner
        $user = auth()->user();
        $this->assertEquals('Partner Account', $user->name);
        $this->assertEquals('partner@test.com', $user->email);
        $this->assertEquals('normal_admin', $user->role);

        // Access protected inventory list page
        $inventoryResponse = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/inventory');

        $inventoryResponse->assertStatus(200);
    }

    /**
     * Test approval succeeds and is not rolled back when email sending fails.
     */
    public function test_approval_succeeds_even_if_email_fails()
    {
        // Mock Mail facade to throw exception
        \Illuminate\Support\Facades\Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \Exception('Connection could not be established with host mailpit:1025'));

        $request = PartnershipRequest::create([
            'full_name' => 'Failed Mail Partner',
            'email' => 'failmail@example.com',
            'phone_number' => '1234567890',
            'business_name' => 'Fail Mail Gems',
            'business_type' => 'Retailer',
            'purpose' => 'Supplier',
            'status' => 'Pending'
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post(route('partnership-requests.approve', $request->id), [
                'notes' => 'Approve with failing mailer'
            ]);

        // Verify redirect back with warning
        $response->assertRedirect(route('partnership-requests.show', $request->id));
        $response->assertSessionHas('warning', 'Account created successfully but onboarding email could not be delivered.');

        $request->refresh();
        $this->assertEquals('Approved', $request->status);
        $this->assertNotNull($request->converted_to_user_id);

        // Verify User Account was created despite email failure
        $user = User::find($request->converted_to_user_id);
        $this->assertNotNull($user);
        $this->assertEquals('Failed Mail Partner', $user->name);
        $this->assertEquals('failmail@example.com', $user->email);
        $this->assertEquals('normal_admin', $user->role);

        // Verify permissions were seeded correctly
        $this->assertDatabaseHas('admin_permissions', [
            'user_id' => $user->id,
            'permission' => 'view_inventory'
        ]);
    }

    /**
     * Test that deleting a Normal Admin created from a Partnership Request
     * automatically revokes/rejects the partnership request.
     */
    public function test_deleting_normal_admin_revokes_partnership_request()
    {
        Mail::fake();

        $request = PartnershipRequest::create([
            'full_name' => 'Revoke Test Partner',
            'email' => 'revoketest@example.com',
            'phone_number' => '1234567890',
            'business_name' => 'Revoke Test Gems',
            'business_type' => 'Retailer',
            'purpose' => 'Supplier',
            'status' => 'Pending',
            'notes' => 'Initial inquiry notes'
        ]);

        // 1. Approve the request
        $this->actingAs($this->superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->post(route('partnership-requests.approve', $request->id), [
                'notes' => 'Approved partnership notes'
            ]);

        $request->refresh();
        $this->assertEquals('Approved', $request->status);
        $user_id = $request->converted_to_user_id;
        $this->assertNotNull($user_id);
        
        $user = User::findOrFail($user_id);

        // 2. Delete the created Normal Admin via User Management route
        $deleteResponse = $this->actingAs($this->superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->delete(route('admins.destroy', $user->id));

        $deleteResponse->assertRedirect(route('admins.index'));

        // Verify user is deleted
        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);

        // 3. Verify the partnership request status changes to Rejected
        $request->refresh();
        $this->assertEquals('Rejected', $request->status);
        $this->assertNull($request->converted_to_user_id);
        
        // Verify audit trail fields are populated correctly
        $this->assertNotNull($request->approved_at);
        $this->assertEquals($this->superAdmin->id, $request->approved_by);
        $this->assertNotNull($request->rejected_at);
        $this->assertEquals($this->superAdmin->id, $request->rejected_by);

        // Verify notes contain original notes plus the revocation message
        $this->assertStringContainsString('Approved partnership notes', $request->notes);
        $this->assertStringContainsString('Partner account was removed by Super Admin. Approval has been revoked.', $request->notes);
    }
}
