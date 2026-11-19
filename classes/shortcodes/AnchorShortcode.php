<?php
namespace Grav\Plugin\Shortcodes;

use Grav\Common\Inflector;
use Grav\Plugin\PageToc\UniqueSlugify;
use Grav\Plugin\PageTOCPlugin;
use Thunder\Shortcode\Shortcode\ProcessedShortcode;

class AnchorShortcode extends Shortcode
{
  /**
   * @return array<string,mixed>
   */
  protected function getSlugifyOptions($page): array
  {
    $options = [];
    $customRulesets = (bool) PageTOCPlugin::configVar('slugify.custom_rulesets', $page, false);

    foreach (['regexp', 'separator', 'lowercase', 'lowercase_after_regexp', 'trim', 'strip_tags'] as $key) {
      $value = PageTOCPlugin::configVar('slugify.' . $key, $page);
      if ($value !== null) {
        $options[$key] = $value;
      }
    }

    if ($customRulesets) {
      $options['rulesets'] = PageTOCPlugin::configVar('slugify.rulesets', $page, []);
    }

    return $options;
  }

  public function init()
  {
    $this->shortcode->getRawHandlers()->add('anchor', function(ProcessedShortcode $sc) {
      // The page whose content holds this shortcode, not necessarily the visited page.
      $page = $this->shortcode->getPage();

      $id = $this->cleanParam($sc->getParameter('id', $sc->getBbCode()));
      $tag = $this->cleanParam($sc->getParameter('tag'));
      $prefix = $this->cleanParam($sc->getParameter('prefix', PageTOCPlugin::configVar('anchors.slug_prefix', $page)));
      $class = $this->cleanParam($sc->getParameter('class', 'inline-anchor'));
      $aria = PageTOCPlugin::configVar('anchors.aria', $page);
      $content = $sc->getContent();
      $slugifyOptions = $this->getSlugifyOptions($page);

      $slugger = new UniqueSlugify($slugifyOptions);

      if (is_null($id)) {
          $id = $slugger->slugify(strip_tags($content), $slugifyOptions);
      }

      if (isset($prefix)) {
          $id = $prefix . $id;
      }

      if ($tag) {
        $output = "<$tag id=\"$id\" class=\"$class\">$content</$tag>";
      } else {
        $output = "<a id=\"$id\" href=\"#$id\" class=\"$class\" aria-label=\"$aria\">$content</a>";
      }

      return $output;
    });
    $this->shortcode->getRawHandlers()->addAlias('#', 'anchor');
  }

  /**
   * @param $param
   * @return string
   */
  protected function cleanParam($param)
  {
    return trim(html_entity_decode($param), '"');
  }
}