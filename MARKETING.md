# ETLCoreBundle — Dossier Marketing

---

## Qu'est-ce que l'ETL ?

**ETL** est l'acronyme de **Extract, Transform, Load** — soit *Extraire, Transformer, Charger*. C'est un paradigme fondamental du traitement de données, utilisé chaque jour par des milliers d'entreprises pour déplacer et préparer leurs données.

| Phase | Rôle |
|---|---|
| **Extraire** | Récupérer des données brutes depuis une source : fichier CSV, API REST, fichier XML, base de données… |
| **Transformer** | Nettoyer, filtrer, reformater et enrichir les données pour les rendre exploitables. |
| **Charger** | Écrire les données transformées vers une destination : fichier JSON, base de données, service tiers… |

Sans pipeline ETL, les équipes écrivent du code d'intégration ad hoc, fragile, non observable et impossible à réutiliser. Avec un moteur ETL structuré, chaque flux de données devient une **brique réutilisable, testable et monitorable**.

---

## Le Projet : ETLCoreBundle

> **Un moteur ETL production-ready, directement intégré dans votre application Symfony.**

`boutdecode/etl-core-bundle` est un **Symfony Bundle** open-source qui apporte un moteur de pipeline ETL complet, extensible et prêt pour la production. Il s'intègre nativement dans votre stack Symfony existante — pas d'infrastructure séparée, pas de nouveau service à opérer.

Que vous ayez besoin de synchroniser des données entre deux systèmes, d'importer des fichiers en masse, ou de transformer des flux d'API en temps réel, ETLCoreBundle vous donne le cadre pour le faire proprement, en PHP, avec les outils Symfony que vous connaissez déjà.

```
PHP >= 8.2 · Symfony ^6.4 || ^7.0 · Licence MIT
```

---

## Accroche

**Arrêtez de ré-écrire la même plomberie d'intégration. Concentrez-vous sur vos règles métier.**

Chaque projet finit par avoir ses scripts d'import CSV bricolés, ses appels API copiés-collés, ses transformations de données sans tests. ETLCoreBundle remplace tout ça par une architecture cohérente : des pipelines déclaratifs, un cycle de vie supervisé, une observabilité de premier ordre — et une intégration Symfony si naturelle qu'elle disparaît dans votre projet.

---

## Fonctionnalités

### Extracteurs intégrés

| Step | Code | Description |
|---|---|---|
| **CSV** | `etl.extractor.csv_file` | Lecture de fichiers CSV avec support du délimiteur, de l'en-tête, de l'enclosure et de l'échappement. Streaming mémoire-efficient via [Flow-PHP](https://github.com/flow-php/flow). |
| **API REST** | `etl.extractor.api` | Appels HTTP (GET, POST, …) avec gestion des en-têtes, du timeout et parsing automatique des réponses JSON, XML, CSV ou texte brut. |
| **XML** | `etl.extractor.xml_file` | Lecture de fichiers XML avec configuration du nœud racine, du nœud d'enregistrement et support des attributs XML. |

### Transformateurs intégrés

| Step | Code | Description |
|---|---|---|
| **Filtre** | `etl.transformer.filter` | Filtrage d'enregistrements via le **Symfony Expression Language** (`item.age >= 25`, `item.status == 'active'`…). |
| **Mapping de données** | `etl.transformer.data_mapping` | Renommage et transformation de champs : `upper`, `lower`, `trim`, `int`, `float`, `bool`, `date`, `json_decode`, ou n'importe quel callable PHP. Suppression optionnelle des champs non mappés. |

### Chargeurs intégrés

| Step | Code | Description |
|---|---|---|
| **JSON** | `etl.loader.json_file` | Écriture vers un fichier JSON avec mode `overwrite`, via Flow-PHP. |

### Middleware pipeline & step

Un système de **middleware en chaîne** (inspiré PSR-15) opère à deux niveaux : autour du pipeline entier, et autour de chaque step individuel. Les middlewares intégrés gèrent :

- **Démarrage & succès** — logging structuré à chaque étape.
- **Gestion des erreurs** — capture de toutes les exceptions, isolation des erreurs par step (un step en erreur n'interrompt pas les suivants, il enregistre l'erreur dans le `Context`).
- **Persistance de l'historique** — chaque exécution de pipeline et de step est tracée en base avec son statut, son input et son résultat.
- **Transitions d'état** — chaque event du pipeline met à jour la machine à états automatiquement.

Ajoutez vos propres middlewares en implémentant une interface et en ajoutant un tag Symfony — aucune modification du bundle nécessaire.

### Cycle de vie supervisé (Symfony Workflow)

Le bundle embarque une **machine à états** `pipeline_lifecycle` via le composant Symfony Workflow :

```
[pending] ──► [in_progress] ──► [completed]
                    │                │
                   ▼                ▼
                [failed] ◄──── reset ──► [pending]
                    │
                 restart
                    │
               [in_progress]
```

Chaque transition est auditée et loggée automatiquement. Les transitions `complete`, `fail`, `reset` et `restart` sont déclenchées par les middlewares — aucune intervention manuelle requise.

### Planification automatique (Symfony Scheduler)

