<?php
// @group installed
$I = new UsersAcceptanceTester($scenario);
$I->wantTo('Test Users login page');
$I->amOnPage('/admin/login');
$I->see('#CVEPDB CMS');
