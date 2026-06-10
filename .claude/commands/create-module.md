# Skill: Crear módulo Drupal

**Descripción**: Workflow completo para crear un módulo Drupal 11 custom con estructura, tests y documentación.

## Entrada

```
/create-module "nombre_del_modulo" [--description="descripción"] [--api-only] [--with-tests]
```

## Salida esperada

```
web/modules/custom/nombre_del_modulo/
├── nombre_del_modulo.info.yml       # Metadata
├── nombre_del_modulo.module          # Hooks (si aplica)
├── src/
│   ├── Controller/
│   ├── Plugin/
│   ├── Form/
│   └── Service/
├── config/install/
├── config/schema/
├── templates/
├── tests/
│   ├── src/Unit/
│   └── src/Functional/
├── README.md                         # Documentación
└── CHANGELOG.md                      # Historial
```

## Proceso paso a paso

### 1. Validación del nombre

- ✓ Nombre en snake_case
- ✓ No colisiona con módulos core
- ✓ No colisiona con módulos contrib disponibles
- ✓ Máximo 31 caracteres (limitación Drupal)

### 2. Crear estructura de directorios

```bash
mkdir -p web/modules/custom/nombre_del_modulo/{src,config/install,config/schema,templates,tests/src/{Unit,Functional}}
touch web/modules/custom/nombre_del_modulo/{src/.gitkeep,config/.gitkeep}
```

### 3. Generar nombre_del_modulo.info.yml

```yaml
name: 'Human Readable Module Name'
description: 'Description of what the module does.'
package: 'Custom'
type: module
core_version_requirement: '^11.0'

# Dependencias si aplica
# dependencies:
#   - drupal:views
#   - drupal:webform
```

### 4. Diseñar y escribir tests (TDD — red)

**Antes de crear cualquier clase de producción**, el TDD Specialist diseña los casos de prueba y escribe los tests. `composer test` debe **fallar** porque las clases todavía no existen.

**Unit tests** (lógica pura):
```php
// tests/src/Unit/MyModuleServiceTest.php
namespace Drupal\Tests\nombre_del_modulo\Unit;

use PHPUnit\Framework\TestCase;
use Drupal\nombre_del_modulo\Service\MyModuleService;

class MyModuleServiceTest extends TestCase {
  public function testCalculate() {
    $service = new MyModuleService();
    $result = $service->calculate(5, 3);
    $this->assertEquals(8, $result);
  }
}
```

**Functional tests** (integración con Drupal):
```php
// tests/src/Functional/MyModuleTest.php
namespace Drupal\Tests\nombre_del_modulo\Functional;

use Drupal\Tests\BrowserTestBase;

class MyModuleTest extends BrowserTestBase {
  protected $defaultTheme = 'stark';
  
  protected static $modules = ['nombre_del_modulo'];
  
  public function testModuleInstallation() {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('nombre_del_modulo'));
  }
}
```

```bash
composer test
# ❌ FAIL esperado: Class "Drupal\nombre_del_modulo\Service\MyModuleService" not found
```

### 5. Implementar hasta verde (TDD — green, máx. 3 intentos)

Crear el namespace, autoload y las clases con el código **mínimo** necesario para que los tests del paso anterior pasen.

```php
// src/Service/MyModuleService.php
namespace Drupal\nombre_del_modulo\Service;

class MyModuleService {
  public function __construct() {}
}
```

La autoload viene de:
```json
{
  "autoload": {
    "psr-4": {
      "Drupal\\nombre_del_modulo\\": "src/"
    }
  }
}
```

Composer ya lo define en el proyecto raíz.

**Límite de intentos**: cada ejecución de `composer test` tras un cambio de implementación cuenta como un intento. Máximo **3**.

| Intento | Acción |
|---|---|
| 1 | Implementar lo mínimo y ejecutar `composer test` |
| 2 | Ajustar según el error del intento 1 y re-ejecutar |
| 3 | Último ajuste y re-ejecutar |

