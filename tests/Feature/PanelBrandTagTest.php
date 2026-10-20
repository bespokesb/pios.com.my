<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;
use Tests\TestCase;

class PanelBrandTagTest extends TestCase
{
    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_brand_shows_the_current_panels_tag_beside_the_wordmark(): void
    {
        $cases = [
            'admin' => 'Admin',
            'partner' => 'Partner',
            'business' => 'Business',
        ];

        foreach ($cases as $panel => $tag) {
            Filament::setCurrentPanel($panel);

            $html = view('filament.brand')->render();

            $this->assertStringContainsString('brand/pios-wordmark.png', $html);
            $this->assertStringContainsString('pios-brand-tag', $html);
            $this->assertStringContainsString($tag, $html);
        }
    }

    public function test_each_panel_uses_the_brand_view_as_its_logo(): void
    {
        foreach (['admin', 'partner', 'business'] as $panel) {
            Filament::setCurrentPanel($panel);

            $logo = Filament::getPanel($panel)->getBrandLogo();

            $this->assertInstanceOf(Htmlable::class, $logo);
            $this->assertStringContainsString('pios-brand-tag', $logo->toHtml());
        }
    }
}
