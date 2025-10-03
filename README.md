# ETLCoreBundle

A Symfony Bundle providing a configurable ETL (Extract / Transform / Load) pipeline engine built on top of Domain-Driven Design, CQS, Symfony Messenger, Symfony Workflow and Flow-PHP.

[![PHP Version](https://img.shields.io/badge/PHP-8.3%20%7C%208.4-blue)](https://php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-6.4%20%7C%207.x-green)](https://symfony.com/)
[![License](https://img.shields.io/badge/License-MIT-yellow)](LICENSE)

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | `>= 8.3` |
| Symfony | `^6.4 \|\| ^7.0` |
| Doctrine ORM | `^3.6` |
| Flow-PHP ETL | `~0.25` |

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

### Minimal (all defaults)

No configuration is required. The bundle works out of the box with sensible defaults.

### Full reference

```yaml
# config/packages/boutdecode_etl_core.yaml
boutdecode_etl_core:

    # Symfony Messenger bus service IDs
    messenger:
        command_bus: command.bus   # default
        query_bus:   query.bus     # default
        event_bus:   event.bus     # default

    # Service tags used for autoconfiguration
    tags:
        executable_step:    boutdecode_etl_core.executable_step    # default
        step_middleware:    boutdecode_etl_core.step_middleware     # default
        pipeline_middleware: boutdecode_etl_core.pipeline_middleware # default
```

---

## Symfony Messenger

The bundle expects three named buses to be present. Add this to your application if you do not already have them:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            command.bus: ~
            query.bus:   ~
            event.bus:   ~

        routing:
            'BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\Command':      sync
            'BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\Query':          sync
            'BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\AsyncCommand': async
            'BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\AsyncQuery':     async
```

> Sync routing is the default. Switch any entry to `async` (and configure a transport) to enable background execution.

---

## Data base

Generate and run the migrations:

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
│   └── packages/
│       ├── messenger.yaml          # Buses & routing
│       ├── workflow.yaml           # pipeline_lifecycle state machine
│       └── doctrine.yaml          # ORM mappings
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

Current status: **297 unit tests, 3 integration tests — all passing**.

---

## License

MIT — see [LICENSE](LICENSE).

---

**Built with ❤️ by [Boutdecode](https://github.com/boutdecode)**
