<?php

class IPF_ORM_Validator_Creditcard
{                                                         
    public function validate($value)
    {
        $card_regexes = array(
            "/^4\d{12}(\d\d\d){0,1}$/"      => 'visa',
            "/^5[12345]\d{14}$/"            => 'mastercard',
            "/^3[47]\d{13}$/"               => 'amex',
            "/^6011\d{12}$/"                => 'discover',
            "/^30[012345]\d{11}$/"          => 'diners',
            "/^3[68]\d{12}$/"               => 'diners',
        );

        $cardType = '';
        foreach ($card_regexes as $regex => $type) {
            if (preg_match($regex, $value)) {
                 $cardType = $type;
                 break;
            }
        }
        if (!$cardType)
            return false;

        /* mod 10 checksum algorithm */
        $revcode = strrev($value);
        $checksum = 0;
        for ($i = 0; $i < strlen($revcode); $i++) {
            $currentNum = intval($revcode[$i]);
            if ($i & 1) {               /* Odd position */
                 $currentNum *= 2;
            }
            /* Split digits and add. */
            $checksum += $currentNum % 10;
            if ($currentNum > 9) {
                 $checksum += 1;
            }
        }
        return $checksum % 10 == 0;
    }
}

