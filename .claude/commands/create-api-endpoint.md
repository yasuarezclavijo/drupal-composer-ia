# Skill: Crear API Endpoint

**Descripción**: Workflow para crear un endpoint REST con validación, documentación y tests, separando responsabilidades en tres capas: **Resource** (HTTP), **Service** (lógica de negocio) y **Repository** (acceso/transformación de datos).

## Entrada

```
/create-api-endpoint "endpoint_name" --method="GET|POST" --resource="resource_type" --returns="json|xml"
```

## Arquitectura de capas (obligatoria)

```
HTTP Request
   ↓
Resource (src/Plugin/rest/resource/)
   ├─ Verifica permisos (currentUser->hasPermission)
   ├─ Parsea/valida la FORMA del request (¿llegó "title"? ¿es array?)
   ├─ Delega en el Service
   ├─ Traduce excepciones de dominio → HTTP exceptions
   └─ Construye ResourceResponse + cache metadata
   ↓
Service (src/Service/)
   ├─ Reglas de negocio y validaciones de dominio
   ├─ Orquesta uno o varios Repository
   └─ Transforma entidades → estructuras de salida (arrays/DTOs)
   ↓
Repository (src/Repository/)
   ├─ Queries vía EntityTypeManager / Database
   ├─ load() / loadMultiple() / create() / save() / delete()
   └─ Sin reglas de negocio: solo acceso y forma de los datos
```

**Regla no negociable**: la clase `*Resource` NO debe contener `getQuery()`,
`loadMultiple()`, `EntityTypeManagerInterface`, lógica de transformación de
datos ni reglas de validación de negocio. Su única responsabilidad es la capa
HTTP (permisos, request/response, cache, mapeo de excepciones). Toda esa
lógica vive en `Service` y `Repository`, igual que se hizo en su momento para
sacar la lógica de negocio de los `Controller`.

## Salida esperada

```
web/modules/custom/MODULO/
├── MODULO.services.yml
├── src/
│   ├── Plugin/
│   │   └── rest/
│   │       └── resource/
│   │           └── MyEndpointResource.php      # Capa HTTP (delgada)
│   ├── Service/
│   │   └── MyEndpointService.php                # Lógica de negocio
│   └── Repository/
│       └── MyEndpointRepository.php             # Acceso/transformación de datos
├── config/install/
│   └── rest.resource.my_endpoint.yml
├── tests/
│   └── src/
│       ├── Unit/
│       │   └── Service/
│       │       └── MyEndpointServiceTest.php    # Mockea el Repository
│       └── Functional/
│           └── MyEndpointResourceTest.php       # End-to-end vía HTTP
└── README.md (actualizado)
```

## Ejemplo: GET /api/campaigns

### 1. Diseñar y escribir tests (TDD — red)

Antes de crear las clases, el TDD Specialist escribe **dos niveles** de test.

#### 1a. Unit test del Service (mockea el Repository)

`composer test` debe **fallar**: `CampaignsService` y `CampaignsRepository`
todavía no existen.

```php
// tests/src/Unit/Service/CampaignsServiceTest.php
namespace Drupal\Tests\campaigns\Unit\Service;

use Drupal\campaigns\Repository\CampaignsRepository;
use Drupal\campaigns\Service\CampaignsService;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\TestCase;

class CampaignsServiceTest extends TestCase {

  public function testGetCampaignsListTransformsNodes(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn(1);
    $node->method('getTitle')->willReturn('Test Campaign');
    $node->method('getCreatedTime')->willReturn(1717949280);
    $node->body = (object) ['value' => 'Test description'];

    $repository = $this->createMock(CampaignsRepository::class);
    $repository->expects($this->once())
      ->method('getPublishedCampaigns')
      ->willReturn([$node]);

    $service = new CampaignsService($repository);
    $result = $service->getCampaignsList();

    $this->assertSame([
      [
        'id' => 1,
        'title' => 'Test Campaign',
        'description' => 'Test description',
        'created' => 1717949280,
      ],
    ], $result['data']);
  }
}
```

#### 1b. Functional test del Resource (end-to-end vía HTTP)

`composer test` debe **fallar**: la ruta `/api/campaigns` aún no existe → 404.

```php
// tests/src/Functional/CampaignsResourceTest.php
namespace Drupal\Tests\campaigns\Functional;

use Drupal\Tests\rest\Functional\ResourceTestBase;
use Drupal\node\Entity\Node;

class CampaignsResourceTest extends ResourceTestBase {

  protected $defaultTheme = 'stark';
  protected static $modules = ['rest', 'campaigns'];
  protected $resourceConfigId = 'campaigns';

  protected function setUp(): void {
    parent::setUp();

    Node::create([
      'type' => 'campaign',
      'title' => 'Test Campaign',
      'body' => 'Test description',
      'status' => 1,
    ])->save();
  }

  public function testGetCampaigns() {
    $url = $this->baseUrl . '/api/campaigns';
    $response = $this->httpClient->request('GET', $url);

    $this->assertEquals(200, $response->getStatusCode());

    $data = json_decode($response->getBody(), true);
    $this->assertIsArray($data);
    $this->assertGreaterThan(0, count($data));
    $this->assertEquals('Test Campaign', $data[0]['title']);
  }
}
```

