<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2020/8/12
 * Time: 17:57
 */

/**
 * related message:https://learnku.com/articles/20450
 */

namespace App\Service\Excel;


use App\Service\Service;

/**
 * Class PDFService
 * 需要依赖 "barryvdh/laravel-snappy": "^0.4.7" （版本不严格要求
 * @package App\Service\Excel
 */
class PDFService extends Service {

    /**
     * @param $fileName
     * @param $viewName
     * @param $data
     *
     * @return mixed
     */
    public function export($fileName, $viewName, $data) {
        # 下载
//        $pdf = \PDF::loadView('info.report_pdf', ['report' => $data]);
        $pdf = \PDF::loadView($viewName, $data);
        $pdf->setOption('enable-javascript', true);
        $pdf->setOption('javascript-delay', 5000);
        //控制页面自动缩放
        $pdf->setOption('enable-smart-shrinking', true);
//        $pdf->setOption('disable-smart-shrinking', true);
        $pdf->setOption('no-stop-slow-scripts', true);
        $pdf->setOption('lowquality', false);
        $pdf->setOption('images', true);
//        $pdf->setOption('window-status', 'ready');
//        $pdf->setOption('run-script', 'window.setTimeout(function(){window.status="ready";},15000);');
        return $pdf->download($fileName.'.pdf');

        # 渲染页面
//        $html = '<html><head><meta charset="utf-8"></head><h1>订单id</h1><h2>12346546</h2></html>';
//        $pdf = \PDF::loadHTML($html);
//        return $pdf->inline();
    }
}