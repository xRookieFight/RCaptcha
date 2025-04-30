<?php

namespace xRookieFight\RCaptcha\listener;

use czechpmdevs\imageonmap\ImageOnMap;
use czechpmdevs\imageonmap\item\FilledMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use xRookieFight\RCaptcha\Captcha;
use xRookieFight\RCaptcha\Main;

class CaptchaListener implements Listener
{

    function onJoin(PlayerJoinEvent $event) : void
    {
        $player = $event->getPlayer();
        switch (Main::$config->getNested("captcha-settings.mode")){
            case "FIRSTJOIN":
                if (!$player->hasPlayedBefore()) {
                    $this->captchaControl($player);
                }
                break;
            case "ALL":
                $this->captchaControl($player);
                break;
        }
    }

    function onTransaction(InventoryTransactionEvent $event) : void
    {
        $player = $event->getTransaction()->getSource();
        if (isset(Main::$captcha[$player->getName()])){
            $event->cancel();
        }
    }

    function onQuit(PlayerQuitEvent $event) : void
    {
        $player = $event->getPlayer();
        if (Captcha::isExists($player->getName())){
            Captcha::delete($player->getName());
        }
        if (isset(Main::$captcha[$player->getName()])){
            if (Main::$captcha[$player->getName()]->oldItem != null) $player->getInventory()->setItem(0, Main::$captcha[$player->getName()]->oldItem);
            unset(Main::$captcha[$player->getName()]);
        }
        if(Main::$config->getNested("captcha-settings.blindness-effect")) $player->getEffects()->remove(VanillaEffects::BLINDNESS());
    }

    function onChat(PlayerChatEvent $event) : void
    {
        $player = $event->getPlayer();
        if (isset(Main::$captcha[$player->getName()])){
            $text = Main::$captcha[$player->getName()]->getText();
            if ($event->getMessage() == $text){
                $player = $event->getPlayer();
                if (Captcha::isExists($player->getName())){
                    Captcha::delete($player->getName());
                }
                if (Main::$captcha[$player->getName()]->oldItem != null) $player->getInventory()->setItem(0, Main::$captcha[$player->getName()]->oldItem);

                if (isset(Main::$captcha[$player->getName()])){
                    unset(Main::$captcha[$player->getName()]);
                }

                if(Main::$config->getNested("captcha-settings.blindness-effect")) $player->getEffects()->remove(VanillaEffects::BLINDNESS());
                $player->sendMessage(Main::$messages->get("success"));
                $event->cancel();
            } else {
                $event->cancel();
                $player->kick(Main::$messages->get("fail"));
            }
        }
    }

    function onOpen(InventoryOpenEvent $event) : void
    {
        $player = $event->getPlayer();
        if (isset(Main::$captcha[$player->getName()])){
            $event->cancel();
        }
    }

    function onDrop(PlayerDropItemEvent $event) : void
    {
        $player = $event->getPlayer();
        if (isset(Main::$captcha[$player->getName()])){
            $player->sendMessage(Main::$messages->get("blocked"));
            $event->cancel();
        }
    }

    function onInteract(PlayerInteractEvent $event) : void
    {
        $player = $event->getPlayer();
        if (isset(Main::$captcha[$player->getName()]) && Main::$config->getNested("captcha-settings.disable-interact")){
            $event->cancel();
        }
    }

    function onBreak(BlockBreakEvent $event) : void
    {
        $player = $event->getPlayer();
        if (isset(Main::$captcha[$player->getName()]) && Main::$config->getNested("captcha-settings.disable-interact")){
            $player->sendMessage(Main::$messages->get("blocked"));
            $event->cancel();
        }
    }

    function onPlace(BlockPlaceEvent $event) : void
    {
        $player = $event->getPlayer();
        if (isset(Main::$captcha[$player->getName()]) && Main::$config->getNested("captcha-settings.disable-interact")){
            $player->sendMessage(Main::$messages->get("blocked"));
            $event->cancel();
        }
    }

    function onExhaust(PlayerExhaustEvent $event): void
    {
        $player = $event->getPlayer();
        if (isset(Main::$captcha[$player->getName()])){
            $event->cancel();
        }
    }

    function onCommand(CommandEvent $event) : void
    {
        $command = $event->getCommand();
        foreach (Main::$config->getNested("captcha-settings.captcha-protected-commands") as $blocked){
            if ($command === $blocked){
                $event->getSender()->sendMessage(Main::$messages->get("blocked"));
                $event->cancel();
            }
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function captchaControl(Player $player): void
    {
        if (Captcha::isExists($player->getName())) {
            Captcha::delete($player->getName());
        }
        if (isset(Main::$captcha[$player->getName()])) {
            unset(Main::$captcha[$player->getName()]);
        }

        if (Main::$config->getNested("captcha-settings.op-override") && $player->getServer()->isOp($player->getName())) return;
        if (Main::$config->getNested("captcha-settings.permission-override") && $player->hasPermission("captcha.override")) return;

        if (Main::$config->getNested("captcha-settings.held-slot") == -1) $offhand = true;
        else $offhand = false;

        if ($offhand) {
            if ($player->getOffHandInventory()->getItem(0)->getTypeId() != -10000) {

                $oldItem = $player->getOffHandInventory()->getItem(0);
            } else $oldItem = null;
        } else {
            if ($player->getInventory()->getItem(0)->getTypeId() != -10000) {

                $oldItem = $player->getInventory()->getItem(0);
            } else $oldItem = null;
        }


        $rt = new Captcha($player, $oldItem);
        Main::$captcha[$player->getName()] = $rt;
        $id = ImageOnMap::getInstance()->getImageFromFile(
            file: $rt->getImagePath(),
            xChunkCount: 1, yChunkCount: 1, xOffset: 0, yOffset: 0
        );
        if(Main::$config->getNested("captcha-settings.blindness-effect")) $player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 200000, 255, false));
        $player->sendMessage(Main::$messages->get("join"));
        $item = (FilledMap::get())->setMapId($id);
        $item->setCustomName(" ");
        if ($offhand) {
            $player->getOffHandInventory()->setItem(0, $item);
        } else {
            $player->getInventory()->setItem(0, $item);
        }
    }

    function onDamage(EntityDamageEvent $event) : void
    {
        $player = $event->getEntity();
        if ($player instanceof Player && isset(Main::$captcha[$player->getName()]) && Main::$config->getNested("captcha-settings.disable-damage")) {
            $player->sendMessage(Main::$messages->get("blocked"));
            $event->cancel();
        }
    }
    
}