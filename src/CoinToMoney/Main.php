<?php

namespace CoinToMoney;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use onebone\economyapi\EconomyAPI;
use onebone\coinapi\CoinAPI;
use jojoe77777\FormAPI\CustomForm;

class Main extends PluginBase {

    private EconomyAPI $economy;
    private CoinAPI $coinAPI;

    protected function onEnable(): void {
        $this->saveDefaultConfig();

        $this->economy = EconomyAPI::getInstance();
        $this->coinAPI = CoinAPI::getInstance();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            return true;
        }

        $this->openConvertForm($sender);
        return true;
    }

    private function openConvertForm(Player $player): void {
        $cfg = $this->getConfig();

        $form = new CustomForm(function (Player $player, ?array $data) use ($cfg) {
            if ($data === null) return;

            $coin = (int) $data[1];
            if ($coin <= 0) {
                $player->sendMessage($cfg->get("messages")["invalid-number"]);
                return;
            }

            $min = $cfg->get("settings")["minimum-convert"];
            if ($coin < $min) {
                $player->sendMessage($cfg->get("messages")["invalid-number"]);
                return;
            }

            $playerCoin = $this->coinAPI->myCoin($player);
            if ($playerCoin < $coin) {
                $player->sendMessage($cfg->get("messages")["not-enough-coin"]);
                return;
            }

            $rate = $cfg->get("rate")["coin-to-money"];
            $money = $coin * $rate;

            // Convert
            $this->coinAPI->reduceCoin($player, $coin);
            $this->economy->addMoney($player, $money);

            $msg = $cfg->get("messages")["success"];
            $msg = str_replace(
                ["{coin}", "{money}"],
                [$coin, number_format($money)],
                $msg
            );

            $player->sendMessage($msg);
        });

        $rate = number_format($cfg->get("rate")["coin-to-money"]);

        $form->setTitle($cfg->get("messages")["title"]);
        $form->addLabel(
            str_replace("{rate}", $rate, $cfg->get("messages")["rate-info"])
        );
        $form->addInput($cfg->get("messages")["input"], "example: 5");

        $player->sendForm($form);
    }
}

