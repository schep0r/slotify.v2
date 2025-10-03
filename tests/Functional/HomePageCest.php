<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;

final class HomePageCest
{
    public function _before(FunctionalTester $I): void
    {
        // You can put common setup here if needed
    }

    public function home_page_is_accessible_and_contains_expected_content(FunctionalTester $I): void
    {
        // Go to homepage
        $I->amOnPage('/');

        // Assert successful response
        if (method_exists($I, 'seeResponseCodeIsSuccessful')) {
            // Available in Symfony module (Codeception >= 5)
            $I->seeResponseCodeIsSuccessful();
        } else {
            $I->seeResponseCodeIs(200);
        }

        // Check for key texts present on the page for guests (not logged in)
        $I->see('Welcome to Slotify');
        $I->seeInTitle('Slotify - Premium Casino Experience');

        // Call-to-action buttons for guest users
        $I->see('Start Playing Now');
        $I->see('Sign In');
    }
}
