<?php
// @group installed
$I = new UsersAcceptanceTester($scenario);
$I->wantTo('Test Users index without filter');
$I->login();
$I->amOnPage('/admin/users');
$I->see('#CVEPDB CMS');
