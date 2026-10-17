<?php

namespace Tests\Feature;

use App\Models\GeneratedEmail;
use App\Models\ReceivedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_generate_disposable_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('emails.generate'));

        $response->assertRedirect();
        $this->assertDatabaseCount('generated_emails', 1);
        $this->assertEquals($user->id, GeneratedEmail::first()->user_id);
    }

    public function test_user_cannot_exceed_max_allowed_active_emails(): void
    {
        $user = User::factory()->create();
        config(['imap.email_max_per_user' => 2]);

        $this->actingAs($user)->post(route('emails.generate'));
        $this->actingAs($user)->post(route('emails.generate'));
        $response = $this->actingAs($user)->post(route('emails.generate'));

        $this->assertDatabaseCount('generated_emails', 2);
    }

    public function test_user_can_view_inbox_and_received_emails(): void
    {
        $user = User::factory()->create();
        $generated = GeneratedEmail::create([
            'user_id' => $user->id,
            'email_address' => 'test.box123@example.com',
            'local_part' => 'test.box123',
            'is_active' => true,
            'expires_at' => now()->addHour(),
        ]);

        $received = ReceivedEmail::create([
            'generated_email_id' => $generated->id,
            'from_address' => 'security@facebookmail.com',
            'from_name' => 'Facebook',
            'subject' => 'Kode OTP: 789123',
            'body_html' => '<p>OTP: 789123</p>',
            'body_text' => 'OTP: 789123',
            'received_at' => now(),
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->get(route('inbox.index', $generated->id));
        $response->assertOk();

        // View single email marks as read
        $showResponse = $this->actingAs($user)->get(route('inbox.show', [$generated->id, $received->id]));
        $showResponse->assertOk();

        $this->assertTrue($received->fresh()->is_read);
    }

    public function test_user_cannot_access_other_users_inbox(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $generated = GeneratedEmail::create([
            'user_id' => $user1->id,
            'email_address' => 'user1.private@example.com',
            'local_part' => 'user1.private',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user2)->get(route('inbox.index', $generated->id));
        $response->assertStatus(403);
    }
}