```bash
composer test
# ❌ FAIL esperado:
#   - Class "Drupal\campaigns\Service\CampaignsService" not found
#   - Class "Drupal\campaigns\Repository\CampaignsRepository" not found
#   - 404 Not Found en /api/campaigns
```

### 2. Implementar Repository → Service → Resource (TDD — green, máx. 3 intentos)

Implementar en este orden: primero el Repository (acceso a datos), luego el
Service (lógica de negocio que el Unit test ejercita), y por último el
Resource (capa HTTP delgada que el Functional test ejercita).

#### 2.1 Repository — acceso a datos

```php
// src/Repository/CampaignsRepository.php
namespace Drupal\campaigns\Repository;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Acceso a datos de nodos tipo "campaign".
 */
class CampaignsRepository {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns published campaign nodes.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Published campaign nodes, keyed by node ID.
   */
  public function getPublishedCampaigns(): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $nids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'campaign')
      ->condition('status', 1)
      ->execute();

    return $nids ? $storage->loadMultiple($nids) : [];
  }
}
```

#### 2.2 Service — lógica de negocio y transformación

```php
// src/Service/CampaignsService.php
namespace Drupal\campaigns\Service;

use Drupal\campaigns\Repository\CampaignsRepository;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Lógica de negocio para campañas expuestas vía API.
 */
class CampaignsService {

  public function __construct(
    protected CampaignsRepository $campaignsRepository,
  ) {}

  /**
   * Returns campaigns formatted for the API response.
   *
   * @return array{data: array<int, array<string, mixed>>, cacheable_metadata: \Drupal\Core\Cache\CacheableMetadata}
   *   The serializable data and the cache metadata for the response.
   */
  public function getCampaignsList(): array {
    $campaigns = $this->campaignsRepository->getPublishedCampaigns();

    $cacheable_metadata = new CacheableMetadata();
    $data = [];

    foreach ($campaigns as $campaign) {
      $cacheable_metadata->addCacheableDependency($campaign);
      $data[] = [
        'id' => (int) $campaign->id(),
        'title' => $campaign->getTitle(),
        'description' => $campaign->body->value ?? '',
        'created' => (int) $campaign->getCreatedTime(),
      ];
    }

    return ['data' => $data, 'cacheable_metadata' => $cacheable_metadata];
  }
}
```

#### 2.3 Resource — capa HTTP (delgada)

```php
// src/Plugin/rest/resource/CampaignsResource.php
namespace Drupal\campaigns\Plugin\rest\resource;

use Drupal\campaigns\Service\CampaignsService;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get campaigns.
 *
 * @RestResource(
 *   id = "campaigns",
 *   label = @Translation("Campaigns"),
 *   uri_paths = {
 *     "canonical" = "/api/campaigns"
 *   }
 * )
 */
class CampaignsResource extends ResourceBase {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected CampaignsService $campaignsService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('campaigns.campaigns_service'),
    );
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   */
  public function get() {
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $result = $this->campaignsService->getCampaignsList();

    $response = new ResourceResponse($result['data']);
    $response->addCacheableDependency($result['cacheable_metadata']);
    return $response;
  }
}
```

**campaigns.services.yml**:
```yaml
services:
  campaigns.campaigns_repository:
    class: Drupal\campaigns\Repository\CampaignsRepository
    arguments: ['@entity_type.manager']

  campaigns.campaigns_service:
    class: Drupal\campaigns\Service\CampaignsService
    arguments: ['@campaigns.campaigns_repository']
```

**config/install/rest.resource.campaigns.yml**:
```yaml
langcode: en
status: true
dependencies:
  module:
    - rest
    - campaigns
id: campaigns
plugin_id: campaigns
granularity: resource
configuration:
  methods:
    - GET
  formats:
    - json
  authentication:
    - basic_auth
```

