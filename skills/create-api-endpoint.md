# Skill: Crear API Endpoint

**Descripción**: Workflow para crear un endpoint REST con validación, documentación y tests.

## Entrada

```
/skill:create-api-endpoint "endpoint_name" --method="GET|POST" --resource="resource_type" --returns="json|xml"
```

## Salida esperada

```
web/modules/custom/MODULO/
├── src/
│   └── Plugin/
│       └── rest/
│           └── resource/
│               └── MyEndpointResource.php
├── config/install/
│   └── rest.resource.my_endpoint.yml
├── tests/
│   └── src/Functional/
│       └── MyEndpointTest.php
└── README.md (actualizado)
```

## Ejemplo: GET /api/campaigns

### 1. Crear la clase Resource

```php
// src/Plugin/rest/resource/CampaignsResource.php
namespace Drupal\campaigns\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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

  protected $entityTypeManager;

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   */
  public function get() {
    // Security: Check permission
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'campaign')
      ->condition('status', 1);

    $nids = $query->execute();
    $campaigns = $storage->loadMultiple($nids);

    $data = [];
    foreach ($campaigns as $campaign) {
      $data[] = [
        'id' => $campaign->id(),
        'title' => $campaign->getTitle(),
        'description' => $campaign->body->value ?? '',
        'created' => $campaign->getCreatedTime(),
      ];
    }

    $response = new ResourceResponse($data);
    $response->addCacheableDependency($campaigns);
    return $response;
  }
}
```

### 2. Configurar REST resource

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

### 3. Crear test funcional

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
    
    // Create test campaign
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

  public function testGetCampaignsUnauthorized() {
    // Test sin autenticación
    $url = $this->baseUrl . '/api/campaigns';
    $response = $this->httpClient->request('GET', $url);
    
    // Si se requiere auth
    // $this->assertEquals(403, $response->getStatusCode());
  }
}
```

### 4. Documentación

En README.md agregar:
```markdown
## API Endpoints

### GET /api/campaigns

Retorna lista de campañas.

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
❌ BAD: Sin validación
public function post($data) {
  $node = Node::create(['type' => 'campaign', 'title' => $data]);
  $node->save();
  return new ResourceResponse($node);
}

✓ GOOD: Con validación
public function post($data) {
  if (!$this->currentUser->hasPermission('create campaign content')) {
    throw new AccessDeniedHttpException();
  }
  
  if (empty($data['title'])) {
    throw new BadRequestHttpException('Title is required');
  }
  
  $node = Node::create([
    'type' => 'campaign',
    'title' => (string) $data['title'],
    'uid' => $this->currentUser->id(),
  ]);
  
  $violations = $node->validate();
  if ($violations->count() > 0) {
    throw new UnprocessableEntityHttpException((string) $violations);
  }
  
  $node->save();
  return new ResourceResponse($node, 201);
}
```

## Variaciones

### POST endpoint (crear recurso)

```php
public function post($data) {
  // Validar input
  // Crear entidad
  // Retornar 201 Created
  return new ResourceResponse($entity, 201);
}
```

### PATCH endpoint (actualizar recurso)

```php
public function patch($id, $data) {
  // Cargar entidad
  // Actualizar campos
  // Validar
  // Guardar
  return new ResourceResponse($entity, 200);
}
```

### DELETE endpoint

```php
public function delete($id) {
  // Cargar entidad
  // Verificar permiso
  // Eliminar
  return new ResourceResponse(null, 204);
}
```

## Checklist

- [ ] Clase Resource creada con @RestResource
- [ ] Métodos implementados (GET, POST, etc.)
- [ ] Validación de permisos (AccessDeniedHttpException)
- [ ] Input validado (BadRequestHttpException)
- [ ] Response con formato correcto (JSON)
- [ ] Tests funcionales creando/deletando
- [ ] Security Review completado
- [ ] Documentación en README
- [ ] Configuración rest.resource.*.yml

## Referencias

- [REST API](https://www.drupal.org/docs/8/api/rest-api)
- [Secure API](https://www.drupal.org/docs/drupal-apis/security)
- [Testing REST](https://www.drupal.org/docs/automated-testing/simpletests-1)
