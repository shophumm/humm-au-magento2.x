<?php
/**
 *
 */

namespace Humm\HummPaymentGateway\Cron;

use Humm\HummPaymentGateway\Helper\HummLogger;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Humm\HummPaymentGateway\Gateway\Config;
use Magento\Sales\Model\Order;
use Magento\Framework\Event\ManagerInterface;

/**
 * Class UpdateHummOrder
 * @package Humm\HummPaymentGateway\Cron
 * @author Roger.bi@flexigroup.com.au
 */
class UpdateHummOrder
{
    const paymentMethod = 'humm';
    const statuses = ['pending'];
    /**
     * @var HummLogger
     */
    protected $_hummlogger;
    /**
     * @var CollectionFactory
     */
    protected $_orderCollectionFactory;
    /**
     * @var
     */
    protected $_orderManager;
    /**
     * @var
     */
    protected $_collection;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timeZone;

    /**
     * @var Config\Config
     */
    protected $_hummConfig;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var
     */
    static protected $_currentTime;

    /**
     * UpdateHummOrder constructor.
     * @param HummLogger $hummLogger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param CollectionFactory $orderCollectionFactory
     * @param Config\Config $config
     */
    public function __construct(
        \Humm\HummPaymentGateway\Helper\HummLogger $hummLogger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Humm\HummPaymentGateway\Gateway\Config\Config $config
    )
    {
        $this->_hummlogger = $hummLogger;
        $this->_timeZone = $timezone;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_hummConfig = $config;
        $this->_eventManager = $eventManager;
    }

    /**
     * @return $this
     * @throws \Exception
     */

    public function execute()
    {
        $yesNo = $this->_hummConfig->getConfigdata('humm_conf/pending_order');
        if (!intval($yesNo)) {
            $this->_hummlogger->log("Clean Pend Order in Crontab Disable");
            return $this;
        }
        $daysSkip = intval($this->_hummConfig->getConfigdata('humm_conf/pending_orders_timeout'));

        $dataNow = new \DateTime(null, new \DateTimeZone('Australia/Sydney'));
        $dateCheck = new \DateTime(null, new \DateTimeZone('GMT'));
        $from = $dateCheck->sub(new \DateInterval('P' . $daysSkip . 'D'))->format('Y-m-d H:i:s');

        $to = self::getGMTTime();
        $this->_hummlogger->log(sprintf("Start Crontab now [%s]...from [%s] to [%s] Status Enable [..%s..]", $dataNow->format('Y-m-d H:i:s'), $from, $to, $yesNo));
        $this->_hummlogger->log(sprintf("start from %s to %s", $from, $to));
        $_collection = $this->getOrderCollectionPaymentMethod(self::paymentMethod, $from, $to);
        $this->processCollection($_collection);
        return $this;
    }

    /**
     * @param null $paymentMethod
     * @param $from
     * @param $to
     * @return $this
     */
    public function getOrderCollectionPaymentMethod($paymentMethod = null, $from, $to)
    {
        $collection = $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('created_at',
                ['gteq' => $from]

            )
            ->addFieldToFilter('created_at',
                ['lteq' => $to]
            )
            ->addFieldToFilter('status', ['in' => self::statuses]
            );

        $collection->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                array('method', 'amount_paid', 'amount_ordered')
            )
            ->where('sop.method like "%humm%" and sop.amount_paid is NULL');

        $collection->setOrder(
            'created_at',
            'desc'
        );
        $this->_hummlogger->log(sprintf("Cron Functions Query %s", $collection->getSelect()->__toString()), true);
        return $collection;

    }

    /**
     * @param $collection
     */

    public function processCollection($collection)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        foreach ($collection as $key => $item) {
            $this->_hummlogger->log(sprintf("Cron: OrderID %s, State %s, Status %s EntityId %s Created_at %s", $item->getData('increment_id'), $item->getData('state'), $item->getData('status'), $item->getData('entity_id'), $item->getData('created_at')), true);

            $hummOrderId = $item->getData('increment_id');
            $this->processHummOrder($hummOrderId, $objectManager);
        }

    }

    /**
     * @param $hummOrderId
     * @param $objectManager
     */

    public function processHummOrder($hummOrderId, $objectManager)

    {
        $hummOrder = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($hummOrderId);


        if ($hummOrder->getAppliedRuleIds()) {
            $this->_hummlogger->log(sprintf("Begin Cron Coupon functions [OrderId:%s] Coupon [Ids:%s]", $hummOrderId, json_encode($hummOrder->getAppliedRuleIds())));
            $objectManager->create('\Humm\HummPaymentGateway\Model\Observer\CouponCode')->ProcessCoupon($hummOrder);
        }

        if ($hummOrder->getId() && $hummOrder->getState() != Order::STATE_CANCELED) {
            $hummPayment = $hummOrder->getPayment();
            $AdditionalInformation = $hummPayment->getAdditionalInformation();
            $AdditionalInformationNew = array_merge($AdditionalInformation, [$hummOrderId => sprintf("Update Humm Pending OrderId %s to Cancelled ", $hummOrderId)]);
            $this->_hummlogger->log(sprintf("Additional %s ", json_encode($AdditionalInformationNew)));
            $hummPayment->setAdditionalInformation($AdditionalInformationNew);
            $hummOrder->registerCancellation('Cancelled by Humm Corntab due to long time pending')->save();
        }
    }

    /**
     * @param array $statuses
     * @return mixed
     */

    public function getOrderCollectionByStatus($statuses = [])
    {
        $collection = $this->_orderCollectionFactory()->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('status',
                ['in' => $statuses]
            );
        return $collection;

    }

    /**
     *
     */

    public function getCurrentTime()
    {
        $time = $this->_timeZone->scopeTimeStamp();
        $span = rand(21, 53);
        $dateNow = (new \DateTime())->setTimestamp($time);
        $toDataNow = $dateNow->sub(new \DateInterval('PT' . $span . 'M'))->format('Y-m-d H:i:s');
        $this->_hummlogger->log(sprintf("UpdateTotime: %s   span time  %s", $toDataNow, $span), true);
        return $toDataNow;
    }


    /**
     * @return string
     * @throws \Exception
     */

    public function getGMTTime()
    {

        $dateCheck = new \DateTime(null, new \DateTimeZone('GMT'));

        $span = rand(21, 53);

        $toDataNow = $dateCheck->sub(new \DateInterval('PT' . $span . 'M'))->format('Y-m-d H:i:s');

        $this->_hummlogger->log(sprintf("UpdateTotime: %s   span time  %s", $toDataNow, $span), true);

        return $toDataNow;
    }
}

