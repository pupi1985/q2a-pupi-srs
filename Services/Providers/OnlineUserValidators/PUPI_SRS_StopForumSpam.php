<?php

class PUPI_SRS_StopForumSpam extends PUPI_SRS_AbstractOnlineUserValidator
{
    public function __construct()
    {
        $this->name = 'StopForumSpam';
    }

    /**
     * @throws Exception
     */
    public function isSpamUser(string $email, string $ip): bool
    {
        $url = sprintf('http://api.stopforumspam.org/api?email=%s&ip=%s&json', urlencode($email), urlencode($ip));
        $data = qa_retrieve_url($url);

        $data = json_decode($data, true);

        if (empty($data)) {
            throw new Exception('Error fetching data from server');
        }

        if ($data['success'] === 0) {
            throw new Exception('API Error: ' . $data['error'] ?? '');
        }

        if (isset($data['email']['error'])) {
            throw new Exception('API Error: ' . $data['email']['error'] ?? '');
        }

        if (isset($data['ip']['error'])) {
            throw new Exception('API Error: ' . $data['ip']['error'] ?? '');
        }

        if ($data['email']['blacklisted'] ?? 0 === 1) {
            return true;
        }

        if ($data['ip']['blacklisted'] ?? 0 === 1) {
            return true;
        }

        return false;
    }

    public function getAdminFormFields(): array
    {
        return [];
    }

    public function saveAdminForm()
    {
    }
}
