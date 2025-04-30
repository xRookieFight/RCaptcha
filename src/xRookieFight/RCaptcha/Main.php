<?php

namespace xRookieFight\RCaptcha;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use xRookieFight\RCaptcha\listener\CaptchaListener;

class Main extends PluginBase
{

    public static self $instance;
    public static \AttachableLogger $logger;
    public static Config $config, $messages;
    public static array $captcha = [];

    protected function onEnable(): void
    {
        $this->saveDefaultConfig();
        $this->saveResource("messages.yml");
        self::$instance = $this;
        self::$logger = $this->getLogger();
        self::$messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        self::$config = $this->getConfig();
        self::$logger->debug("Starting RCaptcha ".$this->getDescription()->getVersion());
        $this->init();
    }

    protected function onDisable(): void {
        foreach (self::$captcha as $name => $captcha) {
            $captcha->delete($name);
        }
        if (file_exists($this->getDataFolder() . "cache")) rmdir($this->getDataFolder() . "cache");
    }

    function init() : void
    {
        self::$logger->debug("Initiating plugin");
        foreach (self::$captcha as $name => $captcha) {
            $captcha->delete();
        }
        if (!file_exists($this->getDataFolder() . "cache")) mkdir($this->getDataFolder() . "cache");
        $this->getServer()->getPluginManager()->registerEvents(new CaptchaListener(), $this);
    }

}