Si tras el intento 3 los tests siguen en rojo: **detener** (no hacer un 4to intento), generar el "Resumen de bloqueo" (ver [agents/tdd-specialist.md](.claude/agents/tdd-specialist.md#template-resumen-de-bloqueo-3-intentos-sin-verde)), registrarlo en `docs/activity-log/` y esperar indicación del usuario.

### 6. Crear documentación

**README.md**:
```markdown
# Nombre del Módulo

Descripción clara de qué hace el módulo.

## Instalación

```bash
composer require drupal/nombre_del_modulo
drush en nombre_del_modulo
```

## Configuración

Explicar cómo configurar el módulo.

## API

Si el módulo proporciona hooks o services:

### Services

- `nombre_del_modulo.my_service`: Descripción

### Hooks

- `hook_nombre_del_modulo_process()`: Descripción

## Tests

```bash
composer test
```
```

**CHANGELOG.md**:
```markdown
# Changelog

All notable changes to this module will be documented in this file.

## [1.0.0] - 2024-06-08

### Added
- Initial release
- Core features...
```

### 7. Validaciones finales

```bash
# Pasar todas las validaciones
composer lint:phpcs       # ✓ PHPCS compliance
composer lint:phpstan     # ✓ PHPStan level 5
composer test             # ✓ Tests passing 70%+
composer qa               # ✓ All quality gates
```

### 8. Commit

```bash
git add web/modules/custom/nombre_del_modulo/
git commit -m "feat: crear módulo nombre_del_modulo"
```

## Template mínimo

```bash
# 1. Crear directorio
mkdir -p web/modules/custom/mi_modulo/src/Service

# 2. Crear info.yml
cat > web/modules/custom/mi_modulo/mi_modulo.info.yml <<EOF
name: 'Mi Módulo'
description: 'Descripción.'
package: 'Custom'
type: module
core_version_requirement: '^11.0'
EOF

# 3. Crear test (red) — falla porque MiServicio no existe
mkdir -p web/modules/custom/mi_modulo/tests/src/Unit
cat > web/modules/custom/mi_modulo/tests/src/Unit/MiServicioTest.php <<EOF
<?php

namespace Drupal\Tests\mi_modulo\Unit;

use PHPUnit\Framework\TestCase;
use Drupal\mi_modulo\Service\MiServicio;

class MiServicioTest extends TestCase {
  public function testServicio() {
    \$service = new MiServicio();
    \$this->assertEquals('hecho', \$service->doSomething());
  }
}
EOF

composer test
# ❌ FAIL esperado: Class "Drupal\mi_modulo\Service\MiServicio" not found

# 4. Crear Service básico (green) — hace pasar el test (intento 1 de 3)
cat > web/modules/custom/mi_modulo/src/Service/MiServicio.php <<EOF
<?php

namespace Drupal\mi_modulo\Service;

class MiServicio {
  public function doSomething() {
    return 'hecho';
  }
}
EOF

# 5. Validar
composer qa
composer test
```

## Variaciones

### API-only module (sin UI)

```yaml
# .info.yml
name: 'API Module'
description: 'Provides REST endpoints.'
package: 'Custom'
type: module
core_version_requirement: '^11.0'
dependencies:
  - drupal:rest
```

Estructura:
```
web/modules/custom/api_modulo/
├── src/
│   ├── Plugin/
│   │   └── rest/
│   │       └── resource/
│   └── Controller/
├── tests/
└── api_modulo.info.yml
```

### Module con Entity

```php
// src/Entity/MyEntity.php
namespace Drupal\nombre_del_modulo\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines the MyEntity entity class.
 *
 * @ContentEntityType(
 *   id = "my_entity",
 *   label = @Translation("My Entity"),
 *   ...
 * )
 */
class MyEntity extends ContentEntityBase {
  // ...
}
```

### Module con Config Entity

```php
// src/Entity/MyConfig.php
namespace Drupal\nombre_del_modulo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the MyConfig config entity.
 *
 * @ConfigEntityType(
 *   id = "my_config",
 *   label = @Translation("My Config"),
 *   ...
 * )
 */
class MyConfig extends ConfigEntityBase {
  public $id;
  public $label;
}
```

## Checklist

- [ ] Nombre validado (snake_case, no colisiones)
- [ ] Directorio creado en web/modules/custom
- [ ] .info.yml completo y válido
- [ ] tests/src/Unit/ (y Functional si aplica) escritos y fallando primero (red)
- [ ] src/ con estructura PSR-4 — implementado hasta pasar los tests (green, máx. 3 intentos)
- [ ] README.md con instrucciones
- [ ] Pasó composer lint:phpcs
- [ ] Pasó composer lint:phpstan
- [ ] Pasó composer test (70%+ coverage)
- [ ] Pasó composer qa
- [ ] Commit con mensaje español

## Próximos pasos

1. Para cada feature nueva: escribir tests primero (red)
2. Implementar hasta pasar los tests (green, máx. 3 intentos; si no, generar resumen de bloqueo)
3. Validar con composer qa
4. Actualizar documentación
5. Commit y PR

## Referencias

- [Drupal Module Development](https://www.drupal.org/docs/drupal-apis/module-system)
- [Entity API](https://www.drupal.org/docs/8/api/entity-api)
- [Services and DI](https://www.drupal.org/docs/8/api/services)
- [Testing in Drupal](https://www.drupal.org/docs/automated-testing)
