<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class PageTOCPlugin
 * @package Grav\Plugin
 */
class PageTOCPlugin extends Plugin
{
    protected $start;
    protected $end;

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
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        // Autoload classes
        require_once __DIR__ . '/vendor/autoload.php';

        // Enable the main event we are interested in
        $this->enable([
            'onTwigExtensions' => ['onTwigExtensions', 0],
            'onPageContentProcessed' => ['onPageContentProcessed', 0]
        ]);
    }

    public function onPageContentProcessed(Event $event)
    {
        /** @var Page $page */
        $page = $event['page'];

        $config = $this->mergeConfig($page);

        $active = $config->get('active', $config->get('process'));
        $start = $config->get('start', 1);
        $depth = $config->get('depth', 6);

        if ($active) {
            $markup_fixer  = new \TOC\MarkupFixer();
            $page->setRawContent( $markup_fixer->fix($page->getRawContent(), $start, $depth));
        }
    }

    public function onTwigExtensions()
    {
        $this->grav['twig']->twig->addExtension(new \TOC\TocTwigExtension());
    }
}
