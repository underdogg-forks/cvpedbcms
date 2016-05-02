<?php
// @group pages
// @group admin
$I = new FunctionalTester($scenario);
$I->wantTo('Test Pages index');
$I->amLoggedAs(['email' => 'antoine@cvepdb.fr', 'password'=> 'CMK7kodQ']);
$I->amOnPage('/admin/pages');
$I->see('#CVEPDB CMS');