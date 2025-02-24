<?php

namespace NewMagentoAssignment\Wallet\Model;

use Magento\Framework\Model\AbstractModel;
use NewMagentoAssignment\Wallet\Api\Data\TransactionInterface;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet as WalletResource;

class Wallet extends AbstractModel implements TransactionInterface
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(WalletResource::class);
    }

    /**
     * Get ID
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Get customer ID
     * @return int
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set customer ID
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get transaction type
     * @return string
     */
    public function getTransactionType()
    {
        return $this->getData(self::TRANSACTION_TYPE);
    }

    /**
     * Set transaction type
     * @param string $transactionType
     * @return $this
     */
    public function setTransactionType($transactionType)
    {
        return $this->setData(self::TRANSACTION_TYPE, $transactionType);
    }

    /**
     * Get amount
     * @return float
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * Set amount
     * @param float $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * Get transaction status
     * @return string
     */
    public function getTransactionStatus()
    {
        return $this->getData(self::TRANSACTION_STATUS);
    }

    /**
     * Set transaction status
     * @param string $transactionStatus
     * @return $this
     */
    public function setTransactionStatus($transactionStatus)
    {
        return $this->setData(self::TRANSACTION_STATUS, $transactionStatus);
    }

    /**
     * Get OTP code
     * @return string|null
     */
    public function getOtpCode()
    {
        return $this->getData(self::OTP_CODE);
    }

    /**
     * Set OTP code
     * @param string|null $otpCode
     * @return $this
     */
    public function setOtpCode($otpCode)
    {
        return $this->setData(self::OTP_CODE, $otpCode);
    }

    /**
     * Get admin action
     * @return string|null
     */
    public function getAdminAction()
    {
        return $this->getData(self::ADMIN_ACTION);
    }

    /**
     * Set admin action
     * @param string|null $adminAction
     * @return $this
     */
    public function setAdminAction($adminAction)
    {
        return $this->setData(self::ADMIN_ACTION, $adminAction);
    }

    /**
     * Get remarks
     * @return string|null
     */
    public function getRemarks()
    {
        return $this->getData(self::REMARKS);
    }

    /**
     * Set remarks
     * @param string|null $remarks
     * @return $this
     */
    public function setRemarks($remarks)
    {
        return $this->setData(self::REMARKS, $remarks);
    }

    /**
     * Get created at
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created at
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
