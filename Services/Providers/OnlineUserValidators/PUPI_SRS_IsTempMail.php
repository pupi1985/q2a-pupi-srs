<?php

class PUPI_SRS_IsTempMail extends PUPI_SRS_AbstractOnlineUserValidator
{
    const ISTEMPMAIL_KEY_SETTING = 'pupi_srs_istempmail_key';

    /**
     * API key for the IsTempMail service.
     *
     * @var string
     */
    private $key;

    public function __construct()
    {
        $this->name = 'IsTempMail';
        $this->key = qa_opt(self::ISTEMPMAIL_KEY_SETTING);
    }

    /**
     * @throws Exception
     */
    public function isSpamUser(string $email, string $ip): bool
    {
        if (empty($this->key)) {
            throw new Exception('Invalid API key');
        }

        $url = sprintf('https://www.istempmail.com/api/check/%s/%s', urlencode($this->key), urlencode($email));
        $dataString = qa_retrieve_url($url);

        $data = json_decode($dataString, true);

        if (empty($data)) {
            throw new Exception('Error fetching data from server');
        }

        if (!isset($data['blocked'])) {
            throw new Exception('Unknown error. Data returned: ' . $dataString);
        }

        if ($data['blocked']) {
            return true;
        }

        if (isset($data['unresolvable']) && $data['unresolvable']) {
            return true;
        }

        return false;
    }

    public function getAdminFormFields(): array
    {
        return [
            self::ISTEMPMAIL_KEY_SETTING => [
                'label' => 'IsTempMail key:', // Intentionally untranslated to make Providers be a single file
                'value' => qa_html(qa_opt(self::ISTEMPMAIL_KEY_SETTING)),
                'tags' => sprintf('name="%s"', self::ISTEMPMAIL_KEY_SETTING),
            ],
        ];
    }

    public function saveAdminForm()
    {
        qa_opt(self::ISTEMPMAIL_KEY_SETTING, qa_post_text(self::ISTEMPMAIL_KEY_SETTING));
    }
}
