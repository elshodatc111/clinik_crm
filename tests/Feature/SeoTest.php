<?php

namespace Tests\Feature;

use Tests\ClinicTestCase;

class SeoTest extends ClinicTestCase
{
    public function test_landing_has_canonical_og_and_jsonld(): void
    {
        $r = $this->get('/')->assertOk();
        $r->assertSee('<link rel="canonical" href="'.url('/').'">', false)
            ->assertSee('property="og:title"', false)->assertSee('application/ld+json', false)->assertSee('SoftwareApplication', false)
            ->assertSee('content="index,follow', false);
        $this->assertNull($r->headers->get('X-Robots-Tag'));
    }

    public function test_canonical_ignores_query_string(): void
    {
        $this->get('/?utm_source=x')->assertSee('<link rel="canonical" href="'.url('/').'">', false);
    }

    public function test_private_pages_are_noindex(): void
    {
        $this->get(route('login'))->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('noindex,nofollow', false);
        $this->get(route('register'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->actingAs($this->superAdmin())->get('/admin')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_robots_and_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')->assertSee('Sitemap: '.url('/sitemap.xml'), false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>'.url('/').'</loc>', false)->assertSee(route('offer'), false)->assertDontSee('/login');
    }
}
