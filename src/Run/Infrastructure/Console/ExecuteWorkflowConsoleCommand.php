<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Console;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PipelinePersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\WorkflowProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\PipelineFactory;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\InMemoryWorkflow;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Run\Domain\Runner\PipelineRunner;
use BoutDeCode\ETLCoreBundle\Run\Domain\Specification\IsPipelineExecutableSpecification;
use BoutDeCode\ETLCoreBundle\Run\Domain\Workflow\PipelineWorkflow;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'etl:workflow:execute',
    description: 'Create and execute a pipeline from a workflow code or from a YAML/JSON workflow file.',
)]
final class ExecuteWorkflowConsoleCommand extends Command
{
    public function __construct(
        private readonly WorkflowProvider $workflowProvider,
        private readonly PipelineFactory $pipelineFactory,
        private readonly PipelinePersister $pipelinePersister,
        private readonly PipelineWorkflow $pipelineWorkflow,
        private readonly PipelineRunner $pipelineRunner,
        private readonly IsPipelineExecutableSpecification $isPipelineExecutableSpecification,
        private readonly WorkflowFileLoader $workflowFileLoader,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('workflow', InputArgument::OPTIONAL, 'Code of an existing workflow')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to a YAML or JSON workflow file')
            ->addOption('input', 'i', InputOption::VALUE_REQUIRED, 'Pipeline input as a JSON object');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $code = $input->getArgument('workflow');
        $file = $input->getOption('file');
        $code = \is_string($code) ? $code : null;
        $file = \is_string($file) ? $file : null;

        if (($code === null) === ($file === null)) {
            $io->error('Provide either a workflow code or the --file option, but not both.');

            return Command::INVALID;
        }

        try {
            $pipelineInput = $this->parseInput($input->getOption('input'));
            $workflow = $file !== null
                ? InMemoryWorkflow::fromArray($this->workflowFileLoader->load($file))
                : $this->workflowProvider->findWorkflowByIdentifier($code ?? '');
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        if (! $workflow instanceof Workflow) {
            $io->error(sprintf('Workflow "%s" not found.', $code ?? ''));

            return Command::FAILURE;
        }

        $pipeline = $this->pipelinePersister->create(
            $this->pipelineFactory->createFromWorkflow(
                $workflow,
                sprintf('%s pipeline', $workflow->getName()),
                [],
                $pipelineInput,
            ),
        );

        $this->pipelineWorkflow->schedule($pipeline);

        if (! $this->isPipelineExecutableSpecification->isSatisfiedBy($pipeline)) {
            $io->error(sprintf('Pipeline "%s" is not executable.', $pipeline->getId()));

            return Command::FAILURE;
        }

        $io->text(sprintf('Executing pipeline "%s"...', $pipeline->getId()));

        $errors = $this->pipelineRunner->run($pipeline)->getErrors();

        if ($errors !== []) {
            foreach ($errors as $step => $message) {
                $io->error(sprintf('%s: %s', $step, $message));
            }

            return Command::FAILURE;
        }

        $io->success('Pipeline executed successfully.');

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseInput(mixed $json): array
    {
        if (! \is_string($json)) {
            return [];
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException(sprintf('Invalid --input JSON: %s', $exception->getMessage()), 0, $exception);
        }

        if (! \is_array($data)) {
            throw new \InvalidArgumentException('The --input option must be a JSON object.');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
