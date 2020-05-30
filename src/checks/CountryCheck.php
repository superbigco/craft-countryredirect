<?php

namespace superbig\countryredirect\checks;

use Jaybizzle\CrawlerDetect\CrawlerDetect;
use superbig\countryredirect\CountryRedirect;
use superbig\countryredirect\models\RedirectRequest;

class CountryCheck extends BaseCheck implements CheckInterface
{
    public function execute(RedirectRequest $redirectRequest)
    {
        /*
        $countryCode   = $this->getCountryCode();
         $countryLocale = $this->getSiteHandle($countryCode);

         if ($url = $this->getRedirectUrl($countryLocale)) {
             // Set redirected flash/query param
             if ($redirectParam = $this->config->redirectedParam) {
                 Craft::$app->getSession()->setFlash($redirectParam, true);
             }

             if ($this->config->enableLogging) {
                 CountryRedirect::$plugin->getLog()->logRedirect($url);
             }

             return Craft::$app->getResponse()->redirect($url);
         }
         */
    }
}