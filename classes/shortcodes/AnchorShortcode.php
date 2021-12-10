<?php
namespace Grav\Plugin\Shortcodes;

use Grav\Common\Inflector;
use Grav\Plugin\PageToc\UniqueSlugify;
use Grav\Plugin\PageTOCPlugin;
use Thunder\Shortcode\Shortcode\ProcessedShortcode;

class AnchorShortcode extends Shortcode
{
  public function init()
  {
    $this->shortcode->getHandlers()->add('anchor', function(ProcessedShortcode $sc) {
      $id = $sc->getParameter('id', $sc->getBbCode());
      $prefix = $sc->getParameter('prefix', PageTOCPlugin::upstreamConfigVar('anchors.slug_prefix'));
      $class = $sc->getParameter('class', 'inline-anchor');
      $aria = PageTOCPlugin::upstreamConfigVar('anchors.aria');
      $content = $sc->getContent();

      $slugger = new UniqueSlugify();

      if (is_null($id)) {
          $id = $slugger->slugify(strip_tags($content));
      }

      if (isset($prefix)) {
          $id = $prefix . $id;
      }

      return "<a id=\"$id\" href=\"#$id\" class=\"$class\" aria-label=\"$aria\">$content</a>";
    });
    $this->shortcode->getHandlers()->addAlias('#', 'anchor');
  }
}