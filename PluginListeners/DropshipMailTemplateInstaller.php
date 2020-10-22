<?php

namespace MxcDropship\PluginListeners;

use MxcCommons\Plugin\Mail\MailManager;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class DropshipMailTemplateInstaller implements AugmentedObject
{
    use ClassConfigAwareTrait;

    protected $mailManager;

    public function __construct(MailManager $mailManager)
    {
        $this->mailManager = $mailManager;
    }

    public function install(InstallContext $context)
    {
        // install our workflow notification mail template
        $this->mailManager->setMailTemplates($this->classConfig);
    }

    public function uninstall(UninstallContext $context)
    {
        // remove our custom mail templates
        $names = array_column($this->classConfig, 'name');
        foreach ($names as $name) {
            $this->mailManager->deleteMailTemplate($name);
        }
    }


}