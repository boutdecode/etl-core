# Analyse de compatibilité PHP

**Date :** 22 février 2026  
**Codebase :** `boutdecode/etl-core-bundle`  
**Version PHP déclarée :** `>=8.3`  
**Objectif :** Déterminer si la contrainte PHP peut être abaissée à `>=8.0` ou `>=8.1`

---

## Verdict

> **Le plancher réel est PHP 8.3. Il est impossible de descendre sans refactoring.**

Le code utilise des fonctionnalités introduites à chaque version mineure depuis PHP 8.0 jusqu'à PHP 8.3. La contrainte actuelle dans `composer.json` est donc correcte et justifiée.

---

## Fonctionnalités par version

### PHP 8.3 — Bloquant principal : constantes de classe typées

La fonctionnalité `typed class constants` (ex. `public const string CODE = '...'`) est exclusive à PHP 8.3. Elle est utilisée dans **9 fichiers** :

| Fichier | Constante |
|---|---|
| `src/ETL/Infrastructure/Step/Extractor/ApiExtractStep.php` | `public const string CODE` |
| `src/ETL/Infrastructure/Step/Extractor/CsvFileExtractStep.php` | `public const string CODE` |
| `src/ETL/Infrastructure/Step/Extractor/XmlFileExtractStep.php` | `public const string CODE` |
| `src/ETL/Infrastructure/Step/Transformer/FilterTransformStep.php` | `public const string CODE` |
| `src/ETL/Infrastructure/Step/Transformer/DataMappingTransformStep.php` | `public const string CODE` |
| `src/ETL/Infrastructure/Step/Loader/JsonFileLoadStep.php` | `public const string CODE` |
| `src/ETL/Infrastructure/Step/Loader/DatabaseLoadStep.php` | `public const string CODE` |
| `src/Run/Domain/Middleware/CycleLife/Step/StepHistoryMiddleware.php` | `public const string STEP_HISTORIES_CONFIG_KEY` |
| `src/CQS/Infrastructure/Instrumentation/Logger.php` | `public const string LOG_CHANNEL` |

---

### PHP 8.2 — `readonly class`

Le mot-clé `readonly class` (classe entièrement immutable, PHP 8.2) est utilisé dans **17 classes** :

| Fichier |
|---|
| `src/Run/Application/Operation/Command/ExecuteWorkflowCommand.php` |
| `src/Run/Application/Operation/Command/ExecuteWorkflowCommandHandler.php` |
| `src/Run/Domain/Runner/DefaultPipelineRunner.php` |
| `src/Run/Domain/Middleware/CycleLife/Pipeline/PipelineStartMiddleware.php` |
| `src/Run/Domain/Middleware/CycleLife/Pipeline/PipelineSuccessMiddleware.php` |
| `src/Run/Domain/Middleware/CycleLife/Pipeline/PipelineFailureMiddleware.php` |
| `src/Run/Domain/Middleware/CycleLife/Pipeline/PipelineProcessMiddleware.php` |
| `src/Run/Domain/Middleware/CycleLife/Pipeline/PipelineHistoryMiddleware.php` |
| `src/Run/Domain/Middleware/CycleLife/Step/StepStartMiddleware.php` |
| `src/Run/Domain/Middleware/CycleLife/Step/StepSuccessMiddleware.php` |
| `src/Run/Domain/Middleware/CycleLife/Step/StepFailureMiddleware.php` |
| `src/Run/Domain/Middleware/CycleLife/Step/StepHistoryMiddleware.php` |
| `src/Run/Infrastructure/Instrumentation/Logger.php` |
| `src/Run/Infrastructure/Scheduler/PipelineScheduler.php` |
| `src/Run/Infrastructure/Workflow/PipelineStateMachine.php` |
| `src/Run/Infrastructure/Workflow/PipelineStateMachineEventSubscriber.php` |
| `src/Core/Infrastructure/Persistence/ORM/Factory/PipelineFactory.php` |

---

