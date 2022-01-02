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

        $isDuplicated = $this->checkDuplicateEmailValidators($email);

        if ($isDuplicated) {
            return qa_lang('users/email_exists');
        }

        $isSpamUser = $this->checkOnlineUserValidators($email, qa_remote_ip_address());

        return $isSpamUser ? qa_lang_html('users/email_invalid') : null;
    }

    private function checkDuplicateEmailValidators(string $email): bool
    {
        require_once $this->directory . 'Services/PUPI_SRS_DuplicateEmailValidatorManager.php';

        return (new PUPI_SRS_DuplicateEmailValidatorManager($this->directory))->isDuplicated($email);
    }

    private function checkOnlineUserValidators(string $email, string $ipAddress): bool
    {
        require_once $this->directory . 'Services/PUPI_SRS_OnlineUserValidatorManager.php';

        return (new PUPI_SRS_OnlineUserValidatorManager($this->directory))->isSpammer($email, $ipAddress);
    }
}
