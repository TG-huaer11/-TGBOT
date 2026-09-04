<?php /*a:4:{s:60:"/www/wwwroot/tiktokv.fit/app/admin/view/dashboard/index.html";i:1716492204;s:59:"/www/wwwroot/tiktokv.fit/app/admin/view/layout/default.html";i:1716492206;s:56:"/www/wwwroot/tiktokv.fit/app/admin/view/common/meta.html";i:1716492208;s:58:"/www/wwwroot/tiktokv.fit/app/admin/view/common/script.html";i:1716492208;}*/ ?>
<!DOCTYPE html>
<html lang="<?php echo htmlentities($config['language']); ?>">
    <head>
        <meta charset="utf-8">
<title><?php echo htmlentities((isset($title) && ($title !== '')?$title:'')); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="renderer" content="webkit">
<meta name="referrer" content="never">
<meta name="referrer" content="no-referrer">
<link rel="shortcut icon" href="/assets/img/favicon.ico"/>
<!-- Loading Bootstrap -->
<link href="/assets/css/backend<?php echo app('request')->env('app_debug')?'':'.min'; ?>.css?v=<?php echo htmlentities(config('site.version')); ?>"
      rel="stylesheet">
<!-- HTML5 shim, for IE6-8 support of HTML5 elements. All other JS at the end of file. -->
<!--[if lt IE 9]>
<script src="/assets/js/html5shiv.js"></script>
<script src="/assets/js/respond.min.js"></script>
<![endif]-->
<script type="text/javascript">
    var require = {
        config: <?php echo json_encode($config); ?>
    };
</script>
    </head>

    <body class="inside-header inside-aside <?php echo defined('IS_DIALOG') && IS_DIALOG ? 'is-dialog' : ''; ?>">
        <div id="main" role="main">
            <div class="tab-content tab-addtabs">
                <div id="content">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <section class="content-header hide">
                                <h1>
                                    <?php echo __('Dashboard'); ?>
                                    <small><?php echo __('Control panel'); ?></small>
                                </h1>
                            </section>
                            <?php if(!IS_DIALOG && !config('fastadmin.multiplenav') && config('fastadmin.breadcrumb')): ?>
                            <!-- RIBBON -->
                            <div id="ribbon">
                                <ol class="breadcrumb pull-left">
                                    <?php if($auth->check('dashboard')): ?>
                                    <li><a href="dashboard" class="addtabsit"><i class="fa fa-dashboard"></i> <?php echo __('Dashboard'); ?></a></li>
                                    <?php endif; ?>
                                </ol>
                                <ol class="breadcrumb pull-right">
                                    <?php foreach($breadcrumb as $vo): ?>
                                    <li><a href="javascript:;" data-url="<?php echo htmlentities($vo['url']); ?>"><?php echo htmlentities($vo['title']); ?></a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                            <!-- END RIBBON -->
                            <?php endif; ?>
                            <div class="content">
                                <style>
    .tab-content>.chart {
        padding: 10px;
    }

    .card {
        background-color: #fff !important;
        color: rgba(0, 0, 0, 0.85) !important;
    }

    .card-head-wrapper {
        display: flex;
        align-items: center;
        min-height: 36px;
        padding: 0 12px;
        font-size: 14px;
    }

    .card-title {
        display: inline-block;
        flex: 1;
        padding: 16px 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        color: rgba(0, 0, 0, 0.85) !important;
        background-color: #fff !important;
        text-align: left !important;
        border-bottom: 1px solid #f0f0f0;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .small-box h3 {
        font-size: 30px;
        margin: 0 0 10px 0;
        padding: 0;
        font-weight: unset !important;
    }

    .justify-between{
        justify-content: space-between;
    }

    .flex {
        display: flex;
    }
