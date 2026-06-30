<?php
function checkRequestType($position, $formType) {
    if ($position === 'ปริญญาตรี' || $position === 'ปริญญาโท' || $position === 'ปริญญาเอก' && $formType !== 'student') {
        return [
            'can_submit' => false,
            'message' => 'โปรดยื่นทุนในส่วนของนิสิตเท่านั้น'
        ];
    }
    return ['can_submit' => true];
}
