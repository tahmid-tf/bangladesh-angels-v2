<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class BlogContentSanitizer
{
    private const ALLOWED_TAGS = '<p><br><h2><h3><h4><strong><b><em><i><u><ul><ol><li><blockquote><a><hr>';

    public static function clean(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\\1>#is', '', $html) ?? '';
        $html = preg_replace('#<div(?:\\s[^>]*)?>#i', '<p>', $html) ?? $html;
        $html = preg_replace('#</div>#i', '</p>', $html) ?? $html;
        $html = trim(strip_tags($html, self::ALLOWED_TAGS));

        if ($html === '' || ! class_exists(DOMDocument::class)) {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="blog-content-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('blog-content-root');
        if (! $root) {
            return '';
        }

        self::sanitizeChildren($root);

        $clean = '';
        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    private static function sanitizeChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $allowedAttributes = $child->tagName === 'a' ? ['href', 'target', 'rel'] : [];
            foreach (iterator_to_array($child->attributes) as $attribute) {
                if (! in_array($attribute->name, $allowedAttributes, true)) {
                    $child->removeAttribute($attribute->name);
                }
            }

            if ($child->tagName === 'a') {
                $href = trim($child->getAttribute('href'));
                if (! self::isSafeLink($href)) {
                    $child->removeAttribute('href');
                    $child->removeAttribute('target');
                }
                if ($child->getAttribute('target') === '_blank') {
                    $child->setAttribute('rel', 'noopener noreferrer');
                } else {
                    $child->removeAttribute('target');
                    $child->removeAttribute('rel');
                }
            }

            self::sanitizeChildren($child);
        }
    }

    private static function isSafeLink(string $href): bool
    {
        if ($href === '' || str_starts_with($href, '/') || str_starts_with($href, '#')) {
            return true;
        }

        return (bool) preg_match('#^(https?://|mailto:|tel:)#i', $href);
    }
}
