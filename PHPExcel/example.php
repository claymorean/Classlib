<?php
// Test CVS

require_once 'Excel/reader.php';

// ExcelFile($filename, $encoding);
$data = new Spreadsheet_Excel_Reader();


// Set output Encoding.
$data->setOutputEncoding('CP936');

/***
* if you want you can change 'iconv' to mb_convert_encoding:
* $data->setUTFEncoder('mb');
*
**/

/***
* By default rows & cols indeces start with 1
* For change initial index use:
* $data->setRowColOffset(0);
*
**/

//处理xls上传
// if (! empty ( $_FILES ['file'] ['name'] ))
// {
//     $tmp_file = $_FILES ['file'] ['tmp_name'];
//     $file_types = explode ( ".", $_FILES ['file'] ['name'] );
//     $file_type = $file_types [count ( $file_types ) - 1];
//      /*判别是不是.xls文件，判别是不是excel文件*/
//      if (strtolower ( $file_type ) != "xls")              
//     {
//         echo "<script>alert('不是Excel文件，重新上传')</script>";
//      }
//     /*设置上传路径*/
//      $savePath ='./PHPExcel/Excel/';
//     /*以时间来命名上传的文件*/
//      $str = date ( 'Ymdhis' ); 
//      $file_name = $str . "." . $file_type;
//      /*是否上传成功*/
//      if (! copy ( $tmp_file, $savePath . $file_name )) {
//         echo "<script>alert('上传失败!');</script>";
//       }else{}
// }

/***
*  Some function for formatting output.
* $data->setDefaultFormat('%.2f');
* setDefaultFormat - set format for columns with unknown formatting
*
* $data->setColumnFormat(4, '%.3f');
* setColumnFormat - set format for column (apply only to number fields)
*
**/

$data->read('test.xls');

/*


 $data->sheets[0]['numRows'] - count rows
 $data->sheets[0]['numCols'] - count columns
 $data->sheets[0]['cells'][$i][$j] - data from $i-row $j-column

 $data->sheets[0]['cellsInfo'][$i][$j] - extended info about cell
    
    $data->sheets[0]['cellsInfo'][$i][$j]['type'] = "date" | "number" | "unknown"
        if 'type' == "unknown" - use 'raw' value, because  cell contain value with format '0.00';
    $data->sheets[0]['cellsInfo'][$i][$j]['raw'] = value if cell without format 
    $data->sheets[0]['cellsInfo'][$i][$j]['colspan'] 
    $data->sheets[0]['cellsInfo'][$i][$j]['rowspan'] 
*/

error_reporting(E_ALL ^ E_NOTICE);

for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {         //从第1行开始读取数据 
	for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {     //从第1列读取数据 
		echo "\"".$data->sheets[0]['cells'][$i][$j]."\",";
	}
	echo "\n<br>";

}


//print_r($data);
//print_r($data->formatRecords);
?>
