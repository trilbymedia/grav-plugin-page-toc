<?php
namespace Grav\Plugin\Shortcodes;

use Grav\Common\Inflector;
use Thunder\Shortcode\Shortcode\ProcessedShortcode;

class FragShortcode extends Shortcode
{
  public function init()
  {
    $this->shortcode->getHandlers()->add('frag', function(ProcessedShortcode $sc) {
      $anchor_class = $this->grav['config']->get('plugins.page-toc.anchors_class', 'anchor');
      $id = $sc->getParameter('id', $sc->getBbCode());
      $prefix = $sc->getParameter('prefix');
      $class = $sc->getParameter('class');
      $content = $sc->getContent();

      if (is_null($id)) {
          $id = Inflector::hyphenize(strip_tags($content));
      }

      if (isset($prefix)) {
          $id = $prefix . $id;
      }

      return "<a class=\"$anchor_class $class\" href=\"#$id\" id=\"$id\">$content</a>";
    });
  }
}