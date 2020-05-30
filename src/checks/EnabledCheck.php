<?php

namespace superbig\countryredirect\checks;

use Craft;
use craft\helpers\ArrayHelper;
use superbig\countryredirect\models\RedirectRequest;

class EnabledCheck extends BaseCheck implements CheckInterface
{
    public function execute(RedirectRequest $redirectRequest)
    {
        $enabled = $this->getSettings()->enabled;

        // Don't redirect if the plugin is soft disabled
        if ($enabled === false) {
            $redirectRequest->shouldRedirect = false;

            return true;
        }

        // Depending on enabled setting, do some extra checks
        if (!is_bool($enabled) && is_array($enabled)) {
            $user = Craft::$app->getUser()->getIdentity();

            // Should we check logged in users?
            if (isset($enabled['loggedIn'])) {
                if ($user && !$enabled['loggedIn']) {
                    $redirectRequest->shouldRedirect = false;
                }
            }

            // Should we check anon users?
            if (isset($enabled['anonymous'])) {
                if (!$user && !$enabled['anonymous']) {
                    $redirectRequest->shouldRedirect = false;
                }
            }
        }
    }
}