</style>
<div class="row">
    <div class="col-lg-2 col-xs-2">
        <div class="small-box bg-aqua card card-bordered">
            <div class="card-head-wrapper">
                <span class="card-title">今日注册</span>
            </div>
            <div class="inner">
                <h3><?php echo htmlentities($todayUser); ?></h3>

                <div class="flex justify-between">
                    <span>昨日注册</span>
                    <span><?php echo htmlentities($yesterdayUser); ?></span>
                </div>
            </div>

        </div>
    </div>

    <div class="col-lg-2 col-xs-2">
        <div class="small-box bg-aqua card card-bordered">
            <div class="card-head-wrapper">
                <span class="card-title">今日充值</span>
            </div>
            <div class="inner">
                <h3><?php echo htmlentities($todayRecharge); ?></h3>

                <div class="flex justify-between">
                    <span>昨日充值</span>
                    <span><?php echo htmlentities($yesterdayRecharge); ?></span>
                </div>
            </div>

        </div>
    </div>

    <div class="col-lg-2 col-xs-2">
        <div class="small-box bg-aqua card card-bordered">
            <div class="card-head-wrapper">
                <span class="card-title">今日取款</span>
            </div>
            <div class="inner">
                <h3><?php echo htmlentities($todayDeposit); ?></h3>

                <div class="flex justify-between">
                    <span>昨日取款</span>
                    <span><?php echo htmlentities($yesterdayDeposit); ?></span>
                </div>
            </div>

        </div>
    </div>

    <div class="col-lg-2 col-xs-2">
        <div class="small-box bg-aqua card card-bordered">
            <div class="card-head-wrapper">
                <span class="card-title">充值人数</span>
            </div>
            <div class="inner">
                <h3><?php echo htmlentities($todayRechargeUser); ?></h3>

                <div class="flex justify-between">
                    <span>昨日人数</span>
                    <span><?php echo htmlentities($yesterdayRechargeUser); ?></span>
                </div>
            </div>

        </div>
    </div>

    <div class="col-lg-2 col-xs-2">
        <div class="small-box bg-aqua card card-bordered">
            <div class="card-head-wrapper">
                <span class="card-title">今日盈利</span>
            </div>
            <div class="inner">
                <h3><?php echo htmlentities($todayProfit); ?></h3>

                <div class="flex justify-between">
                    <span>昨日盈利</span>
                    <span><?php echo htmlentities($yesterdayProfit); ?></span>
                </div>
            </div>

        </div>
    </div>

    <div class="col-lg-2 col-xs-2">
        <div class="small-box bg-aqua card card-bordered">
            <div class="card-head-wrapper">
                <span class="card-title">本月盈利</span>
            </div>
            <div class="inner">
                <h3><?php echo htmlentities($thisMonthProfit); ?></h3>

                <div class="flex justify-between">
                    <span>上月盈利</span>
                    <span><?php echo htmlentities($lastMonthProfit); ?></span>
                </div>
            </div>

        </div>
    </div>

</div>
<div class="panel panel-default panel-intro">
    <div class="panel-heading">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#register" data-toggle="tab"><?php echo __('Register'); ?></a></li>
            <li><a href="#recharge" data-toggle="tab"><?php echo __('Recharge'); ?></a></li>
            <li><a href="#withdraw" data-toggle="tab"><?php echo __('Withdraw'); ?></a></li>
            <li><a href="#profit" data-toggle="tab"><?php echo __('Profit'); ?></a></li>
        </ul>
    </div>
    <div class="panel-body">
        <div id="myTabContent" class="tab-content">
            <div class="tab-pane fade active in" id="register">

                <div class="row">
                    <div class="col-lg-12">
                        <div id="registerEchart" class="btn-refresh" style="height:300px;width:100%;"></div>
                    </div>
                </div>

            </div>

            <div class="tab-pane fade" id="recharge">

                <div class="row">
                    <div class="col-xs-12">
                        <div id="rechargeEchart" class="btn-refresh" style="height:300px;width:100%;"></div>
                    </div>
                </div>

            </div>

            <div class="tab-pane fade" id="withdraw">

                <div class="row">
                    <div class="col-xs-12">
                        <div id="withdrawEchart" class="btn-refresh" style="height:300px;width:100%;"></div>
                    </div>
                </div>

            </div>

            <div class="tab-pane fade" id="profit">

                <div class="row">
                    <div class="col-xs-12">
                        <div id="profitEchart" class="btn-refresh" style="height:300px;width:100%;"></div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="/assets/js/require<?php echo app('request')->env('app_debug')?'':'.min'; ?>.js"
        data-main="/assets/js/require-backend<?php echo app('request')->env('app_debug')?'':'.min'; ?>.js?v=<?php echo htmlentities($site['version']); ?>"></script>
    </body>
</html>