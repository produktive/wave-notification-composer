<?php

namespace Wave\Plugins\NotificationComposer\Support;

use Illuminate\Support\Str;

class MessageContent
{
    public static function plainText(string $html): string
    {
        $html = Str::sanitizeHtml($html);
        $html = preg_replace('~</(?:p|div|h[1-6]|li|blockquote|tr|td|th)\s*>|<br\b[^>]*>~i', ' ', $html);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
