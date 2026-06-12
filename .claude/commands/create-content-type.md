# Skill: Crear Content Type

**Descripción**: Workflow para crear un content type (entidad de contenido) con campos, vistas y configuración.

## Entrada

```
/create-content-type "nombre" [--machine-name="nombre_maquina"] [--with-views] [--with-forms]
```

## Salida esperada

```
web/modules/custom/MODULO/
├── config/install/
│   └── node.type.nombre_maquina.yml
│       + field.field.node.nombre_maquina.*.yml
│       + field.storage.node.*.yml
├── config/schema/
│   └── nombre.schema.yml
└── templates/
    └── node--nombre-maquina.html.twig
```

## Proceso

### 1. Definir estructura de campos

Identificar:
- Campos requeridos vs opcionales
- Tipos de campo (text, number, entity_reference, etc.)
- Cardininalidad (uno o múltiples valores)

Ejemplo (tipo "campaña"):
```
Campos:
  - Title (core)
  - Description (text_long)
  - Start Date (datetime)
  - End Date (datetime)
  - Status (list_string: active, draft, archived)
  - Budget (number_decimal)
  - Manager (entity_reference -> user)
```

### 2. Diseñar y escribir tests (TDD — red)

Antes de crear la configuración (`node.type.*.yml`, `field.storage.*.yml`, `field.field.*.yml`), el TDD Specialist escribe un test Kernel que verifica que el content type y sus campos existen y que se puede crear un nodo válido. `composer test` debe **fallar** porque la configuración todavía no existe.

```php
// tests/src/Kernel/CampaignContentTypeTest.php
namespace Drupal\Tests\campaigns\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// Drupal 11.3+ marca KernelTestBase sin #[RunTestsInSeparateProcesses]
// como deprecated (será excepción en Drupal 12). Incluir siempre en
// tests Kernel nuevos.
#[RunTestsInSeparateProcesses]
final class CampaignContentTypeTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'field', 'text', 'datetime', 'node', 'campaigns'];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['field', 'node', 'campaigns']);
  }

  public function testCampaignContentTypeExists(): void {
    $type = \Drupal::entityTypeManager()->getStorage('node_type')->load('campaign');
    $this->assertNotNull($type, 'El content type "campaign" debe existir.');
  }

  public function testCampaignHasDescriptionField(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'campaign');
    $this->assertArrayHasKey('field_description', $fields);
  }

  public function testCreateCampaignNode(): void {
    $node = Node::create([
      'type' => 'campaign',
      'title' => 'Test Campaign',
      'field_description' => 'Descripción de prueba',
    ]);
    $this->assertCount(0, $node->validate());
  }
}
```

```bash
composer test
# ❌ FAIL esperado: el content type "campaign" no existe todavía
```

### 3. Crear configuración (TDD — green, máx. 3 intentos)

**node.type.NOMBRE.yml**:
```yaml
langcode: en
status: true
dependencies: {}
name: 'Campaign'
type: campaign
description: 'A marketing campaign.'
help: ''
new_revision: true
preview_mode: 1
display_submitted: true
```

**field.storage.node.CAMPO.yml** (definición de campo):
```yaml
langcode: en
status: true
dependencies:
  module:
    - node
id: node.campo_nombre
field_name: campo_nombre
entity_type: node
type: text_long
cardinality: 1
settings: {}
```

