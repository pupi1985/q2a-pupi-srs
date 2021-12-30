<?php

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

qa_register_plugin_module('filter', 'PUPI_SRS_RegistrationFilter.php', 'PUPI_SRS_RegistrationFilter', 'PUPI_SRS Registration Filter');
qa_register_plugin_module('process', 'PUPI_SRS_AdminProcess.php', 'PUPI_SRS_AdminProcess', 'PUPI_SRS Admin Process');

qa_register_plugin_phrases('lang/pupi_srs_*.php', 'pupi_srs');
