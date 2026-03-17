# ETLCoreBundle

A Symfony Bundle providing a configurable ETL (Extract / Transform / Load) pipeline engine built on top of Domain-Driven Design, CQS, Symfony Messenger, Symfony Workflow and Flow-PHP.

[![PHP Version](https://img.shields.io/badge/PHP-8.2%20%7C%208.3%20%7C%208.4-blue)](https://php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-6.4%20%7C%207.x-green)](https://symfony.com/)
[![License](https://img.shields.io/badge/License-MIT-yellow)](LICENSE)

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | `>= 8.2` |
| Symfony | `^6.4 \|\| ^7.0` |
| Doctrine ORM | `^3.6` |
| Flow-PHP ETL | `~0.25` |

---

## Concepts

### ETL — Extract, Transform, Load

ETL is a data processing pattern split into three sequential stages:

| Stage | Role |
|---|---|
| **Extract** | Read raw data from a source (CSV file, API, database, …) |
| **Transform** | Filter, map, enrich or validate the extracted data |
| **Load** | Write the processed data to a destination (database, JSON file, …) |

Each stage is implemented as a **Step** — a single, focused unit of work. Steps are chained together so the output of one becomes the input of the next, flowing through a shared `Context` object.

### Workflow vs Pipeline

These two terms look similar but represent fundamentally different things in this bundle:

**Workflow — the reusable template**

A `Workflow` is a named, static definition that describes *what* should happen:
- the ordered list of steps to execute (`stepConfiguration`), each identified by a `code` that maps to a registered `ExecutableStep` service
- the default configuration for each step
- global options (timeout, retry policy, …) via `configuration`

A `Workflow` has no notion of time, data, or execution state. It never runs by itself. Think of it as a *class* or a *recipe*.

**Pipeline — the execution instance**

A `Pipeline` is a concrete, time-bound instance created from a `Workflow`. It represents *one specific run*:
- it holds the actual **input data** for that run (e.g. path to the file to import)
- it may **override** the step configuration for that run specifically
- it carries a **status** (`pending` → `in_progress` → `completed` / `failed`) managed by a Symfony Workflow state machine
- it records timestamps (`scheduledAt`, `startedAt`, `finishedAt`)

Think of it as an *object instantiated from a class* — or a *ticket raised against a recipe*.

```
Workflow  ──createFromWorkflowId()──►  Pipeline  ──dispatch()──►  execution
(template, reusable)                   (instance, stateful)        (runtime)
```

**Step — configuration vs execution**

The same word "step" covers two distinct things:

| Concept | Where | Role |
|---|---|---|
| `Step` (config) | Stored with the `Pipeline` | Carries `code`, `order`, and per-step `configuration`. A value object — no logic. |
| `ExecutableStep` (service) | Symfony DI container | Implements the actual ETL logic in `process(Context)`. Tagged `boutdecode_etl_core.executable_step`. |

At runtime the `StepResolver` bridges the two: it looks up the `ExecutableStep` service whose tag matches `Step::getCode()`, clones it, applies the step configuration, and hands it to the execution chain.

---

## Installation

```bash
composer require boutdecode/etl-core-bundle
```

If you are **not** using Symfony Flex, register the bundle manually:

```php
// config/bundles.php
return [
    // ...
    BoutDeCode\ETLCoreBundle\BoutDeCodeETLCoreBundle::class => ['all' => true],
];
```

```yaml
// config/packages/boutdecode_etl_core.yaml

imports:
    - { resource: "@BoutDeCodeETLCoreBundle/Resources/config/config.yaml" }
```

---

## Configuration

No configuration is required. The bundle works out of the box with sensible defaults.

The bundle exposes no configurable keys under `boutdecode_etl_core:` — all service IDs, tags, and bus names are fixed constants defined by the bundle itself:

| Constant | Value |
|---|---|
| Command bus | `boutdecode_etl_core.command.bus` |
| Query bus | `boutdecode_etl_core.query.bus` |
| Executable step tag | `boutdecode_etl_core.executable_step` |
| Step middleware tag | `boutdecode_etl_core.step_middleware` |
| Pipeline middleware tag | `boutdecode_etl_core.pipeline_middleware` |

---

## Data

### Entities

The bundle does **not** ship Doctrine entities. You must create them in your application and then generate the migrations.

The bundle provides abstract base classes to extend and interfaces to implement:

| What to create | Extends | Implements |
|---|---|---|
| `Workflow` entity | `AbstractWorkflow` | — |
| `Step` entity | `AbstractStep` | — |
| `Pipeline` entity | `AbstractPipeline` | — |
| `StepHistory` entity | `AbstractStepHistory` | — |
| `PipelineHistory` entity | `AbstractPipelineHistory` | — |

Each abstract class holds all the typed properties and method implementations. The only thing left to add in the concrete entity is:
- A Doctrine `#[ORM\Entity]` / `#[ORM\Table]` mapping.
- An `$id` property with its getter (`getId(): string`), except for `Step` and history entities where you may choose any PK strategy.
- The ORM column/relation mappings on the inherited properties (use `#[ORM\Column]` etc. directly in the child class).

#### Example — minimal entity set

```php
// src/Entity/Workflow.php
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractWorkflow;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workflow')]
class Workflow extends AbstractWorkflow
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column]
    protected string $name;

    #[ORM\Column(nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: 'json')]
    protected array $stepConfiguration = [];

    #[ORM\Column(type: 'json')]
    protected array $configuration = [];

    #[ORM\Column]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    protected ?\DateTimeImmutable $updatedAt = null;

    public function __construct(string $name)
    {
        $this->id = (string) Uuid::v7();
        $this->name = $name;
        $this->createdAt = new \DateTimeImmutable();
        $this->stepConfiguration = [];
        $this->configuration = [];
    }

    public function getId(): string
    {
        return $this->id;
    }
}
```

```php
// src/Entity/Step.php
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractStep;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'step')]
class Step extends AbstractStep
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Workflow::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Workflow $workflow;

    #[ORM\Column(nullable: true)]
    protected ?string $name = null;

    #[ORM\Column]
    protected string $code;

    #[ORM\Column(type: 'json')]
    protected array $configuration = [];

    #[ORM\Column]
    protected int $order = 0;

    public function __construct(string $code, Workflow $workflow)
    {
        $this->id = (string) Uuid::v7();
        $this->code = $code;
        $this->workflow = $workflow;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
```

```php
// src/Entity/Pipeline.php
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractPipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'pipeline')]
class Pipeline extends AbstractPipeline
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Workflow::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected Workflow $workflow;

    #[ORM\OneToMany(targetEntity: Step::class, mappedBy: 'pipeline', cascade: ['persist'])]
    #[ORM\OrderBy(['order' => 'ASC'])]
    protected iterable $steps;

    #[ORM\Column(type: 'json')]
    protected array $configuration = [];

    #[ORM\Column(type: 'json')]
    protected array $input = [];

    #[ORM\Column(enumType: PipelineStatus::class)]
    protected PipelineStatus $status;

    #[ORM\Column]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    protected ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(nullable: true)]
    protected ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    protected ?\DateTimeImmutable $finishedAt = null;

    public function __construct(Workflow $workflow)
    {
        $this->id = (string) Uuid::v7();
        $this->workflow = $workflow;
        $this->status = PipelineStatus::PENDING;
        $this->createdAt = new \DateTimeImmutable();
        $this->steps = new ArrayCollection();
        $this->runnableSteps = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }
}
```

```php
// src/Entity/StepHistory.php
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\AbstractStepHistory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\StepHistoryStatusEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'step_history')]
class StepHistory extends AbstractStepHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(enumType: StepHistoryStatusEnum::class)]
    protected StepHistoryStatusEnum $status;

    #[ORM\Column]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'json', nullable: true)]
    protected mixed $input = null;

    #[ORM\Column(type: 'json', nullable: true)]
    protected mixed $result = null;

    public function __construct(StepHistoryStatusEnum $status, mixed $input, mixed $result)
    {
        $this->id = (string) Uuid::v7();
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->input = $input;
        $this->result = $result;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
```

```php
// src/Entity/PipelineHistory.php
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\AbstractPipelineHistory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline as PipelineInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'pipeline_history')]
class PipelineHistory extends AbstractPipelineHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Pipeline::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected PipelineInterface $pipeline;

    #[ORM\Column(enumType: PipelineHistoryStatusEnum::class)]
    protected PipelineHistoryStatusEnum $status;

    #[ORM\Column]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'json', nullable: true)]
    protected mixed $input = null;

    #[ORM\Column(type: 'json', nullable: true)]
    protected mixed $result = null;

    #[ORM\OneToMany(targetEntity: StepHistory::class, mappedBy: 'pipelineHistory', cascade: ['persist'])]
    protected iterable $stepHistories;

    public function __construct(PipelineInterface $pipeline, PipelineHistoryStatusEnum $status, mixed $input, mixed $result)
    {
        $this->id = (string) Uuid::v7();
        $this->pipeline = $pipeline;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->input = $input;
        $this->result = $result;
        $this->stepHistories = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }
}
```

### Migrations

Once all entities are created, generate and run the Doctrine migrations:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

---

## Architecture

```
src/
├── ETLCoreBundle.php               # Bundle entry point
├── DependencyInjection/
│   ├── ETLCoreExtension.php        # Loads services, exposes config parameters
│   └── Configuration.php           # Config tree (boutdecode_etl_core:)
├── Resources/config/
│   ├── services.yaml               # Service definitions & tagged iterators
│   ├── config.yaml                 # Root import (messenger + workflow)
│   └── packages/
│       ├── messenger.yaml          # Buses & routing
│       └── workflow.yaml           # pipeline_lifecycle state machine
├── Core/                           # Central domain (Pipeline, Step, Context)
├── ETL/                            # ETL logic (Extract, Transform, Load)
├── Run/                            # Execution engine & middleware
└── CQS/                            # Command / Query Separation
```

### Key patterns

| Pattern | Where |
|---|---|
| Domain-Driven Design | `*/Domain/` layers |
| CQS (Command / Query Separation) | `src/CQS/` |
| Middleware chain | `Run/Domain/Middleware/` |
| Strategy (pluggable steps) | `ETL/Domain/Model/` + `ExecutableStep` tag |
| State machine | `pipeline_lifecycle` Symfony Workflow |

---

## Implementing a Custom Step

### 1. Create the step class

Extend one of the three base step classes depending on the role:

```php
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExtractorStep;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Context;

final class MyCsvExtractorStep extends AbstractExtractorStep
{
    public function process(Context $context): Context
    {
        $rows = $this->readCsv($context->getConfigurationValue('file'));

        return $context->setResult('extracted_data', $rows);
    }
}
```

Available base classes:

| Base class | Role |
|---|---|
| `AbstractExtractorStep` | Reads data from a source |
| `AbstractTransformerStep` | Transforms / filters data |
| `AbstractLoaderStep` | Writes data to a destination |

### 2. Register the step

Tag it with `boutdecode_etl_core.executable_step` (or let `_instanceof` do it automatically since all `ExecutableStep` implementations are tagged by default):

```yaml
# config/services.yaml
App\ETL\Step\MyCsvExtractorStep:
    tags:
        - { name: boutdecode_etl_core.executable_step }
```

---

## CQS — Commands & Queries

### Dispatching a command

Inject `CommandBus` and call `dispatch()` with any object implementing `Command`:

```php
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;

class MyService
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function doSomething(): void
    {
        $this->commandBus->dispatch(new MyCommand(/* ... */));
    }
}
```

### Running a pipeline from a Workflow

The bundle ships one built-in command: `ExecuteWorkflowCommand`. It takes a persisted pipeline ID and triggers the full middleware chain asynchronously.

#### Step 1 — Implement `PipelineFactory`

The bundle provides the `PipelineFactory` interface but no concrete implementation — you must provide one (typically a Doctrine-backed service):

```php
// src/Factory/PipelineFactory.php
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\PipelineFactory as PipelineFactoryInterface;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\WorkflowProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PipelinePersister;

final class PipelineFactory implements PipelineFactoryInterface
{
    public function __construct(
        private readonly WorkflowProvider $workflowProvider,
        private readonly PipelinePersister $pipelinePersister,
    ) {}

    public function create(array $steps = [], array $configuration = []): Pipeline
    {
        // build a Pipeline from a list of Step objects
        // ...
    }

    /**
     * @param array<string, mixed> $overrideConfiguration
     * @param array<string, mixed> $input
     */
    public function createFromWorkflowId(
        string $workflowId,
        array $overrideConfiguration = [],
        array $input = [],
    ): Pipeline {
        $workflow = $this->workflowProvider->findWorkflowByIdentifier($workflowId);

        // build Pipeline from Workflow steps & config, then persist it
        $pipeline = new \App\Entity\Pipeline($workflow);
        // ... populate steps, configuration, input ...

        return $this->pipelinePersister->create($pipeline);
    }
}
```

The bundle's `DataInterfaceAliasPass` compiler pass automatically creates the DI alias as soon as your class is registered as a service — no manual wiring needed.

#### Step 2 — Create and persist the Pipeline

```php
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\PipelineFactory;

final class StartImportHandler
{
    public function __construct(
        private readonly PipelineFactory $pipelineFactory,
    ) {}

    public function handle(string $workflowId): string
    {
        $pipeline = $this->pipelineFactory->createFromWorkflowId(
            workflowId: $workflowId,
            overrideConfiguration: [
                'extract_step' => ['file' => '/data/import.csv'],
            ],
            input: ['source' => 'manual'],
        );

        // Pipeline is now persisted with PipelineStatus::PENDING
        return $pipeline->getId();
    }
}
```

#### Step 3 — Dispatch the execution command

```php
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecuteWorkflowCommand;

final class StartImportHandler
{
    public function __construct(
        private readonly PipelineFactory $pipelineFactory,
        private readonly CommandBus $commandBus,
    ) {}

    public function handle(string $workflowId): void
    {
        $pipeline = $this->pipelineFactory->createFromWorkflowId(
            workflowId: $workflowId,
            input: ['source' => 'manual'],
        );

        // ExecuteWorkflowCommand implements AsyncCommand:
        // routed to an async Messenger transport if one is configured,
        // otherwise handled synchronously.
        $this->commandBus->dispatch(
            new ExecuteWorkflowCommand(pipelineId: $pipeline->getId())
        );
    }
}
```

> **Note:** `ExecuteWorkflowCommand` implements `AsyncCommand`. If you configure a Symfony Messenger transport for the `async` routing key the execution will be deferred to a worker. The pipeline must be in `PipelineStatus::PENDING` — if it is already `IN_PROGRESS`, `COMPLETED`, or `FAILED` the handler returns silently without re-running it.

#### Reading the results

`CommandBus::dispatch()` returns the value produced by the handler (`Context`). You can inspect the results directly when running synchronously:

```php
use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

/** @var Context $context */
$context = $this->commandBus->dispatch(
    new ExecuteWorkflowCommand(pipelineId: $pipeline->getId())
);

// Last result produced by the pipeline
$result = $context->getResult();

// Result keyed by step name
$extracted = $context->getResultByKey('extract_step');

// Check for step failures
$errors = $context->getErrors(); // array<string, mixed>
```

---

## Adding Custom Middleware

### Pipeline middleware

```php
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Context;

final class AuditPipelineMiddleware implements Middleware
{
    public function process(Context $context, callable $next): Context
    {
        // before
        $result = $next($context);
        // after
        return $result;
    }
}
```

```yaml
# config/services.yaml
App\Middleware\AuditPipelineMiddleware:
    tags:
        - { name: boutdecode_etl_core.pipeline_middleware, priority: 50 }
```

### Step middleware

Same pattern, tag name: `boutdecode_etl_core.step_middleware`.

Built-in middleware priority reference:

| Middleware | Tag | Priority |
|---|---|---|
| `PipelineStartMiddleware` | pipeline | 100 |
| `PipelineFailureMiddleware` | pipeline | 1 |
| `PipelineProcessMiddleware` | pipeline | 0 |
| `PipelineHistoryMiddleware` | pipeline | -50 |
| `PipelineSuccessMiddleware` | pipeline | -100 |
| `StepStartMiddleware` | step | 100 |
| `StepFailureMiddleware` | step | 1 |
| `StepProcessMiddleware` | step | 0 |
| `StepHistoryMiddleware` | step | -50 |
| `StepSuccessMiddleware` | step | -100 |

---

## Testing

```bash
# All tests
composer test

# Unit tests only
composer test:unit

# Integration tests only
composer test:integration
```

Current status: **326 unit tests, 3 integration tests — all passing**.

---

## License

MIT — see [LICENSE](LICENSE).

---

**Built with ❤️ by [Boutdecode](https://github.com/boutdecode)**
