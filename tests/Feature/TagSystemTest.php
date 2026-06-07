<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Label;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TagSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Vault $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create([
            'account_id' => $this->user->account_id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_tag_and_see_it_in_tag_list(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/vaults/{$this->vault->id}/tags", [
                'name' => 'Family',
                'tag_category' => 'Personal',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Family');

        $this->actingAs($this->user)
            ->getJson("/api/vaults/{$this->vault->id}/tags")
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Family']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_filters_contacts_by_multiple_tags_using_and_logic(): void
    {
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $tag1 = Label::factory()->create(['vault_id' => $this->vault->id]);
        $tag2 = Label::factory()->create(['vault_id' => $this->vault->id]);
        $tag3 = Label::factory()->create(['vault_id' => $this->vault->id]);

        $contact->labels()->attach([$tag1->id, $tag2->id]);

        $this->actingAs($this->user)
            ->getJson("/api/vaults/{$this->vault->id}/contacts?tags[]={$tag1->id}&tags[]={$tag2->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $contact->id]);

        $this->actingAs($this->user)
            ->getJson("/api/vaults/{$this->vault->id}/contacts?tags[]={$tag1->id}&tags[]={$tag3->id}")
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $contact->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_detaches_tags_from_contacts_when_tag_is_deleted(): void
    {
        $contact = Contact::factory()->create(['vault_id' => $this->vault->id]);
        $tag = Label::factory()->create(['vault_id' => $this->vault->id]);
        $contact->labels()->attach($tag->id);

        $this->assertDatabaseHas('contact_label', [
            'contact_id' => $contact->id,
            'label_id'   => $tag->id,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/vaults/{$this->vault->id}/tags/{$tag->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('contact_label', [
            'contact_id' => $contact->id,
            'label_id'   => $tag->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_invalidates_cache_when_tag_is_created(): void
    {
        $cacheKey = "vault_{$this->vault->id}_tags";

        Cache::put($cacheKey, 'cached_value', 600);
        $this->assertTrue(Cache::has($cacheKey));

        $this->actingAs($this->user)
            ->postJson("/api/vaults/{$this->vault->id}/tags", ['name' => 'Work']);

        $this->assertFalse(Cache::has($cacheKey));
    }
}
