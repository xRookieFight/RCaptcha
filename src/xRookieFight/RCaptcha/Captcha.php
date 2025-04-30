<?php

namespace xRookieFight\RCaptcha;

use pocketmine\item\Item;
use pocketmine\player\Player;
use xRookieFight\RCaptcha\lib\Text;
use xRookieFight\RCaptcha\lib\TextToImage;

class Captcha
{

    private string $text = "";

    function __construct(public Player $player, public ?Item $oldItem){
        $words = 'abcdefghijklmnopqrstuvwxyz';
        Main::$logger->debug("Generating random text");
        for ($i = 0; $i < 5; $i++) $this->text .= $words[rand(0, strlen($words) - 1)];
        $text = Text::from($this->text)
            ->position(30, 73)
            ->color(0,0,0)
            ->font(20, Main::$instance->getDataFolder() . "font.otf")
            ->rotate(0);
        Main::$logger->debug("Generating and rendering image");
        (new TextToImage(Main::$instance->getDataFolder() . "image.png"))->addTexts($text)->render(Main::$instance->getDataFolder() . "cache/{$this->player->getName()}.png");
    }

    function getText() : string
    {
        return $this->text;
    }

    function getImagePath() : ?string
    {
        if (!self::isExists($this->player->getName())) return null;
        return Main::$instance->getDataFolder() . "cache/{$this->player->getName()}.png";
    }

    static function isExists(string $player) : bool
    {
        if (file_exists(Main::$instance->getDataFolder() . "cache/{$player}.png")) return true;
        else return false;
    }

    static function delete(string $player) : void
    {
        if (!self::isExists($player)) return;
        unlink(Main::$instance->getDataFolder() . "cache/{$player}.png");
    }
}