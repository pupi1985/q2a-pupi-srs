<?php

class PUPI_SRS_RegistrationFilter
{
    /** @var string */
    private $directory;

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

        $message = $this->getMessageFromEmailValidators($email);

        if (isset($message)) {
            return $message;
        }

        return $this->getMessageFromOnlineUserValidators($email, qa_remote_ip_address());
    }

    private function getMessageFromEmailValidators(string $email)
    {
        require_once $this->directory . 'Services/PUPI_SRS_EmailValidatorManager.php';

        $duplicateRecord = (new PUPI_SRS_EmailValidatorManager($this->directory))->getDuplicateRecord($email);

        if ($duplicateRecord['isValid']) {
            return null;
        } else {
            return isset($duplicateRecord['registeredEmail'])
                ? qa_lang_sub('pupi_srs/email_already_registered', $duplicateRecord['registeredEmail'])
                : qa_lang('users/email_exists');
        }
    }

    private function getMessageFromOnlineUserValidators(string $email, string $ipAddress)
    {
        require_once $this->directory . 'Services/PUPI_SRS_OnlineUserValidatorManager.php';

        $isSpamUser = (new PUPI_SRS_OnlineUserValidatorManager($this->directory))->isSpammer($email, $ipAddress);

        return $isSpamUser ? qa_lang_html('users/email_invalid') : null;
    }
}
