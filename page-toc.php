<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Data;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use TOC\MarkupFixer;

/**
 * Class PageTOCPlugin
 * @package Grav\Plugin
 */
class PageTOCPlugin extends Plugin
{
    protected $start;
    protected $end;
    protected $toc_regex = '#\[TOC\s*\/?\]#i';

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
            'onTwigExtensions'          => ['onTwigExtensions', 0],
            'onTwigTemplatePaths'       => ['onTwigTemplatePaths', 0],
            'onPageContentProcessed'    => ['onPageContentProcessed', 0],
        ]);
    }

    public function onPageContentProcessed(Event $event)
    {
        /** @var PageInterface $page */
        $page = $event['page'];

        $content = $page->getRawContent();
        $shortcode_exists = preg_match($this->toc_regex, $content);

        $config = $this->mergeConfig($page);
        $active = $config->get('active', $config->get('process'));
        $start = $config->get('start', 1);
        $depth = $config->get('depth', 6);

        if ($active || $shortcode_exists ) {
            // set the IDs
            $markup_fixer  = new MarkupFixer();
            $content = $markup_fixer->fix($content, $start, $depth);
            $page->setRawContent($content);

            // replace shortcode if necessary
            if ($shortcode_exists) {
                $toc = $this->grav['twig']->processTemplate('components/page-toc.html.twig', ['page' => $page, 'active' => true]);
                $content = preg_replace($this->toc_regex, $toc, $content);
                $page->setRawContent($content);
            }
        }
    }

    public function onTwigExtensions()
    {
        $this->grav['twig']->twig->addExtension(new \TOC\TocTwigExtension());
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
}
