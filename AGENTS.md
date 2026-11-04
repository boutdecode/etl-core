# AGENTS.md

## Project overview

**ETLCoreBundle** is a reusable **Symfony Bundle** (`boutdecode/etl-core-bundle`) providing a configurable ETL (Extract / Transform / Load) pipeline engine.

- Bundle namespace: `BoutDeCode\ETLCoreBundle\`
- Test namespace: `BoutDeCode\ETLCoreBundle\Tests\`
- Symfony compatibility: `^6.4 || ^7.0`
- PHP: `>= 8.3`
- Entry point: `src/BoutDeCodeETLCoreBundle.php`
- DI extension: `src/DependencyInjection/ETLCoreExtension.php`
- Config tree key: `boutdecode_etl_core`

This is a **bundle, not a Symfony application**. There is no `bin/console`, no `.env`, no `config/` app directory, no `public/` folder. Do not add any of those.

---

## Source layout

```
src/
├── ETLCoreBundle.php               # Bundle class — registers autoconfiguration tags
├── DependencyInjection/
│   ├── ETLCoreExtension.php        # Loads services.yaml, exposes config params
│   └── Configuration.php           # Config tree (boutdecode_etl_core:)
├── Resources/config/
│   ├── services.yaml               # Service wiring & tagged iterators
│   └── packages/
│       ├── messenger.yaml          # 3 buses (command/query/event) + routing
│       ├── workflow.yaml           # pipeline_lifecycle state machine
│       └── doctrine.yaml           # ORM entity mappings
├── Core/                           # Central domain: Pipeline, Step, Workflow, Context
│   ├── Domain/
│   │   ├── DTO/                    # Context (the central pass-through object)
│   │   ├── Enum/                   # PipelineStatus, etc.
│   │   ├── Model/                  # Interfaces + abstract base classes
│   │   ├── Factory/
│   │   └── Data/
│   └── Infrastructure/
│       └── Persistence/ORM/        # Doctrine entities & repositories
├── ETL/                            # ETL-specific logic
│   ├── Domain/
│   │   ├── Model/                  # AbstractExtractorStep, AbstractTransformerStep, AbstractLoaderStep + interfaces
│   │   └── Resolver/               # StepResolver / DefaultStepResolver
│   └── Infrastructure/
│       └── Step/
│           ├── Extractor/          # CsvFileExtractStep, ApiExtractStep, XmlFileExtractStep
│           ├── Transformer/        # FilterTransformStep, DataMappingTransformStep
│           └── Loader/             # JsonFileLoadStep, DatabaseLoadStep
├── Run/                            # Execution engine
│   ├── Domain/
│   │   ├── Runner/                 # PipelineRunner interface + DefaultPipelineRunner
│   │   ├── Middleware/             # Middleware interface, chains, CycleLife (Pipeline + Step)
│   │   ├── Specification/
│   │   ├── Workflow/
│   │   ├── Enum/
│   │   ├── Model/
│   │   ├── Factory/
│   │   ├── Data/
│   │   └── Instrumentation/
│   └── Infrastructure/
│       ├── Persistence/ORM/        # PipelineHistory, StepHistory entities & repos
│       ├── Scheduler/              # PipelineScheduler (Symfony Scheduler)
│       ├── Workflow/               # Symfony Workflow integration
│       └── Instrumentation/
└── CQS/                            # Command / Query Separation
    ├── Application/
    │   ├── Operation/
    │   │   ├── Command/            # CommandBus interface, CommandHandler, Command, AsyncCommand
    │   │   └── Query/              # QueryBus interface, QueryHandler, Query, AsyncQuery
    │   ├── Exception/
    │   └── Instrumentation/
    └── Infrastructure/
        └── Messenger/              # MessengerCommandBus, MessengerQueryBus
```

---

## Architectural rules

### Namespace
- **All** classes under `src/` must use namespace `BoutDeCode\ETLCoreBundle\...`
- **All** test classes under `tests/` must use namespace `BoutDeCode\ETLCoreBundle\Tests\...`
- Never use `App\` or any other root namespace.

### DDD layer boundaries
- Interfaces and abstract base classes live in `*/Domain/Model/` or `*/Domain/`.
- Concrete implementations live in `*/Infrastructure/`.
- The `Application/` layer holds command/query handlers and orchestration only — no Doctrine, no HTTP.
- `Domain/` layers must not depend on `Infrastructure/` layers.
- The `Context` DTO (`Core\Domain\DTO\Context`) is the sole object passed between steps.

### Bundle rules
- Do **not** create a `Kernel.php`, `config/` app directory, `public/`, `.env`, or any Symfony application scaffolding.
- Do **not** add `#[AsMessageHandler]` to handlers — the bundle's `build()` method handles autoconfiguration via `registerForAutoconfiguration`.
- Do **not** reference `Sylius\Resource\*` or `Sylius\Component\*` — those are UI-layer concerns excluded from this bundle.
- All `declare(strict_types=1);` must be present at the top of every PHP file.

