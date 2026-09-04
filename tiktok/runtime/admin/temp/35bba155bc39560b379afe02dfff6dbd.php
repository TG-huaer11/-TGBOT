<?php /*a:4:{s:69:"/www/wwwroot/tiktokv.fit/app/admin/view/withdrawal_address/index.html";i:1741875058;s:59:"/www/wwwroot/tiktokv.fit/app/admin/view/layout/default.html";i:1716492206;s:56:"/www/wwwroot/tiktokv.fit/app/admin/view/common/meta.html";i:1716492208;s:58:"/www/wwwroot/tiktokv.fit/app/admin/view/common/script.html";i:1716492208;}*/ ?>
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
                                <div class="panel panel-default panel-intro">
    <?php echo build_heading(); ?>


    <div class="panel-body">
        <div id="myTabContent" class="tab-content">
            <div class="tab-pane fade active in" id="one">
                <div class="widget-body no-padding">
                    <div id="toolbar" class="toolbar">
                        <?php echo build_toolbar('refresh,edit,del'); ?>
                    </div>
                    
                                 <style>


        .search-container {
            display: flex;
            align-items: center;
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            width: fit-content;
        }

        .search-container select,
        .search-container input[type="text"],
        .search-container button {
            margin: 0 15px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        .search-container button {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #0056b3;
        }
        .search-form {
    display: none;
}
    </style>
<form id="custom-search-form">
<div class="search-container">
    <?php if($admin['id']==1): ?>
        <label for="search-type">归属:</label>
        <select id="top_id" name="">
            <option value="0">全部</option>
           <?php if(is_array($agent_list) || $agent_list instanceof \think\Collection || $agent_list instanceof \think\Paginator): $i = 0; $__LIST__ = $agent_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($i % 2 );++$i;?>
            <option value="<?php echo htmlentities($item['user_id']); ?>"><?php echo htmlentities($item['username']); ?></option>
              <?php endforeach; endif; else: echo "" ;endif; ?>
        </select>
        <?php endif; ?>
        <label for="member-id">会员 ID:</label>
        <input type="text" id="member_id" placeholder="输入会员 ID">
        <label for="username">用户名:</label>
        <input type="text" id="username" placeholder="输入用户名">
        <label for="identity">身份:</label>
        <select id="jia">
            <option value="all">全部</option>
            <option value="0">真人</option>
            <option value="1">假人</option>
        </select>
        <button type="submit" style="padding: 10px 30px;">搜索</button>
    </div>
</form>
                    
                    <table id="table" class="table table-striped table-bordered table-hover table-nowrap"
                           data-operate-edit="<?php echo $auth->check('withdrawal_address/edit'); ?>"
                           data-operate-del="<?php echo $auth->check('withdrawal_address/del'); ?>"
                           width="100%">
                    </table>
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