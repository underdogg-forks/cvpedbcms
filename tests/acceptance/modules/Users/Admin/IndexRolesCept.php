<?php
// @group installed
$I = new UsersAcceptanceTester($scenario);
$I->wantTo('Test Users roles index');
$I->login();
$I->amOnPage('/admin/roles');
$I->see('#CVEPDB CMS');