### CQS
- Commands modify state, return `void`.
- Queries read state, return data.
- Implement `AsyncCommand` or `AsyncQuery` to opt into async Messenger transport.
- Handler classes implement `CommandHandler` or `QueryHandler` — no attribute needed.

### Middleware
- Implement `BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware`.
- Tag with `boutdecode_etl_core.pipeline_middleware` (pipeline-level) or `boutdecode_etl_core.step_middleware` (step-level).
- Use `priority` to control ordering (higher runs first). Built-in range: -100 to 100.

### ETL steps
- Extend `AbstractExtractorStep`, `AbstractTransformerStep`, or `AbstractLoaderStep`.
- All `ExecutableStep` implementations are auto-tagged `boutdecode_etl_core.executable_step` via `_instanceof`.
- Steps communicate exclusively through `Context::getInput()` / `Context::setResult()` / `Context::getConfigurationValue()`.

---

## Setup

```bash
composer install
```

No database, no server, no `.env` needed for the bundle itself.

---

## Testing

**Always run all three quality checks before finishing a task.** All must pass with zero errors.

### 1. Code style — ECS

```bash
composer ecs
# or
./vendor/bin/ecs check
```

### 2. Static analysis — PHPStan

```bash
composer phpstan
# or
./vendor/bin/phpstan analyse --no-progress
```

### 3. Tests — PHPUnit

```bash
# All tests
composer test

# Unit tests only (fast — no I/O)
composer test:unit
# or
./vendor/bin/phpunit --colors=always --testsuite=Unit

# Integration tests only
composer test:integration
# or
./vendor/bin/phpunit --colors=always --testsuite=Integration
```

- `phpunit.dist.xml` is configured with `failOnDeprecation`, `failOnNotice`, `failOnWarning` — all three must pass.
- Current baseline: **243 unit tests, 3 integration tests, 0 failures**.

### Test conventions
- One test class per source class, mirroring the `src/` structure under `tests/Unit/` or `tests/Integration/`.
- Use PHPUnit mocks (`$this->createMock()`) for all external dependencies.
- No `App\` FQCNs anywhere in test files — always use the full `BoutDeCode\ETLCoreBundle\` namespace, including inside `assertInstanceOf(\BoutDeCode\ETLCoreBundle\...\Foo::class, ...)` calls.
- Tests must not depend on a running database, network, or filesystem (unit suite).

---

## Code style

- PSR-12 code style.
- `declare(strict_types=1);` on every PHP file.
- PascalCase for classes; camelCase for methods and variables.
- Explicit suffixes: `Command`, `Query`, `Handler`, `Step`, `Factory`, `Middleware`, `Repository`.
- Explicit prefixes for base classes: `Abstract`, `Default`.
- Type hints everywhere — no `mixed` unless unavoidable, no untyped properties.
- PHPDoc only when the type system cannot express the information (e.g. `@param array<string, mixed>`).

---

## Service configuration

- Services are auto-wired and auto-configured via `src/Resources/config/services.yaml`.
- The three Messenger bus IDs are: `command.bus`, `query.bus`, `event.bus`.
- Tagged iterator IDs: `boutdecode_etl_core.executable_step`, `boutdecode_etl_core.step_middleware`, `boutdecode_etl_core.pipeline_middleware`.
- When adding a new built-in middleware, register it explicitly in `services.yaml` with its tag and priority — do not rely solely on autowiring for tagged services.

---

## What NOT to do

- Do not add `symfony/security-*` or any UI bundle (`SyliusAdminUiBundle`, `TwigBundle` for admin, etc.) as a dependency.
- Do not commit `.env`, `var/`, `public/`, `config/` (app-level), or migration files.
- Do not introduce coupling between `ETL/`, `Run/`, and `CQS/` submodules at the domain level — they communicate only through interfaces defined in `Core/`.
- Do not use `sleep()`, real HTTP calls, or real filesystem writes in unit tests.
