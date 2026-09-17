<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_the_root_url_renders_the_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertViewIs('landing');
    }

    public function test_every_file_the_landing_page_loads_exists_in_public(): void
    {
        $page = $this->get('/')->getContent();

        // The view pins the hashed names of a build copied from the separate landing page project,
        // and that bundle hardcodes its /img/ paths: a stale view or a missed image copy breaks the
        // page while the route itself still renders fine.
        $pageFiles = $this->localFilePathsIn($page);
        $bundleFiles = collect($pageFiles)
            ->filter(fn (string $path): bool => preg_match('/\.(js|css)$/', $path) && is_file(public_path($path)))
            ->flatMap(fn (string $bundle): array => $this->localFilePathsIn(file_get_contents(public_path($bundle))))
            ->unique()
            ->values()
            ->all();

        $this->assertSame([], $this->missingFromPublic($pageFiles));
        $this->assertNotEmpty($bundleFiles, 'Found no local files in the landing bundle, so nothing was checked.');
        $this->assertSame([], $this->missingFromPublic($bundleFiles));
    }

    /**
     * Root-relative file paths quoted in HTML, JS or CSS, such as "/img/logo.webp".
     *
     * @return list<string>
     */
    private function localFilePathsIn(string $source): array
    {
        preg_match_all('#["\'`(](/(?!/)[\w\-./]+\.\w+)["\'`)]#', $source, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function missingFromPublic(array $paths): array
    {
        return array_values(array_filter($paths, fn (string $path): bool => ! is_file(public_path($path))));
    }
}