### PHP 8.1 — Enums, propriétés `readonly`, `array_is_list()`

| Fonctionnalité | Fichier(s) |
|---|---|
| `enum` | `src/Core/Domain/Enum/PipelineStatus.php` |
| `enum` | `src/Run/Domain/Enum/PipelineHistoryStatusEnum.php` |
| `enum` | `src/Run/Domain/Enum/StepHistoryStatusEnum.php` |
| Enum comme valeur de propriété par défaut | `src/Core/Infrastructure/Persistence/ORM/Entity/Pipeline.php` |
| Constructor property promotion `readonly` | Généralisé dans tout `src/` |
| `array_is_list()` | `src/ETL/Infrastructure/Step/Extractor/ApiExtractStep.php` (lignes 92, 143) |
| Array spread avec clés string (`[...$arr]`) | `src/CQS/Infrastructure/Instrumentation/Logger.php` (ligne 61) |

---

### PHP 8.0 — Named arguments, `match`, `?->`, attributs, promotion

| Fonctionnalité | Fichier(s) représentatifs |
|---|---|
| Arguments nommés | `src/ETL/Infrastructure/Step/Extractor/CsvFileExtractStep.php`, `src/Core/Infrastructure/Persistence/ORM/Factory/PipelineFactory.php` |
| Expression `match` | `ApiExtractStep.php`, `DataMappingTransformStep.php`, `DatabaseLoadStep.php`, tests |
| Opérateur nullsafe `?->` | `Context.php`, tous les middlewares Step et Pipeline |
| `throw` comme expression | `ApiExtractStep.php`, `DatabaseLoadStep.php` |
| `catch` sans variable (`catch (Foo)`) | `DataMappingTransformStep.php` |
| Attributs PHP `#[...]` | Toutes les entités ORM, `PipelineScheduler.php`, `Logger.php` |
| Constructor property promotion | Généralisé dans tout `src/` |

---

## Tableau de faisabilité

| Cible | Faisabilité | Effort requis |
|---|---|---|
| `>=8.0` | ❌ Impossible | Réécriture totale : enums → classes, readonly → constructeurs manuels, typed constants → supprimées, match → switch, etc. |
| `>=8.1` | ❌ Impossible | Suppression de 17 `readonly class` + 9 typed constants + remplacement par des solutions manuelles |
| `>=8.2` | ⚠️ Techniquement possible | Supprimer le typage sur les 9 constantes de classe (`public const string X` → `public const X`) |
| `>=8.3` | ✅ Actuel — aucun changement | Contrainte correcte, aucune régression |

---

## Analyse du seul palier atteignable : PHP 8.2

Descendre à `>=8.2` ne nécessiterait que de **retirer le type des 9 constantes** :

```php
// Avant (PHP 8.3)
public const string CODE = 'csv_extract';

// Après (PHP 8.2 compatible)
public const CODE = 'csv_extract';
```

### Pourquoi ce n'est pas recommandé

1. **Gain nul en pratique** : PHP 8.2 arrive en fin de vie de ses mises à jour de sécurité fin 2026. Aucun projet sérieux ne devrait cibler PHP 8.2 pour une nouvelle bibliothèque en 2026.
2. **Perte de sécurité de type** : les constantes typées garantissent qu'une sous-classe ne peut pas redéfinir une constante avec un type incompatible. Les supprimer constitue un recul qualitatif.
3. **Cohérence avec l'écosystème** : `symfony/framework-bundle ^7.0` et `doctrine/orm ^3.0` recommandent eux-mêmes PHP 8.2+ comme baseline.

---

## Conclusion

La contrainte `php: >=8.3` dans `composer.json` est **correcte, justifiée et à conserver**. Le code fait un usage intentionnel et cohérent des fonctionnalités modernes de PHP (typed constants, readonly classes, enums) qui améliorent la solidité du typage statique et l'immutabilité des objets. Toute tentative d'abaisser cette contrainte représenterait un recul sans bénéfice concret.
