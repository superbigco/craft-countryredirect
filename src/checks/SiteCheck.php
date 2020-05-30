<?php

namespace superbig\countryredirect\checks;

use Craft;
use craft\helpers\ArrayHelper;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use superbig\countryredirect\CountryRedirect;
use superbig\countryredirect\helpers\CountryRedirectHelper;
use superbig\countryredirect\models\RedirectRequest;

class SiteCheck extends BaseCheck implements CheckInterface
{
    /** @var RedirectRequest $redirectRequest */
    public $redirectRequest;

    public function execute(RedirectRequest $redirectRequest)
    {
        $this->redirectRequest = $redirectRequest;

        if (empty($redirectRequest->currentSite)) {
            $redirectRequest->currentSite = Craft::$app->getSites()->getCurrentSite();
        }

        $this->getTargetSite();
    }

    public function getTargetSite()
    {
        $map          = $this->getSettings()->getRedirectMap();
        $hasLanguages = !empty($this->redirectRequest->matchedLanguages);
        $hasCountry   = !empty($this->redirectRequest->matchedCountry);
        $hasCity      = !empty($this->redirectRequest->matchedCity);
        $comparisons  = [
            'country'           => 'matchedCountry.country.isoCode',
            'continent'         => 'matchedCountry.continent.code',
            'isInEuropeanUnion' => 'matchedCountry.country.isInEuropeanUnion',
        ];

        // Get all
        $filteredMap = ArrayHelper::firstWhere($map, function($redirect) use ($comparisons) {
            $isMatch = true;

            foreach ($comparisons as $key => $valuePath) {
                $isMatch = $this->compareValue($redirect->{$key}, $valuePath);
                dump($valuePath, ['isMatch' => $isMatch]);
            }

            dump($redirect, $isMatch);

            return $isMatch;
        });

        if (empty($filteredMap)) {
            $defaultRedirect = ArrayHelper::firstWhere($map, 'isDefault');

            dump('Default', $defaultRedirect);
        }

        // Get fallback if empty
        foreach ($map as $redirect) {
            // @todo
        }

        //dump($this->redirectRequest);
        // @todo Get country, city, continent and locale/browser language
    }

    public function compareValue($values, $compareAgainstKey = null, $normalize = true)
    {
        $emptyArray = is_array($values) && empty($values);

        if ($values === null || $emptyArray) {
            //dump('Is empty, skipping');
            return true;
        }

        $compareAgainst = ArrayHelper::getValue($this->redirectRequest, $compareAgainstKey);

        if ($normalize) {
            $compareAgainst = CountryRedirectHelper::normalize($compareAgainst);
        }

        if (is_array($values)) {
            //dump('---- START', $compareAgainstKey, $compareAgainst, $values, in_array($compareAgainst, $values), 'x-----x END');

            return in_array($compareAgainst, $values);
        }

        //dump('---- START', $compareAgainstKey, $compareAgainst, $values, $values === $compareAgainst, 'x----x END');

        return $values === $compareAgainst;
    }
}