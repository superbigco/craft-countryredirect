<?php

namespace superbig\countryredirect\checks;

use Craft;
use craft\base\Element;
use craft\helpers\App;
use craft\models\Site;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use superbig\countryredirect\CountryRedirect;
use superbig\countryredirect\models\RedirectRequest;

class ElementCheck extends BaseCheck implements CheckInterface
{
    /** @var RedirectRequest $redirectRequest */
    public $redirectRequest;

    /** @var Element|bool $_matchedElement */
    private $_matchedElement;
    private $_matchedElementRoute;

    public function execute(RedirectRequest $redirectRequest)
    {
        $this->redirectRequest = $redirectRequest;

        $site = $redirectRequest->targetSite;

        // Check if we're on an element's url, then prefer to redirect to that url
        /** @var Element $currentElement */
        $currentElement = $redirectRequest->currentElement = $this->getMatchedElement($redirectRequest->currentUri);

        if (!$site) {
            return true;
        }

        // Get the site URL for the found site, this will be the fallback if we're not on an element's url
        $url = $this->getSettings()->redirectMatchingElementOnly ? null : $site->baseUrl;

        if ($currentElement && !empty($currentElement->getUrl())) {
            $redirectElement = Craft::$app->getElements()->getElementById($currentElement->id, \get_class($currentElement), $site->id);

            if ($redirectElement && isset($redirectElement->url) && $redirectElement->url !== '') {
                $url = $redirectElement->url;
            }
        }

        $redirectRequest->redirectUrl = Craft::parseEnv($url);
    }

    /**
     * @param Site $site
     * @param bool $elementMatchOnly
     *
     * @return bool|string
     */
    public static function getCurrentLinkForSite($site, $elementMatchOnly = false)
    {

    }

    protected function getMatchedElement($url = null)
    {
        if (!Craft::$app->getIsInitialized()) {
            Craft::warning(__METHOD__ . "() was called before the application was fully initialized.\n" .
                "Stack trace:\n" . App::backtrace(), __METHOD__);
        }

        if ($this->_matchedElement !== null) {
            return $this->_matchedElement;
        }

        $request = $this->getRequestService();

        if (!$request->getIsSiteRequest() && empty($url)) {
            return $this->_matchedElement = false;
        }

        $this->getMatchedElementRoute($url ?? $request->getPathInfo());

        return $this->_matchedElement;
    }

    /**
     * Attempts to match a path with an element in the database.
     *
     * @param string $path
     *
     * @return mixed
     * @throws \craft\errors\SiteNotFoundException
     */
    protected function getMatchedElementRoute(string $path)
    {
        if ($this->_matchedElementRoute !== null) {
            return $this->_matchedElementRoute;
        }

        $this->_matchedElement      = false;
        $this->_matchedElementRoute = false;

        if (Craft::$app->getIsInstalled() && $this->getRequestService()->getIsSiteRequest() || !empty($path)) {
            $path = rtrim(ltrim(parse_url($path, PHP_URL_PATH), DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

            /** @var Element $element */
            /** @noinspection PhpUnhandledExceptionInspection */
            $element = Craft::$app->getElements()->getElementByUri($path, Craft::$app->getSites()->getCurrentSite()->id, true);

            if ($element && $element->enabledForSite) {
                $route = $element->getRoute();

                if ($route) {
                    if (is_string($route)) {
                        $route = [$route, []];
                    }

                    $this->_matchedElement      = $element;
                    $this->_matchedElementRoute = $route;
                }
            }
        }

        if (YII_DEBUG) {
            Craft::debug([
                'rule'   => 'Element URI: ' . $path,
                'match'  => isset($element, $route),
                'parent' => null,
            ], __METHOD__);
        }

        return $this->_matchedElementRoute;
    }
}