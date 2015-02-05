<?php

namespace Bezdomni\IsaacRebirth;

class SaveGame
{
    // Offsets for ranges
    const ACHIEVEMENTS = 33;
    const PROGRESS = 327;
    const ITEMS = 676;
    const MINI_BOSSES = 1035;
    const BOSSES = 1054;
    const CHALLENGES = 1123;

    private $offsets = [
        227 => 'mom_kills',
        231 => 'rocks',
        235 => 'marked_rocks',
        239 => 'poops',
        243 => 'pills',
        247 => 'death_card_uses',
        255 => 'arcades_entered',
        259 => 'deaths',
        263 => 'isaac_kills',
        267 => 'shopkeepers_exploded',
        271 => 'satan_kills',
        275 => 'shell_game_played',
        279 => 'angel_deals',
        283 => 'devil_deals',
        287 => 'blood_donated',
        291 => 'arcades_exploded',
        295 => "lost_unlock",
        299 => 'coins_donated',
        303 => 'eden_tokens',
        307 => 'win_streak',
        311 => 'best_streak',
        315 => 'blue_baby_kills',
        319 => 'lamb_kills',
        591 => 'boss_rush_wins',
        599 => 'loss_streak',
        619 => 'boss_kills_01',
        623 => 'boss_kills_02',
        627 => 'boss_kills_03',
        631 => 'boss_kills_04',
        635 => 'boss_kills_05',
        639 => 'boss_kills_06',
        643 => 'boss_kills_07',
        647 => 'boss_kills_08',
        651 => 'boss_kills_09',
        659 => 'boss_kills_10',
        1163 => 'win_count',
    ];

    private $data;

    private $catalogue;

    public function __construct($data)
    {
        $this->data = $data;
        $this->catalogue = new Catalogue();
    }

    public static function load($path)
    {
        $data = file_get_contents($path);
        if ($data === false) {
            throw new \Exception("Cannot load savegame.");
        }

        return new self($data);
    }

    // -------------------------------------------------------------------------

    public function catalogue()
    {
        return $this->catalogue;
    }

    public function offsets()
    {
        return $this->offsets;
    }

    public function stats()
    {
        $stats = [];
        foreach ($this->offsets as $offset => $name) {
            $stats[$name] = $this->getLong($offset);
        }
        return $stats;
    }

    // -- Get single -----------------------------------------------------------

    public function boss($bossID)
    {
        $offset = self::BOSSES + $bossID - 1;
        return $this->getChar($offset);
    }

    public function item($itemID)
    {
        $offset = self::ITEMS + $itemID - 1;
        return $this->getChar($offset);
    }

    // -- Get range ------------------------------------------------------------

    public function achievements()
    {
        $catalogue = $this->catalogue->achievements();
        return $this->many($catalogue, self::ACHIEVEMENTS);
    }

    public function bosses()
    {
        $catalogue = $this->catalogue->bosses();
        return $this->many($catalogue, self::BOSSES);
    }

    public function challenges()
    {
        $catalogue = $this->catalogue->challenges();
        return $this->many($catalogue, self::CHALLENGES);
    }

    public function items()
    {
        $catalogue = $this->catalogue->items();
        return $this->many($catalogue, self::ITEMS);
    }

    public function miniBosses()
    {
        $catalogue = $this->catalogue->miniBosses();
        return $this->many($catalogue, self::MINI_BOSSES);
    }

    public function progress()
    {
        $characters = $this->catalogue->characters();
        $progresses = $this->catalogue->progresses();

        $offset = self::PROGRESS;
        $return = [];
        foreach ($progresses as $pid => $progress) {
            foreach ($characters as $cid => $character) {
                $value = $this->getLong($offset);
                $return[$cid][$pid] = $value;
                $offset += 4;
            }
        }

        return $return;
    }

    public function many($catalogue, $startOffset)
    {
        foreach ($catalogue as $id => $name) {
            $offset = $startOffset + $id - 1;
            $catalogue[$id] = $this->getChar($offset);
        }

        return $catalogue;
    }

    // -- Helpers --------------------------------------------------------------

    public function get($offset, $length = 1)
    {
        return substr($this->data, $offset, $length);
    }

    public function getChar($offset)
    {
        $str = $this->get($offset, 1);
        $chars = unpack('C', $str);
        return reset($chars);
    }

    public function getLong($offset)
    {
        $str = $this->get($offset, 4);
        $chars = unpack('L', $str);
        return reset($chars);
    }

    public function getShort($offset)
    {
        $str = $this->get($offset, 2);
        $chars = unpack('S', $str);
        return reset($chars);
    }
}