**Límite de intentos**: cada `composer test` cuenta como un intento. Máximo **3**. Si tras el intento 3 algún test sigue en rojo: **detener** (no hacer un 4to intento), generar el "Resumen de bloqueo" (ver [agents/tdd-specialist.md](.claude/agents/tdd-specialist.md#template-resumen-de-bloqueo-3-intentos-sin-verde)), registrarlo en `docs/activity-log/` y esperar indicación del usuario.

### 3. Documentación

En README.md agregar:
```markdown
## API Endpoints

### GET /api/campaigns

Retorna lista de campañas. Lógica de negocio en
`Drupal\campaigns\Service\CampaignsService::getCampaignsList()`,
acceso a datos en `Drupal\campaigns\Repository\CampaignsRepository`.

**Response:**
```json
[
  {
    "id": 1,
    "title": "Campaign Name",
    "description": "Description",
    "created": 1717949280
  }
]
```

**Permissions:**
- Require: `access content`

**Authentication:**
- Optional

**Curl:**
```bash
curl http://example.com/api/campaigns
```
```

## Seguridad (Security Review)

```
❌ BAD: Resource con acceso a datos y reglas de negocio mezcladas con HTTP
public function post($data) {
  $node = Node::create(['type' => 'campaign', 'title' => $data['title']]);
  $node->save();
  return new ResourceResponse($node);
}

✓ GOOD: Resource delega en Service; Service valida y delega en Repository
```

```php
// Resource: solo permisos + delegación + mapeo de excepciones
public function post(array $data) {
  if (!$this->currentUser->hasPermission('create campaign content')) {
    throw new AccessDeniedHttpException();
  }

  try {
    $campaign = $this->campaignsService->createCampaign($data, $this->currentUser);
  }
  catch (\InvalidArgumentException $e) {
    throw new BadRequestHttpException($e->getMessage());
  }

  return new ResourceResponse(['id' => (int) $campaign->id()], 201);
}
```

```php
// Service: valida reglas de negocio y orquesta el Repository
public function createCampaign(array $input, AccountInterface $author): NodeInterface {
  if (empty($input['title'])) {
    throw new \InvalidArgumentException('Title is required');
  }

  $campaign = $this->campaignsRepository->create((string) $input['title'], (int) $author->id());

  $violations = $campaign->validate();
  if ($violations->count() > 0) {
    throw new \InvalidArgumentException((string) $violations);
  }

  $this->campaignsRepository->save($campaign);
  return $campaign;
}
```

```php
// Repository: solo construcción de la entidad y persistencia
public function create(string $title, int $uid): NodeInterface {
  return $this->entityTypeManager->getStorage('node')->create([
    'type' => 'campaign',
    'title' => $title,
    'uid' => $uid,
  ]);
}

public function save(NodeInterface $node): void {
  $node->save();
}
```

## Variaciones

Mismo patrón Resource → Service → Repository para cada verbo HTTP.

### POST endpoint (crear recurso)

- **Resource**: verifica permiso de creación, delega en `Service::createX($data, $currentUser)`, traduce `\InvalidArgumentException` → `BadRequestHttpException` (400) / `UnprocessableEntityHttpException` (422), retorna `ResourceResponse($entity, 201)`.
- **Service**: valida campos requeridos y reglas de negocio, llama `Repository::create()` + `$entity->validate()` + `Repository::save()`.
- **Repository**: `entityTypeManager->getStorage(...)->create([...])` y `save()`.

### PATCH endpoint (actualizar recurso)

- **Resource**: verifica permiso de edición, delega en `Service::updateX($id, $data)`, traduce excepción de "no encontrado" → `NotFoundHttpException` (404).
- **Service**: carga la entidad vía `Repository::findById($id)` (lanza excepción de dominio si es `NULL`), aplica los cambios permitidos, valida, llama `Repository::save()`.
- **Repository**: `findById($id)` (usa `load()`), `save($entity)`.

### DELETE endpoint

- **Resource**: verifica permiso de borrado, delega en `Service::deleteX($id)`, traduce excepción de "no encontrado" → `NotFoundHttpException` (404), retorna `ResourceResponse(NULL, 204)`.
- **Service**: carga la entidad vía `Repository::findById($id)`, aplica reglas de negocio adicionales si aplica (p.ej. no permitir borrar si tiene dependencias), llama `Repository::delete($entity)`.
- **Repository**: `findById($id)`, `delete($entity)`.

## Checklist

- [ ] Unit test del Service escrito y fallando primero (red, mockeando el Repository)
- [ ] Functional test del Resource escrito y fallando primero (red — 404)
- [ ] `Repository` creado: solo acceso/transformación de datos (`getQuery()`, `load*()`, `create()`, `save()`, `delete()`)
- [ ] `Service` creado: lógica de negocio, validaciones de dominio, orquesta el/los `Repository`
- [ ] `Resource` creado: SOLO capa HTTP (permisos, parseo de forma, mapeo de excepciones, `ResourceResponse`, cache)
- [ ] `MODULO.services.yml` registra `Repository` y `Service` con DI (`@entity_type.manager`, etc.)
- [ ] Configuración `rest.resource.*.yml`
- [ ] Métodos implementados (GET, POST, etc.) delegando en el `Service`
- [ ] Validación de permisos en `Resource` (`AccessDeniedHttpException`)
- [ ] Validación de negocio en `Service` (excepciones de dominio mapeadas a HTTP exceptions en `Resource`)
- [ ] Response con formato correcto (JSON)
- [ ] `composer test` en verde (Unit del Service + Functional del Resource)
- [ ] Security Review completado
- [ ] Documentación en README

## Referencias

- [REST API](https://www.drupal.org/docs/8/api/rest-api)
- [Services and DI](https://www.drupal.org/docs/8/api/services)
- [Secure API](https://www.drupal.org/docs/drupal-apis/security)
- [Testing REST](https://www.drupal.org/docs/automated-testing/simpletests-1)
