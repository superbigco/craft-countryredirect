<?php

namespace superbig\countryredirect\checks;

use superbig\countryredirect\CountryRedirect;
use superbig\countryredirect\models\IgnoreSegment;
use superbig\countryredirect\models\RedirectRequest;

class IgnoredSegmentCheck extends BaseCheck implements CheckInterface
{
    public function execute(RedirectRequest $redirectRequest)
    {
        $currentUri = $redirectRequest->currentUri;

        if (empty($currentUri) && !$this->getRequestService()->getIsConsoleRequest()) {
            $currentUri = $this->getRequestService()->resolveRequestUri();
        }

        $path           = parse_url($currentUri, PHP_URL_PATH);
        $ignoreSegments = CountryRedirect::$plugin->getSettings()->getIgnoredSegments();

        $matchingSegments = array_filter($ignoreSegments, function(IgnoreSegment $ignoreSegment) use ($path) {
            return $ignoreSegment->match($path);
        });

        if (!empty($matchingSegments)) {
            $redirectRequest->addIgnoredSegments($matchingSegments)->preventRedirect();
        }
    }
}