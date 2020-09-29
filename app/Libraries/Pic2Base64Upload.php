<!--前端页面图片转base64上传-->
<?php
require_once('config.php');
header('Content-type:text/html;charset=utf-8');
$base64_image_content = $_POST['imgBase64'];
$mid = $_POST['mid'] * 1;
if ($_SESSION['user_id'] * 1 > 0) {
    //匹配出图片的格式
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
        $type = $result[2];
        $new_file = "upfiles/poptp/";
        if (!file_exists($new_file)) {
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($new_file, 0700);
        }
        $new_file = $new_file . $_SESSION['user_id'] . time() . ".{$type}";
        if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
            $res['err'] = 1;
            $res['url'] = $new_file;
            mysql_query("INSERT INTO ds_popular (m_id,u_id,img_src,status) VALUES (" . $mid . "," . $_SESSION['user_id'] . ",'" . $new_file . "',0)");
        } else {
            $res['err'] = 2;
        }
    }
} else {
    $res['err'] = 3;
}
echo json_encode($res);
?>
<input type="file" id="file"/>
<script type="text/javascript">

    $(document).on('change', '#file', function () {
        // $('.mask').hide();
        fileUpload(this.files[0]);
    });

    /**
     * 将文件转成base64
     * @param obj
     * @param callback
     * @returns {boolean}
     */
    var fileUpload = function (obj) {
        var file = obj;
        var reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function (e) {
            var image_base64 = e.target.result;
            image_base64 = image_base64.replace(/data:;/, "data:image/jpeg;");

            //console.log(image_base64);
            //alert(image_base64);
            //判断类型是不是图片
            if (!/image/.test(image_base64)) {
                alert("类型错误！请重新选择照片，请确保文件为图像类型");
                $('.mask').hide();
                return false;
            }

            // 调用回调
            $.ajax({
                url: 'bs64up.php',
                type: 'post',
                dataType: 'json',
                data: {imgBase64: image_base64, mid: $("#getmid").val()},
                success: function (json) {
                    console.log(json);
                }
            })
        }
    }
</script>