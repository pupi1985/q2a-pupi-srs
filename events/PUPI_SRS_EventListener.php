<?php

class PUPI_SRS_EventListener
{
    /** @var string */
    private $directory;

    public function load_module($directory, $urlToRoot)
    {
        $this->directory = $directory;
    }

    public function process_event($event, $userId, $handle, $cookieId, $params)
    {
        if ($event !== 'u_confirmed' && $event !== 'u_register') {
            return;
        }

        require_once $this->directory . 'Services/PUPI_SRS_ServiceManager.php';
        require_once $this->directory . 'Services/PUPI_SRS_EmailValidatorManager.php';

        $email = $params['email'];
        $services = PUPI_SRS_ServiceManager::getAllEmailValidators($this->directory);

        $standarizationResults = $event === 'u_register'
            ? PUPI_SRS_EmailValidatorManager::getStandarizationResultsCache()
            : (new PUPI_SRS_EmailValidatorManager($this->directory))->getStandarizationResults($email, $services);

        if (is_null($standarizationResults['standarizedByService'])) {
            return;
        }

        require_once $this->directory . 'Models/PUPI_SRS_StandarizedEmailsModel.php';

        (new PUPI_SRS_StandarizedEmailsModel())->insertUpdateEmailInDatabase($standarizationResults['email'], $email);
    }
}
