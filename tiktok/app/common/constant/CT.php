<?php

namespace app\common\constant;

class CT
{

    /** 默认值 */
    const DEFAULT_VALUE = 0;

    /** 通用状态 */
    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1;

    /** Date */
    const YEAR = 31536000;
    const MONTH = 2592000;
    const WEEK = 604800;
    const DAY = 86400;
    const HOUR = 3600;
    const MINUTE = 60;

    /**
     * Deal Status
     * 用户交易状态：0=交易冻结，1=停止交易，2=等待交易，3=交易中
     */
    const D_S_FREEZE = 0;
    const D_S_STOP = 1;
    const D_S_WAITING = 2;
    const D_S_TRADING = 3;

    /**
     * Order Status 指派，已获取，冻结，已返佣
     * 订单状态：-1=指派，0=待付款，1=交易完成，2=用户取消，3=强制完成，4=强制取消，5=交易冻结
     */
    const O_S_ASSIGN = -1;
    const O_S_PENDING_PAYMENT = 0;
    const O_S_COMPLETE = 1;
    const O_S_CANCEL = 2;
    const O_S_ENFORCE_COMPLETE = 3;
    const O_S_ENFORCE_CANCEL = 4;
    const O_S_FREEZE = 5;

    /**
     * Commission Status
     * 佣金状态：0=未发放，1=已发放，2=账号冻结
     */
    const C_S_NOT_ISSUED = 0;
    const C_S_ISSUED = 1;
    const C_S_FREEZE = 2;

    /**
     * Balance Log Type
     * 交易类型：0=系统，1=充值，2=交易，3=返佣，4=强制交易，5=推广返佣，6=下级交易返佣，7=提现，8=利息宝
     */
    const B_L_T_SYSTEM = 0;
    const B_L_T_RECHARGE = 1;
    const B_L_T_TRADE = 2;
    const B_L_T_REBATE = 3;
    const B_L_T_ENFORCE_TRADE = 4;
    const B_L_T_REBATE_PROMOTE = 5;
    const B_L_T_REBATE_SUB = 6;
    const B_L_T_WITHDRAW = 7;
    const B_L_T_INTEREST = 8;

    const B_L_T_COMMISSION = 9;

    /**
     * Balance Log Status
     * 状态：收入=1，支出=2
     */
    const B_L_S_INCOME = 1;
    const B_L_S_EXPENSES = 2;

    /**
     * Reward Log Type
     * 类型：1=充值订单(推广返佣)，2=交易订单(交易返佣)
     */
    const R_L_T_RECHARGE = 1;
    const R_L_T_TRADE = 2;

    /**
     * Reward Log Status
     * 佣金发放状态：0=自动发放，1=未发放，2=已发放
     */
    const R_L_S_AUTO = 0;
    const R_L_S_NOT_ISSUED = 1;
    const R_L_S_ISSUED = 2;

    /**
     * Message Log Type
     * 类型：1=公告，2=通知
     */
    const M_L_T_ANNOUNCEMENT = 1;
    const M_L_T_NOTIFY = 2;

    /**
     * Recharge type
     * 支付方式 1=微信，2=支付宝，3=qq，4=后台
     */
    const R_T_WXPAY = 1;
    const R_T_ALIPAY = 2;
    const R_T_QQ = 3;
    const R_T_SYSTEM = 4;

    /**
     * Recharge status
     * 订单状态 1=下单成功，2=充值成功，3=充值失败
     */
    const R_S_ORDER_SUCCESS = 1;
    const R_S_RECHARGE_SUCCESS = 2;
    const R_S_FAIL = 3;

    /**
     * Deposit status
     * 订单状态 1=待处理，2=审核通过，3=审核不通过
     */
    const D_S_Pending = 1;
    const D_S_PASS = 2;
    const D_S_NOT_PASS = 3;

    /**
     * Verify msg type
     * 订单状态 1=注册，2=修改密码，3=绑定手机
     */
    const VM_T_REGISTER = 1;
    const VM_T_CHANGE_PASS = 2;
    const VM_T_BIND_TEL = 3;
}