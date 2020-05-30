<?php

namespace superbig\countryredirect\checks;

use Craft;
use craft\helpers\ArrayHelper;
use superbig\countryredirect\helpers\CountryRedirectHelper;
use superbig\countryredirect\models\Language;
use superbig\countryredirect\models\RedirectRequest;
use Teto\HTTP\AcceptLanguage;

class LanguageCheck extends BaseCheck implements CheckInterface
{
    public function execute(RedirectRequest $redirectRequest)
    {
        $languagesValue = ArrayHelper::getValue($_SERVER, 'HTTP_ACCEPT_LANGUAGE', $redirectRequest->acceptedLanguages);

        // @todo Set accepted languages

        if (!empty($languagesValue)) {
            $languages = AcceptLanguage::getLanguages($languagesValue);
            $result    = array_map(function($quality, $info) {
                $language           = new Language();
                $language->quality  = (int)$quality;
                $language->language = CountryRedirectHelper::normalize(ArrayHelper::getValue($info, '0.language'));
                $language->region   = CountryRedirectHelper::normalize(ArrayHelper::getValue($info, '0.region'));
                $language->script   = CountryRedirectHelper::normalize(ArrayHelper::getValue($info, '0.script'));

                return $language;
            }, array_keys($languages), $languages);

            $redirectRequest->matchedLanguages = ArrayHelper::where($result, 'matchQuality');
        }
    }
}