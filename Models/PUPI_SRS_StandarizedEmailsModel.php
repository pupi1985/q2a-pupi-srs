<?php

class PUPI_SRS_StandarizedEmailsModel
{
    public function getStandarizedEmailRecordFromDatabase(string $email)
    {
        $sql =
            'SELECT `email`, `multiple_attempts` FROM `^pupi_srs_standarized_emails` ' .
            'WHERE `email` = $';

        $result = qa_db_read_one_assoc(qa_db_query_sub($sql, $email), true);

        if (isset($result)) {
            $result['multiple_attempts'] = (bool)$result['multiple_attempts'];
        }

        return $result;
    }

    public function insertUpdateEmailInDatabase(string $email)
    {
        $sql =
            'INSERT INTO `^pupi_srs_standarized_emails`(`email`, `multiple_attempts`) ' .
            'VALUES(#, #) ' .
            'ON DUPLICATE KEY UPDATE `multiple_attempts` = 1';

        qa_db_query_sub($sql, $email, 0);
    }
}
