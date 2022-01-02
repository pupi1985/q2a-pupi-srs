<?php

class PUPI_SRS_BotScout extends PUPI_SRS_AbstractOnlineUserValidator
{
    const BOTSCOUT_KEY_SETTING = 'pupi_srs_botscout_key';
    /**
     * API key for the BotScout service.
     *
     * @var string
     */
    private $key;

    public function __construct()
    {
        $this->name = 'BotScout';
        $this->key = qa_opt(self::BOTSCOUT_KEY_SETTING);
    }

    /**
     * @throws Exception
     */
    public function isSpamUser(string $email, string $ip): bool
    {
        if (empty($this->key)) {
            throw new Exception('Invalid API key');
        }

        $url = sprintf('http://botscout.com/test/?multi&mail=%s&ip=%s&key=%s', urlencode($email), urlencode($ip), urlencode($this->key));
        $data = qa_retrieve_url($url);

        if (empty($data)) {
            throw new Exception('Error fetching data from server');
        }

        if (strpos($data, '! ') === 0) {
            throw new Exception('API Error: ' . substr($data, 2));
        }

        $dataExploded = explode('|', $data);

        if ($dataExploded[0] === 'Y') {
            // Allow up to 5 IP address reports
            if ((int)$dataExploded[3] > 5) {
                return true;
            }

            // Don't allow any email report
            if ((int)$dataExploded[5] > 0) {
                return true;
            }

            return false;
        }

        if ($dataExploded[0] === 'N') {
            return false;
        }

        throw new Exception('Unknown error. Data returned: ' . $data);
    }

    public function getAdminFormFields(): array
    {
        return [
            self::BOTSCOUT_KEY_SETTING => [
                'label' => 'BotScout key:', // Intentionally untranslated to make Providers be a single file
                'value' => qa_html(qa_opt(self::BOTSCOUT_KEY_SETTING)),
                'tags' => sprintf('name="%s"', self::BOTSCOUT_KEY_SETTING),
            ],
        ];
    }

    public function saveAdminForm()
    {
        qa_opt(self::BOTSCOUT_KEY_SETTING, qa_post_text(self::BOTSCOUT_KEY_SETTING));
    }
}
