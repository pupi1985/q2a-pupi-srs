<?php

class PUPI_SRS_RegistrationFilter
{

    /** @var string */
    private $directory;

    /** @var string */
    private $monthsHistory = [];

    public function load_module($directory, $urlToRoot)
    {
        $this->directory = $directory;
    }

    public function filter_email(&$email, $olduser)
    {
        if (isset($olduser)) {
            return null;
        }

        if (!empty($errors)) {
            return null;
        }

        $isSpamUser = $this->checkServices($email, qa_remote_ip_address());

        return $isSpamUser ? qa_lang_html('users/email_invalid') : null;
    }

    private function checkServices(string $email, string $ipAddress): bool
    {
        require_once 'Services/PUPI_SRS_ServiceManager.php';

        $services = PUPI_SRS_ServiceManager::getAllServices($this->directory);
        $newStats = PUPI_SRS_ServiceManager::createStatsSettingsForSevices($services);

        PUPI_SRS_ServiceManager::migrateOldStatsToNewStats($services, $newStats);

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

        PUPI_SRS_ServiceManager::saveStats($newStats);

        return $isSpamUser;
    }
}
