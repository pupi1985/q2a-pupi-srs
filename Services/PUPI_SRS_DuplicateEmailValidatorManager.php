<?php

class PUPI_SRS_DuplicateEmailValidatorManager
{
    /** @var string */
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function isDuplicated(string $email): bool
    {
        // If Q2A is going to flag it as duplicate anyways, avoid checking and incrementing counts
        if ($this->isDuplicatedForQ2A($email)) {
            return true;
        }

        require_once $this->directory . 'Services/PUPI_SRS_ServiceManager.php';

        $services = PUPI_SRS_ServiceManager::getAllDuplicateEmailValidators($this->directory);

        $standarizationResults = $this->getStandarizationResults($email, $services);

        if (is_null($standarizationResults['standarizedByService'])) {
            return false;
        }

        require_once $this->directory . 'Models/PUPI_SRS_StandarizedEmailsModel.php';

        $standarizedEmailsModel = new PUPI_SRS_StandarizedEmailsModel();
        $standarizedEmailRecord = $standarizedEmailsModel->getStandarizedEmailRecordFromDatabase($standarizationResults['email']);

        $foundInDatabase = isset($standarizedEmailRecord);

        if ($foundInDatabase && !$standarizedEmailRecord['multiple_attempts']) {
            $standarizedEmailsModel->insertUpdateEmailInDatabase($standarizationResults['email']);
        }

        $this->updateStats($services, $standarizationResults['standarizedByService'], $foundInDatabase);

        return $foundInDatabase;
    }

    private function updateStats(array $services, $serviceName, bool $foundInDatabase)
    {
        $newStats = PUPI_SRS_ServiceManager::createStatsSettingsForSevices($services);

        PUPI_SRS_ServiceManager::migrateOldStatsToNewStats($services, $newStats, 'pupi_srs_emails_stats');

        PUPI_SRS_ServiceManager::incrementServiceStats($newStats, $serviceName, $foundInDatabase);

        PUPI_SRS_ServiceManager::saveStats('pupi_srs_emails_stats', $newStats);
    }

    private function isDuplicatedForQ2A(string $email): bool
    {
        require_once QA_INCLUDE_DIR . 'db/users.php';

        return !empty(qa_db_user_find_by_email($email));
    }

    public function getStandarizationResults(string $email, array $services): array
    {
        $email = strtolower($email);
        $standarizedByService = null;

        foreach ($services as $service) {
            $domainStartPosition = strrpos($email, '@') + 1;
            $domain = substr($email, $domainStartPosition);
            $user = substr($email, 0, $domainStartPosition - 1);

            if ($service->canProcessEmail($email, $user, $domain)) {
                $email = $service->getStandarizedEmail($email, $user, $domain);
                $standarizedByService = $service->getName();

                break;
            }
        }

        return [
            'email' => $email,
            'standarizedByService' => $standarizedByService,
        ];
    }
}
