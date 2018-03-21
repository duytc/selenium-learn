<?php

namespace Tagcade\Bundle\AppBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

class UpdateIntegrationStatusCommand extends ContainerAwareCommand
{
    /** @var string */
    const CONFIG_KEY = 'disabled_integrations';

    protected function configure()
    {
        $this
            ->setName('tc:unified-report-fetcher:integration:status')
            ->addOption('show-disabled-list', 'l', InputOption::VALUE_NONE, 'Show disabled integrations. This is the highest priority option')
            ->addOption('integration', 'i', InputOption::VALUE_OPTIONAL, 'The integration c-name to show or update status (format: a-z0-9\-_). This is the lowest priority option')
            ->addOption('enable', 'E', InputOption::VALUE_NONE, 'Enable entire integration. Require an integration option following')
            ->addOption('disable', 'D', InputOption::VALUE_NONE, 'Disable entire integration. Require an integration option following')
            ->setDescription('Show or Update status for an integration. Sample commands:'
                . PHP_EOL . 'php app/console tc:unified-report-fetcher:integration:status -l'
                . PHP_EOL . 'php app/console tc:unified-report-fetcher:integration:status -i integration_3'
                . PHP_EOL . 'php app/console tc:unified-report-fetcher:integration:status -E -i integration_3'
                . PHP_EOL . 'php app/console tc:unified-report-fetcher:integration:status -D -i integration_3'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('tc:unified-report-fetcher:integration:status');

        /* get input options, the higher priority the position first */
        $isShowDisableList = $input->getOption('show-disabled-list');
        $integrationCName = $input->getOption('integration');
        $isEnable = $input->getOption('enable');
        $isDisable = $input->getOption('disable');

        $errorCode = 0;

        try {
            $this->validateInputOptions($isShowDisableList, $integrationCName, $isEnable, $isDisable);

            if ($isShowDisableList) {
                $this->showDisableList($io);
                return;
            }

            if (!$isEnable && !$isDisable && !$isShowDisableList) {
                $this->showIntegrationStatus($io, $integrationCName);
                return;
            }

            if ($isEnable && !$isDisable && !$isShowDisableList) {
                $this->enableIntegrationStatus($io, $integrationCName);
                return;
            }

            if (!$isEnable && $isDisable && !$isShowDisableList) {
                $this->disableIntegrationStatus($io, $integrationCName);
                return;
            }

        } catch (Exception $e) {
            $io->error('Complete running running Update Integration Status with ERROR: ' . $e->getMessage());
            $errorCode = 1;
        } finally {
            if ($errorCode === 0) {
                $io->success('Complete running running Update Integration Status with no error');
            }
        }
    }

    /**
     * @param bool $isShowDisableList
     * @param string $integrationCName
     * @param bool $isEnable
     * @param bool $isDisable
     * @throws Exception
     */
    private function validateInputOptions($isShowDisableList, $integrationCName, $isEnable, $isDisable)
    {
        if (!$isShowDisableList) {
            if ($isEnable || $isDisable) {
                if (empty($integrationCName)) {
                    throw new Exception('Missing Integration c-name');
                }
            }

            // validate format
            if (!empty($integrationCName) && !preg_match('/^[a-z0-9\-_]+$/', $integrationCName)) {
                throw new Exception('Integration c-name is wrong format. (Expected: a-z0-9\-_). E.g my-integration-v_102');
            }
        }
    }

    /**
     * @param SymfonyStyle $io
     */
    private function showDisableList(SymfonyStyle $io)
    {
        $parameters = $this->getParameters();
        $disabledIntegrations = $parameters['parameters'][self::CONFIG_KEY];

        if (!is_array($disabledIntegrations)) {
            $disabledIntegrations = [];
        }

        $this->_showDisableList($io, $disabledIntegrations);
    }

    /**
     * @param SymfonyStyle $io
     * @param string $integrationCName
     * @throws Exception
     */
    private function showIntegrationStatus(SymfonyStyle $io, $integrationCName)
    {
        if (empty($integrationCName)) {
            throw new Exception('Missing Integration cname');
        }

        $isDisabled = true;
        $parameters = $this->getParameters();
        $disabledIntegrations = $parameters['parameters'][self::CONFIG_KEY];

        if (!is_array($disabledIntegrations) || !in_array($integrationCName, $disabledIntegrations)) {
            $isDisabled = false;
        }

        $io->text(sprintf('Integration %s is %s', $integrationCName, $isDisabled ? 'DISABLED' : 'ENABLED'));
    }

    /**
     * @param SymfonyStyle $io
     * @param string $integrationCName
     * @throws Exception
     */
    private function enableIntegrationStatus(SymfonyStyle $io, $integrationCName)
    {
        if (empty($integrationCName)) {
            throw new Exception('Missing Integration cname');
        }

        $parameters = $this->getParameters();
        $disabledIntegrations = $parameters['parameters'][self::CONFIG_KEY];

        if (!is_array($disabledIntegrations) || !in_array($integrationCName, $disabledIntegrations)) {
            $io->text(sprintf('Integration %s is already ENABLED', $integrationCName));

            return;
        }

        // remove from to disabled list
        $idx = array_search($integrationCName, $disabledIntegrations);
        if (false !== $idx) {
            unset ($disabledIntegrations[$idx]);
        }

        $this->updateDisabledIntegrationsConfig($disabledIntegrations);

        $io->text(sprintf('Integration %s has been ENABLED', $integrationCName));
    }

    /**
     * @param SymfonyStyle $io
     * @param string $integrationCName
     * @throws Exception
     */
    private function disableIntegrationStatus(SymfonyStyle $io, $integrationCName)
    {
        if (empty($integrationCName)) {
            throw new Exception('Missing Integration cname');
        }

        $parameters = $this->getParameters();
        $disabledIntegrations = $parameters['parameters'][self::CONFIG_KEY];

        if (is_array($disabledIntegrations) && in_array($integrationCName, $disabledIntegrations)) {
            $io->text(sprintf('Integration %s is already DISABLED', $integrationCName));

            return;
        }

        // add to disabled list
        $disabledIntegrations[] = $integrationCName;
        $this->updateDisabledIntegrationsConfig($disabledIntegrations);

        $io->text(sprintf('Integration %s has been DISABLED', $integrationCName));
    }

    /**
     * @param SymfonyStyle $io
     * @param array $integrationCNames
     */
    private function _showDisableList(SymfonyStyle $io, array $integrationCNames)
    {
        $headers = ['#', 'CName'];
        $rows = [];
        $idx = 0;

        foreach ($integrationCNames as $integrationCName) {
            $idx++;
            $rows[] = [$idx, $integrationCName];
        }

        $io->text('Disabled integrations list:');
        $io->table($headers, $rows);
    }

    /**
     * @return array
     */
    private function getParameters()
    {
        // NOTICE: could not use
        //   $this->getContainer()->setParameter(self::CONFIG_KEY, $disabledIntegrations);
        // because parameters.yml is protected as a frozen ParameterBag
        // exception: Impossible to call set() on a frozen ParameterBag.

        // get current config
        $parameters = Yaml::parse(file_get_contents($this->getContainer()->get('kernel')->getRootDir() . '/config/parameters.yml'));

        return $parameters;
    }

    private function updateDisabledIntegrationsConfig(array $disabledIntegrations)
    {
        // get current config
        $parameters = $this->getParameters();

        // make sure unique value in array
        $disabledIntegrations = array_values(array_unique($disabledIntegrations));

        // update config
        $parameters['parameters'][self::CONFIG_KEY] = $disabledIntegrations;

        // write config file
        $parameters = Yaml::dump($parameters, 5); // inline=5 for yaml array style with new line, not as [...values...]
        file_put_contents($this->getContainer()->get('kernel')->getRootDir() . '/config/parameters.yml', $parameters);
    }
}