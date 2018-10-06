<?php
class IntervalConfig
{
    private $sourceItem;
    private $type;
    private $maxValue;
    private $cron;
    private $valueTime;
    private $outdatetTime;
    private $messureTime;
    private $messureItemCount;

    /**
     * ValueType constructor.
     * @param string $sourceItem
     * @param string $type
     * @param string $cron
     * @param integer $maxValue
     * @param mixed $valueTime
     * @param integer $outdatetTime
     * @param integer $messureTime
     * @param integer $messureItemCount
     */
    public function __construct( $sourceItem, $type, $cron, $maxValue, $valueTime = null, $outdatetTime = null, $messureTime = null, $messureItemCount = null )
    {
        $this->sourceItem = $sourceItem;
        $this->type = $type;
        $this->maxValue = $maxValue;
        $this->cron = $cron;
        $this->valueTime = $valueTime;
        $this->outdatetTime = $outdatetTime;
        $this->messureTime = $messureTime;
        $this->messureItemCount = $messureItemCount;
    }

    /**
     * @return string
     */
    public function getCron()
    {
        return $this->cron;
    }

    /**
     * @return string
     */
    public function getSourceItem()
    {
        return $this->sourceItem;
    }

    /**
     * @return integer
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getValueTime()
    {
        return $this->valueTime;
    }

    /**
     * @return integer
     */
    public function getOutdatetTime()
    {
        return $this->outdatetTime;
    }

    /**
     * @return integer
     */
    public function getMessureTime()
    {
        return $this->messureTime;
    }

    /**
     * @return integer
     */
    public function getMessureItemCount()
    {
        return $this->messureItemCount;
    }
}
