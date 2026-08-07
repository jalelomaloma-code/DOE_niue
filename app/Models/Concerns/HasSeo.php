<?php

namespace App\Models\Concerns;

trait HasSeo
{
    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function seoDescription(): ?string
    {
        return $this->seo_description ?: $this->seoFallbackDescription();
    }

    /**
     * Each model names its own summary column, so it supplies the fallback.
     * Implement on every model that uses this trait.
     */
    abstract public function seoFallbackDescription(): ?string;
}
