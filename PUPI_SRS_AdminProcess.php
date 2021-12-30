<?php

class PUPI_SRS_AdminProcess
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
        require_once 'Services/PUPI_SRS_ServiceManager.php';

        $services = PUPI_SRS_ServiceManager::getAllServices($this->directory);

        $ok = null;
        if (qa_clicked(self::RESET_ALL_STATS_BUTTON)) {
            $servicesStats = PUPI_SRS_ServiceManager::createStatsSettingsForSevices($services);

            qa_opt('pupi_srs_services_stats', json_encode($servicesStats));

            $ok = qa_lang_html('pupi_srs/admin_reset_all_stats_success_message');
        } else {
            $servicesStats = json_decode(qa_opt('pupi_srs_services_stats'), true);
            if (is_null($servicesStats)) {
                $servicesStats = PUPI_SRS_ServiceManager::createStatsSettingsForSevices($services);
            }

            if (qa_clicked(self::SAVE_BUTTON)) {
                $this->saveForm($services);
                $ok = qa_lang_html('admin/options_saved');
            }
        }

        $this->prepareFrontEnd($qa_content);

        return [
            'ok' => $ok,
            'fields' => $this->getFields($services, $servicesStats),
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

    /**
     * @param $providers
     *
     * @return string
     */
    private function getServicesTable($providers): string
    {
        $html = sprintf('<h2>%s</h2>', qa_lang_html('pupi_srs/admin_services_stats_title'));

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

    private function getFields(array $services, array $servicesStats): array
    {
        $result = [];

        foreach ($services as $service) {
            $result += $service->getAdminFormFields();
        }

        $result['custom'] = [
            'type' => 'custom',
            'html' => $this->getServicesTable($servicesStats['providers']),
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
