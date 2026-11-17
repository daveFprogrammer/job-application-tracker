<?php

namespace Tests\Feature\Applications;

use App\Models\JobApplication;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_add_and_delete_notes(): void
    {
        $application = JobApplication::factory()->create();

        $this->actingAs($application->user)
            ->post(route('notes.store', $application), ['body' => 'Recruiter called, salary ok'])
            ->assertRedirect();

        $note = Note::sole();
        $this->assertSame('Recruiter called, salary ok', $note->body);

        $this->actingAs($application->user)->delete(route('notes.destroy', $note))->assertRedirect();
        $this->assertModelMissing($note);
    }

    public function test_users_cannot_delete_notes_of_others(): void
    {
        $note = Note::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('notes.destroy', $note))
            ->assertForbidden();

        $this->assertModelExists($note);
    }
}
