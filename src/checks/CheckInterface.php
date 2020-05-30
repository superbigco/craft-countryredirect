<?php

namespace superbig\countryredirect\checks;

interface CheckInterface
{
    public function execute(\superbig\countryredirect\models\RedirectRequest $redirectRequest);
}