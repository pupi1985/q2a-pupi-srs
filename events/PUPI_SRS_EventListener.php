<?php

class PUPI_SRS_EventListener
{
    private $directory;

    public function load_module($directory, $urlToRoot)
    {
        $this->directory = $directory;
    }

    public function process_event($event, $userId, $handle, $cookieId, $params)
    {
        if ($event !== 'u_confirmed') {
            return;
        }

        $email = strtolower($params['email']);

        require_once $this->directory . 'Services/PUPI_SRS_ServiceManager.php';
        require_once $this->directory . 'Services/PUPI_SRS_EmailValidatorManager.php';
        require_once $this->directory . 'Models/PUPI_SRS_StandarizedEmailsModel.php';

        $services = PUPI_SRS_ServiceManager::getAllEmailValidators($this->directory);

        $standarizationResults = (new PUPI_SRS_EmailValidatorManager($this->directory))->getStandarizationResults($email, $services);

        (new PUPI_SRS_StandarizedEmailsModel())->insertUpdateEmailInDatabase($standarizationResults['email']);
    }
}
