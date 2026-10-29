<?php

namespace App\Catalog\Sections;

use App\Catalog\Section;

class CoverSection implements Section
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $subtitle = null,
    ) {}

    public function view(): string
    {
        return 'catalog.sections.cover';
    }

    public function data(): array
    {
        return [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
        ];
    }

    public function pageCount(): int
    {
        return 1;
    }
}
