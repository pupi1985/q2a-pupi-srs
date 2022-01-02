<?php

class PUPI_SRS_Gmail extends PUPI_SRS_AbstractDuplicateEmailValidator
{
    public function __construct()
    {
        $this->name = 'Gmail';
    }

    public function canProcessEmail(string $email, string $user, string $domain): string
    {
        return $domain === 'gmail.com';
    }

    public function getStandarizedEmail(string $email, string $user, string $domain): string
    {
        $user = str_replace('.', '', $user);

        return $user . '@' . $domain;
    }
}
