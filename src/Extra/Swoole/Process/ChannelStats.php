<?php declare(strict_types=1);
namespace Onion\Extra\Swoole\Process;

class ChannelStats
{
    /**
     * Number of messages in the channel
     *
     * @var int
     */
    private $messagesCount = 0;

    /**
     * The amount of memory in bytes the channel currently occupies
     * @var int
     */
    private $size = 0;

    public function __construct(int $messageCount, int $size)
    {
        $this->messagesCount = $messageCount;
        $this->size = $size;
    }

    /**
     * Returns the number of messages currently in the channel
     */
    public function getMessagesCount(): int
    {
        return $this->messagesCount;
    }

    /**
     * Returns the amount of memory in bytes occupied by the channel
     */
    public function getRawSize(): int
    {
        return $this->size;
    }

    /**
     * Return a human readable representation of the amount of
     * memory occupied by the channel. Rounded to the 3rd dec point
     */
    public function getFormattedSize(): string
    {
        $identifiers = [
            'b',
            'kb',
            'mb',
            'gb',
            'tb'
        ];
        $cycle = 0;
        $unit = $this->size;

        while ($unit > 1024) {
            $unit = (double) $unit / 1024;
            $cycle++;
        }
        $unit = number_format($unit, 3);

        return "{$unit} {$identifiers[$cycle]}";
    }
}
