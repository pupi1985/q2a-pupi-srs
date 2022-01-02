<?php

abstract class PUPI_SRS_AbstractValidator
{
    /**
     * Name of the service that helps in identification and logging.
     *
     * @var string
     */
    protected $name;

    public function getName(): string
    {
        return $this->name;
    }
}
