<?php

namespace NewMagentoAssignment\Wallet\Api\Data;

interface TransactionInterface
{
    const ID = 'id';
    const CUSTOMER_ID = 'customer_id';
    const TRANSACTION_TYPE = 'transaction_type';
    const AMOUNT = 'amount';
    const TRANSACTION_STATUS = 'transaction_status';
    const OTP_CODE = 'otp_code';
    const ADMIN_ACTION = 'admin_action';
    const REMARKS = 'remarks';
    const CREATED_AT = 'created_at';

    /**
     * Get transaction ID.
     * @return int
     */
    public function getId();

    /**
     * Get customer ID.
     * @return int
     */
    public function getCustomerId();

    /**
     * Set customer ID.
     */
    public function setCustomerId($customerId);

    /**
     * Get transaction type.
     * @return string
     */
    public function getTransactionType();

    /**
     * Set transaction type.
     */
    public function setTransactionType($transactionType);

    /**
     * Get transaction amount.
     * @return float
     */
    public function getAmount();

    /**
     * Set transaction amount.
     */
    public function setAmount($amount);

    /**
     * Get transaction status.
     * @return string
     */
    public function getTransactionStatus();

    /**
     * Set transaction status.
     */
    public function setTransactionStatus($transactionStatus);

    /**
     * Get OTP code.
     * @return string|null
     */
    public function getOtpCode();

    /**
     * Set OTP code.
     */
    public function setOtpCode($otpCode);

    /**
     * Get admin action.
     * @return string|null
     */
    public function getAdminAction();

    /**
     * Set admin action.
     */
    public function setAdminAction($adminAction);

    /**
     * Get remarks.
     * @return string|null
     */
    public function getRemarks();

    /**
     * Set remarks.
     */
    public function setRemarks($remarks);

    /**
     * Get created at timestamp.
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set created at timestamp.
     */
    public function setCreatedAt($createdAt);
}
