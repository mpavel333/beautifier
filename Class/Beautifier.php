<?php
/*****
 * 01.05.24
 * @mpavel333
*****/

class Beautifier{

    public static $result_files = [];

    public static function work($code,$fileName) {
      if(file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/Out/'. $fileName,self::post($code))):
        array_push(self::$result_files,$fileName);
      endif;
    }

     public static function post($code) {

        $post_fields = ['data'=>$code,
                        'indentation_style'=>"k&r",
                        'indent_with'=>"spaces",
                        'indentation_size'=>"4",
                        'ArrayNested'=>"ArrayNested",
                        'EqualsAlign'=>"",
                        'Fluent'=>"Fluent",
                        'KeepEmptyLines'=>"KeepEmptyLines",
                        'ListClassFunction'=>"",
                        'Lowercase'=>"Lowercase"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://beautifytools.com/pb.php");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);
        curl_close ($ch); 
        
        return $response;
    }

  }

?>