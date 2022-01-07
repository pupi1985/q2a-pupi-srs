<?php

class PUPI_SRS_EmailValidatorManager
{
    /** @var string */
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Return an array with the keys 'isValid' and 'registeredEmail'. First value determines if the email
     * was found and the second one the first email that attempted a registration or the first email that
     * was confirmed.
     *
     */
    public function getDuplicateRecord(string $email): array
    {
        // If Q2A is going to flag it as duplicate anyways, avoid checking and incrementing counts
        if ($this->isDuplicatedForQ2A($email)) {
            return ['isValid' => false];
        }

        require_once $this->directory . 'Services/PUPI_SRS_ServiceManager.php';

        $services = PUPI_SRS_ServiceManager::getAllEmailValidators($this->directory);

        $standarizationResults = $this->getStandarizationResults($email, $services);

        if (is_null($standarizationResults['standarizedByService'])) {
            return ['isValid' => true];
        }

        require_once $this->directory . 'Models/PUPI_SRS_StandarizedEmailsModel.php';

        $standarizedEmailsModel = new PUPI_SRS_StandarizedEmailsModel();
        $standarizedEmailRecord = $standarizedEmailsModel->getStandarizedEmailRecordFromDatabase($standarizationResults['email']);

        $foundInDatabase = isset($standarizedEmailRecord);

        $standarizedEmailsModel->insertUpdateEmailInDatabase(
            $standarizationResults['email'],
            $foundInDatabase ? $standarizedEmailRecord['registered_email'] : $email
        );

        $this->updateStats($services, $standarizationResults['standarizedByService'], $foundInDatabase);

        return [
            'isValid' => !$foundInDatabase,
            'registeredEmail' => $standarizedEmailRecord['registered_email'] ?? null,
        ];
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
