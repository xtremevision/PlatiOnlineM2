<?php

namespace Xtreme\PlatiOnline\Model\Config;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Model\CcConfig;
use Magento\Framework\View\Asset\Source;

/**
 * Class ConfigProvider
 * @api
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @var \Magento\Framework\View\Asset\Source
     */
    protected $assetSource;

    /**
     * @param CcConfig $ccConfig
     * @param Source $assetSource
     */
    public function __construct(
        CcConfig $ccConfig,
        Source $assetSource
    ) {
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'platiOnline' => [
                    'logo' => $this->ccConfig->getViewFileUrl('Xtreme_PlatiOnline::images\logo.png')
                ]
            ]
        ];
    }
}
