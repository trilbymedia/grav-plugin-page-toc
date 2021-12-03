<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Data;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Common\Plugin;
use Grav\Plugin\PageToc\MarkupFixer;
use Grav\Plugin\PageToc\TocGenerator;
use RocketTheme\Toolbox\Event\Event;
use Twig\TwigFunction;


/**
 * Class PageTOCPlugin
 * @package Grav\Plugin
 */
class PageTOCPlugin extends Plugin
{
    protected $start;
    protected $end;
    protected $toc_regex = '#\[TOC\s*\/?\]#i';

    protected $fixer;
    protected $generator;

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            $this->enable([
                'onBlueprintCreated' => ['onBlueprintCreated', 0],
            ]);
            return;
        }

        // Enable the main event we are interested in
        $this->enable([
            'onShortcodeHandlers'       => ['onShortcodeHandlers', 0],
            'onTwigInitialized'         => ['onTwigInitialized', 0],
            'onTwigTemplatePaths'       => ['onTwigTemplatePaths', 0],
            'onTwigSiteVariables'       => ['onTwigSiteVariables', 0],
            'onPageContentProcessed'    => ['onPageContentProcessed', 0],
        ]);
    }

    public function onShortcodeHandlers()
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/classes/shortcodes');
    }

    public function onPageContentProcessed(Event $event)
    {
        /** @var PageInterface $page */
        $page = $event['page'];

        $content = $page->getRawContent();
        $shortcode_exists = preg_match($this->toc_regex, $content);
        $active = $this->upstreamConfigVar('active', $page, false);

        // Set ID anchors if needed
        if ($active || $shortcode_exists) {
            $this->registerTwigFunctions();
            $markup_fixer = new MarkupFixer();
            $content = $markup_fixer->fix($content, $this->getAnchorOptions($page));
            $page->setRawContent($content);
        }

        // Replace shortcode if found
        if ($shortcode_exists) {
            $toc = $this->grav['twig']->processTemplate('components/page-toc.html.twig', ['page' => $page, 'active' => true]);
            $content = preg_replace($this->toc_regex, $toc, $content);
            $page->setRawContent($content);
        }
    }

    public function onTwigInitialized()
    {
        $this->registerTwigFunctions();
    }

    public function onTwigSiteVariables()
    {
        if ($this->grav['config']->get('plugins.page-toc.include_css')) {
            $this->grav['assets']->addCss('plugin://page-toc/assets/page-toc-anchors.css');
        }
    }

    public function registerTwigFunctions()
    {
        static $functions_registered;

        if ($functions_registered) {
            return;
        }

        $this->generator = new TocGenerator();
        $this->fixer     = new MarkupFixer();
        $twig = $this->grav['twig']->twig();

        $twig->addFunction(new TwigFunction('toc', function ($markup, $start = null, $depth = null) {
            $options = $this->getTocOptions(null, $start, $depth);
            return $this->generator->getHtmlMenu($markup, $options['start'], $options['depth']);
        }, ['is_safe' => ['html']]));

        $twig->addFunction(new TwigFunction('toc_ordered', function ($markup, $start = null, $depth = null) {
            $options = $this->getTocOptions(null, $start, $depth);
            return $this->generator->getHtmlMenu($markup, $options['start'], $options['depth'], null, true);
        }, ['is_safe' => ['html']]));

        $twig->addFunction(new TwigFunction('toc_items', function ($markup, $start = null, $depth = null) {
            $options = $this->getTocOptions(null, $start, $depth);
            return $this->generator->getMenu($markup, $options['start'], $options['depth']);
        }));

        $twig->addFunction(new TwigFunction('add_anchors', function ($markup, $start = null, $depth = null) {
            $options = $this->getAnchorOptions(null, $start, $depth);
            return $this->fixer->fix($markup, $options);
        }, ['is_safe' => ['html']]));

        $functions_registered = true;
    }

    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * Extend page blueprints with TOC options.
     *
     * @param Event $event
     */
    public function onBlueprintCreated(Event $event)
    {
        static $inEvent = false;

        /** @var Data\Blueprint $blueprint */
        $blueprint = $event['blueprint'];
        $form = $blueprint->form();

        $advanced_tab_exists = isset($form['fields']['tabs']['fields']['advanced']);

        if (!$inEvent && $advanced_tab_exists) {
            $inEvent = true;
            $blueprints = new Data\Blueprints(__DIR__ . '/blueprints/');
            $extends = $blueprints->get('page-toc');
            $blueprint->extend($extends, true);
            $inEvent = false;
        }
    }

    protected function getTocOptions(PageInterface $page = null, $start = null, $depth = null): array
    {
        $page = $page ?? $this->grav['page'];
        return [
            'start'     => $start ?? $this->upstreamConfigVar('start', $page,1),
            'depth'     => $depth ?? $this->upstreamConfigVar('depth', $page,6),
        ];
    }

    protected function getAnchorOptions(PageInterface $page = null, $start = null, $depth = null): array{
        $page = $page ?? $this->grav['page'];
        return [
            'hclass'    => $this->upstreamConfigVar('hclass', $page,null),
            'start'     => $start ?? $this->upstreamConfigVar('anchors.start', $page,1),
            'depth'     => $depth ?? $this->upstreamConfigVar('anchors.depth', $page,6),
            'link'      => $this->upstreamConfigVar('anchors.link', $page,true),
            'position'  => $this->upstreamConfigVar('anchors.position', $page,'before'),
            'aria'      => $this->upstreamConfigVar('anchors.aria', $page,'Anchor'),
            'icon'      => $this->upstreamConfigVar('anchors.icon', $page,'#'),
            'class'     => $this->upstreamConfigVar('anchors.class', $page,null),
            'maxlen'    => $this->upstreamConfigVar('anchors.slug_maxlen', $page,null),
            'prefix'    => $this->upstreamConfigVar('anchors.slug_prefix', $page,null),
        ];
    }

    protected function upstreamConfigVar($var, $page = null, $default = null)
    {
        $page = $page ?? $this->grav['page'] ?? null;

        // Try to find var in the page headers
        if ($page instanceof PageInterface && $page->exists()) {
            // Loop over pages and look for header vars
            while ($page && !$page->root()) {
                $header = new \Grav\Common\Data\Data((array)$page->header());
                $value = $header->get("page-toc.".$var);
                if (isset($value)) {
                    return $value;
                }
                $page = $page->parent();
            }
        }

        $prefix = "plugins.{$this->name}." ;

        return $this->grav['config']->get($prefix . $var, $default);
    }
}
