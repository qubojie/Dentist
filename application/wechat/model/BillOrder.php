<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/24
 * Time: 下午3:58
 */
namespace app\wechat\model;

use think\Model;

class BillOrder extends Model
{
    protected $table = 'dts_bill_order';

    protected $primaryKey = 'oid';

    public $column_list = [
        "oid",
        "sale_status",
        "pay_no",
        "order_amount",
        "payable_amount",
        "deal_amount",
        "created_at",
    ];
    public $column_list_gsh = [
        "bo.oid",
        "bo.sale_status",
        "bo.pay_no",
        "truncate(bo.order_amount,2) order_amount",
        "truncate(bo.payable_amount,2) payable_amount",
        "truncate(bo.deal_amount,2) deal_amount",
        "bo.created_at",
    ];

    public $column_details = [
        "oid",
        "sid",
        "sale_status",
        "deal_time",
        "pay_type",
        "pay_time",
        "pay_no",
        "finish_time",
        "cancel_user",
        "cancel_time",
        "auto_cancel",
        "is_refund",
        "refund_amount",
        "cancel_reason",
        "order_amount",
        "payable_amount",
        "deal_amount",
        "discount",
        "cus_remark",
        "created_at"
    ];

    public $column_details_gsh = [
        "bo.oid",
        "bo.sid",
        "bo.sale_status",
        "bo.deal_time",
        "bo.pay_type",
        "bo.pay_time",
        "bo.pay_no",
        "bo.finish_time",
        "bo.cancel_user",
        "bo.cancel_time",
        "bo.auto_cancel",
        "bo.is_refund",
        "truncate(bo.refund_amount,2) refund_amount",
        "bo.cancel_reason",
        "truncate(bo.order_amount,2) order_amount",
        "truncate(bo.payable_amount,2) payable_amount",
        "truncate(bo.deal_amount,2) deal_amount",
        "truncate(bo.discount,2) discount",
        "bo.cus_remark",
        "bo.created_at"
    ];
}