<?php

class PUPI_SRS_Admin
{
    const SAVE_BUTTON = 'pupi_srs_save_button';
    const RESET_ALL_STATS_BUTTON = 'pupi_srs_reset_all_stats';

    private $directory;
    private $urlToRoot;

    public function load_module($directory, $urlToRoot)
    {
        $this->directory = $directory;
        $this->urlToRoot = $urlToRoot;
    }

    function admin_form(&$qa_content)
    {
        require_once $this->directory . 'Services/PUPI_SRS_ServiceManager.php';

        $duplicateEmailValidators = PUPI_SRS_ServiceManager::getAllDuplicateEmailValidators($this->directory);
        $onlineUsersValidators = PUPI_SRS_ServiceManager::getAllOnlineUsersValidators($this->directory);

        $ok = null;
        if (qa_clicked(self::RESET_ALL_STATS_BUTTON)) {
            $duplicateEmailValidatorStats = PUPI_SRS_ServiceManager::createStatsSettingsForSevices($duplicateEmailValidators);
            $onlineUsersValidatorStats = PUPI_SRS_ServiceManager::createStatsSettingsForSevices($onlineUsersValidators);

            qa_opt('pupi_srs_emails_stats', json_encode($duplicateEmailValidatorStats));
            qa_opt('pupi_srs_services_stats', json_encode($onlineUsersValidatorStats));

            $ok = qa_lang_html('pupi_srs/admin_reset_all_stats_success_message');
        } else {
            $duplicateEmailValidatorStats = json_decode(qa_opt('pupi_srs_emails_stats'), true);
            if (is_null($duplicateEmailValidatorStats)) {
                $duplicateEmailValidatorStats = PUPI_SRS_ServiceManager::createStatsSettingsForSevices($duplicateEmailValidators);
            }
            $onlineUsersValidatorStats = json_decode(qa_opt('pupi_srs_services_stats'), true);
            if (is_null($onlineUsersValidatorStats)) {
                $onlineUsersValidatorStats = PUPI_SRS_ServiceManager::createStatsSettingsForSevices($onlineUsersValidators);
            }

            if (qa_clicked(self::SAVE_BUTTON)) {
                $this->saveForm($onlineUsersValidators);
                $ok = qa_lang_html('admin/options_saved');
            }
        }

        $this->prepareFrontEnd($qa_content);

        return [
            'ok' => $ok,
            'fields' => $this->getFields($onlineUsersValidators, $duplicateEmailValidatorStats, $onlineUsersValidatorStats),
            'buttons' => $this->getButtons(),
        ];
    }

    private function prepareFrontEnd(&$qa_content)
    {
        if (!isset($qa_content['css_src'])) {
            $qa_content['css_src'] = [];
        }

        $qa_content['css_src'][] = $this->urlToRoot . 'public/admin.min.css';
    }

    private function getButtons(): array
    {
        return [
            self::SAVE_BUTTON => [
                'tags' => sprintf('name="%s"', self::SAVE_BUTTON),
                'label' => qa_lang_html('admin/save_options_button'),
            ],
            self::RESET_ALL_STATS_BUTTON => [
                'tags' => sprintf('name="%s" onclick="return confirm(\'%s\')"', self::RESET_ALL_STATS_BUTTON, qa_lang_html('pupi_srs/admin_reset_all_stats_button_confirmation')),
                'label' => qa_lang_html('pupi_srs/admin_reset_all_stats_button'),
            ],
        ];
    }

    private function getServicesTable($providers, $title): string
    {
        $html = sprintf('<h2 class="pupi_srs_services-stats-title">%s</h2>', $title);

        $html .= '<div class="pupi_srs_services-stats">';

        foreach ($providers as $name => $stats) {
            $html .= '<div class="pupi_srs_service-header">';
            $html .= sprintf('<div class="pupi_srs_service-name">%s</div>', $name);
            $html .= sprintf('<div class="pupi_srs_service-header-title">%s</div>', qa_lang_html('pupi_srs/admin_services_stats_number_of_tests'));
            $html .= sprintf('<div class="pupi_srs_service-header-title">%s</div>', qa_lang_html('pupi_srs/admin_services_stats_number_of_spam_users'));
            $html .= '</div>'; // pupi_srs_service-header

            $html .= '<div class="pupi_srs_service-data">';

            $totalTestsInPeriods = 0;
            $totalSpamUsersInPeriods = 0;

            foreach ($stats['periods'] as $period => $periodData) {
                $html .= '<div class="pupi_srs_service-data-period">';
                $html .= sprintf('<div class="pupi_srs_service-data-period-name">%s</div>', $period);
                $html .= sprintf('<div class="pupi_srs_service-data-count">%d</div>', $periodData['totalTests']);
                $html .= sprintf('<div class="pupi_srs_service-data-count">%d</div>', $periodData['spamUsers']);
                $html .= '</div>'; // pupi_srs_service-data-period

                $totalTestsInPeriods += $periodData['totalTests'];
                $totalSpamUsersInPeriods += $periodData['spamUsers'];
            }

            $html .= '<div class="pupi_srs_service-data-period">';
            $html .= sprintf('<div class="pupi_srs_service-data-period-name">%s</div>', qa_lang_html('pupi_srs/admin_services_stats_previous_months'));
            $html .= sprintf('<div class="pupi_srs_service-data-count">%d</div>', $stats['totalTests'] - $totalTestsInPeriods);
            $html .= sprintf('<div class="pupi_srs_service-data-count">%d</div>', $stats['spamUsers'] - $totalSpamUsersInPeriods);
            $html .= '</div>'; // pupi_srs_service-data-period

            $html .= '<div class="pupi_srs_service-data-total">';
            $html .= sprintf('<div class="pupi_srs_service-data-period-total">%s</div>', qa_lang_html('pupi_srs/admin_services_stats_total'));
            $html .= sprintf('<div class="pupi_srs_service-data-count">%d</div>', $stats['totalTests']);
            $html .= sprintf('<div class="pupi_srs_service-data-count">%d</div>', $stats['spamUsers']);
            $html .= '</div>'; // pupi_srs_service-data-period

            $html .= '</div>'; // pupi_srs_service-data
        }

        $html .= '</div>';

        return $html; // pupi_srs_services-stats
    }

    private function getEmailConfirmationDisabledWarning()
    {
        $html = '<div class="pupi_srs_email-confirmation-warning">';
        $html .= qa_lang_html_sub(
            'pupi_srs/admin_emails_stats_confirmation_warning',
            '<span class="pupi_srs_email-confirmation-warning-setting">' . qa_lang_html('options/confirm_user_emails') . '</span>'
        );
        $html .= '</div>';

        return $html;
    }

    private function getFields(array $onlineUsersValidators, array $duplicateEmailValidatorStats, array $onlineUsersValidatorStats): array
    {
        $result = [];

        foreach ($onlineUsersValidators as $service) {
            $result += $service->getAdminFormFields();
        }

        $html = $this->getServicesTable($duplicateEmailValidatorStats['providers'], qa_lang_html('pupi_srs/admin_emails_stats_title'));

        if (!qa_opt('confirm_user_emails')) {
            $html .= $this->getEmailConfirmationDisabledWarning();
        }

        $html .= $this->getServicesTable($onlineUsersValidatorStats['providers'], qa_lang_html('pupi_srs/admin_online_stats_title'));

        $result['admin_settings'] = [
            'type' => 'custom',
            'html' => $html,
        ];

        return $result;
    }

    private function saveForm(array $services)
    {
        foreach ($services as $service) {
            $service->saveAdminForm();
        }
    }

}