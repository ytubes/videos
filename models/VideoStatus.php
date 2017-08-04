<?php
namespace ytubes\videos\models;

/**
 * Статусы для модели Video
 */
class VideoStatus
{
    const DISABLE = 0;
    const PUBLISH = 10;
    const MODERATE = 20;
    const DELETE = 90;
    /**
     * @var integer
     */
    private $status;
    /**
     * @var integer[]
     */
    private static $validStatuses = [
        self::DISABLE,
        self::PUBLISH,
        self::MODERATE,
        self::DELETE,
    ];
    /**
     * @param integer $status
     */
    public function __construct($status)
    {
        self::ensureIsValidState($status);

        $this->status = $status;
    }
    private static function ensureIsValidState($status)
    {
        if (!in_array($status, self::$validStatuses)) {
            throw new \InvalidArgumentException('Invalid status given');
        }
    }
    /**
     * Return list of status codes and labels

     * @return array
     */
    public static function listStatus()
    {
        return [
            self::DISABLE    => 'Отключено',
            self::PUBLISH    => 'Опубликовано',
            self::MODERATE   => 'На модерации',
            self::DELETE     => 'Удалено',
        ];
    }
    /**
     * Returns label of actual status

     * @param string
     */
    public function label()
    {
        $list = self::listStatus();

        return isset($list[$this->status])
            ? $list[$this->status]
            : $this->status;
    }
    public function __toString()
    {
        return (string) $this->status;
    }
}
