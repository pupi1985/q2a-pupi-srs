<?php

class PUPI_SRS_Outlook extends PUPI_SRS_AbstractDuplicateEmailValidator
{
    public function __construct()
    {
        $this->name = 'Outlook';
    }

    public function canProcessEmail(string $email, string $user, string $domain): string
    {
        return $domain === 'outlook.com';
    }

    public function getStandarizedEmail(string $email, string $user, string $domain): string
    {
        $user = $this->removeAllAfterPlusSign($user);

        return $user . '@' . $domain;
    }
}
