<?php

namespace superbig\countryredirect\checks;

use Craft;
use superbig\countryredirect\CountryRedirect;
use superbig\countryredirect\models\RedirectRequest;

abstract class BaseCheck
{
    public $handle = self::class;
    public $label  = self::class;
    public $reasonForPreventingRedirect;

    /** @var RedirectRequest $redirectRequest */
    public $redirectRequest;

    public function __construct(RedirectRequest $redirectRequest)
    {
        $this->redirectRequest = $redirectRequest;
    }

    public function setDisableReason()
    {
        $this->reasonForPreventingRedirect = $this;
    }

    public function getRequestService()
    {
        return Craft::$app->getRequest();
    }

    public function isWebRequest()
    {
        return !$this->getRequestService()->getIsConsoleRequest();
    }

    /**
     * @return \superbig\countryredirect\models\Settings
     */
    public function getSettings()
    {
        return CountryRedirect::$plugin->getSettings();
    }
}