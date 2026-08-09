<?php

namespace App\Models\Concerns;

use App\Support\RichTextSanitiser;

trait HasSanitisedRichText
{
    public function sanitiseRichText(?string $html): ?string
    {
        return RichTextSanitiser::sanitise($html);
    }
}
