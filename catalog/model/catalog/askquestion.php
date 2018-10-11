<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <yangyw@opencart.cn>
 * @created          2016-11-10 11:12:00
 * @modified         2016-11-10 11:12:00
 */

class ModelCatalogAskquestion extends Model
{
    public function addQuestionAnswer($questionProductId, $questionerName, $questionerAnswer, $questionStatus)
    {
        $query = $this->db->query('INSERT INTO '.DB_PREFIX."product_questions SET product_id = '".$questionProductId."',user_name = '".$questionerName."', product_question = '".$questionerAnswer."', product_status = '".$questionStatus."', question_asked_date = NOW()");
    }
}
