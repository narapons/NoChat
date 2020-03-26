<?php

namespace NoChat;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\server\CommandEvent;
use pocketmine\utils\Config;

class NoChat extends PluginBase implements Listener
{
    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (!file_exists($this->getDataFolder())) {
            @mkdir($this->getDataFolder(), 0744, true);
        }
        $this->list = new Config($this->getDataFolder() . "list.yml", Config::YAML);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ($command->getName()) {
            case "nochat":
                if (!$sender->isop()) {
                    $sender->sendmessage("§cこのコマンドを実行する権限がありません");
                    break;
                }
                if (!isset($args[0])) {
                    return false;
                }
                switch ($args[0]) {
                    case "add":
                        if(!$sender->isOp()){
                            $sender->sendMessage("§cコマンドを実行する権限がありません");
                            break;
                        }
                        if (!isset($args[1]) or !isset($args[2])) {
                            $sender->sendmessage("[NoChat] §c/nochat add 名前 理由");
                            break;
                        }
                        if ($this->list->exists($args[1])) {
                            $sender->sendmessage("[Nochat] §c{$args[1]}は既にチャットの使用を制限されています");
                            break;
                        }
                        $this->getServer()->broadcastMessage("[NoChat] §e{$sender->getName()}が{$args[1]}のチャットの使用を制限しました");
                        $this->getServer()->broadcastMessage("[NoChat] §e理由 : {$args[2]}");
                        $this->list->set($args[1], $args[2]);
                        $this->list->save();
                        break;
                    case "remove":
                        if (!isset($args[1])) {
                            $sender->sendmessage("[NoChat] §c/nochat remove 名前");
                            break;
                        }
                        if (!$this->list->exists($args[1])) {
                            $sender->sendmessage("[Nohat] §c{$args[1]}はチャットを制限されていません");
                            break;
                        }
                        $this->getServer()->broadcastMessage("[NoChat] §e{$sender->getName()}が{$args[1]}のチャットの使用の制限を解除しました");
                        $this->list->remove($args[1]);
                        $this->list->save();
                        break;
                    case "list":
                        $sender->sendMessage("§aチャット使用制限リスト");
                        foreach ($this->list->getAll() as $key => $value) {
                            $sender->sendMessage("{$key}   理由 : {$this->list->get($key)}");
                        }
                        break;
                    default:
                        return false;
                }
                break;
        }
        return true;
    }

    public function onChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $playerName = $event->getPlayer()->getName();
        if ($this->list->exists($playerName)) {
            $player->sendmessage("§cあなたはチャットの使用を制限されています");
            $event->setCancelled();
        }
    }

    public function onCmd(CommandEvent $event)
    {
        $cmd = explode(" ",$event->getCommand());
        switch ($cmd[0]) {
            case "tell":
            case "w":
            case "msg":
            case "me":
            case "say":
                if ($this->list->exists($event->getSender()->getName())) {
                    $event->getSender()->sendmessage("§cあなたはチャットの使用を制限されています");
                    $event->setCancelled();
                }
                break;
        }
    }

}