> ⚠️ **Excepción: campo `body`**. El perfil `standard` (perfil por defecto de
> Drupal core, usado también por Drupal CMS) ya provee `field.storage.node.body`
> globalmente. Si el content type
> usa el campo `body` (muy común), **NO** generar
> `config/install/field.storage.node.body.yml` — el módulo fallaría al
> habilitarse con:
> `Configuration objects (field.storage.node.body) provided by <módulo>
> already exist in active configuration`.
> En ese caso, crear **solo** `field.field.node.<bundle>.body.yml`,
> referenciando el storage global existente (`field.storage.node.body` en
> `dependencies.config`, sin incluir su `.yml` en `config/install/`). Ver
> [docs/architecture.md](../../docs/architecture.md#dependencia-implícita-del-perfil-standard).

**field.field.node.TIPO.CAMPO.yml** (instancia de campo):
```yaml
langcode: en
status: true
dependencies:
  config:
    - field.storage.node.campo_nombre
    - node.type.campaign
  module:
    - text
id: node.campaign.campo_nombre
field_name: campo_nombre
entity_type: node
bundle: campaign
label: 'Campaign Description'
description: ''
required: true
translatable: false
default_value: []
default_value_callback: ''
settings:
  rows: 5
```

**Límite de intentos**: cada `composer test` (ejecutando el test Kernel del paso anterior) cuenta como un intento. Máximo **3**. Si tras el intento 3 el test sigue en rojo: **detener**, generar el "Resumen de bloqueo" (ver [agents/tdd-specialist.md](.claude/agents/tdd-specialist.md#template-resumen-de-bloqueo-3-intentos-sin-verde)), registrarlo en `docs/activity-log/` y esperar indicación del usuario.

### 4. Crear template Twig

**templates/node--TIPO.html.twig**:
```twig
{%
  set classes = [
    'node',
    'node--' ~ node.bundle|clean_class,
    node.isPromoted() ? 'node--promoted' : '',
  ]
%}

<article{{ attributes.addClass(classes) }}>
  <header>
    {{ title_prefix }}
    <h1>{{ label }}</h1>
    {{ title_suffix }}
  </header>
  
  <div{{ content_attributes.addClass('content') }}>
    {{ content }}
  </div>
</article>
```

### 5. Crear vistas (opcional)

Si `--with-views`:

**views.view.NOMBRE.yml**:
```yaml
langcode: en
status: true
dependencies:
  config:
    - node.type.campaign
  module:
    - node
id: campaign_list
label: 'Campaign List'
description: 'A list of all campaigns'
tag: ''
base_table: node_field_data
base_field: nid
display:
  default:
    display_plugin: default
    id: default
    display_title: Master
    position: 0
    display_options:
      access:
        type: perm
        options:
          permission: 'access content'
      cache:
        type: tag
        options: {}
      query:
        type: views_query
        options:
          disable_sql_rewrite: false
          distinct: false
          replica: false
          query_tags: []
      exposed_form:
        type: basic
        options:
          submit_button: Apply
          reset_button: true
          reset_button_label: Reset
          delete_user_filters: false
          hide_admin_filters: false
          operator_limit_selection: false
          operator_list: []
      pager:
        type: mini
        options:
          items_per_page: 10
          offset: 0
          id: 0
          total_pages: null
          expose:
            items_per_page: false
          tags:
            previous: '‹ previous'
            next: 'next ›'
            first: '« first'
            last: 'last »'
      style:
        type: table
        options:
          grouping: []
          row_class: ''
          default_row_class: true
          columns:
            title: title
            created: created
            status: status
          default: title
          info:
            title:
              sortable: true
              default_sort_order: asc
              align: ''
              separator: ''
              empty_column: false
            created:
              sortable: true
              default_sort_order: desc
            status:
              sortable: false
          override: true
          sticky: true
          summary: ''
          empty_table: false
      row:
        type: node
        options:
          view_mode: teaser
      fields:
        title:
          id: title
          table: node_field_data
          field: title
          entity_type: node
          entity_field: title
          label: Title
          element_type: ''
          element_class: ''
          alter:
            alter_text: false
          hide_empty: false
          empty: ''
          hide_alter_empty: true
          link_to_node: true
        created:
          id: created
          table: node_field_data
          field: created
          entity_type: node
          entity_field: created
          label: Created
          settings:
            date_format: short
        status:
          id: status
          table: node_field_data
          field: status
          entity_type: node
          entity_field: status
          label: Status
      filters:
        status:
          id: status
          table: node_field_data
          field: status
          value: '1'
          operator: '='
        type:
          id: type
          table: node_field_data
          field: type
          value:
            campaign: campaign
  page:
    display_plugin: page
    id: page
    display_title: Page
    position: 1
    display_options:
      path: admin/content/campaigns
      menu:
        type: normal
        title: Campaigns
        parent: system.admin_content
        weight: 0
```

### 6. Configuración de campos (schema)

**config/schema/MODULO.schema.yml**:
```yaml
node.type.campaign:
  type: config_entity
  label: 'Campaign type'
  mapping:
    name:
      type: label
      label: 'Campaign name'
    description:
      type: text
      label: 'Campaign description'

field.field.node.campaign.campo_nombre:
  type: field.field_config
  label: 'Campaign description field'
  mapping:
    settings:
      type: mapping
      label: 'Settings'
```

### 7. Validar y exportar

```bash
# 1. Crear el content type en UI admin
# 2. Agregar campos
# 3. Exportar configuración
drush config:export

# 4. Mover a web/modules/custom/MODULO/config/install/

# 5. Validar (incluye el test Kernel del paso 2, ahora en verde)
composer lint:phpcs
composer lint:phpstan
composer test

# 6. Commit
git add web/modules/custom/MODULO/config/
git commit -m "feat: crear content type campaign"
```

## Template (Mínimo)

```bash
# Supone que ya existe un módulo
# web/modules/custom/campaigns/

# 1. Crear config directory
mkdir -p web/modules/custom/campaigns/config/install
mkdir -p web/modules/custom/campaigns/config/schema
mkdir -p web/modules/custom/campaigns/templates

# 2. Crear node.type.campaign.yml
cat > web/modules/custom/campaigns/config/install/node.type.campaign.yml <<EOF
langcode: en
status: true
dependencies: {}
name: 'Campaign'
type: campaign
description: 'A marketing campaign'
help: ''
new_revision: true
preview_mode: 1
display_submitted: true
EOF

# 3. Crear fields (ejemplo: title)
# Nota: los campos de entidad ya existen (title, created, uid)
# Solo agregar campos custom

# 4. Crear template
mkdir -p web/modules/custom/campaigns/templates
cat > web/modules/custom/campaigns/templates/node--campaign.html.twig <<EOF
{{ title_prefix }}
<h1>{{ label }}</h1>
{{ title_suffix }}
<div class="node-content">
  {{ content }}
</div>
EOF

# 5. Instalar módulo
drush en campaigns

# 6. Validar
composer qa
```

## Variaciones

### Content type con formulario personalizado

Igual que un REST Resource, el `Form` es capa HTTP: construye el formulario y
delega cualquier creación/actualización de datos en un `Service` (que a su
vez usa un `Repository`). El `submitForm()` no debe contener
`EntityTypeManager`, `getQuery()` ni reglas de negocio — ver
[create-api-endpoint](create-api-endpoint.md#arquitectura-de-capas-obligatoria).

```php
// src/Form/CampaignForm.php
namespace Drupal\campaigns\Form;

use Drupal\campaigns\Service\CampaignsService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CampaignForm extends FormBase {

  public function __construct(
    protected CampaignsService $campaignsService,
  ) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('campaigns.campaigns_service'));
  }

  public function getFormId() {
    return 'campaign_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campaign name'),
      '#required' => true,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->campaignsService->createCampaign(
      ['title' => $form_state->getValue('name')],
      $this->currentUser(),
    );

    $this->messenger()->addMessage($this->t('Saved!'));
  }
}
```

### Content type con relaciones

```yaml
# field.storage.node.campaign_manager.yml
type: entity_reference
settings:
  target_type: user

# field.field.node.campaign.campaign_manager.yml
settings:
  handler: 'default:user'
  handler_settings:
    target_bundles:
      user: user
    auto_create: false
```

## Checklist

- [ ] Nombre y machine_name definidos
- [ ] Campos identificados (tipos, cardinality)
- [ ] Test Kernel escrito y fallando primero (red)
- [ ] node.type.NOMBRE.yml creado (green, máx. 3 intentos)
- [ ] field.storage.* creados para campos custom (si el campo es `body`, NO incluir field.storage.node.body.yml — ver nota en paso 3)
- [ ] field.field.* creados para campos custom
- [ ] templates/node--TIPO.html.twig creado
- [ ] config/schema/ definido
- [ ] (Opcional) views creadas
- [ ] Validaciones pasando (incluye composer test en verde)
- [ ] Documentación en README

## Referencias

- [Content Types](https://www.drupal.org/docs/8/api/node-api)
- [Field API](https://www.drupal.org/docs/8/api/field-api)
- [Views](https://www.drupal.org/project/views)
- [Theming](https://www.drupal.org/docs/8/theming)
