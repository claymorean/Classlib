<?php
/**
 * Created by PhpStorm.
 * User: senuer
 * Date: 2019/4/24
 * Time: 10:23
 */

namespace App\Service\Excel;

use Maatwebsite\Excel\Facades\Excel;

/**
 * 需要composer依赖:"maatwebsite/excel": "^2.1.30" （版本严格要求 新版已弃用旧版方法
 * Class ExcelService
 *
 * @package App\Service
 */
class ExcelService {
    public $excel;
    public $option;

    public function __construct() {
        $this->excel = \App::make('excel');
//        $this->option = [
//            'title' => 'title',
//            'creator' => 'admin',
//            'description' => 'description',
//            'col' => [],//name=>'名称'
//            'ext_data' => []
//        ];
    }

    public function handlePage($request) {
        if (isset($request->page) && $request->page)
            if (strpos($request->page, '[') !== false)
                return explode(',', str_replace(']', '', str_replace('[', '', $request->page)));
        return [];
    }

    /**
     * @param $option
     * @param $data
     * 通过key运算关联关系输出
     *
     * @return mixed
     */
    private function handleData($option, $data) {
        foreach ($option[ 'col' ] as $key => $value) {
            $tempValue = $data;
            //判断有没有需要运算的部分 只支持*
            $cal = strstr($key, '*');
            //关联->->  方法调用 时间 区分判断  运算未做 待处理
            foreach (explode('->', $key) as $tempKey) {
                if (strpos($tempKey, '(') !== false) {
                    $leftPosition = strpos($tempKey, '(');
                    $rightPosition = strpos($tempKey, ')');
                    if ($rightPosition == $leftPosition + 1) {
                        $tempValue = $tempValue->{substr($tempKey, 0, -2)}();
                    } else {
                        $tempValue = $tempValue->{substr($tempKey, 0, $leftPosition - strlen($tempKey))}($tempValue->{substr($tempKey, $leftPosition + 1, $rightPosition - $leftPosition - 1)});
                    }
                }
//              elseif (strpos($tempKey, 'time') !== false)
//                    $tempValue = $tempValue->{$tempKey} && $tempValue->{$tempKey} > 0 ? date('Y-m-d', $tempValue->{$tempKey}) : '-';
                elseif (strpos($tempKey, '~') !== false) { //只有收支统计明细使用 粗糙方法
                    $result = '';
                    foreach (explode('~', $tempKey) as $func) {
                        $result .= $tempValue->{$func}();
                    }
                    $tempValue = $result;
                } elseif ($cal)
                    $tempValue = $tempValue->{strstr($key, '*', true)};
                else
                    $tempValue = $tempValue->{$tempKey};
                if (!$tempValue)
                    break;
            }
            $tempRow[ $value ] = $cal ? $tempValue * substr($cal, 1, (strlen($cal) - 1)) : ($tempValue.' ');
        }
        return $tempRow;
    }

    /**
     * @param $option
     */
    public function export($option, $data) {
        $this->excel->create($option[ 'title' ], function ($excel) use ($option, $data) {

            $excel->setTitle($option[ 'title' ]);

            $excel->setCreator($option[ 'creator' ]);

            $excel->setDescription($option[ 'description' ]);

            $excel->sheet('Sheet1', function ($sheet) use ($option, $data) {
                $sheet->setAutoSize(true);

                $sheet->appendRow($option[ 'col' ]);
                //connection对象就用chunk 否则直接循环
                if (is_array($data)) {
                    foreach ($data as $datum) {
                        foreach ($option[ 'col' ] as $key => $value)
                            $tempRow[ $value ] = $datum[ $key ].' ';
                        $sheet->appendRow($tempRow);
                    }
                } else if ($option[ 'method' ] == 'get') {
                    foreach ($data->get() as $datum)
                        $sheet->appendRow($this->handleData($option, $datum));
                } else
////                        $data->chunkById(200, function ($allData) use ($option, $sheet) {
                    $data->chunk(500, function ($allData) use ($option, $sheet) {
                        foreach ($allData as $data)
                            $sheet->appendRow($this->handleData($option, $data));
                        unset($allData);
                    });
                //take分页通过option加参method解决
//                foreach ($data as $datum)
//                    $sheet->appendRow($this->handleData($option, $datum));
            });
        })->download('xls');
    }

    /**
     * @param $request
     *
     * @return error-string data-
     * foreach ($data[ 0 ] as $value) {
     * if (!isset($value->sn)) {
     * return $this->setImportError(['导入文件错误']);
     * }
     * if (strlen($value->sn) > 20) {
     * $longs[] = $value->sn;
     * } else {
     * $sns[] = $value->sn;
     * }}
     */
    public function loadExcel($request) {
        $sheet = $request->sheet;
        if ($sheet) {
            if ($sheet->isValid()) {
                if ($sheet->getClientOriginalExtension() == 'xlsx' || $sheet->getClientOriginalExtension() == 'xls') {
                    if ($sheet->getClientSize() > (1024 * 1024 * 5))
                        return '上传文件超过5M';
//                    $sheet->setInputEncoding('UTF-8');
//                    $sheet->noHeading(); //这一句
                    $sheetEncoding = $this->detectEncoding($sheet);
                    if ($sheetEncoding == 'ISO-8859-1' || $sheetEncoding == 'UTF-8')
                        return $this->excel->load($sheet, function ($reader) {
                        })->all();
                    return [];
//                    if (file_exists("upload/excel/".date('YmdHis').$sheet->getClientOriginalName()))
//                        return '上传文件已存在';
//
//                    $sheetName = date('YmdHis').$sheet->getClientOriginalName();
//                    $save = $request->sheet->storeAs('upload/excel', $sheetName, 'upload');
//                    if ($save) {
//                        $sheetPath = "public/upload/excel/".$sheetName;
//                        $data = $this->excel->load($sheetPath, function ($reader) {
//                        })->all();
//                        return $data;
//                    } else return '文件保存失败';
                } else return '上传文件类型错误';
            } else return '上传文件无效';
        } else return '没有上传文件';
    }

    private function fileToSrting($file_path, $filesize = '') {
        //判断文件路径中是否含有中文，如果有，那就对路径进行转码，如此才能识别
        if (preg_match("/[\x7f-\xff]/", $file_path)) {
            $file_path = iconv('UTF-8', 'GBK', $file_path);
        }
        if (file_exists($file_path)) {
            $fp = fopen($file_path, "r");
            if ($filesize === '') {
                $filesize = filesize($file_path);
            }
            $str = fread($fp, $filesize); //指定读取大小，这里默认把整个文件内容读取出来
            return $str = str_replace("\r\n", "<br />", $str);
        } else {
            die('文件路径错误！');
        }
    }

    public function detectEncoding($file_path, $filesize = '1000') {
        $list = array('GBK', 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1');
        $str = $this->fileToSrting($file_path, $filesize);
        foreach ($list as $item) {
            $tmp = mb_convert_encoding($str, $item, $item);
            if (md5($tmp) == md5($str)) {
                return $item;
            }
        }
        return '遇到识别不出来的编码！';
    }

}