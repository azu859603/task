<?php
use common\helpers\ImageHelper;

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>排行榜</title>

  <link rel="stylesheet" href="/resources/css/rank.css">
</head>
<body>

<div style="padding:20px;background-color:#F2F2F2;">
    <div class="ques-section clearfix">
        <div class="ques-section-item">
            <div class="ques-section-card">
                <div class="ques-card-head ques-card-bul">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="323px" height="30px" xml:space="preserve">
                            <defs>
                                <pattern id="water" width=".25" height="1.1" patternContentUnits="objectBoundingBox">
                                    <path fill="#fff" d="M0.25,1H0c0,0,0-0.659,0-0.916c0.083-0.303,0.158,0.334,0.25,0C0.25,0.327,0.25,1,0.25,1z"></path>
                                </pattern>
                                <g id="eff">
                                    <rect fill="url(#water)" x="-119.461" y="0" width="1200" height="120" opacity="0.3">
                                        <animate attributeType="xml" attributeName="x" from="-300" to="0" repeatCount="indefinite" dur="10s"></animate>
                                    </rect>
                                    <rect fill="url(#water)" y="5" width="1600" height="125" opacity="0.3" x="-399.447">
                                        <animate attributeType="xml" attributeName="x" from="-400" to="0" repeatCount="indefinite" dur="13s"></animate>
                                    </rect>
                                </g>
                            </defs>
                        <use xlink:href="#eff" opacity="1" style="mix-blend-mode:normal;"></use>
                        </svg>
                </div>
                <div class="ques-card-title ques-card-title-top">积分排行榜(<?= $type_name?>)</div>
                <ul class="ques-card-list">
                    <?php for ($i = 0; $i < count($integral_rank); $i++):?>
                        <li class="<?php if ($i == 0)echo 'ques-card-list-noe';elseif ($i == 1)echo 'ques-card-list-two';elseif ($i == 2) echo 'ques-card-list-three'?>">
                            <div class="ques-list-box clearfix">
                                <div class="ques-list-head">
                                    <div class="ques-list-image">
                                        <img src="<?= ImageHelper::defaultHeaderPortrait($integral_rank[$i]['member']['head_portrait'])?>" alt="">
                                    </div>
                                </div>
                                <div class="ques-list-name">
                                    <div class="ques-list-name-head"><?= $integral_rank[$i]['member']['nickname']?></div>
                                    <div class="ques-list-name-text">积分数: <?= $integral_rank[$i]['integral_sum'];?></div>
                                </div>
                                <span class="ques-list-name-icon item-icon00<?= $i+1?>"><?= $i+1?></span>
                            </div>
                        </li>
                    <?php endfor;?>
                </ul>
            </div>
        </div>
        <div class="ques-section-item">
            <div class="ques-section-card">
                <div class="ques-card-head ques-card-gul">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="323px" height="30px" xml:space="preserve">
                            <defs>
                                <pattern id="water" width=".25" height="1.1" patternContentUnits="objectBoundingBox">
                                    <path fill="#fff" d="M0.25,1H0c0,0,0-0.659,0-0.916c0.083-0.303,0.158,0.334,0.25,0C0.25,0.327,0.25,1,0.25,1z"></path>
                                </pattern>
                                <g id="eff">
                                    <rect fill="url(#water)" x="-119.461" y="0" width="1200" height="120" opacity="0.3">
                                        <animate attributeType="xml" attributeName="x" from="-300" to="0" repeatCount="indefinite" dur="10s"></animate>
                                    </rect>
                                    <rect fill="url(#water)" y="5" width="1600" height="125" opacity="0.3" x="-399.447">
                                        <animate attributeType="xml" attributeName="x" from="-400" to="0" repeatCount="indefinite" dur="13s"></animate>
                                    </rect>
                                </g>
                            </defs>
                        <use xlink:href="#eff" opacity="1" style="mix-blend-mode:normal;"></use>
                        </svg>
                </div>
                <div class="ques-card-title ques-card-title-top">打码排行榜(<?= $type_name?>)</div>
                <ul class="ques-card-list">
                    <?php for ($i = 0; $i < count($account_value); $i++):?>
                        <li class="<?php if ($i == 0)echo 'ques-card-list-noe';elseif ($i == 1)echo 'ques-card-list-two';elseif ($i == 2) echo 'ques-card-list-three'?>">
                            <div class="ques-list-box clearfix">
                                <div class="ques-list-head">
                                    <div class="ques-list-image">
                                        <img src="<?= ImageHelper::defaultHeaderPortrait('')?>" alt="">
                                    </div>
                                </div>
                                <div class="ques-list-name">
                                    <div class="ques-list-name-head"><?= $account_value[$i]['account']?></div>
                                    <div class="ques-list-name-text">打码: <?= $account_value[$i]['value_sum'];?></div>
                                </div>
                                <span class="ques-list-name-icon item-icon00<?= $i+1?>"><?= $i+1?></span>
                            </div>
                        </li>
                    <?php endfor;?>
                </ul>
            </div>
        </div>
        <div class="ques-section-item">
            <div class="ques-section-card">
                <div class="ques-card-head ques-card-org">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="323px" height="30px" xml:space="preserve">
                            <defs>
                                <pattern id="water" width=".25" height="1.1" patternContentUnits="objectBoundingBox">
                                    <path fill="#fff" d="M0.25,1H0c0,0,0-0.659,0-0.916c0.083-0.303,0.158,0.334,0.25,0C0.25,0.327,0.25,1,0.25,1z"></path>
                                </pattern>
                                <g id="eff">
                                    <rect fill="url(#water)" x="-119.461" y="0" width="1200" height="120" opacity="0.3">
                                        <animate attributeType="xml" attributeName="x" from="-300" to="0" repeatCount="indefinite" dur="10s"></animate>
                                    </rect>
                                    <rect fill="url(#water)" y="5" width="1600" height="125" opacity="0.3" x="-399.447">
                                        <animate attributeType="xml" attributeName="x" from="-400" to="0" repeatCount="indefinite" dur="13s"></animate>
                                    </rect>
                                </g>
                            </defs>
                        <use xlink:href="#eff" opacity="1" style="mix-blend-mode:normal;"></use>
                        </svg>
                </div>
                <div class="ques-card-title ques-card-title-top">连续签到排行榜</div>
                <ul class="ques-card-list">
                    <?php for ($i = 0; $i < count($sign_rank); $i++):?>
                        <li class="<?php if ($i == 0)echo 'ques-card-list-noe';elseif ($i == 1)echo 'ques-card-list-two';elseif ($i == 2) echo 'ques-card-list-three'?>">
                            <div class="ques-list-box clearfix">
                                <div class="ques-list-head">
                                    <div class="ques-list-image">
                                        <img src="<?= ImageHelper::defaultHeaderPortrait($sign_rank[$i]['head_portrait'])?>" alt="">
                                    </div>
                                </div>
                                <div class="ques-list-name">
                                    <div class="ques-list-name-head"><?= $sign_rank[$i]['nickname']?></div>
                                    <div class="ques-list-name-text">天数: <?= $sign_rank[$i]['sign_days']?></div>
                                </div>
                                <span class="ques-list-name-icon item-icon00<?= $i+1?>"><?= $i+1?></span>
                            </div>
                        </li>
                    <?php endfor;?>
                </ul>
            </div>
        </div>
    </div>
</div>

</body>
</html>