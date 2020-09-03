<?php

namespace MxcDropship\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_Template_Manager;

class TemplateRegistration implements SubscriberInterface
{
    /** @var string */
    private $pluginDirectory;

    /** @var Enlight_Template_Manager */
    private $templateManager;

    public function __construct($pluginDirectory, Enlight_Template_Manager $templateManager)
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->templateManager = $templateManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
        ];
    }

    public function onPreDispatch(Enlight_Event_EventArgs $args)
    {
        $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
    }
}
