<?php

abstract class PUPI_SRS_AbstractService
{
    /**
     * Name of the service that helps in identification and logging.
     *
     * @var string
     */
    protected $name;

    /**
     * Return whether the given email and IP address are likely to belong to a SPAM user.
     * If any error is thrown, return an exception with the message to log in the server error log.
     *
     * @throws Exception
     */
    public abstract function isSpamUser(string $email, string $ip): bool;

    /**
     * Return all fields needed to display the admin form. Array keys should be included.
     */
    public abstract function getAdminFormFields(): array;

    /**
     * Executed when saving the admin form.
     */
    public abstract function saveAdminForm();

    public function getName(): string
    {
        return $this->name;
    }
}
