<?php

namespace superbig\countryredirect\checks;

use Jaybizzle\CrawlerDetect\CrawlerDetect;
use superbig\countryredirect\models\RedirectRequest;

class BotCheck extends BaseCheck implements CheckInterface
{
    public function execute(RedirectRequest $redirectRequest)
    {
        $redirectRequest->isCrawler = (new CrawlerDetect(null, $redirectRequest->userAgent))
            ->isCrawler();

        if ($this->getSettings()->ignoreBots && $redirectRequest->isCrawler) {
            $redirectRequest->shouldRedirect = false;
        }
    }
}