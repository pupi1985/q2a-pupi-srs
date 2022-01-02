<?php

abstract class PUPI_SRS_AbstractDuplicateEmailValidator extends PUPI_SRS_AbstractValidator
{
    /**
     * Return whether the email can be processed.
     */
    public abstract function canProcessEmail(string $email, string $user, string $domain): string;

    /**
     * Return the email in a standard format.
     */
    public abstract function getStandarizedEmail(string $email, string $user, string $domain): string;
}
