<?php

namespace com\tsphpbots\bots;
use com\tsphpbots\bots\TestBot;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-08-06 at 07:49:38.
 */
class BotManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BotManager
     */
    protected $botManager;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->botManager = new BotManager();
        $res = $this->botManager->initialize();
        $this->assertTrue($res === true, "Could not initialize the bot manager!");        
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        $this->botManager->shutdown();
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::registerBotClass
     */
    public function testRegisterBotClass() {
        $BOT_CLASS = "com/tsphpbots/bots/TestBot";
        $res = $this->botManager->registerBotClass($BOT_CLASS);
        $this->assertTrue($res === true, "Could not register a bot class!");
        // try to register the same class for a second time, this must fail
        $res2 = $this->botManager->registerBotClass($BOT_CLASS);
        $this->assertTrue($res2 === false, "Undetected invalid registration!");        
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::addBot
     */
    public function testAddBot() {
        $bot = new TestBot();
        $res = $this->botManager->addBot($bot);
        $this->assertTrue($res === true, "Could not add a bot!");
        // try to add the same bot for a second time, this must fail
        $res2 = $this->botManager->addBot($bot);
        $this->assertTrue($res2 === false, "Undetected invalid bot adding!");
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::removeBot
     */
    public function testRemoveBot() {
        $bot = new TestBot();
        $res = $this->botManager->removeBot($bot);
        $this->assertTrue($res === false, "Invalid bot removal!");
        $res2 = $this->botManager->addBot($bot);
        $this->assertTrue($res2 === true, "Could not add a bot!");
        $res3 = $this->botManager->removeBot($bot);
        $this->assertTrue($res3 === true, "Could not remove bot!");
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::findBot
     */
    public function testFindBot() {
        $bot = new TestBot();
        $bot->model->id = 42;
        $res = $this->botManager->addBot($bot);
        $this->assertTrue($res === true, "Could not add a bot!");
        
        $res2 = $this->botManager->findBot($bot->getType(), $bot->getID());
        $this->assertTrue(!is_null($res2) && ($res2->getID() === 42), "Could not find a given bot!");

        $res3 = $this->botManager->findBot($bot->getType(), 43);
        $this->assertTrue(is_null($res3), "Invalid bot find!");
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::findBotClass
     */
    public function testFindBotClass() {
        $BOT_CLASS = "com/tsphpbots/bots/TestBot";
        $res = $this->botManager->registerBotClass($BOT_CLASS);
        $this->assertTrue($res === true, "Could not register a bot class!");
        $bot = new TestBot();
        $res2 = $this->botManager->findBotClass($bot->getType());
        $this->assertTrue(!is_null($res2), "Could not find bot class!");        
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::update
     * @todo   Implement testUpdate().
     */
    public function testUpdate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::loadBots
     * @todo   Implement testLoadBots().
     */
    public function testLoadBots()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::createBot
     * @todo   Implement testCreateBot().
     */
    public function testCreateBot()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::notifyServerEvent
     * @todo   Implement testNotifyServerEvent().
     */
    public function testNotifyServerEvent()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::notifyBotUpdate
     * @todo   Implement testNotifyBotUpdate().
     */
    public function testNotifyBotUpdate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::notifyBotAdd
     * @todo   Implement testNotifyBotAdd().
     */
    public function testNotifyBotAdd()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers com\tsphpbots\bots\BotManager::notifyBotDelete
     * @todo   Implement testNotifyBotDelete().
     */
    public function testNotifyBotDelete()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
