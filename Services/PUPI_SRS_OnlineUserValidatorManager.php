<?php

class PUPI_SRS_OnlineUserValidatorManager
{
    /** @var string */
    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function isSpammer(string $email, string $ipAddress): bool
    {
        require_once $this->directory . 'Services/PUPI_SRS_ServiceManager.php';

        $services = PUPI_SRS_ServiceManager::getAllOnlineUsersValidators($this->directory);
        $newStats = PUPI_SRS_ServiceManager::createStatsSettingsForSevices($services);

        PUPI_SRS_ServiceManager::migrateOldStatsToNewStats($services, $newStats, 'pupi_srs_services_stats');

        shuffle($services);
        $isSpamUser = false;
        foreach ($services as $service) {
            $serviceName = $service->getName();
            try {
                $isSpamUser = $service->isSpamUser($email, $ipAddress);

                PUPI_SRS_ServiceManager::incrementServiceStats($newStats, $serviceName, $isSpamUser);

                if ($isSpamUser) {
                    break;
                }
            } catch (Exception $e) {
                error_log(sprintf('<PUPI_SRS - %s> %s', $serviceName, $e->getMessage()));
            }
        }

        PUPI_SRS_ServiceManager::saveStats('pupi_srs_services_stats', $newStats);

        return $isSpamUser;
    }
}
