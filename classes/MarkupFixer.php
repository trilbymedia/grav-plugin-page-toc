<?php

/**
 * PageTOC
 *
 * This plugin allows creation of Table of Contents + Link Anchors
 *
 * Based on the original version https://github.com/caseyamcl/toc
 * by Casey McLaughlin <caseyamcl@gmail.com>
 *
 * Licensed under MIT, see LICENSE.
 */

declare(strict_types=1);

namespace Grav\Plugin\PageToc;

use DOMElement;
use RuntimeException;
use Cocur\Slugify\SlugifyInterface;

/**
 * TOC Markup Fixer adds `id` attributes to all H1...H6 tags where they do not
 * already exist
 *
 * @author Casey McLaughlin <caseyamcl@gmail.com>
 */
class MarkupFixer
{
    use HtmlHelper;

    /**
     * Fix markup
     *
     * @param string $markup
     * @param int    $start
     * @param int    $depth
     * @param array  $options
     * @return string Markup with added IDs
     * @throws RuntimeException
     */
    public function fix(string $markup, array $options = []): string
    {
        if (! $this->isFullHtmlDocument($markup)) {
            $partialID = uniqid('toc_generator_');
            $markup = sprintf("<body id='%s'>%s</body>", $partialID, $markup);
        }

        $start = $options['start'] ?? 1;
        $depth = $options['depth'] ?? 6;

        $domDocument = $this->getHTMLParser($markup);

        $slugger = new UniqueSlugify();

        /** @var DOMElement $node */
        foreach ($this->traverseHeaderTags($domDocument, $start, $depth) as $node) {
            if ($node->getAttribute('id')) {
                continue;
            }
            $slug = $slugger->slugify($node->getAttribute('title') ?: $node->textContent, $options);

            $node->setAttribute('id', $slug);

            if ($options['link']) {
                $link = $domDocument->createElement("a");
                $class = isset($options['class']) ? " {$options['class']}" : "";
                $link->setAttribute('href', "#$slug");
                $link->setAttribute('class', "toc-anchor {$options['position']}$class");
                $link->setAttribute('data-anchor-icon', $options['icon']);
                $link->setAttribute('aria-label', $options['aria']);
                if ($options['position'] == 'after') {
                    $node->appendChild($link);
                } else {
                    $node->insertBefore($link, $node->firstChild);
                }
            }
        }

        return $domDocument->saveHTML(
            (isset($partialID)) ? $domDocument->getElementById($partialID) : $domDocument
        );
    }
}
