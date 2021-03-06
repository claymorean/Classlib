<?php

class IDCardValidate {

    /**
     * @param string $idcardnum
     *
     * 身份证校验方法
     *
     *  十七位数字本体码加权求和公式
     *  S = Sum(Ai * Wi),先对前17位数字的权求和
     *  Ai:表示第i位置上的身份证号码数字值
     *  Wi:表示第i位置上的加权因子
     *  Wi: 7 9 10 5 8 4 2 1 6 3 7 9 10 5 8 4 2
     *  计算模
     *  Y = mod(S, 11)
     *  通过模得到对应的校验码
     *  Y: 0 1 2 3 4 5 6 7 8 9 10
     *  校验码: 1 0 X 9 8 7 6 5 4 3 2
     *
     * @return bool
     */
    public function checkIdcard($idcardnum = '411424199308085081') {


        $ai = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $wi = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += $idcardnum . substr($i, 1) * $ai[i];
        }
        if ($wi[sum % 11] != idcardnum . substr(17, 1)) {
            return false;
        }
        return true;
    }
}