Le `PipelineScheduler` tourne **toutes les minutes** sur le scheduler `etl`. Il récupère tous les pipelines planifiés et dispatche une commande `ExecuteWorkflowCommand` (asynchrone) pour chacun via le bus de messages Symfony Messenger. L'exécution est naturellement **asynchrone et découplée** de la requête HTTP.

### Système CQS (Command/Query Separation)

- `CommandBus` et `QueryBus` wrappent Symfony Messenger avec un logging complet.
- Les commandes (`Command`) et requêtes (`Query`) sont synchrones par défaut.
- Implémenter `AsyncCommand` ou `AsyncQuery` les route automatiquement vers le transport `async` — sans aucune configuration supplémentaire.
- Aucun attribut `#[AsMessageHandler]` à écrire : l'autoconfiguration est gérée par le bundle.

### Observabilité & Métriques

- **Canal Monolog dédié** (`pipeline`) — toutes les logs du moteur sont séparées des logs applicatifs, avec le contexte complet (`Context` sérialisé).
- **MetricsCollector** en mémoire — collecte automatiquement :
  - Compteurs : `pipeline.started`, `pipeline.completed`, `pipeline.failed`, `step.started`, `step.completed`, `step.failed`.
  - Timings : durée min/max/avg des pipelines et des steps.
  - Gauges : records traités par step, débit (records/seconde).
- Snapshot complet disponible via `getMetrics()` — prêt à être exposé vers Prometheus, Datadog, ou tout autre backend.

### Architecture extensible (Port & Adapter)

La couche de persistance est entièrement abstraite derrière des **interfaces de domaine**. Le bundle ne fournit aucune entité Doctrine — vous branchez vos propres implémentations (`PipelinePersister`, `PipelineProvider`, `PipelineHistoryPersister`, etc.). Utilisez Doctrine ORM, un autre ORM, ou un store en mémoire pour les tests — le moteur s'en fiche.

---

## FAQ

### Pourquoi utiliser ETLCoreBundle plutôt que des scripts PHP artisanaux ?

Les scripts ad hoc fonctionnent pour le prototype. Dès que la logique métier se complexifie — plusieurs sources, règles de transformation changeantes, besoin de rejouer des exécutions passées — ils deviennent impossibles à maintenir. ETLCoreBundle apporte la structure dès le premier jour : cycle de vie tracé, erreurs isolées, historique en base, planification intégrée.

### Pourquoi pas un outil ETL dédié (Talend, Airbyte, Meltano…) ?

Les outils ETL standalone sont excellents pour les équipes Data. Mais si vous êtes une équipe PHP/Symfony qui veut garder la logique de transformation **dans le code applicatif**, avec le même pipeline CI, les mêmes tests PHPUnit, le même déploiement — ETLCoreBundle est le bon choix. Pas de nouveau service à opérer, pas de DSL propriétaire à apprendre.

### Mon projet a déjà Symfony Messenger et Workflow. Est-ce compatible ?

Oui. Le bundle configure ses propres bus (`boutdecode_etl_core.command.bus`, `boutdecode_etl_core.query.bus`) et sa propre machine à états (`pipeline_lifecycle`) — ils n'interfèrent pas avec vos buses et workflows existants.

### Comment ajouter un extracteur métier spécifique à mon projet ?

Étendez `AbstractExtractorStep`, implémentez `extract(Context): mixed`, définissez votre `CODE` de step. Le bundle auto-configure votre classe comme `boutdecode_etl_core.executable_step` via l'interface `ExecutableStep`. C'est tout.

### Les pipelines peuvent-ils tourner en parallèle ?

Oui. Chaque `ExecuteWorkflowCommand` est une commande asynchrone indépendante. Avec plusieurs workers Messenger actifs, plusieurs pipelines s'exécutent en parallèle. Le runner clone chaque step avant exécution pour garantir l'isolation entre les runs.

### La couche de persistance est-elle obligatoire ?

Non. L'historique et la persistance d'état sont gérés par des middlewares que vous pouvez désactiver ou remplacer. Pour un usage sans base de données, implémentez des persisters no-op ou en mémoire — la mécanique du pipeline fonctionne indépendamment.

### Quel est l'impact mémoire sur des fichiers volumineux ?

Pour les CSV et JSON, le bundle délègue à **Flow-PHP** qui lit les fichiers en streaming sans charger l'intégralité en mémoire. Pour les APIs et XML, les données sont chargées en mémoire — adaptez la taille des batches en conséquence.

### Peut-on tester un pipeline sans base de données ni HTTP réel ?

Oui. L'architecture Port & Adapter permet de substituer toutes les dépendances I/O par des mocks ou des fakes dans les tests. La suite de tests du bundle elle-même (297 tests unitaires) ne touche ni base de données, ni réseau, ni filesystem.

### Quelles versions PHP et Symfony sont supportées ?

PHP **>= 8.2** et Symfony **^6.4 ou ^7.0**. Le bundle utilise les fonctionnalités modernes de PHP 8.2+ (enums, `readonly class`, named arguments) — pas de rétrocompatibilité avec des versions antérieures.

### La licence permet-elle un usage commercial ?

Oui. Le bundle est publié sous licence **MIT** — utilisation commerciale libre, sans restrictions.

---

*Rapport généré le 13 mars 2026.*
